<?php
/**
 * Include our config file and the PayPal library.
 */
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
}

if (isset($_POST['disconnect'])) {
  unset($_SESSION['verified_merchant_data']);
}

if( !isset( $_SESSION['verified_merchant_data'] ) ) {
  header('Location: ./');
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Partner Referral Merchant Onboarding Demo | Merchant Onboarded | PHP Class Library | Angell EYE</title>
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
        <div class="cart-page-title">
          <div class="cart-title-icon cp-icon">
            <?php echo inline_svg('../../assets/images/pay-complete.svg'); ?>
          </div>
          <h1>Merchant Onboarding Complete!</h1>
        </div>

        <!-- Intro -->
        <div class="cart-intro">
          <p>The PayPal Partner Referral flow has completed successfully.</p>
          <p>Below is the information returned by PayPal, which was provided in the 
            <strong>verifyMerchantOnboarding</strong> response, for the newly onboarded merchant.
          </p>
        </div>
        <?php if( isset( $_SESSION['verified_merchant_data'] ) ) {
          $verifiedMerchantData = $_SESSION['verified_merchant_data'];

          if(!$verifiedMerchantData['primary_email_confirmed']) { ?>
            <div class="warning-info onboarded-warning">
              <span class="warning-icon">!</span>Please confirm your PayPal email.
            </div>
          <?php } 

          if(!$verifiedMerchantData['payments_receivable']) { ?>
            <div class="warning-info onboarded-warning">
              <span class="warning-icon">!</span>Your PayPal account cannot receive payments.
            </div>
          <?php }

          $merchantId   = isset($verifiedMerchantData['merchant_id']) ? $verifiedMerchantData['merchant_id'] : '';
          $legalName    = isset($verifiedMerchantData['legal_name']) ? $verifiedMerchantData['legal_name'] : '';
          $email        = isset($verifiedMerchantData['primary_email']) ? $verifiedMerchantData['primary_email'] : '';
          $emailConfirm = !empty($verifiedMerchantData['primary_email_confirmed']) ? 'YES' : 'NO';
          $payments     = !empty($verifiedMerchantData['payments_receivable']) ? 'YES' : 'NO';
          $country      = isset($verifiedMerchantData['country']) ? $verifiedMerchantData['country'] : '';
          $currency     = isset($verifiedMerchantData['primary_currency']) ? $verifiedMerchantData['primary_currency'] : '';
        ?>
          <h4 class="merchant-title">Merchant Account Details</h4>
          <table class="table table-items table-bordered">
            <tr>
              <th>Merchant ID:</th>
              <td><?php echo $merchantId; ?></td>
            </tr>
            <tr>
              <th>Legal Name:</th>
              <td><?php echo $legalName; ?></td>
            </tr>
            <tr>
              <th>Primary Email:</th>
              <td><?php echo $email; ?></td>
            </tr>
            <tr>
              <th>Email Confirmed:</th>
              <td><?php echo $emailConfirm; ?></td>
            </tr>
            <tr>
              <th>Payments Receivable:</th>
              <td><?php echo $payments; ?></td>
            </tr>
            <tr>
              <th>Country:</th>
              <td><?php echo $country; ?></td>
            </tr>
            <tr>
              <th>Currency:</th>
              <td><?php echo $currency; ?></td>
            </tr>

            <!-- OAUTH SCOPES -->
            <tr>
              <th>OAuth Scopes:</th>
              <td>
                <?php
                  if (isset($verifiedMerchantData['oauth_integrations'][0]['oauth_third_party'][0]['scopes']) && is_array($verifiedMerchantData['oauth_integrations'][0]['oauth_third_party'][0]['scopes'])) { ?>
                    <ul>
                      <?php foreach ($verifiedMerchantData['oauth_integrations'][0]['oauth_third_party'][0]['scopes'] as $scope) {
                        echo '<li>' . $scope . '</li>';
                      } ?>
                    </ul>
                  <?php }
                ?>
              </td>
            </tr>

            <!-- CAPABILITIES WITH STATUS -->
            <tr>
              <th>Capabilities:</th>
              <td>
                <?php if (isset($verifiedMerchantData['capabilities']) && is_array($verifiedMerchantData['capabilities'])) { ?>
                  <ul>
                    <?php foreach ($verifiedMerchantData['capabilities'] as $cap) { ?>
                      <li><?php echo isset($cap['name']) ? $cap['name'] : '' ?> — <?php echo isset($cap['status']) ? $cap['status'] : 'N/A' ?></li>
                    <?php } ?>
                  </ul>
                <?php } ?>
              </td>
            </tr>
          </table> 
          <form method="post">
            <button type="submit" name="disconnect" class="btn btn-danger">Disconnect from PayPal</button>
          </form>
        <?php } ?>
      </div>
    </main>

    <!-- Footer -->
    <?php require_once('../../partials/footer.php'); ?>
  </body>
</html>