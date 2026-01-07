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
 * Define PayPal receiver identifiers for parallel payments.
 *
 * Each receiver can be identified using either:
 * - The merchant's PayPal email address, or
 * - The merchant's PayPal Merchant ID (Payer ID).
 *
 * In parallel payments, each receiver must be uniquely identified so that
 * PayPal can correctly split and route the funds.
 *
 * The following values represent two separate PayPal **sandbox** accounts
 * configured as receivers for testing split/parallel payments.
 */
$seller_a = 'paypal-facilitator@angelleye.com';
$seller_b = 'sandbox-seller@angelleye.com';

/**
 * Merchant IDs (Payer IDs) for the corresponding sandbox seller accounts.
 * These can be used instead of email addresses when constructing the
 * PayPal request payload.
 */
$seller_merchant_id_a = '76CLKUEZXHAUC';
$seller_merchant_id_b = 'QEV4T5D83THAJ';

/**
 * Here we are building a very simple, static shopping cart to use
 * throughout this demo.  In most cases, you will working with a dynamic
 * shopping cart system of some sort.
 *
 * For the purposes of this Parallel Payments demo we have added a
 * "seller_id" to the cart items.
 */
$_SESSION['items'][0] = array(
  'id' => '123-ABC',
  'name' => 'Widget',
  'qty' => '1',
  'price' => '10.00',
  'seller_id' => ($api_mode === 'rest') ? $seller_merchant_id_a : $seller_a,
);

$_SESSION['items'][1] = array(
  'id' => 'XYZ-456',
  'name' => 'Gadget',
  'qty' => '1',
  'price' => '5.00',
  'seller_id' => ($api_mode === 'rest') ? $seller_merchant_id_b : $seller_b,
);
$_SESSION['shopping_cart'] = array(
	'items' => $_SESSION['items'],
	'subtotal' => 15.00,
	'shipping' => 0,
	'handling' => 0,
	'tax' => 0,
);
$_SESSION['shopping_cart']['grand_total'] = number_format($_SESSION['shopping_cart']['subtotal'] + $_SESSION['shopping_cart']['shipping'] + $_SESSION['shopping_cart']['handling'] + $_SESSION['shopping_cart']['tax'],2);
?>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Checkout - Multiparty | PHP Class Library | Angell EYE</title>
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
              <div id="angelleye_logo"> <a href="/"><img alt="Angell EYE PayPal PHP Class Library Demo" src="../assets/images/logo.png"></a> </div>
            </div>
            <div class="col-md-6 column">
              <div id="paypal_partner_logo"> <img alt="PayPal Partner and Certified Developer" src="../assets/images/paypal-partner-logo.png"/> </div>
            </div>
          </div>
          <?php if( $api_mode === 'classic' ) { ?>
            <div class="warning-info">
              <span class="warning-icon">!</span>PayPal Classic API is deprecated. Please upgrade to the REST API for continued support and latest features.
            </div>
          <?php } ?>
          <h2 class="main-title"><img src="../assets/images/cart.svg" alt="Cart">Shopping Cart</h2>
          <p class="main-info">Here we are using a basic shopping cart for display purposes. With the PayPal Parallel Payment, the first item’s payment will go to one PayPal account while the second item’s payment goes to a separate PayPal account.</p>
          <p class="main-info">We are assuming that we have not collected any billing or shipping information from the buyer yet because we'll be obtaining those details from PayPal after the user logs in and is returned back to the site.</p>
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
              <?php foreach($_SESSION['shopping_cart']['items'] as $cart_item) { ?>
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
                    <td class="paypalbtn" colspan="2"><a href="SetExpressCheckout.php"><?php $PayPalCommonFunctions->renderPayPalButton(); ?></a></td>
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