<?php
require_once('../../includes/config.php');
require_once('../../vendor/autoload.php');

/**
 * Setup configuration for the PayPal library using vars from the config file.
 * Then load the PayPal object into $PayPal
 */
$PayPalConfig = array(
	'Sandbox' => $sandbox,
	'PayPalAPIMode' => $api_mode,
  'PayPalAPIUpgrade' => $api_upgrade,
  'APIUsername' => $api_username,
	'APIPassword' => $api_password,
	'APISignature' => $api_signature,
	'ClientID' => $rest_client_id,
	'ClientSecret' => $rest_client_secret,
	'PrintHeaders' => $print_headers, 
	'LogResults' => $log_results, 
	'LogPath' => $log_path,
);
$PayPalCommonFunctions = new angelleye\PayPal\PayPalCommonFunctions($PayPalConfig);

$_SESSION['donation_name']   = !empty($_POST['donation_name']) ? $_POST['donation_name'] : '';
$_SESSION['donation_email']  = !empty($_POST['donation_email']) ? $_POST['donation_email'] : '';
$_SESSION['donation_amount'] = !empty($_POST['donation_amount']) ? $_POST['donation_amount'] : '';
?>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Checkout Donation Demo | Review Donation | PHP Class Library | Angell EYE</title>
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
    <?php $sdk_url = $sandbox ? "https://www.sandbox.paypal.com/web-sdk/v6/core" : "https://www.paypal.com/web-sdk/v6/core"; ?>
    <script src="<?php echo $sdk_url; ?>"></script>
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
          <h2 class="main-title donation-title">
            Review Your Donation
            <a class="back-link" href="./">
              <img src="../assets/images/left.svg" alt="Left Arrow Icon">
              Change Donation Amount
            </a>
          </h2>
          <div class="donation-review">
            <p><strong>Name:</strong> <?php echo $_SESSION['donation_name']; ?></p>
            <p><strong>Email:</strong> <?php echo $_SESSION['donation_email']; ?></p>
            <p><strong>Amount:</strong> $<?php echo $_SESSION['donation_amount']; ?></p>
            <a class="donation-button" href="createOrder.php">
              <h4 class="donate-label">Donate with PayPal</h4>
              <?php $PayPalCommonFunctions->renderPayPalButton(); ?></a>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>