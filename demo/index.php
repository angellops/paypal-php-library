<html lang="en">
<head>
    <meta charset="utf-8">
    <title>PayPal PHP Class Library Demo | PayPal Partner | Angell EYE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!--link rel="stylesheet/less" href="less/bootstrap.less" type="text/css" /-->
    <!--link rel="stylesheet/less" href="less/responsive.less" type="text/css" /-->
    <!--script src="/assets/js/less-1.3.3.min.js"></script-->
    <!--append ‘#!watch’ to the browser URL, then refresh the page. -->

    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
    <script src="/assets/js/html5shiv.js"></script>
    <![endif]-->

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/images/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="assets/images/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/images/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="assets/images/apple-touch-icon-57-precomposed.png">
    <link rel="shortcut icon" href="assets/images/favicon.png">
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="assets/js/scripts.js"></script>
</head>

<body>
<div class="container">
    <div class="row clearfix">
        <div class="col-md-12 column">
            <div id="header" class="row clearfix">
                <div class="col-md-6 column">
                    <div id="angelleye_logo">
                        <a href="/">
                            <img alt="Angell EYE PayPal PHP Class Library Demo" src="assets/images/logo.png">
                        </a>
                    </div>
                </div>
                <div class="col-md-6 column">
                    <div id="paypal_partner_logo">
                        <img alt="PayPal Partner and Certified Developer" src="assets/images/paypal-partner-logo.png"/>
                    </div>
                </div>
            </div>
            <h1 class="main-title demo-title">DemoKits</h1>
            <div class="main-info">
                <p><strong>What is This?</strong></p>
                <p>The class library comes with FREE, fully functional /samples and empty /templates ready for you to work with. The demo kits available here are more complete and tie together a number of APIs within a basic shopping cart interface so that you can see how everything flows together.</p>
            </div>
            <div class="main-info">
                <p><strong>How Does This Work?</strong></p>
                <p>Checkout one of the FREE demo kits included by clucking the Launch button.  This will allow you see an example of how the demo kits work.</p>
                <p>The HTML and code has lots of comments explaining what is going on so that you can see it for yourself, and learn from it.</p>
                <p>
                    <a href="https://www.angelleye.com/product-category/php-class-libraries/demo-kits/" target="_blank">Additional demo kits</a> 
                    are available for purchase and can be installed here to complete your collection.
                </p>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="demo-warning-info warning-info">
                        <span class="warning-icon">!</span>The classic API is still available, but is being labeled as deprecated.  You can still use them, and they work very well, but if you are starting fresh you may want to look at REST instead.
                    </div>
                    <hr>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="PayPal Express Checkout Basic Integration" src="assets/images/paypal-express-checkout.jpg">
                        <div class="caption">
                            <h3>PayPal Checkout</h3>
                            <h4>Basic</h4>
                            <p>Here we are integrating Express Checkout without any line item details or any extra
                                features. We obtain the user's shipping information so that we can calculate shipping
                                and tax, but otherwise no additional data is included with this checkout demo.</p>
                            <p align="center">
                                <a class="btn btn-primary" href="paypal-checkout-basic/" target="_blank">Launch Demo</a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="PayPal Express Checkout Line Items Integration" src="assets/images/paypal-express-checkout-with-line-items.jpg">
                        <?php
                        $DIR = '\express-checkout-line-items';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout</h3>
                            <h4>w/ Line Items</h4>
                            <p>Here we expand on our basic Express Checkout demo to add individual order items to the
                                API requests so that the data is available within PayPal's checkout review pages
                                transaction details.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="express-checkout-line-items/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-express-checkout-php-line-items-demo/?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="PayPal Express Checkout Digital Goods Integration" src="assets/images/paypal-express-checkout-digital-goods.jpg">
                        <?php
                        $DIR = '\express-checkout-digital-goods';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout</h3>
                            <h4>Digital Goods</h4>
                            <p>Learn how to implement digital goods (micro-processing rates) into PayPal Express
                                Checkout.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="express-checkout-digital-goods/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-express-checkout-php-digital-goods-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="Express Checkout Billing Agreement" src="assets/images/paypal-express-checkout-billing-agreement.jpg">
                        <?php
                        $DIR = '\express-checkout-billing-agreement';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout Billing Agreement</h3>
                            <h4>Billing Agreement</h4>
                            <p>Learn how to implement Billing Agreement into PayPal Express Checkout.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="express-checkout-billing-agreement/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-express-checkout-php-billing-agreement-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="Express Checkout Parallel Payments" src="assets/images/paypal-express-parrallel-payments.jpg">
                        <?php
                        $DIR = '\express-checkout-parallel-payments';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout Parallel Payments</h3>
                            <h4>Parallel Payments</h4>
                            <p>Learn how to implement Parallel Payments into PayPal Express Checkout.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="express-checkout-parallel-payments/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-express-checkout-php-parallel-payments-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="Express checkout 3rd Party No Permissions" src="assets/images/paypal-express-3rd-party-no-permissions-required.jpg">
                        <?php
                        $DIR = '\express-checkout-3rd-party-no-permissions';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout</h3>
                            <h4>3rd Party No Permissions</h4>
                            <p>Learn how to integrate Express Checkout on behalf of a 3rd party with no API permissions required.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="express-checkout-3rd-party-no-permissions/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-express-checkout-php-3rd-party-payments-no-permissions-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="Express Checkout In-Context" src="assets/images/paypal-express-checkout-in-context.jpg">
                        <?php
                        $DIR = '\express-checkout-incontext';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout</h3>
                            <h4>In-Context</h4>
                            <p>Learn how to implement Express Checkout In-Context so that users are not redirected away
                                from your site for payment.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="express-checkout-incontext/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-express-checkout-php-in-context-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="Express Checkout Recurring Payment" src="assets/images/express-checkout-recurring-payments.jpg">
                        <?php
                        $DIR = '\express-checkout-recurring-payments';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout</h3>
                            <h4>Recurring Payments</h4>
                            <p>Learn how to create subscription profiles using the Recurring Payments API.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="express-checkout-recurring-payments/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-express-checkout-php-recurring-payments-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="Express Checkout Shipped Items + Recurring Payment" src="assets/images/express-checkout-shipped-items-recurring-payments.jpg">
                        <?php
                        $DIR = '\express-checkout-shipped-items-recurring-payments';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout</h3>
                            <h4>Shipped Items + Subscription</h4>
                            <p>Learn how to implement Express Checkout with shipped items and Subscription / Recurring
                                Payments together on a single order.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary"
                                       href="express-checkout-shipped-items-recurring-payments/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-express-checkout-php-recurring-payments-shipped-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>

                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="PayPal Payments Pro PayFlow Basic Integration" src="assets/images/paypal-payments-pro-payflow.jpg">
                        <?php
                        $DIR = '\payflow-credit-card-checkout-basic';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3> Payments Pro 2.0</h3>
                            <h4> PayFlow </h4>
                            <p>See how to integrate credit card processing directly on your site using PayPal's PayFlow
                                gateway. This allows users to enter credit card details directly on your site without
                                any redirect to PayPal at all.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="payflow-credit-card-checkout-basic/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-payments-pro-payflow-php-basic-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="PayPal Payments Pro PayFlow Recurring Billing Integration" src="assets/images/paypal-payments-pro-payflow-recurring-billing.jpg">
                        <?php
                        $DIR = '\payflow-credit-card-checkout-recurring-billing';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3> Payments Pro 2.0 </h3>
                            <h4> PayFlow Recurring Billing </h4>
                            <p>Here we use the PayFlow gateway to setup recurring billing / subscription profiles. This
                                is done using a credit card directly just like the Payments Pro basic demos.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="payflow-credit-card-checkout-recurring-billing/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-payments-pro-payflow-php-recurring-billing-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail">
                        <img alt="Website Payments Pro 3.0 - DoDirectPayment" src="assets/images/paypal-dodirectpayment-credit-cart-checkout.jpg"/>
                        <?php
                        $DIR = '\website-payments-pro-30-basic';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3> Website Payments Pro 3.0 </h3>
                            <h4> DoDirectPayment </h4>
                            <p>Use the DoDirectPayment API to accept direct credit card payments on your website from
                                buyers who do not have a PayPal account. PayPal processes the payment in the
                                background.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="website-payments-pro-30-basic" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-website-payments-pro-php-dodirectpayment-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>
</html>