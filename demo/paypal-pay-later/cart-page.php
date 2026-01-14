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

// Check if the form with the name/button 'add' has been submitted via POST
if (isset($_POST['add'])) {
  $PayPalCommonFunctions->add_to_cart($_POST['id'], $_POST['name'], $_POST['price'], (int)$_POST['qty']);

  header('Location: cart-page.php');
  exit;
}

// Retrieve the current contents of the shopping cart to be used for display
$cart = $PayPalCommonFunctions->get_cart();
?>

<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Pay Later Demo | Cart | PHP Class Library | Angell EYE</title>
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
  <body class="cart-page">
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
          <form method="post" action="checkout-page.php">
            <div class="heading-wrapper">
              <h2 class="main-title heading"><img src="../assets/images/cart.svg" alt="Cart">Shopping Cart</h2>
              <?php if (!empty($cart)) { ?>
                <button type="submit" name="empty" class="cart-btns cart-empty">Empty Cart</button>
              <?php } ?>
            </div>
            <p class="main-info">Here we are having shopping cart that transmits only the final order total to PayPal, omitting specific line-item details for simplicity. Because we expect to retrieve the buyer's billing and shipping information directly from PayPal upon their return, no personal data is collected on this site prior to login.</p>
            <?php if (!empty($cart)) { ?>
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
                  <?php foreach ($cart as $id => $item) { ?>
                  <tr class="cart-item" data-id="<?php echo $id; ?>" data-price="<?php echo $item['price']; ?>">
                    <td class="center cart-item-image"><img src="../assets/images/product.png" alt="Product Image"></td>
                    <td class="center"><?php echo $id; ?></td>
                    <td class="center font-lightbold"><?php echo $item['name']; ?></td>
                    <td class="center"> $<?php echo number_format($item['price'],2); ?></td>
                    <td class="center font-lightbold">
                      <div class="quantity-controls">
                        <button type="button" class="qty-btn minus">−</button>
                        <span class="qty-value"><?php echo $item['qty']; ?></span>
                        <button type="button" class="qty-btn plus">+</button>
                        <input type="hidden" name="qty[<?php echo $id; ?>]" value="<?php echo $item['qty']; ?>">
                      </div>
                    </td>
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
                        <td class="font-lightbold">$<span class="cart-subtotal-value"><?php echo $PayPalCommonFunctions->cart_total(); ?></span></td>
                      </tr>
                      <tr>
                        <td>Shipping</td>
                        <td class="font-lightbold">$<span class="cart-shipping"><?php echo number_format(0, 2); ?></span></td>
                      </tr>
                      <tr>
                        <td>Handling</td>
                        <td class="font-lightbold">$<span class="cart-handling"><?php echo number_format(0, 2); ?></span></td>
                      </tr>
                      <tr>
                        <td>Tax</td>
                        <td class="font-lightbold">$<span class="cart-tax"><?php echo number_format(0, 2); ?></span></td>
                      </tr>
                      <tr>
                        <td class="font-lightbold total-border-top">Grand Total</td>
                        <td class="font-lightbold total-border-top">$<span class="cart-total-value"><?php echo $PayPalCommonFunctions->cart_total(); ?></span></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <paypal-message
                auto-bootstrap
                amount="<?php echo $PayPalCommonFunctions->cart_total(); ?>"
                currency-code="USD">
              </paypal-message>
              <div class="cart-actions">
                <a class="cart-link" href="./">
                  <img src="../assets/images/left.svg" alt="Left Arrow Icon">
                  Return to Shop
                </a>
                <button type="submit" name="proceed_checkout" class="cart-btns cart-checkout">Proceed to checkout</button>
              </div>
            <?php } else { ?>
              <div class="empty-cart-wrapper">
                <img src="../assets/images/empty-cart.png" alt="Empty Cart">
                <h3>Your cart is currently empty!</h3>
                <p class="empty-cart-message">Before proceed to checkout you must add some products to your shopping cart. You will find a lot of interesting products on our "Shop" page.</p>
                <a class="cart-btns" href="./">Return to Shop</a>
              </div>
            <?php } ?>
          </form>
        </div>
      </div>
    </div>
  </body>
</html>