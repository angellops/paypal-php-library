<?php
require_once('../../../includes/config.php');
require_once('../../../vendor/autoload.php');
require_once('../../core/useful-functions.php');

// Redirect to Demo Home if API mode is classic
if ($api_mode === 'classic') {
  header('Location: ../../');
}

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
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Pay Later Demo | PHP Class Library | Angell EYE</title>
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
    <?php 
      $sdk_url = $sandbox ? "https://www.sandbox.paypal.com/web-sdk/v6/core" : "https://www.paypal.com/web-sdk/v6/core"; 
      $sdk_messages_url = $sandbox ? "https://www.sandbox.paypal.com/web-sdk/v6/paypal-messages" : "https://www.paypal.com/web-sdk/v6/paypal-messages";
    ?>
    <script src="<?php echo $sdk_url; ?>"></script>
    <script src="<?php echo $sdk_messages_url; ?>"></script>
    <script src="paylater.js"></script>
  </head>
  <body class="product-page">
    <!-- HEADER -->
    <?php require_once('../../partials/header.php'); ?>

    <!-- Main -->
    <main class="cart-main">
      <div class="container">
        <!-- Page Title -->
        <div class="cart-page-title">
          <div class="cart-title-icon">
            <?php echo inline_svg('../../assets/images/product-shop.svg'); ?>
          </div>
          <h1>Shop</h1>
        </div>

        <!-- Intro Text -->
        <div class="cart-intro">
          <p>Browse our sample products below to experience a complete checkout flow.</p>
          <p>Adjust quantities, add items to your cart, and proceed to checkout to see how PayPal payments work in a real-world scenario.</p>
        </div>

        <!-- Product Grid -->
        <div class="product-grid">
          <form method="post" action="cart-page.php" class="product-card">
            <div class="product-image">
              <span class="product-sku-tag">SKU: 123-ABC</span>
              <?php echo inline_svg('../../assets/images/product.svg'); ?>
            </div>
            <div class="product-card-body">
              <div class="product-card-row">
                <span class="product-name">Widget</span>
                <span class="product-price">$60.00</span>
              </div>
              <input type="hidden" name="id" value="123-ABC">
              <input type="hidden" name="name" value="Widget">
              <input type="hidden" name="price" value="60.00">
              <div class="product-paylater-msg">
                <paypal-message auto-bootstrap amount="60.00" currency-code="USD"></paypal-message>
              </div>
              <div class="qty-wrapper">
                <label class="quantity-label">Quantity</label>
                <div class="quantity-controls">
                  <button type="button" class="qty-btn minus">−</button>
                  <span class="qty-value">1</span>
                  <button type="button" class="qty-btn plus">+</button>
                  <input type="hidden" name="qty" value="1">
                </div>
              </div>
              <button type="submit" name="add" class="add-btn">
                <?php echo inline_svg('../../assets/images/cart-icon.svg'); ?>
                Add to Cart
              </button>
            </div>
          </form>
          <form method="post" action="cart-page.php" class="product-card">
            <div class="product-image">
              <span class="product-sku-tag">SKU: XYZ-456</span>
              <?php echo inline_svg('../../assets/images/product.svg'); ?>
            </div>
            <div class="product-card-body">
              <div class="product-card-row">
                <span class="product-name">Gadget</span>
                <span class="product-price">$40.40</span>
              </div>
              <input type="hidden" name="id" value="XYZ-456">
              <input type="hidden" name="name" value="Gadget">
              <input type="hidden" name="price" value="40.40">
              <div class="product-paylater-msg">
                <paypal-message auto-bootstrap amount="40.40" currency-code="USD"></paypal-message>
              </div>
              <div class="qty-wrapper">
                <label class="quantity-label">Quantity</label>
                <div class="quantity-controls">
                  <button type="button" class="qty-btn minus">−</button>
                  <span class="qty-value">1</span>
                  <button type="button" class="qty-btn plus">+</button>
                  <input type="hidden" name="qty" value="1">
                </div>
              </div>
              <button type="submit" name="add" class="add-btn">
                <?php echo inline_svg('../../assets/images/cart-icon.svg'); ?>
                Add to Cart
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>

    <!-- FOOTER -->
    <?php require_once('../../partials/footer.php'); ?>
  </body>
</html>