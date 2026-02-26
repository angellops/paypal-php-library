<?php
require_once('../../includes/config.php');
?>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Checkout Donation Demo | Order Complete | PHP Class Library | Angell EYE</title>
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
          <h2 class="main-title">Donation Completed Successfully!</h2>
          <p class="main-info main-center">Thank you for your generous donation! Your support helps us continue our mission.</p>
          <div class="donation-review">
            <p><strong>Name:</strong> <?php echo $_SESSION['donation_name']; ?></p>
            <p><strong>Email:</strong> <?php echo $_SESSION['donation_email']; ?></p>
            <p><strong>Amount:</strong> $<?php echo $_SESSION['donation_amount']; ?></p>
            <p><strong>Transaction ID:</strong> <?php echo $_SESSION['paypal_transaction_id']; ?></p>
            <p><strong>Billing Information</strong>
              <br><strong style="padding-left: 16px;">- Name:</strong> <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>
              <br><strong style="padding-left: 16px;">- Email:</strong> <?php echo $_SESSION['email']; ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
<?php
session_destroy();
?>