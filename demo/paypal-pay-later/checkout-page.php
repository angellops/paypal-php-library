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

// Redirect to Pay Later Product Page if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ./');
}

/**
 * Empty Cart Logic
 * If the 'empty' button was clicked, clear all items and redirect to refresh the UI.
 */
if (isset($_POST['empty'])) {
    $PayPalCommonFunctions->empty_cart();
    header('Location: cart-page.php');
    exit;
}

/**
 * Cart Retrieval & Validation
 * Fetch the updated list of items. If the cart is empty (e.g., after an update 
 * where all quantities became 0), redirect the user away from the checkout process.
 */
$items = $PayPalCommonFunctions->get_cart();
if (empty($items)) {
    header('Location: cart-page.php');
    exit;
}

/**
 * Financial Calculation & Session Storage
 * Calculates the total cost and stores the entire state in a session variable.
 * This 'shopping_cart' array acts as the single source of truth for the PayPal checkout.
 */
$subtotal = $PayPalCommonFunctions->calculate_subtotal($items);
$_SESSION['shopping_cart'] = [
  'items'    => $items,
  'subtotal' => $subtotal,
  'shipping' => 10.00,
  'handling' => 2.50,
];
$_SESSION['shopping_cart']['tax'] = number_format(($_SESSION['shopping_cart']['subtotal'] + $_SESSION['shopping_cart']['shipping'] + $_SESSION['shopping_cart']['handling']) * 0.08, 2);
$_SESSION['shopping_cart']['grand_total'] = number_format($_SESSION['shopping_cart']['subtotal'] + $_SESSION['shopping_cart']['shipping'] + $_SESSION['shopping_cart']['handling'] + $_SESSION['shopping_cart']['tax'],2);
?>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Pay Later Demo | Checkout | PHP Class Library | Angell EYE</title>
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
    <?php 
      $sdk_url = $sandbox ? "https://www.sandbox.paypal.com/web-sdk/v6/core" : "https://www.paypal.com/web-sdk/v6/core"; 
      $sdk_messages_url = $sandbox ? "https://www.sandbox.paypal.com/web-sdk/v6/paypal-messages" : "https://www.paypal.com/web-sdk/v6/paypal-messages";
    ?>
    <script src="<?php echo $sdk_url; ?>"></script>
    <script src="<?php echo $sdk_messages_url; ?>"></script>
    <script src="paylater.js"></script>
  </head>
  <body class="cart-page checkout-page">
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
          <div class="heading-wrapper">
            <h2 class="heading">Checkout</h2>
            <a class="cart-link" href="cart-page.php">
              <img src="../assets/images/left.svg" alt="Left Arrow Icon">
              Back to Cart
            </a>
          </div>
          <p class="main-info">This checkout sends only the final purchase amount to PayPal for payment processing, without sharing individual product details. Billing and shipping information is securely retrieved from PayPal after you log in, so no personal data is collected on this site before checkout.</p>
          <table class="table table-items">
            <thead>
              <tr>
                <th class="center">Image</th>
                <th class="center">ID</th>
                <th class="center">Name</th>
                <th class="center">Price</th>
                <th class="center">QTY</th>
                <th class="center">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($_SESSION['shopping_cart']['items'] as $id => $item) { ?>
              <tr class="cart-item">
                <td class="center cart-item-image"><img src="../assets/images/product.png" alt="Product Image"></td>
                <td class="center"><?php echo $id; ?></td>
                <td class="center font-lightbold"><?php echo $item['name']; ?></td>
                <td class="center">$<?php echo number_format($item['price'],2); ?></td>
                <td class="center font-lightbold"><?php echo $item['qty']; ?></td>
                <td class="center font-lightbold line-total">$<span><?php echo number_format($item['qty'] * $item['price'],2); ?></span></td>
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
                    <td class="font-lightbold">$<span class="cart-subtotal-value"><?php echo number_format($_SESSION['shopping_cart']['subtotal'], 2); ?></span></td>
                  </tr>
                  <tr>
                    <td>Shipping</td>
                    <td class="font-lightbold">$<span class="cart-shipping"><?php echo number_format($_SESSION['shopping_cart']['shipping'], 2); ?></span></td>
                  </tr>
                  <tr>
                    <td>Handling</td>
                    <td class="font-lightbold">$<span class="cart-handling"><?php echo number_format($_SESSION['shopping_cart']['handling'], 2); ?></span></td>
                  </tr>
                  <tr>
                    <td>Tax</td>
                    <td class="font-lightbold">$<span class="cart-tax"><?php echo number_format($_SESSION['shopping_cart']['tax'], 2); ?></span></td>
                  </tr>
                  <tr>
                    <td class="font-lightbold total-border-top">Grand Total</td>
                    <td class="font-lightbold total-border-top">$<span class="cart-total-value"><?php echo number_format($_SESSION['shopping_cart']['grand_total'], 2); ?></span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <paypal-message
            auto-bootstrap
            amount="<?php echo number_format($_SESSION['shopping_cart']['grand_total'], 2); ?>"
            currency-code="USD">
          </paypal-message>
          <a href="createOrder.php" class="paylater-btn">
            <paypal-pay-later-button hidden></paypal-pay-later-button>
          </a>
        </div>
      </div>
    </div>
  </body>
</html>