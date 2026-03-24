<?php
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');
require_once('../../core/useful-functions.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
}
?>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Checkout Donation Demo | PHP Class Library | Angell EYE</title>
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
    <script src="donation-order.js"></script>
  </head>
  <body>
    <!-- HEADER -->
    <?php require_once('../../partials/header.php'); ?>

    <!-- Main -->
    <main class="cart-main">
      <div class="container dn-narrow">

        <!-- Page Title -->
        <div class="cart-page-title dn-title-center">
          <div class="cart-title-icon dn-icon">
            <?php echo inline_svg('../../assets/images/heart-icon.svg'); ?>
          </div>
          <h1>Make a Donation</h1>
        </div>

        <!-- Donation form card -->
        <div class="dn-card">
          <p class="dn-intro-text">
            Your generous contribution helps support our mission and makes a meaningful impact.
            Every donation, big or small, is greatly appreciated. Thank you for your support!
          </p>
          <form action="review-donation.php" method="post" class="dn-form">
            <div class="dn-field">
              <label for="dn-name" class="dn-label">
                <?php echo inline_svg('../../assets/images/user-icon.svg'); ?>
                Name
              </label>
              <input type="text" id="dn-name" name="dn-name" class="dn-input" placeholder="Your full name" autocomplete="name" required />
            </div>
            <div class="dn-field">
              <label for="dn-email" class="dn-label">
                <?php echo inline_svg('../../assets/images/email-icon.svg'); ?>
                Email
              </label>
              <input type="email" id="dn-email" name="dn-email" class="dn-input" placeholder="you@example.com" autocomplete="email" required />
            </div>

            <div class="dn-field">
              <label for="dn-amount" class="dn-label">
                <?php echo inline_svg('../../assets/images/dollar-icon.svg'); ?>
                Donation Amount <span class="dn-label-sub">(USD)</span>
              </label>
              <div class="dn-amount-wrap">
                <span class="dn-currency">$</span>
                <input type="number" id="dn-amount" name="dn-amount" class="dn-input dn-amount-input" placeholder="0.00" min="1" step="0.01" required />
              </div>
            </div>

            <!-- Preset amounts -->
            <div class="dn-presets">
              <div class="dn-preset" data-amount="10">$10</div>
              <div class="dn-preset" data-amount="25">$25</div>
              <div class="dn-preset" data-amount="50">$50</div>
              <div class="dn-preset" data-amount="100">$100</div>
            </div>

            <button type="submit" class="dn-donate-btn">
              <?php echo inline_svg('../../assets/images/heart-icon.svg'); ?>
              Donate Now
            </button>
          </form>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <?php require_once('../../partials/footer.php'); ?>
  </body>
</html>