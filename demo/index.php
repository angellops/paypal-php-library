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
            <div class="row clearfix">
                <div class="col-md-6 column">
                    <div id="angelleye_logo"><a href="/"><img alt="Angell EYE PayPal PHP Class Library Demo"
                                                              src="assets/images/logo.png"></a></div>
                </div>
                <div class="col-md-6 column">
                    <div id="paypal_partner_logo"><img alt="PayPal Partner and Certified Developer"
                                                       src="assets/images/paypal-partner-logo.png"/></div>
                </div>
            </div>
            <h1></h1>
            <p class="bg-info"><strong>What is This?</strong><br>
                The class library comes with FREE, fully functional /samples and empty /templates ready for you to work
                with. The demo kits
                available here are more complete and tie together a number of APIs within a basic shopping cart
                interface so that you can see how everything flows together.</p>
            <p class="bg-info"><strong>How Does This Work?</strong><br>
                Checkout one of the FREE demo kits included by clucking the Launch button.  This will allow you
                see an example of how the demo kits work.
                <br /><br />The HTML and code has lots of comments explaining
                what is going
                on so that you can see it for yourself, and learn from it.
                <br/><br/><a href="https://www.angelleye.com/product-category/php-class-libraries/demo-kits/" target="_blank">Additional demo kits</a> are available
                for purchase and can be installed here to complete your collection.
            </p>
            <div class="row">
                <div class="col-lg-12">
                    <hr>
                    <h1>REST API</h1>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h3>API v2</h3>
                    <hr>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="thumbnail"><img alt="Express Checkout Basic"
                                                src="assets/images/paypal-express-checkout-with-line-items.jpg">
                        <div class="caption">
                            <h3>PayPal Checkout</h3>
                            <h4>w/ Line Items</h4>
                            <p>Learn how to integrate PayPal Checkout w/ Line Items using PayPal's REST API v2.
                                It allows you to create and capture an order using the Orders API.</p>
                            <?php
                            $DIR = '\rest\rest-checkout-line-items-v2';
                            $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                            ?>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/rest-checkout-line-items-v2" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-rest-checkout-line-items-php-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail"><img alt="Express Checkout Smart Payment Buttons Client Side"
                                                src="assets/images/paypal-express-checkout-jsv4-using-rest-client-side.jpg">
                        <?php
                        $DIR = '\rest\checkout-paypal-javascript-sdk-v2-client-side';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>PayPal Checkout Smart Payment Buttons Client Side </h3>
                            <p>Learn how to integrate PayPal Checkout using PayPal's javascript SDK. This code runs client side and
                                always keeps you current with the latest button styles and payment features.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/checkout-paypal-javascript-sdk-v2-client-side" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-checkout-php-smart-payment-buttons-client-side-demo-kit-v2?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail"><img alt="Express Checkout Smart Payment Buttons Server Side"
                                                src="assets/images/paypal-express-checkout-jsv4-using-rest-server-side.jpg">
                        <?php
                        $DIR = '\rest\checkout-paypal-javascript-sdk-v2-server-side-rest';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>PayPal Checkout Smart Payment Buttons Server Side </h3>
                            <p>Learn how to integrate PayPal Checkout using PayPal's javascript SDK. This code runs server side by making an ajax call and
                                always keeps you current with the latest button styles and payment features.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/checkout-paypal-javascript-sdk-v2-server-side-rest    " target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-checkout-php-smart-payment-buttons-server-side-demo-kit-v2?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <hr>

            <div class="row">
                <div class="col-md-12">
                    <h3>API v1</h3>
                    <hr>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="thumbnail"><img alt="Express Checkout Basic"
                                                src="assets/images/paypal-express-checkout-with-line-items.jpg">
                        <div class="caption">
                            <h3>PayPal Checkout</h3>
                            <h4>w/ Line Items</h4>
                            <p>Learn how to integrate PayPal Checkout w/ Line Items using PayPal's REST API. It allows you to create and execute payments using the REST API.</p>
                            <?php
                            $DIR = '\rest\rest-checkout-line-items-v1';
                            $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                            ?>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/rest-checkout-line-items-v1" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-rest-checkout-line-items-php-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail"><img alt="Express Checkout Smart Payment Buttons Client Side"
                                                src="assets/images/paypal-express-checkout-jsv4-using-rest-client-side.jpg">
                        <?php
                        $DIR = '\rest\express-checkout-js-using-rest-v1-client-side';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Checkout Smart Payment Buttons Client Side</h3>
                            <p>Learn how to integrate PayPal Checkout using the checkout.js JavaScript code. This code
                                always keeps you current with the latest button styles and payment features.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/express-checkout-js-using-rest-v1-client-side" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-checkout-php-smart-payment-buttons-client-side-demo-kit-v1?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail"><img alt="Express Checkout Smart Payment Buttons Server Side"
                                                src="assets/images/paypal-express-checkout-jsv4-using-rest-server-side.jpg">
                        <?php
                        $DIR = '\rest\express-checkout-js-using-rest-v1-server-side';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>PayPal Checkout Smart Payment Buttons Server Side</h3>
                            <p>Learn how to integrate PayPal Checkout using the checkout.js JavaScript code. This code
                                always keeps you current with the latest button styles and payment features.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/express-checkout-js-using-rest-v1-server-side" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-checkout-php-smart-payment-buttons-server-side-demo-kit-v1?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
            <div class="row">

                <div class="col-md-4">
                    <div class="thumbnail"><img alt="Express Checkout Smart Payment Buttons Server Side"
                                                src="assets/images/paypal-express-checkout-billing-agreement.jpg">
                        <?php
                        $DIR = '\rest\create-billing-agreement-using-paypal-rest-api';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>PayPal Checkout Billing Agreement</h3>
                            <h4>Payments API</h4>
                            <p>Learn how to Use billing plans and billing agreements to create an agreement for a recurring PayPal payments for goods or services.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/create-billing-agreement-using-paypal-rest-api" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-express-checkout-php-billing-agreement-rest-php-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="thumbnail"><img alt="PayPal Identity (Log In with PayPal)" src="assets/images/login-with-paypal.png">
                        <?php
                        $DIR = '\rest\login-with-paypal-basic';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3> PayPal Identity </h3>
                            <h4> Log In with PayPal - Basic Scope </h4>
                            <p> Log In with PayPal (formerly PayPal Access) is a commerce identity solution that enables
                                your customers to sign in to your web site quickly and securely by using their PayPal
                                login credentials.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/login-with-paypal-basic" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-identity-paypal-login-php-basic-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail"><img alt="PayPal Identity + Grant API Permissions" src="assets/images/login-with-paypal.png">
                        <?php
                        $DIR = '\rest\login-with-paypal-permissions';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3> PayPal Identity </h3>
                            <h4> Grant API Permissions</h4>
                            <p> Log In with PayPal (formerly PayPal Access) is a commerce identity solution that enables
                                your customers to sign in to your web site quickly and securely by using their PayPal
                                login credentials.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/login-with-paypal-permissions" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-identity-paypal-login-php-grant-api-permissions-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="thumbnail"><img alt="Express Checkout Smart Payment Buttons Server Side"
                                                src="assets/images/paypal-dodirectpayment-credit-cart-checkout.jpg">
                        <?php
                        $DIR = '\rest\create-payment-using-credit-card-rest-api';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3> Create Payment </h3>
                            <h4> Direct Credit Card </h4>
                            <p>Learn how to create a payment using direct credit card processing.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/create-payment-using-credit-card-rest-api/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-create-payment-using-credit-rest-php-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="thumbnail"><img alt="Store CreditCard in PayPal Vault"
                                                src="assets/images/paypal-store-creditcard-in-paypal-vault.jpg">
                        <?php
                        $DIR = '\rest\store-credit-card-in-paypal-vault';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3> PayPal Vault </h3>
                            <h4> Save Credit Card on File </h4>
                            <p>Learn how to use the PayPal Vault API to securely store customer credit cards rather than on your server. </p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/store-credit-card-in-paypal-vault/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-store-credit-card-in-vault-rest-php-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="thumbnail"><img alt="Create Payment Using Saved Card - Vault"
                                                src="assets/images/paypal-create-payment-using-savedcard-vault.jpg">
                        <?php
                        $DIR = '\rest\create-payment-using-saved-card';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3> Token Payment (Reference Transaction) </h3>
                            <h4> Pay with Vaulted Card </h4>
                            <p>Learn how to process a payment using a saved (vaulted) card in PayPal.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/create-payment-using-saved-card/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-credit-card-vault-token-payment-reference-transaction-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="thumbnail"><img alt="Create & Send Third Party Invoice PayPal"
                                                src="assets/images/paypal-create-send-third-party-invoice.jpg">
                        <div class="caption">
                            <h3>Create & Send Invoice</h3>
                            <h4>3rd Party User</h4>
                            <p>Learn how to create and send a PayPal Invoice on behalf of a third party user.</p>
                            <?php
                            $DIR = '\rest\create-and-send-third-party-invoice-paypal';
                            $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                            ?>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="rest/create-and-send-third-party-invoice-paypal/" target="_blank">Launch Demo</a>
                                <?php else: ?>
                                    <a class="btn btn-success"
                                       href="https://www.angelleye.com/product/paypal-create-and-send-third-party-invoice-paypal-demo-kit?utm_source=ae_paypal_php_sdk&utm_medium=demo_homepage&utm_campaign=demo_kits"
                                       target="_blank">Buy Now</a>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <h1>Classic API</h1>
                    <h3>Important Note</h3>
                    The classic API is still available, but is being labeled as deprecated.  You can still use them, and they work very well, but if you are starting fresh you may want to look at REST instead.
                    <hr>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="thumbnail"><img alt="PayPal Express Checkout Basic Integration"
                                                src="assets/images/paypal-express-checkout.jpg">
                        <div class="caption">
                            <h3>Express Checkout</h3>
                            <h4>Basic</h4>
                            <p>Here we are integrating Express Checkout without any line item details or any extra
                                features. We obtain the user's shipping information so that we can calculate shipping
                                and tax, but otherwise no additional data is included with this checkout demo.</p>
                            <p align="center"><a class="btn btn-primary" href="classic/express-checkout-basic/" target="_blank">Launch Demo</a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="thumbnail"><img alt="PayPal Express Checkout Line Items Integration"
                                                src="assets/images/paypal-express-checkout-with-line-items.jpg">
                        <?php
                        $DIR = '\classic\express-checkout-line-items';
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
                                    <a class="btn btn-primary" href="classic/express-checkout-line-items/" target="_blank">Launch Demo</a>
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
                    <div class="thumbnail"><img alt="PayPal Express Checkout Digital Goods Integration"
                                                src="assets/images/paypal-express-checkout-digital-goods.jpg">
                        <?php
                        $DIR = '\classic\express-checkout-digital-goods';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout</h3>
                            <h4>Digital Goods</h4>
                            <p>Learn how to implement digital goods (micro-processing rates) into PayPal Express
                                Checkout.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="classic/express-checkout-digital-goods/" target="_blank">Launch Demo</a>
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
                    <div class="thumbnail"><img alt="Express Checkout Billing Agreement"
                                                src="assets/images/paypal-express-checkout-billing-agreement.jpg">
                        <?php
                        $DIR = '\classic\express-checkout-billing-agreement';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout Billing Agreement</h3>
                            <h4>Billing Agreement</h4>
                            <p>Learn how to implement Billing Agreement into PayPal Express Checkout.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="classic/express-checkout-billing-agreement/" target="_blank">Launch Demo</a>
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
                    <div class="thumbnail"><img alt="Express Checkout Parallel Payments"
                                                src="assets/images/paypal-express-parrallel-payments.jpg">
                        <?php
                        $DIR = '\classic\express-checkout-parallel-payments';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout Parallel Payments</h3>
                            <h4>Parallel Payments</h4>
                            <p>Learn how to implement Parallel Payments into PayPal Express Checkout.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="classic/express-checkout-parallel-payments/" target="_blank">Launch Demo</a>
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
                    <div class="thumbnail"><img alt="Express checkout 3rd Party No Permissions"
                                                src="assets/images/paypal-express-3rd-party-no-permissions-required.jpg">
                        <?php
                        $DIR = '\classic\express-checkout-3rd-party-no-permissions';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout</h3>
                            <h4>3rd Party No Permissions</h4>
                            <p>Learn how to integrate Express Checkout on behalf of a 3rd party with no API permissions required.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="classic/express-checkout-3rd-party-no-permissions/" target="_blank">Launch Demo</a>
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
                    <div class="thumbnail"><img alt="Express Checkout In-Context"
                                                src="assets/images/paypal-express-checkout-in-context.jpg">
                        <?php
                        $DIR = '\classic\express-checkout-incontext';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout</h3>
                            <h4>In-Context</h4>
                            <p>Learn how to implement Express Checkout In-Context so that users are not redirected away
                                from your site for payment.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="classic/express-checkout-incontext/" target="_blank">Launch Demo</a>
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
                    <div class="thumbnail"><img alt="Express Checkout Recurring Payment"
                                                src="assets/images/express-checkout-recurring-payments.jpg">
                        <?php
                        $DIR = '\classic\express-checkout-recurring-payments';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3>Express Checkout</h3>
                            <h4>Recurring Payments</h4>
                            <p>Learn how to create subscription profiles using the Recurring Payments API.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="classic/express-checkout-recurring-payments/" target="_blank">Launch Demo</a>
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
                    <div class="thumbnail"><img alt="Express Checkout Shipped Items + Recurring Payment"
                                                src="assets/images/express-checkout-shipped-items-recurring-payments.jpg">
                        <?php
                        $DIR = '\classic\express-checkout-shipped-items-recurring-payments';
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
                                       href="classic/express-checkout-shipped-items-recurring-payments/" target="_blank">Launch Demo</a>
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
                    <div class="thumbnail"><img alt="PayPal Payments Pro PayFlow Basic Integration"
                                                src="assets/images/paypal-payments-pro-payflow.jpg">
                        <?php
                        $DIR = '\classic\payflow-credit-card-checkout-basic';
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
                                    <a class="btn btn-primary" href="classic/payflow-credit-card-checkout-basic/" target="_blank">Launch Demo</a>
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
                    <div class="thumbnail"><img alt="PayPal Payments Pro PayFlow Recurring Billing Integration"
                                                src="assets/images/paypal-payments-pro-payflow-recurring-billing.jpg">
                        <?php
                        $DIR = '\classic\payflow-credit-card-checkout-recurring-billing';
                        $DIR_exists = (is_dir(__DIR__ . $DIR)) ? true : false;
                        ?>
                        <div class="caption">
                            <h3> Payments Pro 2.0 </h3>
                            <h4> PayFlow Recurring Billing </h4>
                            <p>Here we use the PayFlow gateway to setup recurring billing / subscription profiles. This
                                is done using a credit card directly just like the Payments Pro basic demos.</p>
                            <p align="center">
                                <?php if ($DIR_exists) : ?>
                                    <a class="btn btn-primary" href="classic/payflow-credit-card-checkout-recurring-billing/" target="_blank">Launch Demo</a>
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
                        <img alt="Website Payments Pro 3.0 - DoDirectPayment"
                             src="assets/images/paypal-dodirectpayment-credit-cart-checkout.jpg"/>
                        <?php
                        $DIR = '\classic\website-payments-pro-30-basic';
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
                                    <a class="btn btn-primary" href="classic/website-payments-pro-30-basic" target="_blank">Launch Demo</a>
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