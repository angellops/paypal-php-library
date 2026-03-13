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

/**
 * Here we are building a very simple, static shopping cart to use
 * throughout this demo.  In most cases, you will working with a dynamic
 * shopping cart system of some sort.
 */
$_SESSION['callback_items'][0] = array(
  'id' => '123-ABC',
  'name' => 'Widget',
  'qty' => '2',
  'price' => '9.99',
);

$_SESSION['callback_items'][1] = array(
  'id' => 'XYZ-456',
  'name' => 'Gadget',
  'qty' => '1',
  'price' => '4.99',
);
$_SESSION['shopping_cart'] = array(
	'callback_items' => $_SESSION['callback_items'],
	'subtotal' => 24.97,
	'shipping' => 0,
	'handling' => 0,
	'tax' => 0,
);
$_SESSION['shopping_cart']['grand_total'] = number_format($_SESSION['shopping_cart']['subtotal'] + $_SESSION['shopping_cart']['shipping'] + $_SESSION['shopping_cart']['handling'] + $_SESSION['shopping_cart']['tax'],2);
?>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Checkout Shipping Callback | PHP Class Library | Angell EYE</title>
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
    <script src="shipping-cb.js"></script>
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
          <h2 class="main-title"><img src="../assets/images/cart.svg" alt="Cart">Shopping Cart</h2>
          <p class="main-info">Here we are using a basic shopping cart for display purposes, however, for this basic demo, all we are sending to PayPal is the order total without any line item details. We are assuming that we have not collected any billing or shipping information from the buyer yet because we'll be obtaining those details from PayPal after the user logs in and is returned back to the site.</p>
          <div class="demo-cred">
            <h2>Demo Credentials</h2>
            <p>Email: paypal-buyer@angelleye.com</p>
            <p>Password: paypalphp</p>
          </div>
          <table class="table table-items">
            <thead>
              <tr>
                <th class="center">ID</th>
                <th class="center">Name</th>
                <th class="center">Price</th>
                <th class="center">QTY</th>
                <th class="center">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($_SESSION['shopping_cart']['callback_items'] as $cart_item) { ?>
              <tr>
                <td class="center"><?php echo $cart_item['id']; ?></td>
                <td class="center font-lightbold"><?php echo $cart_item['name']; ?></td>
                <td class="center"> $<?php echo number_format($cart_item['price'],2); ?></td>
                <td class="center font-lightbold"><?php echo $cart_item['qty']; ?></td>
                <td class="center font-lightbold"> $<?php echo number_format($cart_item['qty'] * $cart_item['price'],2); ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
          <div class="row clearfix">
            <div class="col-md-4 column"> </div>
            <div class="col-md-3 column"> </div>
            <div class="col-md-5 column">
              <table class="table table-summary">
                <tbody>
                  <tr>
                    <td>Subtotal</td>
                    <td class="font-lightbold">$<?php echo number_format($_SESSION['shopping_cart']['subtotal'],2); ?></td>
                  </tr>
                  <tr>
                    <td>Shipping</td>
                    <td class="font-lightbold">$<?php echo number_format($_SESSION['shopping_cart']['shipping'],2); ?></td>
                  </tr>
                  <tr>
                    <td>Handling</td>
                    <td class="font-lightbold">$<?php echo number_format($_SESSION['shopping_cart']['handling'],2); ?></td>
                  </tr>
                  <tr>
                    <td>Tax</td>
                    <td class="font-lightbold">$<?php echo number_format($_SESSION['shopping_cart']['tax'],2); ?></td>
                  </tr>
                  <tr>
                    <td class="font-lightbold total-border-top">Grand Total</td>
                    <td class="font-lightbold total-border-top">$<?php echo number_format($_SESSION['shopping_cart']['grand_total'],2); ?></td>
                  </tr>
                  <tr>
                    <td class="paypalbtn" colspan="2">
                      <?php if( $api_mode === 'classic' ) : ?>
                        <a href="#" disabled>
                          <img src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif">
                        </a>
                      <?php else: ?>
                        <div id="paypal-button-container" data-checkout='<?php echo json_encode($_SESSION['shopping_cart']); ?>'>
                          <div id="paypalError"></div>
                          <paypal-button type="pay" hidden></paypal-button>
                        </div>
                      <?php endif; ?>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>