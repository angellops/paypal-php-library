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
 * Payer Personal Information
 * Cardholder / customer name details
 */
$_SESSION['payer'] = array(
	'firstname' => 'John', 		// Payer's first name
	'lastname' => 'Doe', 		// Payer's last name
    'email' => 'sandbox@angelleye.com', // Payer's email address
    'phonenumber' => '',   // Payer's phone number
);
	
/**
 * Billing Address Details
 */
$_SESSION['billing'] = array(
	'street' => '707 W. Bay Drive', 	// Required.  First street address.
	'city' => 'Largo', 			// Required.  Name of City.
	'state' => 'FL', 			// Required. Name of State or Province.
	'countrycode' => 'US', 			// Required.  Country code.
	'zip' => '33770', 			// Required.  Postal code of payer.
);

/**
 * Here we are building a very simple, static shopping cart to use
 * throughout this demo.  In most cases, you will working with a dynamic
 * shopping cart system of some sort.
 */
$_SESSION['checkout_options_items'][0] = array(
    'id' => '123-ABC',
    'name' => 'Widget',
    'qty' => '2',
    'price' => '15.00',
);

$_SESSION['checkout_options_items'][1] = array(
    'id' => 'XYZ-456',
    'name' => 'Gadget',
    'qty' => '1',
    'price' => '10.5',
);
$_SESSION['shopping_cart'] = array(
    'checkout_options_items' => $_SESSION['checkout_options_items'],
    'subtotal' => 40.5,
    'shipping' => 15,
    'handling' => 10.5,
    'tax' => 4,
);
$_SESSION['shopping_cart']['grand_total'] = number_format($_SESSION['shopping_cart']['subtotal'] + $_SESSION['shopping_cart']['shipping'] + $_SESSION['shopping_cart']['handling'] + $_SESSION['shopping_cart']['tax'], 2);

$checkoutData = [
    'payer' => $_SESSION['payer'],
    'billing' => $_SESSION['billing'],
    'cart' => $_SESSION['shopping_cart']
];
?>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>PayPal Multiple Checkout Options Demo | PHP Class Library | Angell EYE</title>
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
        <script src="basic-paypal.js"></script>
        <script src="paylater.js"></script>
        <script src="venmo.js"></script>
        <script src="guest-checkout.js"></script>
        <script src="acdc.js"></script>
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
                    <p class="main-info">This demo showcases multiple PayPal checkout options including PayPal Wallet, Guest Checkout (Debit/Credit Cards), Venmo, Pay Later, and Direct Card Payments, allowing customers to choose their preferred payment method during checkout.</p>
                    <div class="demo-main-wrapper">
                        <div class="demo-cred">
                            <h2>PayPal Credentials</h2>
                            <p>Email: paypal-buyer@angelleye.com</p>
                            <p>Password: paypalphp</p>
                        </div>
                        <div class="demo-cred">
                            <h2>Card Credentials</h2>
                            <p>Card Number: 4111111111111111</p>
                            <p>Card Expiry: 12/27</p>
                            <p>Card CVV: 456</p>
                        </div>
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
                            <?php
                            foreach ($_SESSION['shopping_cart']['checkout_options_items'] as $cart_item) {
                                ?>
                                <tr>
                                    <td class="center"><?php echo $cart_item['id']; ?></td>
                                    <td class="center font-lightbold"><?php echo $cart_item['name']; ?></td>
                                    <td class="center"> $<?php echo number_format($cart_item['price'],2); ?></td>
                                    <td class="center font-lightbold"><?php echo $cart_item['qty']; ?></td>
                                    <td class="center font-lightbold"> $<?php echo number_format($cart_item['qty'] * $cart_item['price'],2); ?></td>
                                </tr>
                                <?php
                            }
                            ?>
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
                                            <div class="paylater-message">
                                                <paypal-message 
                                                    auto-bootstrap
                                                    amount="<?php echo number_format($_SESSION['shopping_cart']['grand_total'],2); ?>"
                                                    currency-code="USD">
                                                </paypal-message>
                                            </div>
                                            <div class="inline-buttons other-buttons">
                                                <div id="paypal-container" data-checkout='<?php echo json_encode($_SESSION['shopping_cart']); ?>'>
                                                    <div id="paypalError"></div>
                                                    <paypal-button type="pay" hidden></paypal-button>
                                                </div>
                                                <div id="guest-checkout-container" data-mode="rest" data-checkout='<?php echo json_encode($_SESSION['shopping_cart']); ?>'>
                                                    <paypal-basic-card-container>
                                                        <paypal-basic-card-button id="paypal-basic-card-button" hidden></paypal-basic-card-button>
                                                    </paypal-basic-card-container>
                                                </div>
                                            </div>
                                            <div class="inline-buttons other-buttons">
                                                <div id="venmo-container" data-checkout='<?php echo json_encode($_SESSION['shopping_cart']); ?>'>
                                                    <venmo-button id="venmo-button" type="pay" hidden></venmo-button>
                                                </div>
                                                <div id="paylater-container" data-checkout='<?php echo json_encode($_SESSION['shopping_cart']); ?>'>
                                                    <div id="paypalError"></div>
                                                    <paypal-pay-later-button hidden></paypal-pay-later-button>
                                                </div>
                                            </div>
                                            <div class="or-wrapper">OR</div>
                                            <div id="acdc-container" data-mode="rest" data-checkout='<?php echo json_encode($checkoutData); ?>'>
                                                <div class="card-fields-container">
                                                    <div class="card-field" id="paypal-card-fields-number"></div>
                                                    <div class="card-field" id="paypal-card-fields-expiry"></div>
                                                    <div class="card-field" id="paypal-card-fields-cvv"></div>
                                                </div>
                                                <button id="pay-button" class="pay-button btn btn-primary">Place Order</button>
                                            </div>
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