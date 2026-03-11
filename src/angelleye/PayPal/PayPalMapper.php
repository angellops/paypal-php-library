<?php

namespace angelleye\PayPal;

/**
 * PayPal REST API Class
 * Extends the main PayPal class for consistency and shared functionality
 */
class PayPalMapper extends PayPal
{
    public $rest;

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

        if (in_array($response['status'], [200, 201])) {
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

        $response = $this->rest->createOrder($payload);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success'])) {
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
        $response = $this->rest->getOrder($Token);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success'])) {
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
                        'SHIPTOCOUNTRYNAME' => '',
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

    public function DoExpressCheckoutPaymentMapper($DataArray)
    {
        $orderId = isset($DataArray['DECPFields']['token']) ? $DataArray['DECPFields']['token'] : '';
        $DataPayments = isset($DataArray['Payments']) ? $DataArray['Payments'] : [];
        $txtAmt = isset($DataPayments[0]['taxamt']) ? $DataPayments[0]['taxamt'] : '0.00';

        $response = $this->rest->captureOrder($orderId);

        // Define the primary headers first
        $headers = [
            'TIMESTAMP' => gmdate('c'),
            'ACK'       => 'Success',
            'VERSION'   => $this->APIVersion,
        ];

        if (isset($response['success'])) {
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
}