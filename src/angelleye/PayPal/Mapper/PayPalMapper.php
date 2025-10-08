<?php

namespace PayPal\Mapper;

class PayPalMapper {
    /**
     * @var array Mapping rules from primary source
     */
    protected static $nvpToRestMap = [
        // Payment Amount & Currency
        'AMT' => 'purchase_units[0].amount.value',
        'CURRENCYCODE' => 'purchase_units[0].amount.currency_code',
        
        // URLs
        'RETURNURL' => 'payment_source.paypal.experience_context.return_url',
        'CANCELURL' => 'payment_source.paypal.experience_context.cancel_url',
        
        // Shipping
        'NOSHIPPING' => 'payment_source.paypal.experience_context.shipping_preference',
        'ADDROVERRIDE' => 'payment_source.paypal.experience_context.shipping_preference',
        'SHIPTONAME' => 'purchase_units[0].shipping.name.full_name',
        'SHIPTOSTREET' => 'purchase_units[0].shipping.address.address_line_1',
        'SHIPTOSTREET2' => 'purchase_units[0].shipping.address.address_line_2',
        'SHIPTOCITY' => 'purchase_units[0].shipping.address.admin_area_2',
        'SHIPTOSTATE' => 'purchase_units[0].shipping.address.admin_area_1',
        'SHIPTOZIP' => 'purchase_units[0].shipping.address.postal_code',
        'SHIPTOCOUNTRYCODE' => 'purchase_units[0].shipping.address.country_code',
        'SHIPTOPHONENUM' => 'purchase_units[0].shipping.phone.phone_number.national_number',
        
        // Experience
        'BRANDNAME' => 'payment_source.paypal.experience_context.brand_name',
        'LOCALECODE' => 'payment_source.paypal.experience_context.locale',
        'LANDINGPAGE' => 'payment_source.paypal.experience_context.landing_page',
        
        // Order Details
        'INVNUM' => 'purchase_units[0].invoice_id',
        'CUSTOM' => 'purchase_units[0].custom_id',
        'DESC' => 'purchase_units[0].description',
        
        // Amount Breakdown
        'ITEMAMT' => 'purchase_units[0].amount.breakdown.item_total.value',
        'SHIPPINGAMT' => 'purchase_units[0].amount.breakdown.shipping.value',
        'HANDLINGAMT' => 'purchase_units[0].amount.breakdown.handling.value',
        'TAXAMT' => 'purchase_units[0].amount.breakdown.tax_total.value',
        'SHIPDISCAMT' => 'purchase_units[0].amount.breakdown.shipping_discount.value',
        'INSURANCEAMT' => 'purchase_units[0].amount.breakdown.insurance.value'
    ];

    /**
     * Maps classic NVP parameters to REST structure
     */
    public static function mapNvpToRest($params) {
        $restData = [
            'intent' => self::mapPaymentAction($params),
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => $params['PAYMENTREQUEST_0_CURRENCYCODE'] ?? 'USD',
                        'value' => $params['PAYMENTREQUEST_0_AMT'] ?? '0.00'
                    ]
                ]
            ],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => self::mapExperienceContext($params)
                ]
            ]
        ];

        // Add items if present
        if (self::hasLineItems($params)) {
            $restData['purchase_units'][0]['items'] = self::mapLineItems($params);
            $restData['purchase_units'][0]['amount']['breakdown'] = self::mapAmountBreakdown($params);
        }

        // Add shipping if present
        if (self::hasShippingInfo($params)) {
            $restData['purchase_units'][0]['shipping'] = self::mapShippingDetails($params);
        }

        // Add billing agreement details if present
        if (self::hasBillingAgreement($params)) {
            $restData = self::addBillingAgreementDetails($restData, $params);
        }

        return $restData;
    }

    /**
     * Maps payment action to REST intent
     */
    protected static function mapPaymentAction($params) {
        $action = $params['PAYMENTREQUEST_0_PAYMENTACTION'] ?? $params['PAYMENTACTION'] ?? 'Sale';
        return strtolower($action) === 'authorization' ? 'AUTHORIZE' : 'CAPTURE';
    }

    /**
     * Maps experience context parameters
     */
    protected static function mapExperienceContext($params) {
        $context = [
            'return_url' => $params['RETURNURL'] ?? '',
            'cancel_url' => $params['CANCELURL'] ?? '',
            'brand_name' => $params['BRANDNAME'] ?? null,
            'locale' => $params['LOCALECODE'] ?? 'en-US',
            'shipping_preference' => self::mapShippingPreference($params)
        ];

        if (isset($params['LANDINGPAGE'])) {
            $context['landing_page'] = strtoupper($params['LANDINGPAGE']);
        }

        return array_filter($context, function($value) {
            return $value !== null;
        });
    }

    /**
     * Maps shipping preference
     */
    protected static function mapShippingPreference($params) {
        if (isset($params['NOSHIPPING']) && $params['NOSHIPPING'] === '1') {
            return 'NO_SHIPPING';
        }
        if (isset($params['ADDROVERRIDE']) && $params['ADDROVERRIDE'] === '1') {
            return 'SET_PROVIDED_ADDRESS';
        }
        return 'GET_FROM_FILE';
    }

    /**
     * Maps line items from NVP to REST format
     */
    protected static function mapLineItems($params) {
        $items = [];
        $i = 0;
        while (isset($params["L_PAYMENTREQUEST_0_NAME$i"])) {
            $items[] = [
                'name' => $params["L_PAYMENTREQUEST_0_NAME$i"],
                'quantity' => $params["L_PAYMENTREQUEST_0_QTY$i"] ?? 1,
                'unit_amount' => [
                    'currency_code' => $params['PAYMENTREQUEST_0_CURRENCYCODE'] ?? 'USD',
                    'value' => $params["L_PAYMENTREQUEST_0_AMT$i"]
                ],
                'description' => $params["L_PAYMENTREQUEST_0_DESC$i"] ?? null,
                'sku' => $params["L_PAYMENTREQUEST_0_NUMBER$i"] ?? null
            ];
            $i++;
        }
        return $items;
    }

    /**
     * Maps amount breakdown
     */
    protected static function mapAmountBreakdown($params) {
        $breakdown = [];
        $mappings = [
            'ITEMAMT' => 'item_total',
            'SHIPPINGAMT' => 'shipping',
            'HANDLINGAMT' => 'handling',
            'TAXAMT' => 'tax_total',
            'SHIPDISCAMT' => 'shipping_discount',
            'INSURANCEAMT' => 'insurance'
        ];

        foreach ($mappings as $nvp => $rest) {
            if (isset($params["PAYMENTREQUEST_0_$nvp"])) {
                $breakdown[$rest] = [
                    'currency_code' => $params['PAYMENTREQUEST_0_CURRENCYCODE'] ?? 'USD',
                    'value' => $params["PAYMENTREQUEST_0_$nvp"]
                ];
            }
        }

        return $breakdown;
    }

    /**
     * Maps shipping details
     */
    protected static function mapShippingDetails($params) {
        $shipping = [];

        if (isset($params['PAYMENTREQUEST_0_SHIPTONAME'])) {
            $shipping['name'] = [
                'full_name' => $params['PAYMENTREQUEST_0_SHIPTONAME']
            ];
        }

        if (self::hasShippingAddress($params)) {
            $shipping['address'] = [
                'address_line_1' => $params['PAYMENTREQUEST_0_SHIPTOSTREET'] ?? '',
                'address_line_2' => $params['PAYMENTREQUEST_0_SHIPTOSTREET2'] ?? '',
                'admin_area_2' => $params['PAYMENTREQUEST_0_SHIPTOCITY'] ?? '',
                'admin_area_1' => $params['PAYMENTREQUEST_0_SHIPTOSTATE'] ?? '',
                'postal_code' => $params['PAYMENTREQUEST_0_SHIPTOZIP'] ?? '',
                'country_code' => $params['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE'] ?? ''
            ];
        }

        if (isset($params['PAYMENTREQUEST_0_SHIPTOPHONENUM'])) {
            $shipping['phone'] = [
                'phone_number' => [
                    'national_number' => $params['PAYMENTREQUEST_0_SHIPTOPHONENUM']
                ]
            ];
        }

        return $shipping;
    }

    /**
     * Maps REST response to NVP format
     */
    public static function mapRestToNvp($response) {
        $result = [
            'TOKEN' => $response['id'],
            'TIMESTAMP' => date('Y-m-d\TH:i:s\Z'),
            'ACK' => 'Success'
        ];

        // Map approval URL
        foreach ($response['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $result['REDIRECTURL'] = $link['href'];
                break;
            }
        }

        // Map payment info if present
        if (isset($response['purchase_units'][0]['payments'])) {
            $payments = $response['purchase_units'][0]['payments'];
            if (isset($payments['captures'][0])) {
                $result['PAYMENTINFO_0_TRANSACTIONID'] = $payments['captures'][0]['id'];
                $result['PAYMENTINFO_0_PAYMENTSTATUS'] = $payments['captures'][0]['status'];
            } elseif (isset($payments['authorizations'][0])) {
                $result['PAYMENTINFO_0_TRANSACTIONID'] = $payments['authorizations'][0]['id'];
                $result['PAYMENTINFO_0_PAYMENTSTATUS'] = $payments['authorizations'][0]['status'];
            }
        }

        return $result;
    }

    /**
     * Helper method to check if request has line items
     */
    protected static function hasLineItems($params) {
        return isset($params['L_PAYMENTREQUEST_0_NAME0']);
    }

    /**
     * Helper method to check if request has shipping info
     */
    protected static function hasShippingInfo($params) {
        return isset($params['PAYMENTREQUEST_0_SHIPTONAME']) || 
               self::hasShippingAddress($params);
    }

    /**
     * Helper method to check if request has shipping address
     */
    protected static function hasShippingAddress($params) {
        return isset($params['PAYMENTREQUEST_0_SHIPTOSTREET']) || 
               isset($params['PAYMENTREQUEST_0_SHIPTOCITY']) ||
               isset($params['PAYMENTREQUEST_0_SHIPTOSTATE']) ||
               isset($params['PAYMENTREQUEST_0_SHIPTOZIP']) ||
               isset($params['PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE']);
    }

    /**
     * Helper method to check if request has billing agreement
     */
    protected static function hasBillingAgreement($params) {
        return isset($params['L_BILLINGTYPE0']);
    }

    /**
     * Adds billing agreement details to REST payload
     */
    protected static function addBillingAgreementDetails($restData, $params) {
        $restData['payment_source']['paypal']['attributes'] = [
            'vault' => [
                'store_in_vault' => 'ON_SUCCESS',
                'usage_type' => 'MERCHANT',
                'customer_type' => 'MERCHANT'
            ]
        ];
        return $restData;
    }

    /**
     * Validates required parameters for specific operations
     */
    public static function validateParams($params, $operation) {
        $errors = [];
        
        switch ($operation) {
            case 'SetExpressCheckout':
                if (empty($params['RETURNURL'])) {
                    $errors[] = 'RETURNURL is required';
                }
                if (empty($params['CANCELURL'])) {
                    $errors[] = 'CANCELURL is required';
                }
                if (empty($params['PAYMENTREQUEST_0_AMT'])) {
                    $errors[] = 'Amount is required';
                }
                break;
            
            case 'DoExpressCheckoutPayment':
                if (empty($params['TOKEN'])) {
                    $errors[] = 'TOKEN is required';
                }
                break;
        }

        return $errors;
    }
}