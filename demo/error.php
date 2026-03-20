<?php
require_once('../includes/config.php');
require_once('../autoload.php');

$PayPalConfig = array(
  'Sandbox' => $sandbox,
  'APIUsername' => $api_username,
  'APIPassword' => $api_password,
  'APISignature' => $api_signature,
  'PrintHeaders' => $print_headers,
  'LogResults' => $log_results,
  'LogPath' => $log_path,
);
$PayPal = new angelleye\PayPal\PayPal($PayPalConfig);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PayPal Demo Error | PHP Class Library | Angell EYE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/style.css" />
    
    <!--- Fav and touch icons --->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/images/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="assets/images/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/images/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="assets/images/apple-touch-icon-57-precomposed.png">
    <link rel="shortcut icon" href="assets/images/favicon.png">

    <script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script type="text/javascript" src="assets/js/scripts.js"></script>
  </head>
  <body>
    <!--- HEADER --->
    <header class="header">
      <div class="container header-inner">
        <a href="#" class="logo">
          <img alt="Angell EYE PayPal PHP Class Library Demo" src="assets/images/logo.png" class="logo-img" />
        </a>
        <div id="paypal_partner_logo">
          <img alt="PayPal Partner and Certified Developer" src="assets/images/paypal-partner-logo.png" class="paypal-partner-img" />
          <div class="accept-text">We accept <strong class="paypal-text">PayPal</strong> and <strong class="venmo-text">Venmo</strong></div>
        </div>
      </div>
    </header>

    <!--- Main Content --->
    <main id="paypal_errors">
      <div class="container">
        <?php
        if(isset($_SESSION['errors']) && $_SESSION['errors'] == true) {
          $errors = array_filter($_SESSION['errors']);
          unset($errors['error_array']);
          foreach ($errors as $key => $value){
            echo '<strong>'.ucwords(str_replace('_',' ',$key)).' : </strong>'. $value.'<br/>';
          }
          unset($_SESSION['rest_errors']);
        } else {
          $PayPal->DisplayErrors($_SESSION['paypal_errors']);
        }
        ?>
      </div>
    </main>

    <!--- FOOTER --->
    <footer>
      <div class="container">
        <p>© <span id="year"></span> AngelEye Consulting Services. All rights reserved.</p>
        <p>PayPal Partner. Not directly affiliated with PayPal, Inc.</p>
      </div>
    </footer>
  </body>
</html>