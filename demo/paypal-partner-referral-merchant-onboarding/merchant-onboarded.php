<?php 
/**
 * Include our config file.
 */
require_once('../../includes/config.php'); 

if (isset($_POST['disconnect'])) {
  unset($_SESSION['verified_merchant_data']);
}

if( !isset( $_SESSION['verified_merchant_data'] ) ) {
  header('Location: ./');
}
?>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Partner Referral Merchant Onboarding Demo | Merchant Onboarded | PHP Class Library | Angell EYE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!--link rel="stylesheet/less" href="less/bootstrap.less" type="text/css" /-->
    <!--link rel="stylesheet/less" href="less/responsive.less" type="text/css" /-->
    <!--script src="../assets/js/less-1.3.3.min.js"></script-->
    <!--append ‘#!watch’ to the browser URL, then refresh the page. -->

    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
        <script src="../assets/js/html5shiv.js"></script>
        <![endif]-->

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="../assets/images/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="../assets/images/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="../assets/images/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="../assets/images/apple-touch-icon-57-precomposed.png">
    <link rel="shortcut icon" href="../assets/images/favicon.png">
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="../assets/js/scripts.js"></script>
  </head>
  <body>
    <div class="container">
      <div class="row clearfix">
        <div class="col-md-12 column">
          <div id="header" class="row clearfix">
            <div class="col-md-6 column">
              <div id="angelleye_logo">
                <a href="/">
                  <img alt="Angell EYE PayPal PHP Class Library Demo" src="../assets/images/logo.png">
                </a>
              </div>
            </div>
            <div class="col-md-6 column">
              <div id="paypal_partner_logo">
                <img alt="PayPal Partner and Certified Developer" src="../assets/images/paypal-partner-logo.png"/>
                <div class="accept-text">
                  We accept <strong class="paypal-text">PayPal</strong> and <strong class="venmo-text">Venmo</strong>
                </div>
              </div>
            </div>
          </div>
          <?php if( $api_mode === 'classic' ) { ?>
            <div class="warning-info">
              <span class="warning-icon">!</span>PayPal Classic API is deprecated. Please upgrade to the REST API for continued support and latest features.
            </div>
          <?php } ?>
          <h2 class="main-title">Merchant Onboarding Complete!</h2>
          <p class="main-info">The PayPal Partner Referral flow has completed successfully.</p>
          <p class="main-info">Below is the information returned by PayPal, which was provided in the <strong>verifyMerchantOnboarding</strong> response, for the newly onboarded merchant.</p>
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
      </div>
    </div>
  </body>
</html>