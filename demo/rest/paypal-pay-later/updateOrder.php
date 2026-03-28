<?php
// Load dependencies and configuration
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');
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
    <title>PayPal Pay Later Demo | Update Order | PHP Class Library | Angell EYE</title>
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
          <h1>Update Order</h1>
        </div>

        <!-- Donation form card -->
        <div class="dn-card">
          <p class="dn-intro-text">
            Enter updated details below to modify your order before final payment confirmation.
          </p>
          <form method="post" action="patchOrder.php" class="dn-form">
            <div class="dn-field">
              <label for="dn-name" class="dn-label">
                <?php echo inline_svg('../../assets/images/user-icon.svg'); ?>
                First Name
              </label>
              <input type="text" id="dn-name" name="first_name" class="dn-input" placeholder="Enter first name" autocomplete="name" required />
            </div>

            <div class="dn-field">
              <label for="dn-name" class="dn-label">
                <?php echo inline_svg('../../assets/images/user-icon.svg'); ?>
                Last Name
              </label>
              <input type="text" id="dn-name" name="last_name" class="dn-input" placeholder="Enter last name" autocomplete="name" required />
            </div>

            <button type="submit" class="btn-buy btn-launch">Update Order</button>
          </form>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <?php require_once('../../partials/footer.php'); ?>
  </body>
</html>