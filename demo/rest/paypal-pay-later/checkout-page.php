<?php
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');
require_once('../../core/useful-functions.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
}

/**
 * Empty Cart Logic
 * If the 'empty' button was clicked, clear all items and redirect to refresh the UI.
 */
if (isset($_GET['empty_cart']) && $_GET['empty_cart'] === 'true') {
  empty_cart();
  header('Location: cart-page.php');
  exit;
}

/**
 * Cart Retrieval & Validation
 * Fetch the updated list of items. If the cart is empty (e.g., after an update 
 * where all quantities became 0), redirect the user away from the checkout process.
 */
$items = get_cart();
if (empty($items)) {
  header('Location: cart-page.php');
  exit;
}

/**
 * Financial Calculation & Session Storage
 * Calculates the total cost and stores the entire state in a session variable.
 * This 'shopping_cart' array acts as the single source of truth for the PayPal checkout.
 */
$subtotal = calculate_subtotal($items);
$_SESSION['shopping_cart'] = [
  'paylater_items' => $items,
  'subtotal' => $subtotal,
  'shipping' => 10.00,
  'handling' => 2.50,
];
$_SESSION['shopping_cart']['tax'] = number_format(($_SESSION['shopping_cart']['subtotal'] + $_SESSION['shopping_cart']['shipping'] + $_SESSION['shopping_cart']['handling']) * 0.08, 2);
$_SESSION['shopping_cart']['grand_total'] = number_format($_SESSION['shopping_cart']['subtotal'] + $_SESSION['shopping_cart']['shipping'] + $_SESSION['shopping_cart']['handling'] + $_SESSION['shopping_cart']['tax'],2);

$checkoutData = [
  'payer' => $_SESSION['payer'],
  'billing' => $_SESSION['billing'],
  'cart' => $_SESSION['shopping_cart']
];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Pay Later Demo | Checkout | PHP Class Library | Angell EYE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="stylesheet" href="../../assets/css/style.css" />

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="../../assets/images/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="../../assets/images/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="../../assets/images/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="../../assets/images/apple-touch-icon-57-precomposed.png">
    <link rel="shortcut icon" href="../../assets/images/favicon.png">

    <script type="text/javascript" src="../../assets/js/jquery.min.js"></script>
    <?php 
      $sdk_url = $sandbox ? "https://www.sandbox.paypal.com/web-sdk/v6/core" : "https://www.paypal.com/web-sdk/v6/core"; 
      $sdk_messages_url = $sandbox ? "https://www.sandbox.paypal.com/web-sdk/v6/paypal-messages" : "https://www.paypal.com/web-sdk/v6/paypal-messages";
    ?>
    <script src="<?php echo $sdk_url; ?>"></script>
    <script src="<?php echo $sdk_messages_url; ?>"></script>
    <script src="paylater.js"></script>
  </head>
  <body class="cart-page checkout-page">
    <!-- HEADER -->
    <?php require_once('../../partials/header.php'); ?>

    <!-- Main -->
    <main class="cart-main">
      <div class="container">
        <!-- Page Title -->
        <div class="cart-page-title">
          <div class="cart-title-icon">
            <?php echo inline_svg('../../assets/images/cart-icon.svg'); ?>
          </div>
          <h1>Shopping Cart</h1>
        </div>

        <!-- Intro Text -->
        <div class="cart-intro">
          <p>This checkout sends only the final purchase amount to PayPal for payment processing, without sharing individual product details. 
            Billing and shipping information is securely retrieved from PayPal after you log in, so no personal data is collected on this site 
            before checkout.
          </p>
        </div>

        <!-- Demo Credentials -->
        <div class="demo-credentials">
          <div class="demo-credentials-icon">
            <?php echo inline_svg('../../assets/images/lock-icon.svg'); ?>
          </div>
          <div>
            <h3 class="demo-credentials-title">Demo Credentials</h3>
            <div class="demo-credentials-row">
              <span class="demo-credentials-label">Email</span>
              <span class="demo-credentials-value">: paypal-buyer@angelleye.com</a>
            </div>
            <div class="demo-credentials-row">
              <span class="demo-credentials-label">Password</span>
              <span class="demo-credentials-value">: paypalphp</span>
            </div>
          </div>
        </div>


        <!-- Two-column Layout -->
        <div class="cart-layout">

          <!-- LEFT: Cart Items -->
          <section class="cart-items-card">
            <div class="cart-items-header">
              <h2>Your Items</h2>
              <span class="cart-count">
                <?php echo count($_SESSION['shopping_cart']['paylater_items']); ?> items
              </span>
            </div>

            <!-- Table Header -->
            <div class="cart-table">
              <div class="cart-table-head">
                <span class="col-id">ID</span>
                <span class="col-name">Name</span>
                <span class="col-price">Price</span>
                <span class="col-qty">Qty</span>
                <span class="col-total">Total</span>
              </div>

              <!-- Item Rows -->
              <?php foreach ($_SESSION['shopping_cart']['paylater_items'] as $id => $item) { ?>
                <div class="cart-table-row">
                  <span class="col-id">
                    <span class="item-id-badge">
                      <?php echo $id; ?>
                    </span>
                  </span>
                  <span class="col-name">
                    <div class="item-icon">
                      <?php echo inline_svg('../../assets/images/product.svg'); ?>
                    </div>
                    <div>
                      <div class="item-name">
                        <?php echo $item['name']; ?>
                      </div>
                      <div class="item-sku">SKU:
                        <?php echo $id; ?>
                      </div>
                    </div>
                  </span>
                  <span class="col-price">
                    <?php echo '$' . number_format($item['price'], 2); ?>
                  </span>
                  <span class="col-qty">
                    <div class="qty-badge">
                      <?php echo $item['qty']; ?>
                    </div>
                  </span>
                  <span class="col-total">
                    <?php echo '$' . number_format($item['qty'] * $item['price'], 2); ?>
                  </span>
                </div>
              <?php
              }?>
            </div>

            <div class="cart-back-link">
              <a href="cart-page.php">
                <?php echo inline_svg('../../assets/images/back-icon.svg'); ?>
                Back to Cart
              </a>
            </div>
          </section>

          <!-- Right: Order Summary -->
          <aside class="cart-summary">
            <div class="summary-card">
              <h2 class="summary-title">Order Summary</h2>

              <div class="summary-rows">
                <div class="summary-row">
                  <span>Subtotal</span>
                  <span class="summary-value">
                    <?php echo '$' . number_format($_SESSION['shopping_cart']['subtotal'], 2); ?>
                  </span>
                </div>
                <div class="summary-row">
                  <span>Shipping</span>
                  <span
                    class="summary-value <?php echo ($_SESSION['shopping_cart']['shipping'] === 0) ? 'summary-free' : ''; ?>">
                    <?php echo '$' . number_format($_SESSION['shopping_cart']['shipping'], 2); ?>
                  </span>
                </div>
                <div class="summary-row">
                  <span>Handling</span>
                  <span
                    class="summary-value <?php echo ($_SESSION['shopping_cart']['handling'] === 0) ? 'summary-free' : ''; ?>">
                    <?php echo '$' . number_format($_SESSION['shopping_cart']['handling'], 2); ?>
                  </span>
                </div>
                <div class="summary-row">
                  <span>Tax</span>
                  <span
                    class="summary-value <?php echo ($_SESSION['shopping_cart']['tax'] === 0) ? 'summary-free' : ''; ?>">
                    <?php echo '$' . number_format($_SESSION['shopping_cart']['tax'], 2); ?>
                  </span>
                </div>
              </div>

              <div class="summary-divider"></div>

              <div class="summary-total">
                <span>Grand Total</span>
                <span class="summary-total-amount">
                  <?php echo '$' . number_format($_SESSION['shopping_cart']['grand_total'], 2); ?>
                </span>
              </div>

              <!-- Pay Later Button -->
              <paypal-message
                auto-bootstrap
                amount="<?php echo number_format($_SESSION['shopping_cart']['grand_total'], 2); ?>"
                currency-code="USD">
              </paypal-message>
              <div id="paylater-button-container" data-checkout='<?php echo json_encode($checkoutData); ?>'>
                <div id="paypalError"></div>
                <paypal-pay-later-button hidden></paypal-pay-later-button>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </main>

    <!-- FOOTER -->
    <?php require_once('../../partials/footer.php'); ?>
  </body>
</html>