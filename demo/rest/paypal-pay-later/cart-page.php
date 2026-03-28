<?php
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');
require_once('../../core/useful-functions.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
}

// Check if the form with the name/button 'add' has been submitted via POST
if (isset($_POST['add'])) {
  add_to_cart($_POST['id'], $_POST['name'], $_POST['price'], (int)$_POST['qty']);
  header('Location: cart-page.php');
  exit;
}

// Retrieve the current contents of the shopping cart to be used for display
$cart = get_cart();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Pay Later Demo | Cart | PHP Class Library | Angell EYE</title>
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
  <body class="cart-page">
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
          <p>Here we are having shopping cart that transmits only the final order total to PayPal, omitting specific line-item details 
            for simplicity. Because we expect to retrieve the buyer's billing and shipping information directly from PayPal upon their 
            return, no personal data is collected on this site prior to login.
          </p>
        </div>

        <form method="post" class="cart-page-form" action="checkout-page.php">
          <?php if (!empty($cart)) { ?>
            <!-- Two-column Layout -->
            <div class="cart-layout">

              <!-- LEFT: Cart Items -->
              <section class="cart-items-card">
                <div class="cart-items-header">
                  <h2>Your Items</h2>
                  <span class="cart-count">
                    <?php echo count($cart); ?> items
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
                  <?php foreach ($cart as $id => $item) { ?>
                    <div class="cart-table-row" data-id="<?php echo $id; ?>" data-price="<?php echo $item['price']; ?>">
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
                        <div class="quantity-controls">
                          <button type="button" class="qty-btn minus">−</button>
                          <span class="qty-value"><?php echo $item['qty']; ?></span>
                          <button type="button" class="qty-btn plus">+</button>
                          <input type="hidden" name="qty[<?php echo $id; ?>]" value="<?php echo $item['qty']; ?>">
                        </div>
                      </span>
                      <span class="col-total line-total">
                        <?php echo '$<span>' . number_format($item['qty'] * $item['price'], 2) . '</span>'; ?>
                      </span>
                    </div>
                  <?php
                  }?>
                </div>

                <div class="cart-back-link">
                  <a href="./">
                    <?php echo inline_svg('../../assets/images/back-icon.svg'); ?>
                    Return to Shop
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
                        <?php echo '$<span class="cart-subtotal-value">' . number_format(cart_total(), 2) . '</span>'; ?>
                      </span>
                    </div>
                    <div class="summary-row">
                      <span>Shipping</span>
                      <span class="summary-value summary-free">
                        <?php echo '$<span class="cart-shipping">' . number_format(0, 2) . '</span>'; ?>
                      </span>
                    </div>
                    <div class="summary-row">
                      <span>Handling</span>
                      <span class="summary-value summary-free">
                        <?php echo '$<span class="cart-handling">' . number_format(0, 2) . '</span>'; ?>
                      </span>
                    </div>
                    <div class="summary-row">
                      <span>Tax</span>
                      <span class="summary-value summary-free">
                        <?php echo '$<span class="cart-tax">' . number_format(0, 2) . '</span>'; ?>
                      </span>
                    </div>
                  </div>

                  <div class="summary-divider"></div>

                  <div class="summary-total">
                    <span>Grand Total</span>
                    <span class="summary-total-amount">
                      <?php echo '$<span class="cart-total-value">' . number_format(cart_total(), 2) . '</span>'; ?>
                    </span>
                  </div>

                  <paypal-message
                    auto-bootstrap
                    amount="<?php echo cart_total(); ?>"
                    currency-code="USD">
                  </paypal-message>

                  <button type="submit" name="proceed_checkout" class="paylater-checkout-btn btn-buy btn-launch">
                    Proceed to checkout
                    <?php echo inline_svg('../../assets/images/redirect-icon.svg'); ?>
                  </button>
                </div>
              </aside>
            </div>
          <?php } else { ?>
            <div class="empty-cart-wrapper">
              <img src="../../assets/images/empty-cart.png" alt="Empty Cart">
              <h3>Your cart is currently empty!</h3>
              <p class="empty-cart-message">Before proceed to checkout you must add some products to your shopping cart. You will find a lot of interesting products on our "Shop" page.</p>
              <a class="cart-btns" href="./">Return to Shop</a>
            </div>
          <?php } ?>
        </form>
      </div>
    </main>

    <!-- FOOTER -->
    <?php require_once('../../partials/footer.php'); ?>
  </body>
</html>