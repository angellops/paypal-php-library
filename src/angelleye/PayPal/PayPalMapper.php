<?php

namespace angelleye\PayPal;

/**
 * PayPal REST API Class
 * Extends the main PayPal class for consistency and shared functionality
 */
class PayPalMapper extends PayPal
{
    public $rest;
    protected string $requiredMode = 'classic';

    public function __construct($config)
    {
        // Call parent constructor first
        parent::__construct($config);

        // Initialize REST Class
        require_once __DIR__ . '/PayPalREST.php';
        $this->rest = new PayPalREST($config);
    }

    /**
     * Convert REST API error response into Classic NVP-style errors
     *
     * @param array $response REST API response array
     * @return array Returns an array with 'flat' and 'list' keys containing errors
     */
    function convertRESTErrorsToNVP($response) {
        $flatErrors = [];
        $errorsList = [];

        // Get the raw error details if available
        $rawErrors = !empty($response['errors']['details']) ? $response['errors']['details'] : [];

        // If no details but top-level message exists (like 403 Forbidden)
        if (empty($rawErrors) && isset($response['errors']['message'])) {
            $rawErrors[] = [
                'issue'       => !empty($response['errors']['name']) ? $response['errors']['name'] : 'ERROR',
                'description' => $response['errors']['message']
            ];
        }

        // Loop through each error and build NVP structure
        foreach ($rawErrors as $i => $error) {
            $code = $error['issue'] ?? '99999';
            $msg  = $error['description'] ?? 'No description provided';

            // Flattened keys
            $flatErrors["L_ERRORCODE{$i}"]    = $code;
            $flatErrors["L_SHORTMESSAGE{$i}"] = 'REST_API_ERROR';
            $flatErrors["L_LONGMESSAGE{$i}"]  = $msg;
            $flatErrors["L_SEVERITYCODE{$i}"] = 'Error';

            // Nested errors list
            $errorsList[] = [
                'L_ERRORCODE'    => $code,
                'L_SHORTMESSAGE' => 'REST_API_ERROR',
                'L_LONGMESSAGE'  => $msg,
                'L_SEVERITYCODE' => 'Error'
            ];
        }

        return [
            'flatErrors' => $flatErrors,
            'errorsList' => $errorsList
        ];
    }

    /**
     * Map the getBalances response to the Classic NVP format
     *
     * @return array
     */
    public function GetBalanceMapper()
    {
        // Call the REST method to get balances
        $response = $this->rest->getBalances();

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $body = $response['full_response'];

            $balances = [];
            $flatBalances = [];

            if (!empty($body['balances'])) {
                $balData = $body['balances'];
                usort($balData, function($a, $b) {
                    $aPrimary = (isset($a['primary']) && $a['primary'] == 1) ? 1 : 0;
                    $bPrimary = (isset($b['primary']) && $b['primary'] == 1) ? 1 : 0;
                    
                    return $bPrimary <=> $aPrimary;
                });

                foreach ($balData as $i => $bal) {
                    $amount = $bal['total_balance']['value'];
                    $currency = $bal['currency'];

                    $balances[] = [
                        'L_AMT' => $amount,
                        'L_CURRENCYCODE' => $currency,
                    ];

                    $flatBalances["L_AMT{$i}"] = $amount;
                    $flatBalances["L_CURRENCYCODE{$i}"] = $currency;
                }
            }

            $result = array_merge(
                $headers,
                $flatBalances,
                [
                    'ERRORS'         => array(),
                    'BALANCERESULTS' => $balances,
                    'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
                ]
            );

            return $result;
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'BALANCERESULTS' => array(),
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }

    /**
     * Calls the PayPal REST API to retrieve the authenticated user's account
     * information and maps the response into a Classic NVP-style format
     * compatible with the GetPalDetails response structure.
     * 
     * @return array
     */
    public function GetPalDetailsMapper()
    {
        // Call the REST method to get balances
        $response = $this->rest->getPayPalUserInfo();

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $body = $response['full_response'];
            $email = '';
            if (!empty($body['emails'])) {
                foreach ($body['emails'] as $emailData) {
                    if (!empty($emailData['confirmed']) && $emailData['confirmed'] == 1) {
                        $email = $emailData['value'];
                        break;
                    }
                }
            }

            $result = array_merge(
                $headers,
                [
                    'PAL'           => !empty($body['payer_id']) ? $body['payer_id'] : '',
                    'LOCALE'        => 'en_US',
                    'PALNAME'       => !empty($body['name']) ? $body['name'] : '',
                    'PALEMAIL'      => $email,
                    'ERRORS'        => array(),
                    'RAWRESPONSE'   => isset($response['raw_response']) ? $response['raw_response'] : [],
                ]
            );

            return $result;
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'BALANCERESULTS' => array(),
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }

    /**
     * Maps Classic SetExpressCheckout data array to a PayPal REST order payload
     * and creates the order using the REST API
     * 
     * @return array
     */
    public function SetExpressCheckoutMapper($DataArray)
    {   
        $SECFields = isset($DataArray['SECFields']) ? $DataArray['SECFields'] : [];
        $paymentsList = isset($DataArray['Payments']) ? $DataArray['Payments'] : [];
        $payerData = isset($DataArray['PayerData']) ? $DataArray['PayerData'] : [];

        $purchase_units = [];

        foreach ($paymentsList as $index => $payments) {
            $items = isset($payments['order_items']) ? $payments['order_items'] : [];
            $purchase_items = [];
            $item_total = 0;

            foreach ($items as $it) {
                $qty = isset($it['qty']) ? (int)$it['qty'] : 1;
                $amt = isset($it['amt']) ? (float)$it['amt'] : 0;

                $purchase_items[] = [
                    "name" => isset($it['name']) ? $it['name'] : "",
                    "description" => isset($it['desc']) ? $it['desc'] : "",
                    "quantity" => (string)$qty,
                    "unit_amount" => [
                        "currency_code" => isset($payments['currencycode']) ? $payments['currencycode'] : "USD",
                        "value" => number_format($amt, 2, '.', '')
                    ],
                    "category" =>
                        isset($it['itemcategory'])
                            ? (strtolower($it['itemcategory']) === 'digital'
                                ? 'DIGITAL_GOODS'
                                : (strtolower($it['itemcategory']) === 'physical'
                                    ? 'PHYSICAL_GOODS'
                                    : $it['itemcategory']
                                )
                            )
                            : ""
                ];
                $item_total += ($amt * $qty);
            }

            $currency = isset($payments['currencycode']) ? $payments['currencycode'] : "USD";

            $amount = [
                "currency_code" => $currency,
                "value" => number_format(isset($payments['amt']) ? $payments['amt'] : $item_total, 2, '.', ''),
                "breakdown" => [
                    "item_total" => [
                        "currency_code" => $currency,
                        "value" => number_format($item_total, 2, '.', '')
                    ],
                    "shipping" => [
                        "currency_code" => $currency,
                        "value" => number_format(isset($payments['shippingamt']) ? $payments['shippingamt'] : 0, 2, '.', '')
                    ],
                    "handling" => [
                        "currency_code" => $currency,
                        "value" => number_format(isset($payments['handlingamt']) ? $payments['handlingamt'] : 0, 2, '.', '')
                    ],
                    "tax_total" => [
                        "currency_code" => $currency,
                        "value" => number_format(isset($payments['taxamt']) ? $payments['taxamt'] : 0, 2, '.', '')
                    ]
                ]
            ];

            $purchase_unit = [
                "amount" => $amount,
                "description" => isset($payments['desc']) ? $payments['desc'] : "",
                "items" => $purchase_items
            ];

            if( !empty($payments['paymentrequestid']) ) {
                $purchase_unit['reference_id'] = $payments['paymentrequestid'];
            }

            if (isset($payments['sellerpaypalaccountid']) && !empty($payments['sellerpaypalaccountid'])) {
                $purchase_unit['payee'] = [
                    'merchant_id' => $payments['sellerpaypalaccountid']
                ];
            }

            $purchase_units[] = $purchase_unit;
        }

        $payload = [
            "intent" => "CAPTURE",
            "purchase_units" => $purchase_units,
            "payment_method" => [
                "payer_selected" => "PAYPAL",
            ],
            "application_context" => [
                "return_url" => isset($SECFields['returnurl']) ? $SECFields['returnurl'] : "",
                "cancel_url" => isset($SECFields['cancelurl']) ? $SECFields['cancelurl'] : "",
                "brand_name" => isset($SECFields['brandname']) ? $SECFields['brandname'] : "",
            ]
        ];

        // If we are skipping details, we need to set the shipping preference and user action
        if( isset($SECFields['skipdetails']) && $SECFields['skipdetails'] ) {
            $payload['application_context']['user_action'] = 'PAY_NOW';
        }

        if( !empty($payerData) && !empty($payerData['buyeremail']) ) {
            $payload['payer'] = [
                "email_address" => $payerData['buyeremail']
            ];
        }

        // Call the REST method to createOrder
        $response = $this->rest->createOrder($payload);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $result = array_merge(
                $headers,
                [
                    'TOKEN'         => isset($response['order_id']) ? $response['order_id'] : '',
                    'REDIRECTURL'   => isset($response['approval_url']) ? $response['approval_url'] : '',
                    'ERRORS'        => array(),
                    'RAWRESPONSE'   => isset($response['raw_response']) ? $response['raw_response'] : [],
                ]
            );

            return $result;
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }

    /**
     * Maps a PayPal REST Get Order response to Classic NVP-style
     * GetExpressCheckoutDetails response structure.
     * 
     * @return array
     */
    public function GetExpressCheckoutDetailsMapper($Token)
    {   
        // Call the REST method to getOrder
        $response = $this->rest->getOrder($Token);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $order = $response['order'];
            $nvp = [];

            // Basic Order / Payer Info
            $nvp['TOKEN'] = !empty($order['id']) ? $order['id'] : '';
            if (!empty($order['payer'])) {
                $payer = $order['payer'];

                $nvp['EMAIL'] = !empty($payer['email_address']) ? $payer['email_address'] : '';
                $nvp['PAYERID'] = !empty($payer['payer_id']) ? $payer['payer_id'] : '';
                $nvp['FIRSTNAME'] = !empty($payer['name']['given_name']) ? $payer['name']['given_name'] : '';
                $nvp['LASTNAME'] = !empty($payer['name']['surname']) ? $payer['name']['surname'] : '';
                $nvp['COUNTRYCODE'] = !empty($payer['address']['country_code']) ? $payer['address']['country_code'] : '';
                $nvp['ADDRESSSTATUS'] = '';

                if (!empty($order['payment_source']['paypal']['account_status'])) {
                    $nvp['PAYERSTATUS'] = strtolower($order['payment_source']['paypal']['account_status']);
                }
            }

            // Purchase Units Loop
            if (!empty($order['purchase_units'])) {
                foreach ($order['purchase_units'] as $pIndex => $unit) {
                    $amount = !empty($unit['amount']) ? $unit['amount'] : [];
                    $breakdown = !empty($amount['breakdown']) ? $amount['breakdown'] : [];
                    $shipping = !empty($unit['shipping']) ? $unit['shipping'] : [];
                    $address = !empty($shipping['address']) ? $shipping['address'] : [];

                    // Classic top-level payment fields (from first purchase_unit)
                    if ($pIndex === 0) {
                        $nvp['CURRENCYCODE'] = !empty($amount['currency_code']) ? $amount['currency_code'] : '';
                        $nvp['AMT'] = !empty($amount['value']) ? $amount['value'] : '';
                        $nvp['ITEMAMT'] = !empty($breakdown['item_total']['value']) ? $breakdown['item_total']['value'] : '0.00';
                        $nvp['SHIPPINGAMT'] = !empty($breakdown['shipping']['value']) ? $breakdown['shipping']['value'] : '0.00';
                        $nvp['HANDLINGAMT'] = !empty($breakdown['handling']['value']) ? $breakdown['handling']['value'] : '0.00';
                        $nvp['TAXAMT'] = !empty($breakdown['tax_total']['value']) ? $breakdown['tax_total']['value'] : '0.00';
                        $nvp['DESC'] = !empty($unit['description']) ? $unit['description'] : '';
                        $nvp['INSURANCEAMT'] = '0.00';
                        $nvp['SHIPDISCAMT'] = '0.00';
                        $nvp['NOTETEXT'] = '';
                        $nvp['INSURANCEOPTIONOFFERED'] = 'false';
                    }

                    // PAYMENTREQUEST_n
                    $nvp["PAYMENTREQUEST_{$pIndex}_CURRENCYCODE"] = !empty($amount['currency_code']) ? $amount['currency_code'] : '';
                    $nvp["PAYMENTREQUEST_{$pIndex}_AMT"] = !empty($amount['value']) ? $amount['value'] : '';
                    $nvp["PAYMENTREQUEST_{$pIndex}_ITEMAMT"] = !empty($breakdown['item_total']['value']) ? $breakdown['item_total']['value'] : '0.00';
                    $nvp["PAYMENTREQUEST_{$pIndex}_SHIPPINGAMT"] = !empty($breakdown['shipping']['value']) ? $breakdown['shipping']['value'] : '0.00';
                    $nvp["PAYMENTREQUEST_{$pIndex}_HANDLINGAMT"] = !empty($breakdown['handling']['value']) ? $breakdown['handling']['value'] : '0.00';
                    $nvp["PAYMENTREQUEST_{$pIndex}_TAXAMT"] = !empty($breakdown['tax_total']['value']) ? $breakdown['tax_total']['value'] : '0.00';
                    $nvp["PAYMENTREQUEST_{$pIndex}_DESC"] = !empty($unit['description']) ? $unit['description'] : '';
                    $nvp["PAYMENTREQUEST_{$pIndex}_INSURANCEAMT"] = '0.00';
                    $nvp["PAYMENTREQUEST_{$pIndex}_SHIPDISCAMT"] = '0.00';
                    $nvp["PAYMENTREQUEST_{$pIndex}_NOTETEXT"] = '';
                    $nvp["PAYMENTREQUEST_{$pIndex}_SELLERPAYPALACCOUNTID"] = !empty($unit['payee']['email_address']) ? $unit['payee']['email_address'] : '';
                    $nvp["PAYMENTREQUEST_{$pIndex}_INSURANCEOPTIONOFFERED"] = 'false';

                    // ORDERITEMS (Classic structure)
                    if (!empty($unit['items'])) {
                        foreach ($unit['items'] as $i => $item) {
                            $nvp['ORDERITEMS'][$i] = [
                                'L_NAME' => !empty($item['name']) ? $item['name'] : '',
                                'L_DESC' => !empty($item['description']) ? $item['description'] : '',
                                'L_NUMBER' => !empty($item['name']) ? preg_replace('/\D/', '', $item['name']) : '',
                                'L_QTY' => !empty($item['quantity']) ? $item['quantity'] : 1,
                                'L_AMT' => !empty($item['unit_amount']['value']) ? $item['unit_amount']['value'] : '0.00',
                                'L_OPTIONSNAME' => '',
                                'L_OPTIONSVALUE' => '',
                                'L_ITEMWEIGHTVALUE' => '',
                                'L_ITEMWEIGHTUNIT' => '',
                                'L_ITEMWIDTHVALUE' => '',
                                'L_ITEMWIDTHUNIT' => '',
                                'L_ITEMLENGTHVALUE' => '',
                                'L_ITEMLENGTHUNIT' => '',
                                'L_TAXAMT' => '0.00',
                                'L_EBAYITEMTXNID' => '',
                                'L_EBAYITEMORDERID' => ''
                            ];
                        }
                    }

                    // Payment structure
                    $nvp['PAYMENTS'] = [];
                    $payment = [
                        'SHIPTONAME' => !empty($shipping['name']['full_name']) ? $shipping['name']['full_name'] : '',
                        'SHIPTOSTREET' => !empty($address['address_line_1']) ? $address['address_line_1'] : '',
                        'SHIPTOSTREET2' => !empty($address['address_line_2']) ? $address['address_line_2'] : '',
                        'SHIPTOCITY' => !empty($address['admin_area_2']) ? $address['admin_area_2'] : '',
                        'SHIPTOSTATE' => !empty($address['admin_area_1']) ? $address['admin_area_1'] : '',
                        'SHIPTOZIP' => !empty($address['postal_code']) ? $address['postal_code'] : '',
                        'SHIPTOCOUNTRYCODE' => !empty($address['country_code']) ? $address['country_code'] : '',
                        'SHIPTOCOUNTRYNAME' => !empty($address['country_code']) ? $address['country_code'] : '',
                        'SHIPTOPHONENUM' => !empty($shipping['phone_number']['national_number']) ? $shipping['phone_number']['national_number'] : '',
                        'ADDRESSSTATUS' => '',
                        'AMT' => !empty($amount['value']) ? $amount['value'] : '',
                        'CURRENCYCODE' => !empty($amount['currency_code']) ? $amount['currency_code'] : '',
                        'ITEMAMT' => !empty($breakdown['item_total']['value']) ? $breakdown['item_total']['value'] : '',
                        'SHIPPINGAMT' => !empty($breakdown['shipping']['value']) ? $breakdown['shipping']['value'] : '',
                        'INSURANCEOPTIONOFFERED' => false,
                        'HANDLINGAMT' => !empty($breakdown['handling']['value']) ? $breakdown['handling']['value'] : '',
                        'TAXAMT' => !empty($breakdown['tax_total']['value']) ? $breakdown['tax_total']['value'] : '',
                        'DESC' => !empty($unit['description']) ? $unit['description'] : '',
                        'CUSTOM' => '',
                        'INVNUM' => '',
                        'NOTIFYURL' => '',
                        'NOTETEXT' => !empty($noteText) ? $noteText : '',
                        'TRANSACTIONID' => '',
                        'ALLOWEDPAYMENTMETHOD' => '',
                        'PAYMENTREQUESTID' => '',
                        'ORDERITEMS' => []
                    ];

                    // Items Loop
                    if (!empty($unit['items'])) {
                        foreach ($unit['items'] as $iIndex => $item) {
                            $name = !empty($item['name']) ? $item['name'] : '';
                            $qty = !empty($item['quantity']) ? $item['quantity'] : 1;
                            $amt = !empty($item['unit_amount']['value']) ? $item['unit_amount']['value'] : '0.00';

                            $category = (!empty($item['category']) && $item['category'] == 'DIGITAL_GOODS') ? 'Digital' : 'Physical';

                            // Classic item fields
                            $nvp["L_NAME{$iIndex}"] = $name;
                            $nvp["L_NUMBER{$iIndex}"] = preg_replace('/\D/', '', $name);
                            $nvp["L_QTY{$iIndex}"] = $qty;
                            $nvp["L_TAXAMT{$iIndex}"] = '0.00';
                            $nvp["L_AMT{$iIndex}"] = $amt;
                            $nvp["L_DESC{$iIndex}"] = !empty($item['description']) ? $item['description'] : '';
                            $nvp["L_ITEMCATEGORY{$iIndex}"] = $category;

                            // PaymentRequest item fields
                            $nvp["L_PAYMENTREQUEST_{$pIndex}_NAME{$iIndex}"] = $name;
                            $nvp["L_PAYMENTREQUEST_{$pIndex}_NUMBER{$iIndex}"] = preg_replace('/\D/', '', $name);
                            $nvp["L_PAYMENTREQUEST_{$pIndex}_QTY{$iIndex}"] = $qty;
                            $nvp["L_PAYMENTREQUEST_{$pIndex}_TAXAMT{$iIndex}"] = '0.00';
                            $nvp["L_PAYMENTREQUEST_{$pIndex}_AMT{$iIndex}"] = $amt;
                            $nvp["L_PAYMENTREQUEST_{$pIndex}_DESC{$iIndex}"] = !empty($item['description']) ? $item['description'] : '';
                            $nvp["L_PAYMENTREQUEST_{$pIndex}_ITEMCATEGORY{$iIndex}"] = $category;

                            // Payment items array
                            $payment['ORDERITEMS'][] = [
                                'NAME' => $name,
                                'DESC' => !empty($item['description']) ? $item['description'] : '',
                                'AMT' => $amt,
                                'NUMBER' => preg_replace('/\D/', '', $name),
                                'QTY' => $qty,
                                'TAXAMT' => '0.00',
                                'ITEMWEIGHTVALUE' => '',
                                'ITEMWEIGHTUNIT' => '',
                                'ITEMLENGTHVALUE' => '',
                                'ITEMLENGTHUNIT' => '',
                                'ITEMWIDTHVALUE' => '',
                                'ITEMWIDTHUNIT' => '',
                                'EBAYITEMNUMBER' => '',
                                'EBAYAUCTIONTXNID' => '',
                                'EBAYITEMORDERID' => '',
                                'EBAYITEMCARTID' => '',
                            ];
                        }
                    }

                    $nvp['PAYMENTS'][$pIndex] = $payment;
                    $nvp["PAYMENTREQUESTINFO_{$pIndex}_ERRORCODE"] = 0;
                }
            }

            $nvp['ERRORS'] = [];
            $nvp['RAWRESPONSE'] = !empty($response['raw_response']) ? $response['raw_response'] : null;

            $result = array_merge($headers, $nvp);
            return $result;
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }

    /**
     * Map PayPal REST captureOrder response to Classic NVP DoExpressCheckoutPayment format.
     * 
     * @return array
     */
    public function DoExpressCheckoutPaymentMapper($DataArray)
    {
        $orderId = isset($DataArray['DECPFields']['token']) ? $DataArray['DECPFields']['token'] : '';
        $DataPayments = isset($DataArray['Payments']) ? $DataArray['Payments'] : [];
        $txtAmt = isset($DataPayments[0]['taxamt']) ? $DataPayments[0]['taxamt'] : '0.00';

        // Call the REST method to captureOrder
        $response = $this->rest->captureOrder($orderId);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $responseData = array();

            $responseData['TOKEN'] = $orderId;
            $responseData['SUCCESSPAGEREDIRECTREQUESTED'] = 'false';
            $responseData['INSURANCEOPTIONSELECTED'] = 'false';
            $responseData['SHIPPINGOPTIONISDEFAULT'] = 'false';
            $responseData['NOTE'] = '';
            
            $purchaseUnits = isset($response['full_response']['purchase_units']) ? $response['full_response']['purchase_units'] : array();
            $paymentIndex = 0;
            foreach ($purchaseUnits as $unitIndex => $unit) {
                $captures = array();
                if (isset($unit['payments']['captures']) && is_array($unit['payments']['captures'])) {
                    $captures = $unit['payments']['captures'];
                }
                foreach ($captures as $capture) {
                    $amount = isset($capture['amount']['value']) ? $capture['amount']['value'] : '';
                    $currency = isset($capture['amount']['currency_code']) ? $capture['amount']['currency_code'] : '';
                    $fee = isset($capture['seller_receivable_breakdown']['paypal_fee']['value']) ? $capture['seller_receivable_breakdown']['paypal_fee']['value'] : '';
                    $gross = isset($capture['seller_receivable_breakdown']['gross_amount']['value']) ? $capture['seller_receivable_breakdown']['gross_amount']['value'] : '';
                    $net = isset($capture['seller_receivable_breakdown']['net_amount']['value']) ? $capture['seller_receivable_breakdown']['net_amount']['value'] : '';
                    $protectionTypes = '';
                    if ( isset($capture['seller_protection']['dispute_categories']) && is_array($capture['seller_protection']['dispute_categories']) ) {
                        $protectionTypes = implode(',', $capture['seller_protection']['dispute_categories']);
                    }

                    $responseData["PAYMENTINFO_{$paymentIndex}_TRANSACTIONID"] = isset($capture['id']) ? $capture['id'] : '';
                    $responseData["PAYMENTINFO_{$paymentIndex}_TRANSACTIONTYPE"] = "cart";
                    $responseData["PAYMENTINFO_{$paymentIndex}_PAYMENTTYPE"] = "instant";
                    $responseData["PAYMENTINFO_{$paymentIndex}_ORDERTIME"] = isset($capture['create_time']) ? $capture['create_time'] : '';
                    $responseData["PAYMENTINFO_{$paymentIndex}_AMT"] = $amount;
                    $responseData["PAYMENTINFO_{$paymentIndex}_FEEAMT"] = $fee;
                    $responseData["PAYMENTINFO_{$paymentIndex}_TAXAMT"] = $txtAmt;
                    $responseData["PAYMENTINFO_{$paymentIndex}_CURRENCYCODE"] = $currency;
                    $responseData["PAYMENTINFO_{$paymentIndex}_PAYMENTSTATUS"] = isset($capture['status']) ? ucfirst(strtolower($capture['status'])) : '';
                    $responseData["PAYMENTINFO_{$paymentIndex}_PENDINGREASON"] = "None";
                    $responseData["PAYMENTINFO_{$paymentIndex}_REASONCODE"] = "None";
                    $responseData["PAYMENTINFO_{$paymentIndex}_PROTECTIONELIGIBILITY"] = isset($capture['seller_protection']['status']) ? $capture['seller_protection']['status'] : '';
                    $responseData["PAYMENTINFO_{$paymentIndex}_PROTECTIONELIGIBILITYTYPE"]  = $protectionTypes;
                    $responseData["PAYMENTINFO_{$paymentIndex}_SELLERPAYPALACCOUNTID"]  = isset($response['full_response']['payer']['email_address']) ? $response['full_response']['payer']['email_address'] : '';
                    $responseData["PAYMENTINFO_{$paymentIndex}_SECUREMERCHANTACCOUNTID"]  = isset($response['full_response']['payer']['payer_id']) ? $response['full_response']['payer']['payer_id'] : '';
                    $responseData["PAYMENTINFO_{$paymentIndex}_ERRORCODE"] = 0;
                    $responseData["PAYMENTINFO_{$paymentIndex}_ACK"]       = "Success";
                    
                    $responseData['ERRORS'] = array();
                    $responseData['PAYMENTS'] = array();
                    $responseData['PAYMENTS'][$paymentIndex] = array(
                        'TRANSACTIONID'   => isset($capture['id']) ? $capture['id'] : '',
                        'TRANSACTIONTYPE' => 'cart',
                        'PAYMENTTYPE'     => 'instant',
                        'ORDERTIME'       => isset($capture['create_time']) ? $capture['create_time'] : '',
                        'AMT'             => $amount,
                        'FEEAMT'          => $fee,
                        'SETTLEAMT'       => $net,
                        'TAXAMT'          => $txtAmt,
                        'CURRENCYCODE'    => $currency,
                        'PAYMENTSTATUS'   => isset($capture['status']) ? ucfirst(strtolower($capture['status'])) : '',
                        'PENDINGREASON'   => 'None',
                        'REASONCODE'      => 'None',
                        'PROTECTIONELIGIBILITY' => isset($capture['seller_protection']['status']) ? $capture['seller_protection']['status'] : '',
                        'ERRORCODE'       => 0,
                        'FMFILTERS'       => array(),
                        'ERRORS'          => array()
                    );

                    $paymentIndex++;
                }
            }

            $responseData['RAWRESPONSE'] = isset($response['raw_response']) ? $response['raw_response'] : array();

            $result = array_merge($headers, $responseData);
            return $result;            
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }

    /**
     * Map Classic CreateRecurringPaymentsProfile request to PayPal REST Subscription flow.
     * 
     * @return array
     */
    public function CreateRecurringPaymentsProfileMapper($DataArray)
    {
        $profileDetails = !empty($DataArray['profileDetails']) ? $DataArray['profileDetails'] : [];
        $scheduleDetails = !empty($DataArray['ScheduleDetails']) ? $DataArray['ScheduleDetails'] : [];
        $billingPeriod = !empty($DataArray['BillingPeriod']) ? $DataArray['BillingPeriod'] : [];
        $payerInfo = !empty($DataArray['PayerInfo']) ? $DataArray['PayerInfo'] : [];
        $payerName = !empty($DataArray['PayerName']) ? $DataArray['PayerName'] : [];
        $billingAddress = !empty($DataArray['BillingAddress']) ? $DataArray['BillingAddress'] : [];
        $domain = !empty($DataArray['DomainDetails']) ? $DataArray['DomainDetails'] : '';

        $ProductData = array(
            "name" => !empty($scheduleDetails['desc']) ? $scheduleDetails['desc'] : "",
            "description" => "Web hosting recurring subscription",
            "type" => "SERVICE",
            "category" => "SOFTWARE",
        );

        $PlanData = array(
            "product_id" => "",
            "name" => "Daily Hosting Plan",
            "description" => "Daily recurring billing for hosting",
            "billing_cycles" => array(
                array(
                    "frequency" => array(
                        "interval_unit" => !empty($billingPeriod['billingperiod']) ? strtoupper($billingPeriod['billingperiod']) : "",
                        "interval_count" => !empty($billingPeriod['billingfrequency']) ? strtoupper($billingPeriod['billingfrequency']) : "",
                    ),
                    "tenure_type" => "REGULAR",
                    "sequence" => 1,
                    "total_cycles" => !empty($billingPeriod['totalbillingcycles']) ? strtoupper($billingPeriod['totalbillingcycles']) : 0, // 0 = infinite
                    "pricing_scheme" => array(
                        "fixed_price" => array(
                            "value" => !empty($billingPeriod['amt']) ? strtoupper($billingPeriod['amt']) : "10.00",
                            "currency_code" => !empty($billingPeriod['currencycode']) ? strtoupper($billingPeriod['currencycode']) : "USD"
                        )
                    )
                )
            ),
            "payment_preferences" => array(
                "auto_bill_outstanding" => true,
                "setup_fee_failure_action" => "CONTINUE",
                "payment_failure_threshold" => 3
            )
        );

        $SubscriptionData = array(
            "plan_id" => '',
            "start_time" => gmdate("Y-m-d\TH:i:s\Z", strtotime("+10 minutes")),
            "subscriber" => array(
                "name" => array(
                    "given_name" => !empty($payerName['firstname']) ? strtoupper($payerName['firstname']) : "",
                    "surname" => !empty($payerName['lastname']) ? strtoupper($payerName['lastname']) : "",
                ),
                "email_address" => !empty($payerInfo['email']) ? strtoupper($payerInfo['email']) : "",
                "shipping_address" => array(
                    "name" => array("full_name" => !empty($profileDetails['subscribername']) ? strtoupper($profileDetails['subscribername']) : ""),
                    "address" => array(
                            "address_line_1" => !empty($billingAddress['street']) ? strtoupper($billingAddress['street']) : "",
                            "admin_area_2" => !empty($billingAddress['city']) ? strtoupper($billingAddress['city']) : "",
                            "admin_area_1" => !empty($billingAddress['state']) ? strtoupper($billingAddress['state']) : "",
                            "postal_code" => !empty($billingAddress['zip']) ? strtoupper($billingAddress['zip']) : "",
                            "country_code" => !empty($billingAddress['countrycode']) ? strtoupper($billingAddress['countrycode']) : "",
                    )
                )
            ),
            "application_context" => array(
                "brand_name" => "Angell EYE Web Hosting",
                "locale" => "en-US",
                "shipping_preference" => "SET_PROVIDED_ADDRESS",
                "user_action" => "SUBSCRIBE_NOW",
                "return_url" => $domain . "samples/classic/GetRecurringPaymentsProfileDetails.php",
                "cancel_url" => $domain . "samples/classic/", 
            )
        );

        $PayPalRequestData = array(
            'ProductData' => $ProductData, 
            'PlanData' => $PlanData, 
            'SubscriptionData' => $SubscriptionData, 
        );

        // Call the REST method to createSubscriptionProfile
        $response = $this->rest->createSubscriptionProfile($PayPalRequestData);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $result = array_merge(
                $headers,
                [
                    'PROFILEID'         => isset($response['subscription_id']) ? $response['subscription_id'] : '',
                    'PROFILESTATUS'     => isset($response['response']['status']) ? $response['response']['status'] : '',
                    'APPROVALURL'       => isset($response['approval_url']) ? $response['approval_url'] : '',
                    'RAWRESPONSE'       => isset($response['raw_response']) ? $response['raw_response'] : null,
                ]
            );

            return $result;
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }

    /**
     * Map PayPal REST subscription details to Classic
     * GetRecurringPaymentsProfileDetails NVP response format.
     * 
     * @return array
     */
    public function GetRecurringPaymentsProfileDetailsMapper($DataArray)
    {
        $GRPPDFields = !empty($DataArray['GRPPDFields']) ? $DataArray['GRPPDFields'] : [];
        $subscriptionID = !empty($GRPPDFields['profileid']) ? $GRPPDFields['profileid'] : '';

        // Call the REST method to getSubscriptionProfile
        $response = $this->rest->getSubscriptionProfile($subscriptionID);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $fullResponse = $response['full_response'];
            $givenName = !empty( $fullResponse['subscriber']['name']['given_name'] ) ? $fullResponse['subscriber']['name']['given_name'] : '';
            $surname = !empty( $fullResponse['subscriber']['name']['surname'] ) ? $fullResponse['subscriber']['name']['surname'] : '';
            $phone_number = !empty( $fullResponse['subscriber']['phone_number'] ) ? $fullResponse['subscriber']['phone_number'] : '';
            $payer_id = !empty( $fullResponse['subscriber']['payer_id'] ) ? $fullResponse['subscriber']['payer_id'] : '';
            $subscriberName = !empty( $givenName ) ? $givenName . ' ' . $surname : $surname;

            $responseData = array(
                'PROFILEID'             => !empty( $fullResponse['id'] ) ? $fullResponse['id'] : '-',
                'STATUS'                => !empty( $fullResponse['status'] ) ? $fullResponse['status'] : '-', 
                'DESC'                  => !empty( $fullResponse['status_change_note'] ) ? $fullResponse['status_change_note'] : '',
                'AUTOBILLOUTAMT'        => '',
                'MAXFAILEDPAYMENTS'     => '',
                'FIRSTNAME'             => $givenName,
                'LASTNAME'              => $surname,
                'SUBSCRIBERNAME'        => $subscriberName,
                'SUBSCRIBEREMAIL'       => !empty( $fullResponse['subscriber']['email_address'] ) ? $fullResponse['subscriber']['email_address'] : '',
                'PHONENUMBER'           => $phone_number,
                'PAYERID'               => $payer_id,
                'PROFILESTARTDATE'      => !empty( $fullResponse['start_time'] ) ? $fullResponse['start_time'] : '',
                'NEXTBILLINGDATE'       => !empty( $fullResponse['billing_info']['next_billing_time'] ) ? $fullResponse['billing_info']['next_billing_time'] : '',
                'NUMCYCLESCOMPLETED'    => !empty( $fullResponse['billing_info']['cycle_executions'][0]['cycles_completed'] ) ? $fullResponse['billing_info']['cycle_executions'][0]['cycles_completed'] : '',
                'NUMCYCLESREMAINING'    => !empty( $fullResponse['billing_info']['cycle_executions'][0]['cycles_remaining'] ) ? $fullResponse['billing_info']['cycle_executions'][0]['cycles_remaining'] : '',
                'OUTSTANDINGBALANCE'    => !empty( $fullResponse['billing_info']['outstanding_balance']['value'] ) ? $fullResponse['billing_info']['outstanding_balance']['value'] : '',
                'FAILEDPAYMENTCOUNT'    => !empty( $fullResponse['billing_info']['failed_payments_count'] ) ? $fullResponse['billing_info']['failed_payments_count'] : '',
                'LASTPAYMENTDATE'       => !empty( $fullResponse['billing_info']['last_payment']['time'] ) ? $fullResponse['billing_info']['last_payment']['time'] : '',
                'LASTPAYMENTAMT'        => !empty( $fullResponse['billing_info']['last_payment']['amount']['value'] ) ? $fullResponse['billing_info']['last_payment']['amount']['value'] : '',
                'SHIPTONAME'            => !empty( $fullResponse['subscriber']['shipping_address']['name']['full_name'] ) ? $fullResponse['subscriber']['shipping_address']['name']['full_name'] : '',
                'SHIPTOSTREET'          => !empty( $fullResponse['subscriber']['shipping_address']['address']['address_line_1'] ) ? $fullResponse['subscriber']['shipping_address']['address']['address_line_1'] : '',
                'SHIPTOCITY'            => !empty( $fullResponse['subscriber']['shipping_address']['address']['admin_area_2'] ) ? $fullResponse['subscriber']['shipping_address']['address']['admin_area_2'] : '',
                'SHIPTOSTATE'           => !empty( $fullResponse['subscriber']['shipping_address']['address']['admin_area_1'] ) ? $fullResponse['subscriber']['shipping_address']['address']['admin_area_1'] : '',
                'SHIPTOZIP'             => !empty( $fullResponse['subscriber']['shipping_address']['address']['postal_code'] ) ? $fullResponse['subscriber']['shipping_address']['address']['postal_code'] : '',
                'SHIPTOCOUNTRYCODE'     => !empty( $fullResponse['subscriber']['shipping_address']['address']['country_code'] ) ? $fullResponse['subscriber']['shipping_address']['address']['country_code'] : '',
                'SHIPTOCOUNTRY'         => !empty( $fullResponse['subscriber']['shipping_address']['address']['country_code'] ) ? $fullResponse['subscriber']['shipping_address']['address']['country_code'] : '',
                'SHIPADDRESSOWNER'      => !empty( $fullResponse['subscriber']['tenant'] ) ? $fullResponse['subscriber']['tenant'] : '',
                'SHIPPINGAMT'           => !empty( $fullResponse['shipping_amount']['value'] ) ? $fullResponse['shipping_amount']['value'] : '',
                'SHIPPINGCURRENCY'      => !empty( $fullResponse['shipping_amount']['currency_code'] ) ? $fullResponse['shipping_amount']['currency_code'] : '',
                'BILLINGPERIOD'         => '',
                'BILLINGFREQUENCY'      => '',
                'TOTALBILLINGCYCLES'    => '',
                'CURRENCYCODE'          => !empty( $fullResponse['billing_info']['last_payment']['amount']['currency_code'] ) ? $fullResponse['billing_info']['last_payment']['amount']['currency_code'] : '',
                'AMT'                   => !empty( $fullResponse['billing_info']['last_payment']['amount']['value'] ) ? $fullResponse['billing_info']['last_payment']['amount']['value'] : '', 
                'REGULARBILLINGPERIOD'       => '',
                'REGULARBILLINGFREQUENCY'    => '',
                'REGULARTOTALBILLINGCYCLES'  => '',
                'REGULARCURRENCYCODE'   => !empty( $fullResponse['billing_info']['last_payment']['amount']['currency_code'] ) ? $fullResponse['billing_info']['last_payment']['amount']['currency_code'] : '',
                'REGULARAMT'            => !empty( $fullResponse['billing_info']['last_payment']['amount']['value'] ) ? $fullResponse['billing_info']['last_payment']['amount']['value'] : '',
                'AGGREGATEAMT'          => '',
                'AGGREGATEOPTIONALAMT'  => '',
                'ERRORS'                => [],
                'RAWRESPONSE'           => !empty( $response['raw_response'] ) ? $response['raw_response'] : [],
            );

            $result = array_merge($headers, $responseData);
            return $result;             
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }

    /**
     * Map Classic ManageRecurringPaymentsProfileStatus request to PayPal REST
     * subscription management actions.
     * 
     * @return array
     */
    public function ManageRecurringPaymentsProfileStatusMapper($DataArray)
    {
        $MRPPSFields = !empty($DataArray['MRPPSFields']) ? $DataArray['MRPPSFields'] : [];

        $requestData = array(
            'subscription_id' => !empty($MRPPSFields['profileid']) ? $MRPPSFields['profileid'] : '',                // Subscription ID of the profile you want to manage
            'subscription_action' => !empty($MRPPSFields['action']) ? strtolower($MRPPSFields['action']) : '',      // options: cancel | suspend | activate
            'subscription_reason' => !empty($MRPPSFields['note']) ? $MRPPSFields['note'] : ''                       // Reason for the change in status
        );

        // Call the REST method to manageSubscriptionProfile
        $response = $this->rest->manageSubscriptionProfile($requestData);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $result = array_merge(
                $headers,
                [
                    'PROFILEID'      => !empty($MRPPSFields['profileid']) ? $MRPPSFields['profileid'] : '',
                    'L_LONGMESSAGE0' => isset($response['body']) ? $response['body'] : ['message' => 'Actions like cancel or suspend may not return a body'],
                    'ERRORS'         => [],
                    'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
                ]
            );

            return $result;
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }

    /**
     * Map Classic UpdateRecurringPaymentsProfile request to PayPal REST
     * subscription PATCH update operation.
     * 
     * @return array
     */
    public function UpdateRecurringPaymentsProfileMapper($DataArray)
    {
        $URPPFields = !empty($DataArray['URPPFields']) ? $DataArray['URPPFields'] : [];

        $patchData = [
            [
                "op" => "replace",
                    "path" => "/shipping_amount",
                    "value" => [
                        "currency_code" => "USD",
                        "value" => !empty($URPPFields['amt']) ? $URPPFields['amt'] : "0.00"
                    ]
            ]
        ];

        // Prepare request arrays
        $requestData = array(
            'subscription_id' => !empty($URPPFields['profileid']) ? $URPPFields['profileid'] : '',
            'patches' => $patchData,
        );

        // Call the REST method to updateSubscriptionProfile
        $response = $this->rest->updateSubscriptionProfile($requestData);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $result = array_merge(
                $headers,
                [
                    'PROFILEID'      => !empty($URPPFields['profileid']) ? $URPPFields['profileid'] : '',
                    'L_LONGMESSAGE0' => isset($response['body']) ? $response['body'] : ['message' => 'Patch Operations completed successfully which may not return a body'],
                    'ERRORS'         => [],
                    'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
                ]
            );

            return $result;
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }

    /**
     * Maps the REST reauthorization response to NVP (Name-Value Pair) format.
     * 
     * @return array
     */
    public function UpdateAuthorizationMapper($DataArray)
    {
        $UAFields = !empty($DataArray['UAFields']) ? $DataArray['UAFields'] : [];
        $transactionID = !empty($UAFields['transactionid']) ? $UAFields['transactionid'] : '';

        // Call the REST method to updateAuthorization
        $response = $this->rest->updateAuthorization($transactionID);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $result = array_merge(
                $headers,
                [
                    'L_LONGMESSAGE0' => isset($response['body']) ? $response['body'] : ['message' => ''],
                    'ERRORS'         => [],
                    'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
                ]
            );

            return $result;
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }

    /**
     * Maps Classic MassPay request data to PayPal REST Payouts API format.
     * 
     * @return array
     */
    public function MassPayMapper($DataArray)
    {
        // Extract MassPay Fields from DataArray
        $MPFields = !empty($DataArray['MPFields']) ? $DataArray['MPFields'] : [];
        $MPItems = !empty($DataArray['MPItems']) ? $DataArray['MPItems'] : [];

        // Create Batch Headers
        $SenderBatchHeaders = [
            'sender_batch_id' => uniqid('batch_'), // unique batch id
            'email_subject'   => !empty($MPFields['emailsubject']) ? $MPFields['emailsubject'] : 'You have a payout',
            'email_message'   => 'You have received a payout!'
        ];

        // Create Items
        $Items = [];
        foreach ($MPItems as $item) {
            $recipientType = 'PHONE';
            if (!empty($MPFields['receivertype'])) {
                if ($MPFields['receivertype'] === 'EmailAddress') {
                    $recipientType = 'EMAIL';
                }
            }

            $Items[] = [
                "recipient_type" => $recipientType,
                "amount" => [
                    "value"    => $item['l_amt'],
                    "currency" => $MPFields['currencycode']
                ],
                "receiver" => !empty($item['l_email']) ? $item['l_email'] : $item['l_receiverid'],
                "note" => !empty($item['l_note']) ? $item['l_note'] : '',
                "sender_item_id" => !empty($item['l_uniqueid']) ? $item['l_uniqueid'] : uniqid('item_')
            ];
        }

        $PayPalRequestData = [
            'sender_batch_header' => $SenderBatchHeaders,
            'items' => $Items
        ];

        // Call the REST method to MassPay
        $response = $this->rest->massPayments($PayPalRequestData);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $result = array_merge(
                $headers,
                [
                    'PAYOUTBATCHID'  => !empty($response['full_response']['batch_header']['payout_batch_id']) ? $response['full_response']['batch_header']['payout_batch_id'] : '',
                    'ERRORS'         => [],
                    'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
                ]
            );

            return $result;
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }

    /**
     * Maps Classic MassPay request data to PayPal REST Payouts API format.
     * 
     * @return array
     */
    public function RefundTransactionMapper($DataArray)
    {
        // Extract MassPay Fields from DataArray
        $RTFields = !empty($DataArray['RTFields']) ? $DataArray['RTFields'] : [];

        $PayPalRequestData = [
            'transaction_id' => !empty($RTFields['transactionid']) ? $RTFields['transactionid'] : '',
            'refund_type' => !empty($RTFields['refundtype']) ? strtolower($RTFields['refundtype']) : 'full',
            'refund_fields' => []
        ];

        if (!empty($RTFields['note'])) {
            $PayPalRequestData['refund_fields']['note_to_payer'] = $RTFields['note'];
        }

        if (strtolower($RTFields['refundtype']) !== 'full') {
            $PayPalRequestData['refund_fields']['amount'] = [
                'value' => !empty($RTFields['amt']) ? $RTFields['amt'] : '',
                'currency_code' => !empty($RTFields['currencycode']) ? $RTFields['currencycode'] : 'USD'
            ];
        }

        // Call the REST method to RefundTransaction
        $response = $this->rest->refundPayments($PayPalRequestData);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success']) && !empty($response['success'])) {
            $full_response = !empty($response['full_response']) ? $response['full_response'] : [];
            $amount = !empty($full_response['amount']) ? $full_response['amount'] : [];
            $seller_breakdown = !empty($full_response['seller_payable_breakdown']) ? $full_response['seller_payable_breakdown'] : [];

            $result = array_merge(
                $headers,
                [
                    'FEEREFUNDAMT' => !empty($seller_breakdown['paypal_fee']['value']) ? $seller_breakdown['paypal_fee']['value'] : '0.00',
                    'REFUNDTRANSACTIONID' => !empty($full_response['id']) ? $full_response['id'] : '',
                    'GROSSREFUNDAMT' => !empty($amount['value']) ? $amount['value'] : '0.00',
                    'PENDINGREASON' => 'None',
                    'CURRENCYCODE' => !empty($amount['currency_code']) ? $amount['currency_code'] : 'USD',
                    'NETREFUNDAMT' => !empty($seller_breakdown['net_amount']['value']) ? $seller_breakdown['net_amount']['value'] : '0.00',
                    'REFUNDSTATUS' => (isset($full_response['status']) && $full_response['status'] === 'COMPLETED') ? 'Instant' : 'Pending',
                    'TOTALREFUNDEDAMOUNT' => !empty($seller_breakdown['total_refunded_amount']['value']) ? $seller_breakdown['total_refunded_amount']['value'] : '0.00',
                    'ERRORS'         => [],
                    'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
                ]
            );

            return $result;
        }

        // Call function to convert REST Errors to NVP
        $NVPErrors = $this->convertRESTErrorsToNVP($response);

        $result = array_merge(
            $headers,
            $NVPErrors['flatErrors'], 
            [
                'ERRORS'         => $NVPErrors['errorsList'],
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        return $result;
    }
}