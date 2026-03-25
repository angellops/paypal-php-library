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
    <title>PayPal Checkout Shipped Items + Recurring Payments Demo | Order Review | PHP Class Library | Angell EYE</title>
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
  </head>
  <body>
    <!-- HEADER -->
    <?php require_once('../../partials/header.php'); ?>

    <!-- MAIN -->
    <main class="cart-main">
      <div class="container">

        <!-- Page Title -->
        <div class="cart-page-title">
          <div class="cart-title-icon rv-icon">
            <?php echo inline_svg('../../assets/images/review-icon.svg'); ?>
          </div>
          <h1>Order Review</h1>
        </div>

        <!-- Intro -->
        <div class="cart-intro">
          <p>Here we display a final review to the buyer now that we've calculated shipping, handling, 
            and tax. The billing and shipping information provided here is what we obtained in the 
            <strong>getOrder</strong> response. We have also displayed the subscription information again 
            with a final break-down of everything for the one-time and recurring payments.
          </p>
          <p>When the Complete Order button is clicked it will trigger the call to 
            <strong>createSubscriptionProfile</strong> to process the 
            <?php echo '$' . number_format($_SESSION['shopping_cart']['grand_total'], 2); ?> one-time payment. 
            If this succeeds, it will then process the CreateRecurringPaymentsProfile request to create 
            the subscription profile.
          </p>
        </div>

        <!-- Cart items (reuse cart-items-card styles) -->
        <div class="cart-items-card rv-items-card">
          <div class="cart-items-header">
            <h2>Order Items</h2>
            <?php if (!empty($_SESSION['shopping_cart']['shipped_items'])): ?>
              <span class="cart-count">
                <?php echo count($_SESSION['shopping_cart']['shipped_items']); ?> items
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
            <?php if (!empty($_SESSION['shopping_cart']['shipped_items'])) {
              foreach ($_SESSION['shopping_cart']['shipped_items'] as $cart_item) { ?>
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
            } ?>
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
            <h3 class="rv-summary-title shipped-subscription-title">Order Summary</h3>

            <!-- Add Monthly Subscription Info -->
            <div class="cart-table subscription-shipped-cart-table">
              <div class="cart-table-head">
                <span class="col-name">Name</span>
                <span class="col-total">Amount</span>
              </div>
              <div class="cart-table-row">
                <span class="col-name"><?php echo $_SESSION['shopping_cart']['subscription']['name'] ?></span>
                <span class="col-total"><?php echo '$' . number_format($_SESSION['shopping_cart']['subscription']['amount'], 2); ?></span>
              </div>
            </div>

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

            <div class="summary-recurring">
              + $<?php echo number_format($_SESSION['shopping_cart']['subscription']['amount'], 2) ?> / Month starting next month
            </div>

            <a href="createSubscriptionProfile.php" class="rv-complete-btn">
              <?php echo inline_svg('../../assets/images/check.svg'); ?>
              Complete Order
            </a>

            <a href="./" class="rv-back-link">
              <?php echo inline_svg('../../assets/images/back-icon.svg'); ?>
              Back to Cart
            </a>
          </div>

        </div>

      </div>
    </main>
   
    <!-- Footer -->
    <?php require_once('../../partials/footer.php'); ?>
  </body>
</html>