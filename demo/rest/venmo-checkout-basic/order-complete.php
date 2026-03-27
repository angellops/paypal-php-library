<?php
require_once('../../../includes/config.php');
require_once('../../core/useful-functions.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Venmo Checkout Basic Demo | Order Complete | PHP Class Library | Angell EYE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="stylesheet" href="../../assets/css/style.css" />

    <!-- Fav and Touch Icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="../../assets/images/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="../../assets/images/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="../../assets/images/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="../../assets/images/apple-touch-icon-57-precomposed.png">
    <link rel="shortcut icon" href="../../assets/images/favicon.png">

    <script type="text/javascript" src="../../assets/js/jquery.min.js"></script>
  </head>
  <body>
    <!-- HEADER -->
    <?php require_once('../../partials/header.php'); ?>

    <!-- MAIN -->
    <main class="cart-main">
      <div class="container">

        <!-- Page Title -->
        <div class="cart-page-title">
          <div class="cart-title-icon cp-icon">
            <?php echo inline_svg('../../assets/images/pay-complete.svg'); ?>
          </div>
          <h1>Payment Complete!</h1>
        </div>

        <!-- Intro -->
        <div class="cart-intro">
          <p>We have now reached the final thank you / receipt page and the payment has been processed!  We have added the PayPal Transaction ID to 
            the Billing Information, which was provided in the <strong>getOrder</strong> response.
          </p>
        </div>

        <!-- Cart items (reuse cart-items-card styles) -->
        <div class="cart-items-card rv-items-card">
          <div class="cart-items-header">
            <h2>Order Items</h2>
            <?php if (!empty($_SESSION['shopping_cart']['venmo_items'])): ?>
              <span class="cart-count">
                <?php echo count($_SESSION['shopping_cart']['venmo_items']); ?> items
              </span>
            <?php endif; ?>
          </div>

          <div class="cart-table">
            <!-- Table Header -->
            <div class="cart-table-head">
              <span class="col-id">ID</span>
              <span class="col-name">Name</span>
              <span class="col-price">Price</span>
              <span class="col-qty">Qty</span>
              <span class="col-total">Total</span>
            </div>

            <!-- Item Rows -->
            <?php if (!empty($_SESSION['shopping_cart']['venmo_items'])) {
              foreach ($_SESSION['shopping_cart']['venmo_items'] as $cart_item) { ?>
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
              }
            }?>
          </div>
        </div>

        <!-- Three-column: Billing | Shipping | Summary -->
        <div class="rv-bottom-grid">

          <!-- Billing Information -->
          <div class="rv-info-card">
            <div class="rv-info-header">
              <div class="rv-info-icon rv-info-icon--blue">
                <?php echo inline_svg('../../assets/images/user-icon.svg'); ?>
              </div>
              <h3>Billing Information</h3>
            </div>
            <div class="rv-info-body">
              <div class="rv-info-row">
                <span class="rv-info-label">Name</span>
                <?php $first = !empty($_SESSION['first_name']) ? $_SESSION['first_name'] : '';
                  $last = !empty($_SESSION['last_name']) ? $_SESSION['last_name'] : '';
                  $name = trim($first . ' ' . $last); ?>
                <span class="rv-info-value">
                  <?php echo !empty($name) ? $name : ''; ?>
                </span>
              </div>
              <div class="rv-info-row">
                <span class="rv-info-label">Email</span>
                <span class="rv-info-value">
                  <?php echo !empty($_SESSION['email']) ? $_SESSION['email'] : ''; ?>
                </span>
              </div>
              <?php if ( ! empty( $_SESSION['payment_method_source'] ) ) { 
                $payment_method_source = $_SESSION['payment_method_source'];
                switch ($payment_method_source) {
                  case 'paypal':
                    $payment_label = 'PayPal';
                    break;
                  case 'venmo':
                    $payment_label = 'Venmo';
                    break;
                  default:
                    $payment_label = ucfirst($payment_method_source);
                }
              ?>
                <div class="rv-info-row">
                  <span class="rv-info-label">Payment</span>
                  <span class="dnr-detail-value">
                    <span class="dnc-paypal-badge"><?php echo $payment_label; ?></span>
                  </span>
                </div>
              <?php } ?>
              <div class="rv-info-row cp-txn-row">
                <span class="rv-info-label">Txn ID</span>
                <span class="rv-info-value cp-txn-id">
                  <?php echo !empty($_SESSION['paypal_transaction_id']) ? $_SESSION['paypal_transaction_id'] : ''; ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Shipping Information -->
          <div class="rv-info-card">
            <div class="rv-info-header">
              <div class="rv-info-icon rv-info-icon--purple">
                <?php echo inline_svg('../../assets/images/shipping.svg'); ?>
              </div>
              <h3>Shipping Information</h3>
            </div>
            <div class="rv-info-body">
              <div class="rv-info-row">
                <span class="rv-info-label">Name</span>
                <span class="rv-info-value">
                  <?php echo !empty($_SESSION['shipping_name']) ? $_SESSION['shipping_name'] : ''; ?>
                </span>
              </div>
              <div class="rv-info-row">
                <span class="rv-info-label">Address</span>
                <span class="rv-info-value">
                  <?php echo !empty($_SESSION['shipping_street']) ? $_SESSION['shipping_street'] : ''; ?>
                </span>
              </div>
              <div class="rv-info-row">
                <span class="rv-info-label">City</span>
                <?php $city = !empty($_SESSION['shipping_city']) ? $_SESSION['shipping_city'] : '';
                  $state = !empty($_SESSION['shipping_state']) ? $_SESSION['shipping_state'] : '';
                  $zip = !empty($_SESSION['shipping_zip']) ? $_SESSION['shipping_zip'] : '';
                  $line1 = trim(implode(', ', array_filter([$city, $state])));
                  $line2 = trim($zip);
                ?>
                <span class="rv-info-value">
                  <?php if (!empty($line1))
                    echo $line1;
                  if (!empty($line2))
                    echo ($line1 ? ' ' : '') . $line2;
                  if (empty($line1) && empty($line2))
                    echo 'N/A';
                  ?>
                </span>
              </div>
              <div class="rv-info-row">
                <span class="rv-info-label">Country</span>
                <span class="rv-info-value">
                  <?php echo !empty($_SESSION['shipping_country_name']) ? $_SESSION['shipping_country_name'] : ''; ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="rv-summary-card">
            <h3 class="rv-summary-title">Order Summary</h3>

            <div class="summary-rows">
              <div class="summary-row">
                <span>Subtotal</span>
                <?php if (isset($_SESSION['shopping_cart']['subtotal'])): ?>
                  <span class="summary-value">
                    <?php echo '$' . number_format($_SESSION['shopping_cart']['subtotal'], 2); ?>
                  </span>
                <?php endif; ?>
              </div>
              <div class="summary-row">
                <span>Shipping</span>
                <?php if (isset($_SESSION['shopping_cart']['shipping'])): ?>
                  <span class="summary-value <?php echo ($_SESSION['shopping_cart']['shipping'] === 0) ? 'summary-free' : ''; ?>">
                    <?php echo '$' . number_format($_SESSION['shopping_cart']['shipping'], 2); ?>
                  </span>
                <?php endif; ?>
              </div>
              <div class="summary-row">
                <span>Handling</span>
                <?php if (isset($_SESSION['shopping_cart']['handling'])): ?>
                  <span class="summary-value <?php echo ($_SESSION['shopping_cart']['handling'] === 0) ? 'summary-free' : ''; ?>">
                    <?php echo '$' . number_format($_SESSION['shopping_cart']['handling'], 2); ?>
                  </span>
                <?php endif; ?>
              </div>
              <div class="summary-row">
                <span>Tax</span>
                <?php if (isset($_SESSION['shopping_cart']['tax'])): ?>
                  <span class="summary-value <?php echo ($_SESSION['shopping_cart']['tax'] === 0) ? 'summary-free' : ''; ?>">
                    <?php echo '$' . number_format($_SESSION['shopping_cart']['tax'], 2); ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-total">
              <span>Grand Total</span>
              <?php if (isset($_SESSION['shopping_cart']['grand_total'])): ?>
                <span class="summary-total-amount">
                  <?php echo '$' . number_format($_SESSION['shopping_cart']['grand_total'], 2); ?>
                </span>
              <?php endif; ?>
            </div>

            <div class="cp-paid-badge">
              <?php echo inline_svg('../../assets/images/check.svg'); ?>
              Payment Received
            </div>

            <a href="../../" class="rv-back-link">
              <?php echo inline_svg('../../assets/images/back-icon.svg'); ?>
              Back to All Demos
            </a>
          </div>

        </div>

      </div>
    </main>

    <!--- FOOTER --->
    <?php require_once('../../partials/footer.php'); ?>
  </body>
</html>
<?php
session_destroy();
?>