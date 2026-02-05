<?php
require_once('../../includes/config.php');
require_once('../../vendor/autoload.php');
?>

<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PayPal Pay Later Demo | PHP Class Library | Angell EYE</title>
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
  <body class="product-page">
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
          <div class="heading-wrapper">
            <h2 class="heading">Shop</h2>
            <a class="cart-link <?php echo ( $api_mode === 'classic' ) ? 'disabled' : '' ?>" href="cart-page.php">
              Go to Cart
              <img src="../assets/images/right.svg" alt="Right Arrow Icon">
            </a>
          </div>
          <p class="main-info">Browse our sample products below to experience a complete checkout flow.<br>
          Adjust quantities, add items to your cart, and proceed to checkout to see how PayPal payments work in a real-world scenario.</p>
          <div class="product-grid">
            <form method="post" action="cart-page.php" class="product-card">
              <img src="../assets/images/product.png" alt="Product Image" class="product-image">
              <h3 class="product-name">Widget</h3>
              <p class="price">$60.00</p>
              <input type="hidden" name="id" value="123-ABC">
              <input type="hidden" name="name" value="Widget">
              <input type="hidden" name="price" value="60.00">
              <paypal-message
                auto-bootstrap
                amount="60.00"
                currency-code="USD">
              </paypal-message>
              <div class="qty-wrapper">
                <label class="quantity-label">Quantity</label>
                <div class="quantity-controls">
                  <button type="button" class="qty-btn minus">−</button>
                  <span class="qty-value">1</span>
                  <button type="button" class="qty-btn plus">+</button>
                  <input type="hidden" name="qty" value="1">
                </div>
              </div>
              <button type="submit" name="add" class="add-btn" <?php echo ( $api_mode === 'classic' ) ? 'disabled' : '' ?>>Add to Cart</button>
            </form>
            <form method="post" action="cart-page.php" class="product-card">
              <img src="../assets/images/product.png" alt="Product Image" class="product-image">
              <h3 class="product-name">Gadget</h3>
              <p class="price">$40.40</p>
              <input type="hidden" name="id" value="XYZ-456">
              <input type="hidden" name="name" value="Gadget">
              <input type="hidden" name="price" value="40.40">
              <paypal-message
                auto-bootstrap
                amount="40.40"
                currency-code="USD">
              </paypal-message>
              <div class="qty-wrapper">
                <label class="quantity-label">Quantity</label>
                <div class="quantity-controls">
                  <button type="button" class="qty-btn minus">−</button>
                  <span class="qty-value">1</span>
                  <button type="button" class="qty-btn plus">+</button>
                  <input type="hidden" name="qty" value="1">
                </div>
              </div>
              <button type="submit" name="add" class="add-btn" <?php echo ( $api_mode === 'classic' ) ? 'disabled' : '' ?>>Add to Cart</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>