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

            // Log the response
            $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $result);
            
            return $result;
        }

        $flatErrors = [];
        $errorsList = [];

        // Check if 'details' exists in the 'errors' key of the REST response
        $rawErrors = isset($response['errors']['details']) ? $response['errors']['details'] : [];

        // If 'details' is empty but we have a top-level message (like 403 Forbidden)
        if (empty($rawErrors) && isset($response['errors']['message'])) {
            $rawErrors[] = [
                'issue' => isset($response['errors']['name']) ? $response['errors']['name'] : 'ERROR',
                'description' => $response['errors']['message']
            ];
        }

        // Loop through and build the Classic NVP structure
        foreach ($rawErrors as $i => $error) {
            $code = isset($error['issue']) ? $error['issue'] : '99999';
            $msg  = isset($error['description']) ? $error['description'] : 'No description provided';

            // Top-level flattened keys
            $flatErrors["L_ERRORCODE{$i}"]    = $code;
            $flatErrors["L_SHORTMESSAGE{$i}"] = 'REST_API_ERROR';
            $flatErrors["L_LONGMESSAGE{$i}"]  = $msg;
            $flatErrors["L_SEVERITYCODE{$i}"] = 'Error';

            // Nested ERRORS array
            $errorsList[] = [
                'L_ERRORCODE'    => $code,
                'L_SHORTMESSAGE' => 'REST_API_ERROR',
                'L_LONGMESSAGE'  => $msg,
                'L_SEVERITYCODE' => 'Error'
            ];
        }

        $result = array_merge(
            $headers,
            $flatErrors, 
            [
                'ERRORS'         => $errorsList,
                'BALANCERESULTS' => array(),
                'RAWRESPONSE'    => isset($response['raw_response']) ? $response['raw_response'] : [],
            ]
        );

        $this->Logger($this->LogPath, __FUNCTION__ . 'Response', $result);
        return $result;
    }
}