<?php
require_once('../../../includes/config.php');
require_once('../../core/useful-functions.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
}

$_SESSION['buyer_country'] = 'US';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Partner Referral Merchant Onboarding Demo | PHP Class Library | Angell EYE</title>
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

    <!-- Main -->
    <main class="cart-main">
      <div class="container dn-narrow">

        <!-- Page Title -->
        <div class="cart-page-title dn-title-center">
          <div class="cart-title-icon onb-icon">
            <?php echo inline_svg('../../assets/images/handshake.svg'); ?>
          </div>
          <h1>PayPal Merchant Onboarding</h1>
        </div>

        <!-- Donation form card -->
        <div class="dn-card">
          <div class="onb-image">
            <img class="onb-logo" src="../../assets/images/paypal.png">
          </div>
          <h3 class="onb-subtitle">Welcome to the PayPal Partner Referral<br>(Merchant Onboarding) Demo</h3>
          <p class="dn-intro-text">
            This demo shows how an app can onboard merchants using PayPal Partner Referrals and 
            return them back with onboarding details.
          </p>
          <a href="createMerchantOnboarding.php" class="btn-buy btn-launch">
            Start PayPal Onboarding
            <?php echo inline_svg('../../assets/images/redirect-icon.svg'); ?>
          </a>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <?php require_once('../../partials/footer.php'); ?>
  </body>
</html>