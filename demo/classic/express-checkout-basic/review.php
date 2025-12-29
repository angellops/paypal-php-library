<?php
require_once('../../../includes/config.php');
?>
<html lang="en">
<head>
<meta charset="utf-8">
<title>PayPal Express Checkout Basic Demo | Order Review | PHP Class Library | Angell EYE</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="">
<meta name="author" content="">

<!--link rel="stylesheet/less" href="less/bootstrap.less" type="text/css" /-->
<!--link rel="stylesheet/less" href="less/responsive.less" type="text/css" /-->
<!--script src="../assets/js/less-1.3.3.min.js"></script-->
<!--append ‘#!watch’ to the browser URL, then refresh the page. -->

<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
<link href="../../assets/css/style.css" rel="stylesheet">

<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
    <script src="../../assets/js/html5shiv.js"></script>
    <![endif]-->

<!-- Fav and touch icons -->
<link rel="apple-touch-icon-precomposed" sizes="144x144" href="../../assets/images/apple-touch-icon-144-precomposed.png">
<link rel="apple-touch-icon-precomposed" sizes="114x114" href="../../assets/images/apple-touch-icon-114-precomposed.png">
<link rel="apple-touch-icon-precomposed" sizes="72x72" href="../../assets/images/apple-touch-icon-72-precomposed.png">
<link rel="apple-touch-icon-precomposed" href="../../assets/images/apple-touch-icon-57-precomposed.png">
<link rel="shortcut icon" href="../../assets/images/favicon.png">
<script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script type="text/javascript" src="../../assets/js/scripts.js"></script>
</head>

<body>
<div class="container">
  <div class="row clearfix">
    <div class="col-md-12 column">
      <div id="header" class="row clearfix">
        <div class="col-md-6 column">
          <div id="angelleye_logo"> <a href="/"><img alt="Angell EYE PayPal PHP Class Library Demo" src="../../assets/images/logo.png"></a> </div>
        </div>
        <div class="col-md-6 column">
          <div id="paypal_partner_logo"> <img alt="PayPal Partner and Certified Developer" src="../../assets/images/paypal-partner-logo.png"/> </div>
        </div>
      </div>
      <?php if( $api_mode === 'classic' ) { ?>
        <div class="warning-info">
          <span class="warning-icon">!</span>PayPal Classic API is deprecated. Please upgrade to the REST API for continued support and latest features.
        </div>
      <?php } ?>
      <h2 class="main-title">Order Review</h2>
      <p class="main-info">Here we display a final review to the buyer now that we've calculated shipping, handling, and tax.  The 
      billing and shipping information provided here is what we obtained in the <strong>GetExpressCheckoutDetails</strong> response.</p>
      <p class="main-info">The payment has not been processed at this point because we have not yet called the final <strong>DoExpressCheckoutPayment</strong> API. That is what will 
      happen when we click the "<strong>Complete Order</strong>" button below.</p>
      <table class="table table-items table-bordered">
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
        <div class="col-md-4 column">
          <p><strong>Billing Information</strong></p>
          <p>
          	<?php
              echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] . '<br />' . 
              $_SESSION['email'] . '<br />'. 
              $_SESSION['phone_number'] . '<br />';
            ?>
          </p>
        </div>
        <div class="col-md-4 column">
          <p><strong>Shipping Information</strong></p>
          <p>
          	<?php 
              echo $_SESSION['shipping_name'] . '<br />' .
              $_SESSION['shipping_street'] . '<br />' .
              $_SESSION['shipping_city'] . ', ' . $_SESSION['shipping_state'] . '  ' . $_SESSION['shipping_zip'] . '<br />' . 
              $_SESSION['shipping_country_name']; 
            ?>
          </p>
        </div>
        <div class="col-md-4 column">
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
                <td class="button-center" colspan="2"><a href="DoExpressCheckoutPayment.php" class="btn btn-success btn-lg" role="button">Complete Order</a></td>
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