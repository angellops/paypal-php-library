<?php
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');
require_once('../../core/useful-functions.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
}

$_SESSION['donation_name']   = !empty($_POST['dn-name']) ? $_POST['dn-name'] : '';
$_SESSION['donation_email']  = !empty($_POST['dn-email']) ? $_POST['dn-email'] : '';
$_SESSION['donation_amount'] = !empty($_POST['dn-amount']) ? $_POST['dn-amount'] : '';

$donationItems = [
  'donation_name' => $_SESSION['donation_name'],
  'donation_email' => $_SESSION['donation_email'],
  'donation_amount' => $_SESSION['donation_amount']
];
?>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Checkout Donation Demo | Review Donation | PHP Class Library | Angell EYE</title>
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
    <script src="donation-order.js"></script>
  </head>
  <body>
    <!-- HEADER -->
    <?php require_once('../../partials/header.php'); ?>

    <main class="cart-main">
      <div class="container dn-narrow">

        <!-- Title row with back link -->
        <div class="dnr-title-row">
          <div class="cart-page-title" style="margin-bottom:0;">
            <div class="cart-title-icon dnr-icon">
              <?php echo inline_svg('../../assets/images/review-icon.svg'); ?>
            </div>
            <h1>Review Your Donation</h1>
          </div>
          <a href="./" class="dnr-change-link">
            <?php echo inline_svg('../../assets/images/back-icon.svg'); ?>
            Change Donation Amount
          </a>
        </div>

        <!-- Review card -->
        <div class="dn-card dnr-card">

          <!-- Donor details -->
          <div class="dnr-details">
            <div class="dnr-detail-row">
              <span class="dnr-detail-label">Name</span>
              <span class="dnr-detail-value" id="rv-name"><?php echo $_SESSION['donation_name']; ?></span>
            </div>
            <div class="dnr-detail-row">
              <span class="dnr-detail-label">Email</span>
              <span class="dnr-detail-value" id="rv-email"><?php echo $_SESSION['donation_email']; ?></span>
            </div>
            <div class="dnr-detail-row dnr-amount-row">
              <span class="dnr-detail-label">Amount</span>
              <span class="dnr-detail-value dnr-amount-value" id="rv-amount"><?php echo '$' . $_SESSION['donation_amount']; ?></span>
            </div>
          </div>

          <div class="dnr-divider"></div>

          <!-- PayPal action -->
          <div class="dnr-paypal-section">
            <p class="dnr-paypal-label">Donate with PayPal</p>
            <div id="paypal-button-container" data-checkout='<?php echo json_encode($donationItems); ?>'>
              <div id="paypalMessage"></div>
              <paypal-button type="donate" hidden></paypal-button>
            </div>
          </div>

        </div>
      </div>
    </main>

    <!-- Footer -->
    <?php require_once('../../partials/footer.php'); ?>
  </body>
</html>