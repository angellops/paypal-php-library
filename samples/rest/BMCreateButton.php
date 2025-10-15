<?php
// Include required library files.
require_once('../../includes/config.php');
require_once('../../autoload.php');

// Create PayPal object.
$PayPalConfig = array(
    'Sandbox' => $sandbox,
    'PayPalAPIMode' => $api_mode,
    'ClientID' => $rest_client_id,
    'ClientSecret' => $rest_client_secret,
    'PrintHeaders' => $print_headers, 
    'LogResults' => $log_results,
    'LogPath' => $log_path,
);

$PayPal = angelleye\PayPal\PayPal::init($PayPalConfig);

// Prepare request arrays
$BMCreateButtonFields = array(
    'buttoncode' => 'CLEARTEXT', 	// The kind of button code to create.  It is one of the following values:  HOSTED, ENCRYPTED, CLEARTEXT, TOKEN
    'buttontype' => 'CART',			// Required.  The kind of button you want to create.  It is one of the following values:  BUYNOW, CART, GIFTCERTIFICATE, SUBSCRIBE, DONATE, UNSUBSCRIBE, VIEWCART, PAYMENTPLAN, AUTOBILLING, PAYMENT
    'buttonsubtype' => '',          // The use of button you want to create.  Values are:  PRODUCTS, SERVICES
);

/**
 * You may pass in any variables from the Standard Payments list.
 *
 * Depending on the type of button you are creating some variables will be required.
 * Refer to the HTML Standard Variable reference for more details on variables for specific button types.
 *
 * https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/
 */
$BMButtonVars = array(
    'notify_url' => '',                         // The URL to which PayPal posts information about the payment. in the form of an IPN message.
    'amount' => '',                             // The price or amount of the product, service, or contribution, not including shipping, handling, or tax.  If this variable is omitted from Buy Now or Donate buttons, buyers enter their own amount at the time of the payment.
    'discount_amount' => '',                    // Discount amount associated with an item.  Must be less than the selling price of the item.  Valid only for Buy Now and Add to Cart buttons.
    'discount_amount2' => '',                   // Discount amount associated with each additional quantity of the item.  Must be equal to or less than the selling price of the item.
    'discount_rate' => '',                      // Discount rate (percentage) associated with an item.  Must be set to a value less than 100.
    'discount_rate2' => '',                     // Discount rate (percentage) associated with each additional quantity of the item.  Must be equal to or less than 100.
    'discount_num' => '',                       // Number of additional quantities of the item to which the discount applies.
    'item_name' => 'Test Widget',               // Description of the item.  If this is omitted, buyers enter their own name during checkout.
    'item_number' => '',                        // Pass-through variable for you to track product or service purchased or the contribution made.
    'quantity' => '',                           // Number of items.
    'shipping' => '',                           // The cost of shipping this item.
    'shipping2' => '',                          // The cost of shipping each additional unit of this item.
    'handling' => '',                           // handling charges.  This variable is not quantity-specific.
    'tax' => '',                                // Transaction-based tax override variable.  Set this variable to a flat tax amount to apply to the payment regardless of the buyer's location.  This overrides any tax settings in the account profile.
    'tax_rate' => '',                           // Transaction-based tax override variable.  Set this variable to a percentage that applies to the amount multipled by the quantity selected uring checkout.  This overrides your paypal account profile.
    'undefined_quantity' =>'',                  // Set to 1 to allow the buyer to specify the quantity.
    'weight' => '',                             // Weight of items.
    'weight_unit' => '',                        // The unit of measure if weight is specified.  Values are:  lbs, kgs
    'address_override' => '',                   // Set to 1 to override the payer's address stored in their PayPal account.
    'currency_code' => '',                      // The currency of the payment.  https://developer.paypal.com/docs/classic/api/currency_codes/#id09A6G0U0GYK
    'custom' => '',                             // Pass-through variable for your own tracking purposes, which buyers do not see.
    'invoice' => '',                            // Pass-through variable you can use to identify your invoice number for the purchase.
    'tax_cart' => '',                           // Cart-wide tax, overriding any individual item tax_ value
    'handling_cart' => '',                      // Single handling fee charged cart-wide.
    'weight_cart' => '',                        // If profile-based shipping rates are configured with a basis of weight, PayPal uses this value to calculate the shipping charges for the payment.  This value overrides the weight values of individual items.
    'add' => '',                                // Set to 1 to add an item to the PayPal shopping cart.
    'display' => '',                            // Set to 1 to display the contents of the PayPal shopping cart to the buyer.
    'upload' => '',                             // Set to 1 to upload the contents of a third-party shopping cart or a custom shopping cart.
    'business' => 'sandbo_1215254764_biz@angelleye.com',                           // Your PayPal ID or an email address associated with your PayPal account.  Email addresses must be confirmed.
    'paymentaction' => '',                      // Indicates whether the payment is a finale sale or an authorization for a final sale, to be captured later.  Values are:  sale, authorization, order
    'shopping_url' => '',                       // The URL of the page on the merchant website that buyers go to when they click the Continue Shopping button on the PayPal shopping cart page.
    'a1' => '',                                 // Trial period 1 price.  For a free trial period, specify 0.
    'p1' => '',                                 // Trial period 1 duration.  Required if you specify a1.
    't1' => '',                                 // Trial period 1 units of duration.  Values are:  D, W, M, Y
    'a2' => '',                                 // Trial period 2 price.  Can be specified only if you also specify a1.
    'p2' => '',                                 // Trial period 2 duration.
    't2' => '',                                 // Trial period 2 units of duration.
    'a3' => '',                                 // Regular subscription price.
    'p3' => '',                                 // Regular subscription duration.
    't3' => '',                                 // Regular subscription units of duration.
    'src' => '',                                // Recurring payments.  Subscription payments recur unless subscribers cancel.  Values are:  1, 0
    'sra' => '',                                // Reattempt on failure.  If a recurring payment fails, PayPal attempts to collect the payment two more times before canceling.  Values are:  1, 0
    'no_note' => '',                            // Set to 1 to disable prompts for buyers to include a note with their payments.
    'modify' => '',                             // Modification behavior.  0 - allows subscribers only to sign up for new subscriptions.  1 - allows subscribers to sign up for new subscriptions and modify their current subscriptions.  2 - allows subscribers to modify only their current subscriptions.
    'usr_manage' => '',                         // Set to 1 to have PayPal generate usernames and passwords for subscribers.  https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/subscribe_buttons/#id08ADFB00QWS
    'max_text' => '',                           // A description of the automatic billing plan.
    'set_customer_limit' => '',                 // Specify whether to let buyers enter maximum billing limits in a text box or choose from a list of max billing limits that you specify.  Values are:  max_limit_own, max_limit_defined
    'min_amount' => '',                         // The minimum monthly billing limit, if you have one.
    'disp_tot' => '',                           // Display the total payment amount to buyers during checkout.  Values are:  Y, N
    'page_style' => '',                         // The custom payment page style for checkout pages.  Values are:  paypal, primary, page_style_name
    'image_url' => '',                          // The URL of the 150x50 image displayed as your logo on the PayPal checkout pages.
    'cpp_cart_border_color' => '',              // The HTML hex code for your principal identifying color.  PayPal blends your color to white on the checkout pages.
    'cpp_header_image' => '',                   // The image at the top, left of the checkout page.  Max size is 750x90.
    'cpp_headerback_color' => '',               // The background color for the header of the checkout page.
    'cpp_headerborder_color' => '',             // The border color around the header of the checkout page.
    'cpp_logo_image' => '',                     // A URL to your logo image.  Must be .gif, .jpg, or .png.  190x60
    'cpp_payflow_color' => '',                  // The background color for the checkout page below the header.
    'lc' => '',                                 // The locale of the login or sign-up page.
    'cn' => '',                                 // Label that appears above the note field.
    'no_shipping' => '',                        // Do not prompt buyers for a shipping address.  Values are:  0 - prompt for an address but do not require.  1 - do not prompt.  2 - prompt and require address.
    'return' => '',                             // The URL to which PayPal redirects buyers' browsers after they complete their payment.
    'rm' => '',                                 // Return method.  Values are:  0 - all shopping cart payments use GET method.  1 - buyer's browser is redirected using the GET method. 2 - buyer's browser is redirected using POST.
    'cbt' => '',                                // Sets the text for the Return to Merchant button on the PayPal completed payment page.
    'cancel_return' => '',                      // A URL to which PayPal redirects buyers if they cancel the payment.
    'address1' => '',
    'address2' => '',
    'city' => '',
    'state' => '',
    'zip' => '',
    'country' => '',
    'email' => '',
    'first_name' => '',
    'last_name' => '',
    'charset' => '',                            // Sets the character set and character encoding for the billing login page.
    'night_phone_a' => '',                      // Area code for US phone numbers or country code for phone numbers outside the US.
    'night_phone_b' => '',                      // 3 digit prefix for US numbers or the entire phone number for numbers outside the US.
    'night_phone_c' => '',                      // 4 digit phone number for US numbers.
);

/**
 * Button options are handled similar to order items in other calls within this library.
 * You will need to setup a nested array of options and option selections.
 *
 * You could end up with multiple $BMButtonOption arrays here, and each of those could
 * contain multiple $BMButtonOptionSelection arrays within it.  All are then passed into
 * the final $BMButtonOptions array that gets passed to the API.
 *
 * This sample includes 2 options.  The Color option, which contains 3 selections (with pricing),
 * and the Size option, which contains 2 selections (without pricing).
 */
$BMButtonOptions = array();

$BMButtonOptionSelections = array();
$BMButtonOptionSelection = array(
    'value' => 'Blue',
    'price' => '10.00',
    'type' => ''
);
array_push($BMButtonOptionSelections, $BMButtonOptionSelection);

$BMButtonOptionSelection = array(
    'value' => 'Red',
    'price' => '8.00',
    'type' => ''
);
array_push($BMButtonOptionSelections, $BMButtonOptionSelection);

$BMButtonOptionSelection = array(
    'value' => 'Green',
    'price' => '12.00',
    'type' => ''
);
array_push($BMButtonOptionSelections, $BMButtonOptionSelection);

$BMButtonOption = array(
    'name' => 'Color',
    'selections' => $BMButtonOptionSelections
);
array_push($BMButtonOptions, $BMButtonOption);


$BMButtonOptionSelections = array();
$BMButtonOptionSelection = array(
    'value' => 'Small',
    'price' => '',
    'type' => ''
);
array_push($BMButtonOptionSelections, $BMButtonOptionSelection);

$BMButtonOptionSelection = array(
    'value' => 'Large',
    'price' => '',
    'type' => ''
);
array_push($BMButtonOptionSelections, $BMButtonOptionSelection);

$BMButtonOption = array(
    'name' => 'Size',
    'selections' => $BMButtonOptionSelections
);
array_push($BMButtonOptions, $BMButtonOption);

$PayPalRequestData = array(
    'BMCreateButtonFields' => $BMCreateButtonFields,
    'BMButtonVars' => $BMButtonVars,
    'BMButtonOptions' => $BMButtonOptions
);

$PayPalResult = $PayPal->BMCreateButton($PayPalRequestData);

// Write the contents of the response array to the screen for demo purposes.
echo '<pre />';
print_r($PayPalResult);