<?php
/**
 * Include our config file and the PayPal library.
 */
require_once('../../../includes/config.php');
require_once('../../core/useful-functions.php');

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
      <div class="container">

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

          $messages = [];
          if (empty($verifiedMerchantData['primary_email_confirmed'])) {
            $messages[] = "Please confirm your PayPal email.";
          }
          if (empty($verifiedMerchantData['payments_receivable'])) {
            $messages[] = "Your PayPal account cannot receive payments.";
          }
          if (!empty($messages)) { ?>
            <div class="banner" id="deprecation-banner">
              <div class="banner-icon-wrap">
                <?php echo inline_svg('../../assets/images/warning-icon.svg'); ?>
              </div>
              <div class="banner-body">
                <p><?php echo implode(' ', $messages); ?></p>
              </div>
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
          <div class="merchant-grid">
            <div class="merchant-info-card">
              <div class="merchant-account-info merchant-card">
                <div class="merchant-account-info-header merchant-info-header">
                  <h5 class="merchant-account-info-title">Merchant Account</h5>
                </div>
                <div class="merchant-account-info-body merchant-info-body">
                  <div class="merchant-value-wrap">
                    <span class="mechant-label">Merchant ID</span>
                    <span class="merchant-value"><?php echo $merchantId; ?></span>
                  </div>
                  <div class="merchant-value-wrap">
                    <span class="mechant-label">Legal Name</span>
                    <span class="merchant-value"><?php echo $legalName; ?></span>
                  </div>
                  <div class="merchant-value-wrap">
                    <span class="mechant-label">Primary Email</span>
                    <span class="merchant-value"><?php echo $email; ?></span>
                  </div>
                  <div class="merchant-value-wrap">
                    <span class="mechant-label">Country</span>
                    <span class="merchant-value"><?php echo $country; ?></span>
                  </div>
                  <div class="merchant-value-wrap">
                    <span class="mechant-label">Currency</span>
                    <span class="merchant-value"><?php echo $currency; ?></span>
                  </div>
                  <div class="merchant-value-wrap merchant-wrap-no">
                    <span class="mechant-label">Email Confirmed</span>
                    <span class="merchant-value merchant-value-no">
                      <?php echo inline_svg('../../assets/images/info.svg'); ?>
                      <?php echo $emailConfirm; ?>
                    </span>
                  </div>
                  <div class="merchant-value-wrap merchant-wrap-yes">
                    <span class="mechant-label">Payments Receivable</span>
                    <span class="merchant-value merchant-value-yes">
                      <?php echo inline_svg('../../assets/images/pay-complete.svg'); ?>
                      <?php echo $payments; ?>
                    </span>
                  </div>
                </div>
              </div>
              <div class="merchant-oauth-info merchant-card">
                <div class="merchant-oauth-info-header merchant-info-header">
                  <h5 class="merchant-oauth-info-title">OAuth Integrations</h5>
                </div>
                <div class="merchant-oauth-info-body merchant-info-body">
                  <?php
                    if (isset($verifiedMerchantData['oauth_integrations'][0]['oauth_third_party'][0]['scopes']) && is_array($verifiedMerchantData['oauth_integrations'][0]['oauth_third_party'][0]['scopes'])) { ?>
                      <div class="merchant-oauth-entries">
                        <?php foreach ($verifiedMerchantData['oauth_integrations'][0]['oauth_third_party'][0]['scopes'] as $scope) {
                          echo '<div class="merchant-outh-entry cp-txn-id">' . $scope . '</div>';
                        } ?>
                      </div>
                    <?php }
                  ?>
                </div>
              </div>
            </div>
            <div class="merchant-cap-card merchant-card">
              <div class="merchant-capabilities">
                <div class="merchant-capabilities-header merchant-info-header">
                  <h5 class="merchant-capabilities-title">Merchant Capabilities</h5>
                </div>
                <div class="merchant-capabilities-body merchant-info-body">
                  <?php if (isset($verifiedMerchantData['capabilities']) && is_array($verifiedMerchantData['capabilities'])) { ?>
                    <div class="merchant-cap-entries">
                      <?php foreach ($verifiedMerchantData['capabilities'] as $cap) {
                        $status = isset($cap['status']) ? $cap['status'] : '';
                        $statusClass = ($status === 'ACTIVE') ? 'merchant-value-yes' : 'merchant-value-no';
                        $nameImg = ($status === 'ACTIVE') ? '../../assets/images/pay-complete.svg' : '../../assets/images/info.svg';
                      ?>
                        <div class="merchant-cap-entry">
                          <span class="merchant-cap-name merchant-value <?php echo $statusClass; ?>">
                            <?php echo inline_svg($nameImg); ?>
                            <?php echo isset($cap['name']) ? $cap['name'] : '' ?>
                          </span>
                        </div>
                      <?php } ?>
                    </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
          <form method="post">
            <button type="submit" name="disconnect" class="merchant-disconnect dnc-donate-again">Disconnect from PayPal</button>
          </form>
        <?php } ?>
      </div>
    </main>

    <!-- Footer -->
    <?php require_once('../../partials/footer.php'); ?>
  </body>
</html>