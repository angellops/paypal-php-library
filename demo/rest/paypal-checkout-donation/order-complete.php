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
    <title>PayPal Checkout Donation Demo | Order Complete | PHP Class Library | Angell EYE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="stylesheet" href="../../assets/css/style.css" />

    <!-- Fav and touch icons -->
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

    <main class="cart-main">
      <div class="container dn-narrow">

        <!-- Page title -->
        <div class="cart-page-title dn-title-center">
          <div class="cart-title-icon cp-icon">
            <?php echo inline_svg('../../assets/images/pay-complete.svg'); ?>
          </div>
          <h1>Donation Completed Successfully!</h1>
        </div>

        <!-- Intro -->
        <p class="cart-intro" style="text-align:center;max-width:44rem;margin-inline:auto;">
          Thank you for your generous donation! Your support helps us continue our mission.
        </p>

        <!-- Receipt card -->
        <div class="dn-card dnc-receipt-card">

          <!-- Donor section -->
          <div class="dnc-section">
            <h3 class="dnc-section-title">
              <?php echo inline_svg('../../assets/images/user-icon.svg'); ?>
              Donation Details
            </h3>
            <div class="dnr-details">
              <div class="dnr-detail-row">
                <span class="dnr-detail-label">Name</span>
                <span class="dnr-detail-value"><?php echo !empty($_SESSION['donation_name']) ? $_SESSION['donation_name'] : 'N/A'; ?></span>
              </div>
              <div class="dnr-detail-row">
                <span class="dnr-detail-label">Email</span>
                <span class="dnr-detail-value"><?php echo !empty($_SESSION['donation_email']) ? $_SESSION['donation_email'] : 'N/A'; ?></span>
              </div>
              <div class="dnr-detail-row dnr-amount-row">
                <span class="dnr-detail-label">Amount</span>
                <span class="dnr-detail-value dnr-amount-value"><?php echo !empty($_SESSION['donation_amount']) ? '$' . $_SESSION['donation_amount'] : '$0.00'; ?></span>
              </div>
              <div class="dnr-detail-row">
                <span class="dnr-detail-label">Payment</span>
                <span class="dnr-detail-value">
                  <span class="dnc-paypal-badge">
                    PayPal 
                  </span>
                </span>
              </div>
              <div class="dnr-detail-row cp-txn-row">
                <span class="dnr-detail-label">Txn ID</span>
                <span class="dnr-detail-value cp-txn-id">
                  <?php echo !empty($_SESSION['paypal_transaction_id']) ? $_SESSION['paypal_transaction_id'] : 'N/A'; ?>
                </span>
              </div>
            </div>
          </div>

          <div class="dnr-divider dncp-divider"></div>

          <!-- Billing section -->
          <div class="dnc-section">
            <h3 class="dnc-section-title">
              <?php echo inline_svg('../../assets/images/lock-icon.svg'); ?>
              Billing Information
            </h3>
            <div class="dnr-details">
              <div class="dnr-detail-row">
                <span class="dnr-detail-label">Name</span>
                <?php $first = !empty($_SESSION['first_name']) ? $_SESSION['first_name'] : '';
                  $last = !empty($_SESSION['last_name']) ? $_SESSION['last_name'] : '';
                  $name = trim($first . ' ' . $last); ?>
                <span class="dnr-detail-value">
                  <?php echo !empty($name) ? $name : ''; ?>
                </span>
              </div>
              <div class="dnr-detail-row">
                <span class="dnr-detail-label">Email</span>
                <span class="dnr-detail-value">
                  <?php echo !empty($_SESSION['email']) ? $_SESSION['email'] : ''; ?>
                </span>
              </div>
            </div>
          </div>

        </div>

        <!-- Action links -->
        <div class="dnc-actions">
          <a href="./" class="dnc-donate-again">
            <?php echo inline_svg('../../assets/images/heart-icon.svg'); ?>
            Donate Again
          </a>
          <a href="../../" class="rv-back-link">
            <?php echo inline_svg('../../assets/images/back-icon.svg'); ?>
            Back to All Demos
          </a>
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