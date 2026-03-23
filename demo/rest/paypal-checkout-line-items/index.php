<?php
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');
require_once('../../core/useful-functions.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
}

/**
 * Here we are building a very simple, static shopping cart to use
 * throughout this demo.  In most cases, you will working with a dynamic
 * shopping cart system of some sort.
 */
$line_items = array(
  array(
    'id' => '123-ABC',
    'name' => 'Widget',
    'qty' => '2',
    'price' => '9.99',
    'category' => 'DIGITAL_GOODS',
  ),
  array(
    'id' => 'XYZ-456',
    'name' => 'Gadget',
    'qty' => '1',
    'price' => '4.99',
    'category' => 'DIGITAL_GOODS',
  )
);
$_SESSION['shopping_cart'] = array(
  'line_items' => $line_items,
  'subtotal' => 24.97,
  'shipping' => 0,
  'handling' => 0,
  'tax' => 0,
);
$_SESSION['shopping_cart']['grand_total'] = number_format($_SESSION['shopping_cart']['subtotal'] + $_SESSION['shopping_cart']['shipping'] + $_SESSION['shopping_cart']['handling'] + $_SESSION['shopping_cart']['tax'], 2);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Checkout w/ Line Items Demo | PHP Class Library | Angell EYE</title>
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
    <?php $sdk_url = $sandbox ? "https://www.sandbox.paypal.com/web-sdk/v6/core" : "https://www.paypal.com/web-sdk/v6/core"; ?>
    <script src="<?php echo $sdk_url; ?>"></script>
    <script src="basic-paypal.js"></script>
  </head>

  <body>
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
          <p>
            Here we are using a basic shopping cart for display purposes, and we are expanding on the 
            <a href="../paypal-checkout-basic/">basic demo</a> to include the cart line items with the 
            PayPal payment so that they will appear on the PayPal review pages during checkout and in the 
            PayPal transaction details. Within the items, each product is categorized as <strong>digital goods</strong>. 
            Again, we are assuming that we have not collected any billing or shipping information from the buyer yet, 
            because we will obtain those details from PayPal after the user logs in and is returned to the site.
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
                <?php echo count($_SESSION['shopping_cart']['line_items']); ?> items
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
              <?php foreach ($_SESSION['shopping_cart']['line_items'] as $cart_item) { ?>
                <div class="cart-table-row">
                  <span class="col-id">
                    <span class="item-id-badge">
                      <?php echo $cart_item['id']; ?>
                    </span>
                  </span>
                  <span class="col-name">
                    <div class="item-icon">
                      <?php echo strtoupper($cart_item['name'][0]); ?>
                    </div>
                    <div>
                      <div class="item-name">
                        <?php echo $cart_item['name']; ?>
                      </div>
                      <div class="item-sku">SKU:
                        <?php echo $cart_item['id']; ?>
                      </div>
                    </div>
                  </span>
                  <span class="col-price">
                    <?php echo '$' . number_format($cart_item['price'], 2); ?>
                  </span>
                  <span class="col-qty">
                    <div class="qty-badge">
                      <?php echo $cart_item['qty']; ?>
                    </div>
                  </span>
                  <span class="col-total">
                    <?php echo '$' . number_format($cart_item['qty'] * $cart_item['price'], 2); ?>
                  </span>
                </div>
              <?php
              }?>
            </div>

            <div class="cart-back-link">
              <a href="<?php echo $domain . 'demo/'; ?>">
                <?php echo inline_svg('../../assets/images/back-icon.svg'); ?>
                Continue Shopping
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

              <!-- PayPal Button -->
              <div id="paypal-button-container" data-checkout='<?php echo json_encode($_SESSION['shopping_cart']); ?>'>
                <div id="paypalMessage"></div>
                <paypal-button id="paypalbtn" type="pay" hidden></paypal-button>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <?php require_once('../../partials/footer.php'); ?>
  </body>  
</html>