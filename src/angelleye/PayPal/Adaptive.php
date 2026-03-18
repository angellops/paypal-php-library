<?php namespace angelleye\PayPal;
/**
 *	An open source PHP library written to easily work with PayPal's Adaptive Payments API
 *	
 *	Email:  service@angelleye.com
 *  Facebook: angelleyeconsulting
 *  Twitter: angelleye
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @package			paypal-php-library
 * @author			Andrew Angell <service@angelleye.com>
 * @link			https://github.com/angelleye/paypal-php-library/
 * @website			http://www.angelleye.com
 * @support         http://www.angelleye.com/product/premium-support/
 * @filesource
*/

/**
 * PayPal Adaptive Payments Class
 *
 * This class houses all of the Adaptive Payments specific API's.  
 *
 * @package 		paypal-php-library
 * @author			Andrew Angell <service@angelleye.com>
 */

use DOMDocument;

class Adaptive extends PayPal
{
	var $DeveloperAccountEmail = '';
	var $XMLNamespace = '';
	var $ApplicationID = '';
	var $DeviceID = '';
	var $IPAddress = '';
	var $DetailLevel = '';
	var $ErrorLanguage = '';

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray	Array structure providing config data
	 * @return	void
	 */
	function __construct($DataArray)
	{
		// Throw exception if calling any Adaptive Methods
		throw new \Exception(
			"Deprecated Notice: The Adaptive PayPal methods are deprecated. Please upgrade to the REST-based implementation to ensure compatibility with future updates."
		);

		parent::__construct($DataArray);
		
		$this->XMLNamespace = 'http://svcs.paypal.com/types/ap';
		$this->DeviceID = isset($DataArray['DeviceID']) ? $DataArray['DeviceID'] : '';
		$this->IPAddress = isset($DataArray['IPAddress']) ? $DataArray['IPAddress'] : $_SERVER['REMOTE_ADDR'];
		$this->DetailLevel = isset($DataArray['DetailLevel']) ? $DataArray['DetailLevel'] : 'ReturnAll';
		$this->ErrorLanguage = isset($DataArray['ErrorLanguage']) ? $DataArray['ErrorLanguage'] : 'en_US';
		$this->APISubject = isset($DataArray['APISubject']) ? $DataArray['APISubject'] : '';
		$this->DeveloperAccountEmail = isset($DataArray['DeveloperAccountEmail']) ? $DataArray['DeveloperAccountEmail'] : '';

		if($this -> Sandbox)
		{	
			// Sandbox Credentials
			$this -> ApplicationID = isset($DataArray['ApplicationID']) ? $DataArray['ApplicationID'] : '';
			$this -> APIUsername = isset($DataArray['APIUsername']) && $DataArray['APIUsername'] != '' ? $DataArray['APIUsername'] : '';
			$this -> APIPassword = isset($DataArray['APIPassword']) && $DataArray['APIPassword'] != '' ? $DataArray['APIPassword'] : '';
			$this -> APISignature = isset($DataArray['APISignature']) && $DataArray['APISignature'] != '' ? $DataArray['APISignature'] : '';
			$this -> EndPointURL = isset($DataArray['EndPointURL']) && $DataArray['EndPointURL'] != '' ? $DataArray['EndPointURL'] : 'https://svcs.sandbox.paypal.com/';
		}
		else
		{
			// Live Credentials
			$this -> ApplicationID = isset($DataArray['ApplicationID']) ? $DataArray['ApplicationID'] : '';
			$this -> APIUsername = isset($DataArray['APIUsername']) && $DataArray['APIUsername'] != '' ? $DataArray['APIUsername'] : '';
			$this -> APIPassword = isset($DataArray['APIPassword']) && $DataArray['APIPassword'] != ''  ? $DataArray['APIPassword'] : '';
			$this -> APISignature = isset($DataArray['APISignature']) && $DataArray['APISignature'] != ''  ? $DataArray['APISignature'] : '';
			$this -> EndPointURL = isset($DataArray['EndPointURL']) && $DataArray['EndPointURL'] != ''  ? $DataArray['EndPointURL'] : 'https://svcs.paypal.com/';
		}
	}
	
	/**
	 * Build all HTTP headers required for the API call.
	 *
	 * @access	public
	 * @param	boolean	$PrintHeaders - Whether to print headers on screen or not (true/false)
	 * @return	array $headers
	 */
	
	/**
	 * Builds HTTP headers for an API request.
	 *
	 * @access	public
	 * @param	boolean	$PrintHeaders	Option to output headers to the screen (true/false).
	 * @return	string	$headers		String of HTTP headers.	
	 */
	function BuildHeaders($PrintHeaders)
	{
		$headers = array(
						'X-PAYPAL-SECURITY-USERID: ' . $this -> APIUsername, 
						'X-PAYPAL-SECURITY-PASSWORD: ' . $this -> APIPassword, 
						'X-PAYPAL-SECURITY-SIGNATURE: ' . $this -> APISignature, 
						'X-PAYPAL-SECURITY-SUBJECT: ' . $this -> APISubject, 
						'X-PAYPAL-REQUEST-DATA-FORMAT: XML',
						'X-PAYPAL-RESPONSE-DATA-FORMAT: XML', 
						'X-PAYPAL-APPLICATION-ID: ' . $this -> ApplicationID, 
						'X-PAYPAL-DEVICE-ID: ' . $this -> DeviceID, 
						'X-PAYPAL-DEVICE-IPADDRESS: ' . $this -> IPAddress
						);
		
		if($this -> Sandbox)
		{
			array_push($headers, 'X-PAYPAL-SANDBOX-EMAIL-ADDRESS: '.$this->DeveloperAccountEmail);
		}
		
		if($PrintHeaders)
		{
			echo '<pre />';
			print_r($headers);
		}
		
		return $headers;
	}
	
	/**
	 * Send the API request to PayPal using CURL.
	 *
	 * @access	public
	 * @param	string	$Request		Raw API request string.
	 * @param	string	$APIName		The name of the API which you are calling.
	 * @param	string	$APIOperation	The method in the API you're calling.
     * @param   string  $PrintHeaders   The option to print header output or not.
	 * @return	string	$Response		Returns the raw HTTP response from PayPal.
	 */
	function CURLRequest($Request = "", $APIName = "", $APIOperation = "", $PrintHeaders = false)
	{
		$curl = curl_init();
				curl_setopt($curl, CURLOPT_VERBOSE, 1);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($curl, CURLOPT_TIMEOUT, 30);
				curl_setopt($curl, CURLOPT_URL, $this -> EndPointURL . $APIName . '/' . $APIOperation);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $Request);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $this -> BuildHeaders($this->PrintHeaders));
								
		if($this -> APIMode == 'Certificate')
		{
			curl_setopt($curl, CURLOPT_SSLCERT, $this -> PathToCertKeyPEM);
		}
		
		$Response = curl_exec($curl);		
		curl_close($curl);
		return $Response;
	}
	
	/**
	 * Get all errors returned from PayPal
	 *
	 * @access	public
	 * @param	string	$XML			XML response from PayPal
	 * @return	mixed[]	$ErrorsArray	Returns a parsed array of PayPal errors/warnings.
	 */
	function GetErrors($XML)
	{
		$DOM = new DOMDocument();
		$DOM -> loadXML($XML);
		
		$Errors = $DOM -> getElementsByTagName('error') -> length > 0 ? $DOM -> getElementsByTagName('error') : array();
		$ErrorsArray = array();
		foreach($Errors as $Error)
		{
			$Receiver = $Error -> getElementsByTagName('receiver') -> length > 0 ? $Error -> getElementsByTagName('receiver') -> item(0) -> nodeValue : '';
			$Category = $Error -> getElementsByTagName('category') -> length > 0 ? $Error -> getElementsByTagName('category') -> item(0) -> nodeValue : '';
			$Domain = $Error -> getElementsByTagName('domain') -> length > 0 ? $Error -> getElementsByTagName('domain') -> item(0) -> nodeValue : '';
			$ErrorID = $Error -> getElementsByTagName('errorId') -> length > 0 ? $Error -> getElementsByTagName('errorId') -> item(0) -> nodeValue : '';
			$ExceptionID = $Error -> getElementsByTagName('exceptionId') -> length > 0 ? $Error -> getElementsByTagName('exceptionId') -> item(0) -> nodeValue : '';
			$Message = $Error -> getElementsByTagName('message') -> length > 0 ? $Error -> getElementsByTagName('message') -> item(0) -> nodeValue : '';
			$Parameter = $Error -> getElementsByTagName('parameter') -> length > 0 ? $Error -> getElementsByTagName('parameter') -> item(0) -> nodeValue : '';
			$Severity = $Error -> getElementsByTagName('severity') -> length  > 0 ? $Error -> getElementsByTagName('severity') -> item(0) -> nodeValue : '';
			$Subdomain = $Error -> getElementsByTagName('subdomain') -> length > 0 ? $Error -> getElementsByTagName('subdomain') -> item(0) -> nodeValue : '';
			
			$CurrentError = array(
								  'Receiver' => $Receiver, 
								  'Category' => $Category, 
								  'Domain' => $Domain, 
								  'ErrorID' => $ErrorID, 
								  'ExceptionID' => $ExceptionID, 
								  'Message' => $Message, 
								  'Parameter' => $Parameter, 
								  'Severity' => $Severity, 
								  'Subdomain' => $Subdomain
								  );
			array_push($ErrorsArray, $CurrentError);
		}
		return $ErrorsArray;
	}
	
	/**
	 * Get the request envelope from the XML string
	 *
	 * @access	public
	 * @return	string	$XML	Returns raw XML request envelope
	 */
	function GetXMLRequestEnvelope()
	{
		$XML = '<requestEnvelope xmlns="">';
		$XML .= '<detailLevel>' . $this -> DetailLevel . '</detailLevel>';
		$XML .= '<errorLanguage>' . $this -> ErrorLanguage . '</errorLanguage>';
		$XML .= '</requestEnvelope>';
		
		return $XML;
	}

    /**
     * Log result to a location on the disk.
     *
     * @param $log_path
     * @param $filename
     * @param $string_data
     * @return bool
     */
    function Logger($log_path, $filename, $string_data)
    {

        if($this->LogResults)
        {
            $timestamp = strtotime('now');
            $timestamp = date('mdY_gi_s_A_',$timestamp);

            $file = $log_path.$timestamp.$filename.'.xml';
            $fh = fopen($file, 'w');
            fwrite($fh, $string_data);
            fclose($fh);
        }

        return true;
    }



    /**
	 * Submits Pay API request to PayPal
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function Pay($DataArray)
	{	
		// PayRequest Fields
		$PayRequestFields = isset($DataArray['PayRequestFields']) ? $DataArray['PayRequestFields'] : array();
		$ActionType = isset($PayRequestFields['ActionType']) ? $PayRequestFields['ActionType'] : '';
		$CancelURL = isset($PayRequestFields['CancelURL']) ? $PayRequestFields['CancelURL'] : '';
		$CurrencyCode = isset($PayRequestFields['CurrencyCode']) ? $PayRequestFields['CurrencyCode'] : '';
		$FeesPayer = isset($PayRequestFields['FeesPayer']) ? $PayRequestFields['FeesPayer'] : '';
		$IPNNotificationURL = isset($PayRequestFields['IPNNotificationURL']) ? $PayRequestFields['IPNNotificationURL'] : '';
		$Memo = isset($PayRequestFields['Memo']) ? $PayRequestFields['Memo'] : '';
		$Pin = isset($PayRequestFields['Pin']) ? $PayRequestFields['Pin'] : '';
		$PreapprovalKey = isset($PayRequestFields['PreapprovalKey']) ? $PayRequestFields['PreapprovalKey'] : '';
		$ReturnURL = isset($PayRequestFields['ReturnURL']) ? $PayRequestFields['ReturnURL'] : '';
		$ReverseAllParallelPaymentsOnError = isset($PayRequestFields['ReverseAllParallelPaymentsOnError']) ? $PayRequestFields['ReverseAllParallelPaymentsOnError'] : '';
		$SenderEmail = isset($PayRequestFields['SenderEmail']) ? $PayRequestFields['SenderEmail'] : '';
		$TrackingID = isset($PayRequestFields['TrackingID']) ? $PayRequestFields['TrackingID'] : '';
		
		// ClientDetails Fields
		$ClientDetailsFields = isset($DataArray['ClientDetailsFields']) ? $DataArray['ClientDetailsFields'] : array();
		$CustomerID = isset($ClientDetailsFields['CustomerID']) ? $ClientDetailsFields['CustomerID'] : '';
		$CustomerType = isset($ClientDetailsFields['CustomerType']) ? $ClientDetailsFields['CustomerType'] : '';
		$GeoLocation = isset($ClientDetailsFields['GeoLocation']) ? $ClientDetailsFields['GeoLocation'] : '';
		$Model = isset($ClientDetailsFields['Model']) ? $ClientDetailsFields['Model'] : '';
		$PartnerName = isset($ClientDetailsFields['PartnerName']) ? $ClientDetailsFields['PartnerName'] : '';
		
		// FundingConstraint Fields
		$FundingTypes = isset($DataArray['FundingTypes']) ? $DataArray['FundingTypes'] : array();
		
		// Receivers Fields
		$Receivers = isset($DataArray['Receivers']) ? $DataArray['Receivers'] : array();

		// SenderIdentifier Fields
		$SenderIdentifierFields = isset($DataArray['SenderIdentifierFields']) ? $DataArray['SenderIdentifierFields'] : array();

		// AccountIdentifierFields Fields
		$AccountIdentifierFields = isset($DataArray['AccountIdentifierFields']) ? $DataArray['AccountIdentifierFields'] : array();
		$AccountEmail = isset($AccountIdentifierFields['Email']) ? $AccountIdentifierFields['Email'] : '';
		$AccountPhone = isset($AccountIdentifierFields['Phone']) ? $AccountIdentifierFields['Phone'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<PayRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $ActionType != '' ? '<actionType xmlns="">' . $ActionType . '</actionType>' : '';
		$XMLRequest .= $CancelURL != '' ? '<cancelUrl xmlns="">' . $CancelURL . '</cancelUrl>' : '';
		
		if(count($ClientDetailsFields) > 0)
		{
			$XMLRequest .= '<clientDetails xmlns="">';
			$XMLRequest .= $this -> ApplicationID != '' ? '<applicationId xmlns="">' . $this -> ApplicationID . '</applicationId>' : '';
			$XMLRequest .= $CustomerID != '' ? '<customerId xmlns="">' . $CustomerID . '</customerId>' : '';
			$XMLRequest .= $CustomerType != '' ? '<customerType xmlns="">' . $CustomerType . '</customerType>' : '';
			$XMLRequest .= $this -> DeviceID != '' ? '<deviceId xmlns="">' . $this -> DeviceID . '</deviceId>' : '';
			$XMLRequest .= $GeoLocation != '' ? '<geoLocation xmlns="">' . $GeoLocation . '</geoLocation>' : '';
			$XMLRequest .= $this -> IPAddress != '' ? '<ipAddress xmlns="">' . $this -> IPAddress . '</ipAddress>' : '';
			$XMLRequest .= $Model != '' ? '<model xmlns="">' . $Model . '</model>' : '';
			$XMLRequest .= $PartnerName != '' ? '<partnerName xmlns="">' . $PartnerName . '</partnerName>' : '';
			$XMLRequest .= '</clientDetails>';		
		}
		
		$XMLRequest .= $CurrencyCode != '' ? '<currencyCode xmlns="">' . $CurrencyCode . '</currencyCode>' : '';
		$XMLRequest .= $FeesPayer != '' ? '<feesPayer xmlns="">' . $FeesPayer . '</feesPayer>' : '';
		
		if(count($FundingTypes) > 0)
		{		
			$XMLRequest .= '<fundingConstraint xmlns="">';
			$XMLRequest .= '<allowedFundingType xmlns="">';
			
			foreach($FundingTypes as $FundingType)
			{
				$XMLRequest .= '<fundingTypeInfo xmlns="">';
				$XMLRequest .= '<fundingType xmlns="">' . $FundingType . '</fundingType>';
				$XMLRequest .= '</fundingTypeInfo>';
			}
			
			$XMLRequest .= '</allowedFundingType>';
			$XMLRequest .= '</fundingConstraint>';
		}
		
		$XMLRequest .= $IPNNotificationURL != '' ? '<ipnNotificationUrl xmlns="">' . $IPNNotificationURL . '</ipnNotificationUrl>' : '';
		$XMLRequest .= $Memo != '' ? '<memo xmlns="">' . $Memo . '</memo>' : '';
		$XMLRequest .= $Pin != '' ? '<pin xmlns="">' . $Pin . '</pin>' : '';
		$XMLRequest .= $PreapprovalKey != '' ? '<preapprovalKey xmlns="">' . $PreapprovalKey . '</preapprovalKey>' : '';
		
		$XMLRequest .= '<receiverList xmlns="">';
		
		$IsDigitalGoods = FALSE;
		foreach($Receivers as $Receiver)
		{
			$IsDigitalGoods = strtoupper($Receiver['PaymentType']) == 'DIGITALGOODS' ? TRUE : FALSE;
			
			$XMLRequest .= '<receiver xmlns="">';
			$XMLRequest .= $Receiver['Amount'] != '' ? '<amount xmlns="">' . $Receiver['Amount'] . '</amount>' : '';
			$XMLRequest .= $Receiver['Email'] != '' ? '<email xmlns="">' . $Receiver['Email'] . '</email>' : '';			
                        $XMLRequest .= !empty($Receiver['AccountID']) ? '<accountId xmlns="">' . $Receiver['AccountID'] . '</accountId>' : '';
			$XMLRequest .= $Receiver['InvoiceID'] != '' ? '<invoiceId xmlns="">' . $Receiver['InvoiceID'] . '</invoiceId>' : '';
			$XMLRequest .= $Receiver['PaymentType'] != '' ? '<paymentType xmlns="">' . $Receiver['PaymentType'] . '</paymentType>' : '';
			$XMLRequest .= $Receiver['PaymentSubType'] != '' ? '<paymentSubType xmlns="">' . $Receiver['PaymentSubType'] . '</paymentSubType>' : '';
			
			if($Receiver['Phone']['CountryCode'] != '')
			{
				$XMLRequest .= '<phone xmlns="">';
				$XMLRequest .= $Receiver['Phone']['CountryCode'] != '' ? '<countryCode xmlns="">' . $Receiver['Phone']['CountryCode'] . '</countryCode>' : '';
				$XMLRequest .= $Receiver['Phone']['PhoneNumber'] != '' ? '<phoneNumber xmlns="">' . $Receiver['Phone']['PhoneNumber'] . '</phoneNumber>' : '';
				$XMLRequest .= $Receiver['Phone']['Extension'] != '' ? '<extension xmlns="">' . $Receiver['Phone']['Extension'] . '</extension>' : '';
				$XMLRequest .= '</phone>';
			}
			
			$XMLRequest .= $Receiver['Primary'] != '' ? '<primary xmlns="">' . strtolower($Receiver['Primary']) . '</primary>' : '';
			$XMLRequest .= '</receiver>';
		}
		$XMLRequest .= '</receiverList>';
		
		if(count($SenderIdentifierFields) > 0)
		{
			$XMLRequest .= '<sender>';
			$XMLRequest .= '<useCredentials xmlns="">' . $SenderIdentifierFields['UseCredentials'] . '</useCredentials>';
			$XMLRequest .= '</sender>';	
		}
		
		if(count($AccountIdentifierFields) > 0)
		{
			$XMLRequest .= '<account xmlns="">';
			$XMLRequest .= $AccountEmail != '' ? '<email xmlns="">' . $AccountEmail . '</email>' : '';
			
			if($AccountPhone != '')
			{
				$XMLRequest .= '<phone xmlns="">';
				$XMLRequest .= $AccountPhone['CountryCode'] != '' ? '<countryCode xmlns="">' . $AccountPhone['CountryCode'] . '</countryCode>' : '';
				$XMLRequest .= $AccountPhone['PhoneNumber'] != '' ? '<phoneNumber xmlns="">' . $AccountPhone['PhoneNumber'] . '</phoneNumber>' : '';
				$XMLRequest .= $AccountPhone['Extension'] != '' ? '<extension xmlns="">' . $AccountPhone['Extension'] . '</extension>' : '';
				$XMLRequest .= '</phone>';
			}
			
			$XMLRequest .= '</account>';
		}
		
		$XMLRequest .= $ReturnURL != '' ? '<returnUrl xmlns="">' . $ReturnURL . '</returnUrl>' : '';
		$XMLRequest .= $ReverseAllParallelPaymentsOnError != '' ? '<reverseAllParallelPaymentsOnError xmlns="">' . $ReverseAllParallelPaymentsOnError . '</reverseAllParallelPaymentsOnError>' : '';
		$XMLRequest .= $SenderEmail != '' ? '<senderEmail xmlns="">' . $SenderEmail . '</senderEmail>' : '';
		$XMLRequest .= $TrackingID != '' ? '<trackingId xmlns="">' . $TrackingID . '</trackingId>' : '';
		$XMLRequest .= '</PayRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'Pay');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
						
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$PayKey = $DOM -> getElementsByTagName('payKey') -> length > 0 ? $DOM -> getElementsByTagName('payKey') -> item(0) -> nodeValue : '';
		$PaymentExecStatus = $DOM -> getElementsByTagName('paymentExecStatus') -> length > 0 ? $DOM -> getElementsByTagName('paymentExecStatus') -> item(0) -> nodeValue : '';
		
		if($this -> Sandbox)
		{
			if($IsDigitalGoods)
			{
				$RedirectURL = 'https://www.sandbox.paypal.com/webapps/adaptivepayment/flow/pay?paykey='.$PayKey;
			}
			else
			{
				$RedirectURL = 'https://www.sandbox.paypal.com/webscr?cmd=_ap-payment&paykey=' . $PayKey;
			}
		}
		else
		{
			if($IsDigitalGoods)
			{
				$RedirectURL = 'https://www.paypal.com/webapps/adaptivepayment/flow/pay?paykey='.$PayKey;
			}
			else
			{
				$RedirectURL = 'https://www.paypal.com/webscr?cmd=_ap-payment&paykey=' . $PayKey;
			}
		}
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'PayKey' => $PayKey, 
								   'PaymentExecStatus' => $PaymentExecStatus, 
								   'RedirectURL' => $PayKey != '' ? $RedirectURL : '', 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	}
	
	/**
	 * Submits PayWithOptions API request to PayPal
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function PayWithOptions($DataArray)
	{
		// PayRequest Fields
		$PayRequestFields = isset($DataArray['PayRequestFields']) ? $DataArray['PayRequestFields'] : array();
		$ActionType = 'CREATE';
		$CancelURL = isset($PayRequestFields['CancelURL']) ? $PayRequestFields['CancelURL'] : '';
		$CurrencyCode = isset($PayRequestFields['CurrencyCode']) ? $PayRequestFields['CurrencyCode'] : '';
		$FeesPayer = isset($PayRequestFields['FeesPayer']) ? $PayRequestFields['FeesPayer'] : '';
		$IPNNotificationURL = isset($PayRequestFields['IPNNotificationURL']) ? $PayRequestFields['IPNNotificationURL'] : '';
		$Memo = isset($PayRequestFields['Memo']) ? $PayRequestFields['Memo'] : '';
		$Pin = isset($PayRequestFields['Pin']) ? $PayRequestFields['Pin'] : '';
		$PreapprovalKey = isset($PayRequestFields['PreapprovalKey']) ? $PayRequestFields['PreapprovalKey'] : '';
		$ReturnURL = isset($PayRequestFields['ReturnURL']) ? $PayRequestFields['ReturnURL'] : '';
		$ReverseAllParallelPaymentsOnError = isset($PayRequestFields['ReverseAllParallelPaymentsOnError']) ? $PayRequestFields['ReverseAllParallelPaymentsOnError'] : '';
		$SenderEmail = isset($PayRequestFields['SenderEmail']) ? $PayRequestFields['SenderEmail'] : '';
		$TrackingID = isset($PayRequestFields['TrackingID']) ? $PayRequestFields['TrackingID'] : '';
	
		// ClientDetails Fields
		$ClientDetailsFields = isset($DataArray['ClientDetailsFields']) ? $DataArray['ClientDetailsFields'] : array();
		$CustomerID = isset($ClientDetailsFields['CustomerID']) ? $ClientDetailsFields['CustomerID'] : '';
		$CustomerType = isset($ClientDetailsFields['CustomerType']) ? $ClientDetailsFields['CustomerType'] : '';
		$GeoLocation = isset($ClientDetailsFields['GeoLocation']) ? $ClientDetailsFields['GeoLocation'] : '';
		$Model = isset($ClientDetailsFields['Model']) ? $ClientDetailsFields['Model'] : '';
		$PartnerName = isset($ClientDetailsFields['PartnerName']) ? $ClientDetailsFields['PartnerName'] : '';
	
		// FundingConstraint Fields
		$FundingTypes = isset($DataArray['FundingTypes']) ? $DataArray['FundingTypes'] : array();
	
		// Receivers Fields
		$Receivers = isset($DataArray['Receivers']) ? $DataArray['Receivers'] : array();
		$Amount = isset($Receivers['Amount']) ? $Receivers['Amount'] : '';
		$Email = isset($Receivers['Email']) ? $Receivers['Email'] : '';
		$InvoiceID = isset($Receivers['InvoiceID']) ? $Receivers['InvoiceID'] : '';
		$PaymentType = isset($Receivers['PaymentType']) ? $Receivers['PaymentType'] : '';
		$PaymentSubType = isset($Receivers['PaymentSubType']) ? $Receivers['PaymentSubType'] : '';
		$Phone = isset($Receivers['Phone']) ? $Receivers['Phone'] : '';
		$Primary = isset($Receivers['Primary']) ? strtolower($Receivers['Primary']) : '';
	
		// SenderIdentifier Fields
		$SenderIdentifierFields = isset($DataArray['SenderIdentifierFields']) ? $DataArray['SenderIdentifierFields'] : array();
		$UseCredentials = isset($SenderIdentifierFields['UseCredentials']) ? $SenderIdentifierFields['UseCredentials'] : '';
	
		// AccountIdentifierFields Fields
		$AccountIdentifierFields = isset($DataArray['AccountIdentifierFields']) ? $DataArray['AccountIdentifierFields'] : array();
		$AccountEmail = isset($AccountIdentifierFields['Email']) ? $AccountIdentifierFields['Email'] : '';
		$AccountPhone = isset($AccountIdentifierFields['Phone']) ? $AccountIdentifierFields['Phone'] : '';
	
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<PayRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $ActionType != '' ? '<actionType xmlns="">' . $ActionType . '</actionType>' : '';
		$XMLRequest .= $CancelURL != '' ? '<cancelUrl xmlns="">' . $CancelURL . '</cancelUrl>' : '';
	
		if(count($ClientDetailsFields) > 0)
		{
			$XMLRequest .= '<clientDetails xmlns="">';
			$XMLRequest .= $this -> ApplicationID != '' ? '<applicationId xmlns="">' . $this -> ApplicationID . '</applicationId>' : '';
			$XMLRequest .= $CustomerID != '' ? '<customerId xmlns="">' . $CustomerID . '</customerId>' : '';
			$XMLRequest .= $CustomerType != '' ? '<customerType xmlns="">' . $CustomerType . '</customerType>' : '';
			$XMLRequest .= $this -> DeviceID != '' ? '<deviceId xmlns="">' . $this -> DeviceID . '</deviceId>' : '';
			$XMLRequest .= $GeoLocation != '' ? '<geoLocation xmlns="">' . $GeoLocation . '</geoLocation>' : '';
			$XMLRequest .= $this -> IPAddress != '' ? '<ipAddress xmlns="">' . $this -> IPAddress . '</ipAddress>' : '';
			$XMLRequest .= $Model != '' ? '<model xmlns="">' . $Model . '</model>' : '';
			$XMLRequest .= $PartnerName != '' ? '<partnerName xmlns="">' . $PartnerName . '</partnerName>' : '';
			$XMLRequest .= '</clientDetails>';
		}
	
		$XMLRequest .= $CurrencyCode != '' ? '<currencyCode xmlns="">' . $CurrencyCode . '</currencyCode>' : '';
		$XMLRequest .= $FeesPayer != '' ? '<feesPayer xmlns="">' . $FeesPayer . '</feesPayer>' : '';
	
		if(count($FundingTypes) > 0)
		{
			$XMLRequest .= '<fundingConstraint xmlns="">';
			$XMLRequest .= '<allowedFundingType xmlns="">';
				
			foreach($FundingTypes as $FundingType)
			{
				$XMLRequest .= '<fundingTypeInfo xmlns="">';
				$XMLRequest .= '<fundingType xmlns="">' . $FundingType . '</fundingType>';
				$XMLRequest .= '</fundingTypeInfo>';
			}
				
			$XMLRequest .= '</allowedFundingType>';
			$XMLRequest .= '</fundingConstraint>';
		}
	
		$XMLRequest .= $IPNNotificationURL != '' ? '<ipnNotificationUrl xmlns="">' . $IPNNotificationURL . '</ipnNotificationUrl>' : '';
		$XMLRequest .= $Memo != '' ? '<memo xmlns="">' . $Memo . '</memo>' : '';
		$XMLRequest .= $Pin != '' ? '<pin xmlns="">' . $Pin . '</pin>' : '';
		$XMLRequest .= $PreapprovalKey != '' ? '<preapprovalKey xmlns="">' . $PreapprovalKey . '</preapprovalKey>' : '';
	
		$XMLRequest .= '<receiverList xmlns="">';
	
		$IsDigitalGoods = FALSE;
		foreach($Receivers as $Receiver)
		{
			$IsDigitalGoods = strtoupper($Receiver['PaymentType']) == 'DIGITALGOODS' ? TRUE : FALSE;
				
			$XMLRequest .= '<receiver xmlns="">';
			$XMLRequest .= $Receiver['Amount'] != '' ? '<amount xmlns="">' . $Receiver['Amount'] . '</amount>' : '';
			$XMLRequest .= $Receiver['Email'] != '' ? '<email xmlns="">' . $Receiver['Email'] . '</email>' : '';
			$XMLRequest .= $Receiver['InvoiceID'] != '' ? '<invoiceId xmlns="">' . $Receiver['InvoiceID'] . '</invoiceId>' : '';
			$XMLRequest .= $Receiver['PaymentType'] != '' ? '<paymentType xmlns="">' . $Receiver['PaymentType'] . '</paymentType>' : '';
			$XMLRequest .= $Receiver['PaymentSubType'] != '' ? '<paymentSubType xmlns="">' . $Receiver['PaymentSubType'] . '</paymentSubType>' : '';
				
			if($Receiver['Phone']['CountryCode'] != '')
			{
				$XMLRequest .= '<phone xmlns="">';
				$XMLRequest .= $Receiver['Phone']['CountryCode'] != '' ? '<countryCode xmlns="">' . $Receiver['Phone']['CountryCode'] . '</countryCode>' : '';
				$XMLRequest .= $Receiver['Phone']['PhoneNumber'] != '' ? '<phoneNumber xmlns="">' . $Receiver['Phone']['PhoneNumber'] . '</phoneNumber>' : '';
				$XMLRequest .= $Receiver['Phone']['Extension'] != '' ? '<extension xmlns="">' . $Receiver['Phone']['Extension'] . '</extension>' : '';
				$XMLRequest .= '</phone>';
			}
				
			$XMLRequest .= $Receiver['Primary'] != '' ? '<primary xmlns="">' . strtolower($Receiver['Primary']) . '</primary>' : '';
			$XMLRequest .= '</receiver>';
		}
		$XMLRequest .= '</receiverList>';
	
		if(count($SenderIdentifierFields) > 0)
		{
			$XMLRequest .= '<sender>';
			$XMLRequest .= '<useCredentials xmlns="">' . $SenderIdentifierFields['UseCredentials'] . '</useCredentials>';
			$XMLRequest .= '</sender>';
		}
	
		if(count($AccountIdentifierFields) > 0)
		{
			$XMLRequest .= '<account xmlns="">';
			$XMLRequest .= $AccountEmail != '' ? '<email xmlns="">' . $AccountEmail . '</email>' : '';
				
			if($AccountPhone != '')
			{
				$XMLRequest .= '<phone xmlns="">';
				$XMLRequest .= $AccountPhone['CountryCode'] != '' ? '<countryCode xmlns="">' . $AccountPhone['CountryCode'] . '</countryCode>' : '';
				$XMLRequest .= $AccountPhone['PhoneNumber'] != '' ? '<phoneNumber xmlns="">' . $AccountPhone['PhoneNumber'] . '</phoneNumber>' : '';
				$XMLRequest .= $AccountPhone['Extension'] != '' ? '<extension xmlns="">' . $AccountPhone['Extension'] . '</extension>' : '';
				$XMLRequest .= '</phone>';
			}
				
			$XMLRequest .= '</account>';
		}
	
		$XMLRequest .= $ReturnURL != '' ? '<returnUrl xmlns="">' . $ReturnURL . '</returnUrl>' : '';
		$XMLRequest .= $ReverseAllParallelPaymentsOnError != '' ? '<reverseAllParallelPaymentsOnError xmlns="">' . $ReverseAllParallelPaymentsOnError . '</reverseAllParallelPaymentsOnError>' : '';
		$XMLRequest .= $SenderEmail != '' ? '<senderEmail xmlns="">' . $SenderEmail . '</senderEmail>' : '';
		$XMLRequest .= $TrackingID != '' ? '<trackingId xmlns="">' . $TrackingID . '</trackingId>' : '';
		$XMLRequest .= '</PayRequest>';
		
		$PayXMLRequest = $XMLRequest;
	
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'Pay');
		$PayXMLResponse = $XMLResponse;
		
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, 'PayRequest', $PayXMLRequest);
        $this->Logger($this->LogPath, 'PayResponse', $PayXMLResponse);
	
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
	
		$PayKey = $DOM -> getElementsByTagName('payKey') -> length > 0 ? $DOM -> getElementsByTagName('payKey') -> item(0) -> nodeValue : '';
		$PaymentExecStatus = $DOM -> getElementsByTagName('paymentExecStatus') -> length > 0 ? $DOM -> getElementsByTagName('paymentExecStatus') -> item(0) -> nodeValue : '';
	
		if($this -> Sandbox)
		{
			if($IsDigitalGoods)
			{
				$RedirectURL = 'https://www.sandbox.paypal.com/webapps/adaptivepayment/flow/pay?paykey='.$PayKey;
			}
			else
			{
				$RedirectURL = 'https://www.sandbox.paypal.com/webscr?cmd=_ap-payment&paykey=' . $PayKey;
			}
		}
		else
		{
			if($IsDigitalGoods)
			{
				$RedirectURL = 'https://www.paypal.com/webapps/adaptivepayment/flow/pay?paykey='.$PayKey;
			}
			else
			{
				$RedirectURL = 'https://www.paypal.com/webscr?cmd=_ap-payment&paykey=' . $PayKey;
			}
		}
		
		if(!$this->APICallSuccessful($Ack))
		{
				$ResponseDataArray = array(
					'Errors' => $Errors,
					'Ack' => $Ack,
					'Build' => $Build,
					'CorrelationID' => $CorrelationID,
					'Timestamp' => $Timestamp,
					'PayKey' => $PayKey,
					'PaymentExecStatus' => $PaymentExecStatus,
					'PayXMLRequest' => $PayXMLRequest,
					'PayXMLResponse' => $PayXMLResponse
			);
		
			return $ResponseDataArray;
			exit();
		}
		
		// SetPaymentOptions Basic Fields
		$SPOFields = isset($DataArray['SPOFields']) ? $DataArray['SPOFields'] : array();
		$ShippingAddressID = isset($SPOFields['ShippingAddressID']) ? $SPOFields['ShippingAddressID'] : '';
		
		// DisplayOptions Fields
		$DisplayOptions = isset($DataArray['DisplayOptions']) ? $DataArray['DisplayOptions'] : array();
		$EmailHeaderImageURL = isset($DisplayOptions['EmailHeaderImageURL']) ? $DisplayOptions['EmailHeaderImageURL'] : '';
		$EmailMarketingImageURL = isset($DisplayOptions['EmailMarketingImageURL']) ? $DisplayOptions['EmailMarketingImageURL'] : '';
		$HeaderImageURL = isset($DisplayOptions['HeaderImageURL']) ? $DisplayOptions['HeaderImageURL'] : '';
		$BusinessName = isset($DisplayOptions['BusinessName']) ? $DisplayOptions['BusinessName'] : '';
		
		// InstitutionCustomer Fields
		$InstitutionCustomer = isset($DataArray['InstitutionCustomer']) ? $DataArray['InstitutionCustomer'] : array();
		$CountryCode = isset($InstitutionCustomer['CountryCode']) ? $InstitutionCustomer['CountryCode'] : '';
		$DisplayName = isset($InstitutionCustomer['DisplayName']) ? $InstitutionCustomer['DisplayName'] : '';
		$InstitutionCustomerEmail = isset($InstitutionCustomer['Email']) ? $InstitutionCustomer['Email'] : '';
		$FirstName = isset($InstitutionCustomer['FirstName']) ? $InstitutionCustomer['FirstName'] : '';
		$LastName = isset($InstitutionCustomer['LastName']) ? $InstitutionCustomer['LastName'] : '';
		$InstitutionCustomerID = isset($InstitutionCustomer['InstitutionCustomerID']) ? $InstitutionCustomer['InstitutionCustomerID'] : '';
		$InstitutionID = isset($InstitutionCustomer['InstitutionID']) ? $InstitutionCustomer['InstitutionID'] : '';
		
		// SenderOptions Fields
		$SenderOptions = isset($DataArray['SenderOptions']) ? $DataArray['SenderOptions'] : array();
		$RequireShippingAddressSelection = isset($SenderOptions['RequireShippingAddressSelection']) ? $SenderOptions['RequireShippingAddressSelection'] : '';
		$ReferrerCode = $this->APIButtonSource;
		
		// ReceiverOptions Fields
		$ReceiverOptionsXML = '';
		$ReceiverOptions = isset($DataArray['ReceiverOptions']) ? $DataArray['ReceiverOptions'] : array();
		foreach($ReceiverOptions as $ReceiverOption)
		{
			$Description = isset($ReceiverOption['Description']) ? $ReceiverOption['Description'] : '';
			$CustomID = isset($ReceiverOption['CustomID']) ? $ReceiverOption['CustomID'] : '';
			
			// Invoice Data
			$InvoiceData = isset($ReceiverOption['InvoiceData']) ? $ReceiverOption['InvoiceData'] : array();
			$TotalTax = isset($InvoiceData['TotalTax']) ? $InvoiceData['TotalTax'] : '';
			$TotalShipping = isset($InvoiceData['TotalShipping']) ? $InvoiceData['TotalShipping'] : '';
			
			// InvoiceItem Fields
			$InvoiceItems = isset($ReceiverOption['InvoiceItems']) ? $ReceiverOption['InvoiceItems'] : array();
			
			// ReceiverIdentifer Fields
			$ReceiverIdentifier = isset($ReceiverOption['ReceiverIdentifier']) ? $ReceiverOption['ReceiverIdentifier'] : array();
			$ReceiverIdentifierEmail = isset($ReceiverIdentifier['Email']) ? $ReceiverIdentifier['Email'] : '';
			$PhoneCountryCode = isset($ReceiverIdentifier['PhoneCountryCode']) ? $ReceiverIdentifier['PhoneCountryCode'] : '';
			$PhoneNumber = isset($ReceiverIdentifier['PhoneNumber']) ? $ReceiverIdentifier['PhoneNumber'] : '';
			$PhoneExtension = isset($ReceiverIdentifier['PhoneExtension']) ? $ReceiverIdentifier['PhoneExtension'] : '';
			
			
				$ReceiverOptionsXML .= '<receiverOptions xmlns="">';
				$ReceiverOptionsXML .= $Description != '' ? '<description xmlns="">'.$Description.'</description>' : '';
				$ReceiverOptionsXML .= $CustomID != '' ? '<customId xmlns="">'.$CustomID.'</customId>' : '';
					
				if(!empty($InvoiceData))
				{
					$ReceiverOptionsXML .= '<invoiceData xmlns="">';
					$ReceiverOptionsXML .= $TotalTax != '' ? '<totalTax xmlns="">'.$TotalTax.'</totalTax>' : '';
					$ReceiverOptionsXML .= $TotalShipping != '' ? '<totalShipping xmlns="">'.$TotalShipping.'</totalShipping>' : '';
			
			
					foreach($InvoiceItems as $InvoiceItem)
					{
						$ReceiverOptionsXML .= '<item xmlns="">';
						$ReceiverOptionsXML .= $InvoiceItem['Name'] != '' ? '<name xmlns="">'.$InvoiceItem['Name'].'</name>' : '';
						$ReceiverOptionsXML .= $InvoiceItem['Identifier'] != '' ? '<identifier xmlns="">'.$InvoiceItem['Identifier'].'</identifier>' : '';
						$ReceiverOptionsXML .= $InvoiceItem['Price'] != '' ? '<price xmlns="">'.$InvoiceItem['Price'].'</price>' : '';
						$ReceiverOptionsXML .= $InvoiceItem['ItemPrice'] != '' ? '<itemPrice xmlns="">'.$InvoiceItem['ItemPrice'].'</itemPrice>' : '';
						$ReceiverOptionsXML .= $InvoiceItem['ItemCount'] != '' ? '<itemCount xmlns="">'.$InvoiceItem['ItemCount'].'</itemCount>' : '';
						$ReceiverOptionsXML .= '</item>';
					}
			
					$ReceiverOptionsXML .= '</invoiceData>';
				}
					
				if(count($ReceiverIdentifier) > 0)
				{
					$ReceiverOptionsXML .= '<receiver xmlns="">';
					$ReceiverOptionsXML .= $ReceiverIdentifierEmail != '' ? '<email xmlns="">'.$ReceiverIdentifierEmail.'</email>' : '';
			
					if($PhoneNumber != '')
					{
						$ReceiverOptionsXML .= '<phone xmlns="">';
						$ReceiverOptionsXML .= $PhoneCountryCode != '' ? '<countryCode xmlns="">'.$PhoneCountryCode.'</countryCode>' : '';
						$ReceiverOptionsXML .= $PhoneNumber != '' ? '<phoneNumber xmlns="">'.$PhoneNumber.'</phoneNumber>' : '';
						$ReceiverOptionsXML .= $PhoneExtension != '' ? '<extension xmlns="">'.$PhoneExtension.'</extension>' : '';
						$ReceiverOptionsXML .= '</phone>';
					}
			
					$ReceiverOptionsXML .= '</receiver>';
				}
					
				$ReceiverOptionsXML .= '</receiverOptions>';
		}

		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<SetPaymentOptionsRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $PayKey != '' ? '<payKey xmlns="">'.$PayKey.'</payKey>' : '';
		$XMLRequest .= $ShippingAddressID != '' ? '<shippingAddressId xmlns="">'.$ShippingAddressID.'</shippingAddressId>' : '';
		
		if(count($InstitutionCustomer) > 0)
		{
			$XMLRequest .= '<initiatingEntity xmlns="">';
			$XMLRequest .= '<institutionCustomer xmlns="">';
			$XMLRequest .= $InstitutionID != '' ? '<institutionId xmlns="">'.$InstitutionID.'</institutionId>' : '';
			$XMLRequest .= $FirstName != '' ? '<firstName xmlns="">'.$FirstName.'</firstName>' : '';
			$XMLRequest .= $LastName != '' ? '<lastName xmlns="">'.$LastName.'</lastName>' : '';
			$XMLRequest .= $DisplayName != '' ? '<displayName xmlns="">'.$DisplayName.'</displayName>' : '';
			$XMLRequest .= $InstitutionCustomerID != '' ? '<institutionCustomerId xmlns="">'.$InstitutionCustomerID.'</institutionCustomerId>' : '';
			$XMLRequest .= $CountryCode != '' ? '<countryCode xmlns="">'.$CountryCode.'</countryCode>' : '';
			$XMLRequest .= $InstitutionCustomerEmail != '' ? '<email xmlns="">'.$InstitutionCustomerEmail.'</email>' : '';
			$XMLRequest .= '</institutionCustomer>';
			$XMLRequest .= '</initiatingEntity>';	
		}
		
		if(count($DisplayOptions) > 0)
		{
			$XMLRequest .= '<displayOptions xmlns="">';
			$XMLRequest .= $EmailHeaderImageURL != '' ? '<emailHeaderImageUrl xmlns="">'.$EmailHeaderImageURL.'</emailHeaderImageUrl>' : '';
			$XMLRequest .= $EmailMarketingImageURL != '' ? '<emailMarketingImageUrl xmlns="">'.$EmailMarketingImageURL.'</emailMarketingImageUrl>' : '';
			$XMLRequest .= $HeaderImageURL != '' ? '<headerImageUrl xmlns="">'.$HeaderImageURL.'</headerImageUrl>' : '';
			$XMLRequest .= $BusinessName != '' ? '<businessName xmlns="">'.$BusinessName.'</businessName>' : '';
			$XMLRequest .= '</displayOptions>';	
		}
		
		if(count($SenderOptions) > 0)
		{
			$XMLRequest .= '<senderOptions xmlns="">';
			$XMLRequest .= $RequireShippingAddressSelection != '' ? '<requireShippingAddressSelection xmlns="">'.$RequireShippingAddressSelection.'</requireShippingAddressSelection>' : '';
			$XMLRequest .= $ReferrerCode != '' ? '<referrerCode xmlns="">'.$ReferrerCode.'</referrerCode>' : '';
			$XMLRequest .= '</senderOptions>';	
		}
		
		$XMLRequest .= $ReceiverOptionsXML;
		$XMLRequest .= '</SetPaymentOptionsRequest>';
		$SetPaymentOptionsXMLRequest = $XMLRequest;
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'SetPaymentOptions');
		$SetPaymentOptionsXMLResponse = $XMLResponse;
		
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, 'SetPaymentOptionsRequest', $SetPaymentOptionsXMLRequest);
        $this->Logger($this->LogPath, 'SetPaymentOptionsResponse', $SetPaymentOptionsXMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
	
		$ResponseDataArray = array(
				'Errors' => $Errors,
				'Ack' => $Ack,
				'Build' => $Build,
				'CorrelationID' => $CorrelationID,
				'Timestamp' => $Timestamp,
				'PayKey' => $PayKey,
				'PaymentExecStatus' => $PaymentExecStatus,
				'RedirectURL' => $PayKey != '' ? $RedirectURL : '',
				'PayXMLRequest' => $PayXMLRequest,
				'PayXMLResponse' => $PayXMLResponse, 
				'SetPaymentOptionsXMLRequest' => $SetPaymentOptionsXMLRequest, 
				'SetPaymentOptionsXMLResponse' => $SetPaymentOptionsXMLResponse
		);
	
		return $ResponseDataArray;
	}
	
	/**
	 * Submits PaymentDetails API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function PaymentDetails($DataArray)
	{
		// PaymentDetails Fields
		$PaymentDetailsFields = isset($DataArray['PaymentDetailsFields']) ? $DataArray['PaymentDetailsFields'] : array();
		$PayKey = isset($PaymentDetailsFields['PayKey']) ? $PaymentDetailsFields['PayKey'] : '';
		$TransactionID = isset($PaymentDetailsFields['TransactionID']) ? $PaymentDetailsFields['TransactionID'] : '';
		$TrackingID = isset($PaymentDetailsFields['TrackingID']) ? $PaymentDetailsFields['TrackingID'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<PaymentDetailsRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $PayKey != '' ? '<payKey xmlns="">' . $PayKey . '</payKey>' : '';
		$XMLRequest .= $TransactionID != '' ? '<transactionId xmlns="">' . $TransactionID . '</transactionId>' : '';
		$XMLRequest .= $TrackingID != '' ? '<trackingId xmlns="">' . $TrackingID . '</trackingId>' : '';
		$XMLRequest .= '</PaymentDetailsRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'PaymentDetails');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
						
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$ActionType = $DOM -> getElementsByTagName('actionType') -> length > 0 ? $DOM -> getElementsByTagName('actionType') -> item(0) -> nodeValue : '';
		$CancelURL = $DOM -> getElementsByTagName('cancelUrl') -> length > 0 ? $DOM -> getElementsByTagName('cancelUrl') -> item(0) -> nodeValue : '';
		$CurrencyCode = $DOM -> getElementsByTagName('currencyCode') -> length > 0 ? $DOM -> getElementsByTagName('currencyCode') -> item(0) -> nodeValue : '';
		$FeesPayer = $DOM -> getElementsByTagName('feesPayer') -> length > 0 ? $DOM -> getElementsByTagName('feesPayer') -> item(0) -> nodeValue : '';
		
		$FundingTypesDOM = $DOM -> getElementsByTagName('fundingType') -> length > 0 ? $DOM -> getElementsByTagName('fundingType') : array();
		$FundingTypes = array();
		foreach($FundingTypesDOM as $FundingType)
		{
			array_push($FundingTypes, $FundingType);
		}
		
		$IPNNotificationURL = $DOM -> getElementsByTagName('ipnNotificationUrl') -> length > 0 ? $DOM -> getElementsByTagName('ipnNotificationUrl') -> item(0) -> nodeValue : '';
		$Memo = $DOM -> getElementsByTagName('memo') -> length > 0 ? $DOM -> getElementsByTagName('memo') -> item(0) -> nodeValue : '';
		$PayKey = $DOM -> getElementsByTagName('payKey') -> length > 0 ? $DOM -> getElementsByTagName('payKey') -> item(0) -> nodeValue : '';
		
		$PendingRefund = $DOM -> getElementsByTagName('pendingRefund') -> length > 0 ? $DOM -> getElementsByTagName('pendingRefund') -> item(0) -> nodeValue : 'false';
		$RefundedAmount = $DOM -> getElementsByTagName('refundedAmount') -> length > 0 ? $DOM -> getElementsByTagName('refundedAmount') -> item(0) -> nodeValue : '';
		$SenderTransactionID = $DOM -> getElementsByTagName('senderTransactionID') -> length > 0 ? $DOM -> getElementsByTagName('senderTransactionID') -> item(0) -> nodeValue : '';

		$SenderTransactionStatus = $DOM -> getElementsByTagName('senderTransactionStatus') -> length > 0 ? $DOM -> getElementsByTagName('senderTransactionStatus') -> item(0) -> nodeValue : '';
		$TransactionID = $DOM -> getElementsByTagName('transactionId') -> length > 0 ? $DOM -> getElementsByTagName('transactionId') -> item(0) -> nodeValue : '';
		$TransactionStatus = $DOM -> getElementsByTagName('transactionStatus') -> length > 0 ? $DOM -> getElementsByTagName('transactionStatus') -> item(0) -> nodeValue : '';
		$PaymentInfo = array(
							'PendingRefund' => $PendingRefund, 
							'RefundAmount' => $RefundedAmount, 
							'SenderTransactionID' => $SenderTransactionID, 
							'SenderTransactionStatus' => $SenderTransactionStatus, 
							'TransactionID' => $TransactionID, 
							'TransactionStatus' => $TransactionStatus
							 );
		
		$PreapprovalKey = $DOM -> getElementsByTagName('preapprovalKey') -> length > 0 ? $DOM -> getElementsByTagName('preapprovalKey') -> item(0) -> nodeValue : '';
		$ReturnURL = $DOM -> getElementsByTagName('returnUrl') -> length > 0 ? $DOM -> getElementsByTagName('returnUrl') -> item(0) -> nodeValue : '';
		$ReverseAllParallelPaymentsOnError = $DOM -> getElementsByTagName('reverseAllParallelPaymentsOnError') -> length > 0 ? $DOM -> getElementsByTagName('reverseAllParallelPaymentsOnError') -> item(0) -> nodeValue : '';
		$SenderEmail = $DOM -> getElementsByTagName('senderEmail') -> length > 0 ? $DOM -> getElementsByTagName('senderEmail') -> item(0) -> nodeValue : '';
		$Status = $DOM -> getElementsByTagName('status') -> length > 0 ? $DOM -> getElementsByTagName('status') -> item(0) -> nodeValue : '';
		$TrackingID = $DOM -> getElementsByTagName('trackingId') -> length > 0 ? $DOM -> getElementsByTagName('trackingId') -> item(0) -> nodeValue : '';
		
		$Amount = $DOM -> getElementsByTagName('amount') -> length > 0 ? $DOM -> getElementsByTagName('amount') -> item(0) -> nodeValue : '';
		$Email = $DOM -> getElementsByTagName('email') -> length > 0 ? $DOM -> getElementsByTagName('email') -> item(0) -> nodeValue : '';
		$InvoiceID = $DOM -> getElementsByTagName('invoiceId') -> length > 0 ? $DOM -> getElementsByTagName('invoiceId') -> item(0) -> nodeValue : '';
		$PaymentType = $DOM -> getElementsByTagName('paymentType') -> length > 0 ? $DOM -> getElementsByTagName('paymentType') -> item(0) -> nodeValue : '';
		$Primary = $DOM -> getElementsByTagName('primary') -> length > 0 ? $DOM -> getElementsByTagName('primary') -> item(0) -> nodeValue : 'false';
		$Receiver = array(
						'Amount' => $Amount, 
						'Email' => $Email, 
						'InvoiceID' => $InvoiceID, 
						'PaymentType' => $PaymentType, 
						'Primary' => $Primary
						  );
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'ActionType' => $ActionType, 
								   'CancelURL' => $CancelURL, 
								   'CurrencyCode' => $CurrencyCode, 
								   'FeesPayer' => $FeesPayer, 
								   'FundingTypes' => $FundingTypes, 
								   'IPNNotificationURL' => $IPNNotificationURL, 
								   'Memo' => $Memo, 
								   'PayKey' => $PayKey, 
								   'PaymentInfo' => $PaymentInfo, 
								   'PreapprovalKey' => $PreapprovalKey, 
								   'ReturnURL' => $ReturnURL, 
								   'ReverseAllParallelPaymentsOnError' => $ReverseAllParallelPaymentsOnError, 
								   'SenderEmail' => $SenderEmail, 
								   'Status' => $Status, 
								   'TrackingID' => $TrackingID, 
								   'Receiver' => $Receiver, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	}
	
	
	/**
	 * Submits ExecutePayment API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function ExecutePayment($DataArray)
	{
		// ExecutePaymentFields Fields
		$ExecutePaymentFields = isset($DataArray['ExecutePaymentFields']) ? $DataArray['ExecutePaymentFields'] : array();
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<ExecutePaymentRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= isset($ExecutePaymentFields['PayKey']) && $ExecutePaymentFields['PayKey'] != '' ? '<payKey xmlns="">' . $ExecutePaymentFields['PayKey'] . '</payKey>' : '';
		$XMLRequest .= isset($ExecutePaymentFields['FundingPlanID']) && $ExecutePaymentFields['FundingPlanID'] != '' ? '<fundingPlanId xmlns="">' . $ExecutePaymentFields['FundingPlanID'] . '</fundingPlanId>' : '';
		$XMLRequest .= '</ExecutePaymentRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'ExecutePayment');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
						
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		$PaymentExecStatus = $DOM -> getElementsByTagName('paymentExecStatus') -> length > 0 ? $DOM -> getElementsByTagName('paymentExecStatus') -> item(0) -> nodeValue : '';
	
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'PaymentExecStatus' => $PaymentExecStatus, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	}
	
	
	/**
	 * Submits GetPaymentOptions API request to PayPal.
	 *
	 * @access	public
	 * @param	string	$Paykey				PayKey returned from a previous Pay request.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function GetPaymentOptions($PayKey)
	{
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<GetPaymentOptionsRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $PayKey != '' ? '<payKey xmlns="">' . $PayKey. '</payKey>' : '';
		$XMLRequest .= '</GetPaymentOptionsRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'GetPaymentOptions');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
						
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		// GetPaymentOptionsResponse Fields
		$PayKey = $DOM -> getElementsByTagName('payKey') -> length > 0 ? $DOM -> getElementsByTagName('payKey') -> item(0) -> nodeValue : '';
		$ShippingAddressID = $DOM -> getElementsByTagName('shippingAddressId') -> length > 0 ? $DOM -> getElementsByTagName('shippingAddressId') -> item(0) -> nodeValue : '';
		
		// InitiatingEntity Fields
		$InstitutionCustomer = $DOM -> getElementsByTagName('institutionCustomer') -> length > 0 ? $DOM -> getElementsByTagName('institutionCustomer') -> item(0) -> nodeValue : '';
		$CountryCode = $InstitutionCustomer != '' && $InstitutionCustomer -> getElementsByTagName('countryCode') -> length > 0 ? $InstitutionCustomer -> getElementsByTagName('countryCode') -> item(0) -> nodeValue : '';
		$DisplayName = $InstitutionCustomer != '' && $InstitutionCustomer -> getElementsByTagName('displayName') -> length > 0 ? $InstitutionCustomer -> getElementsByTagName('displayName') -> item(0) -> nodeValue : '';
		$Email = $InstitutionCustomer != '' && $InstitutionCustomer -> getElementsByTagName('email') -> length > 0 ? $InstitutionCustomer -> getElementsByTagName('email') -> item(0) -> nodeValue : '';
		$FirstName = $InstitutionCustomer != '' && $InstitutionCustomer -> getElementsByTagName('firstName') -> length > 0 ? $InstitutionCustomer -> getElementsByTagName('firstName') -> item(0) -> nodeValue : '';
		$InstitutionCustomerID = $InstitutionCustomer != '' &&  $InstitutionCustomer -> getElementsByTagName('institutionCustomerId') -> length > 0 ? $InstitutionCustomer -> getElementsByTagName('institutionCustomerId') -> item(0) -> nodeValue : '';
		$InstitutionID = $InstitutionCustomer != '' && $InstitutionCustomer -> getElementsByTagName('institutionId') -> length > 0 ? $InstitutionCustomer -> getElementsByTagName('institutionId') -> item(0) -> nodeValue : '';
		$LastName = $InstitutionCustomer != '' && $InstitutionCustomer -> getElementsByTagName('lastName') -> length > 0 ? $InstitutionCustomer -> getElementsByTagName('lastName') -> item(0) -> nodeValue : '';
		$InitiatingEntity = array(
										'CountryCode' => $CountryCode, 
										'DisplayName' => $DisplayName, 
										'Email' => $Email, 
										'FirstName' => $FirstName, 
										'InstitutionCustomerID' => $InstitutionCustomerID, 
										'InstitutionID' => $InstitutionID, 
										'LastName' => $LastName
										);	
		
		
		// DisplayOptions Fields
		$EmailHeaderImageURL = $DOM -> getElementsByTagName('emailHeaderImageUrl') -> length > 0 ? $DOM -> getElementsByTagName('emailHeaderImageUrl') -> item(0) -> nodeValue : '';
		$EmailMarketingImageURL = $DOM -> getElementsByTagName('emailMarketingImageUrl') -> length > 0 ? $DOM -> getElementsByTagName('emailMarketingImageUrl') -> item(0) -> nodeValue : '';
		$BusinessName = $DOM -> getElementsByTagName('businessName') -> length > 0 ? $DOM -> getElementsByTagName('businessName') -> item(0) -> nodeValue : '';
		$DisplayOptions = array(
								'EmailHeaderImageURL' => $EmailHeaderImageURL, 
								'EmailMarketingImageURL' => $EmailMarketingImageURL, 
								'BusinessName' => $BusinessName
								);

		// Sender Options
		$RequireShippingAddressSelection = $DOM -> getElementsByTagName('requireShippingAddressSelection') -> length > 0 ? $DOM -> getElementsByTagName('requireShippingAddressSelection') -> item(0) -> nodeValue : '';
	
		// ReceiverOptions Fields
		$ReceiverOptions = $DOM -> getElementsByTagName('receiverOptions') -> length > 0 ? $DOM -> getElementsByTagName('receiverOptions') -> item(0) -> nodeValue : '';
		$Description = $ReceiverOptions != '' && $ReceiverOptions -> getElementsByTagName('description') -> length > 0 ? $ReceiverOptions -> getElementsByTagName('description') -> item(0) -> nodeValue : '';
		$CustomID = $ReceiverOptions != '' && $ReceiverOptions -> getElementsByTagName('customId') -> length > 0 ? $ReceiverOptions -> getElementsByTagName('customId') -> item(0) -> nodeValue : '';
		$Email = $ReceiverOptions != '' && $ReceiverOptions -> getElementsByTagName('email') -> length > 0 ? $ReceiverOptions -> getElementsByTagName('email') -> item(0) -> nodeValue : '';
		$PhoneCountryCode = $ReceiverOptions != '' && $ReceiverOptions -> getElementsByTagName('countryCode') -> length > 0 ? $ReceiverOptions -> getElementsByTagName('countryCode') -> item(0) -> nodeValue : '';
		$PhoneNumber = $ReceiverOptions != '' && $ReceiverOptions -> getElementsByTagName('phoneNumber') -> length > 0 ? $ReceiverOptions -> getElementsByTagName('phoneNumber') -> item(0) -> nodeValue : '';
		$PhoneExtension = $ReceiverOptions != '' && $ReceiverOptions -> getElementsByTagName('extension') -> length > 0 ? $ReceiverOptions -> getElementsByTagName('extension') -> item(0) -> nodeValue : '';
		
		// InvoiceDataFields
		$InvoiceItems = $ReceiverOptions != '' && $ReceiverOptions -> getElementsByTagName('item') -> length > 0 ? $ReceiverOptions -> getElementsByTagName('item') : array();
		$TotalTax = $ReceiverOptions != '' && $ReceiverOptions -> getElementsByTagName('totalTax') -> length > 0 ? $ReceiverOptions -> getElementsByTagName('totalTax') -> item(0) -> nodeValue : '';
		$TotalShipping = $ReceiverOptions != '' && $ReceiverOptions -> getElementsByTagName('totalShipping') -> length > 0 ? $ReceiverOptions -> getElementsByTagName('totalShipping') -> item(0) -> nodeValue : '';
		
		$InvoiceItemsArray = array();
		foreach($InvoiceItems as $InvoiceItem)
		{
			$ItemName = $InvoiceItem -> getElementsByTagName('name') -> length > 0 ? $InvoiceItem -> getElementsByTagName('name') -> item(0) -> nodeValue : '';
			$ItemIdentifier = $InvoiceItem -> getElementsByTagName('identifier') -> length > 0 ? $InvoiceItem -> getElementsByTagName('identifier') -> item(0) -> nodeValue : '';
			$ItemSubtotal = $InvoiceItem -> getElementsByTagName('price') -> length > 0 ? $InvoiceItem -> getElementsByTagName('price') -> item(0) -> nodeValue : '';
			$ItemPrice = $InvoiceItem -> getElementsByTagName('itemPrice') -> length > 0 ? $InvoiceItem -> getElementsByTagName('itemPrice') -> item(0) -> nodeValue : '';
			$ItemCount = $InvoiceItem -> getElementsByTagName('itemCount') -> length > 0 ? $InvoiceItem -> getElementsByTagName('itemCount') -> item(0) -> nodeValue : '';
			
			$CurrentItem = array(
								'Name' => $ItemName, 
								'Identifier' => $ItemIdentifier, 
								'Subtotal' => $ItemSubtotal, 
								'Price' => $ItemPrice, 
								'ItemCount' => $ItemCount
								);
			array_push($InvoiceItemsArray,$CurrentItem);	
		}
		
		$InvoiceData = array('TotalTax' => $TotalTax, 'TotalShipping' => $TotalShipping, 'InvoiceItems' => $InvoiceItemsArray);
		
		$ReceiverOptionsFields = array(
									'Description' => $Description, 
									'CustomID' => $CustomID, 
									'Email' => $Email, 
									'PhoneCountryCode' => $PhoneCountryCode, 
									'PhoneNumber' => $PhoneNumber, 
									'PhoneExtension' => $PhoneExtension, 
									'InvoiceData' => $InvoiceData
									);
	
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'PayKey' => $PayKey, 
								   'ShippingAddressID' => $ShippingAddressID, 
								   'InitiatingEntity' => $InitiatingEntity, 
								   'DisplayOptions' => $DisplayOptions, 
								   'RequireShippingAddressSelection' => $RequireShippingAddressSelection, 
								   'ReceiverOptions' => $ReceiverOptionsFields, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	}
	
	
	/**
	 * Submits SetPaymentOptions API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function SetPaymentOptions($DataArray)
	{
		// SetPaymentOptions Basic Fields
		$SPOFields = isset($DataArray['SPOFields']) ? $DataArray['SPOFields'] : array();
		$PayKey = isset($SPOFields['PayKey']) ? $SPOFields['PayKey'] : '';
		$ShippingAddressID = isset($SPOFields['ShippingAddressID']) ? $SPOFields['ShippingAddressID'] : '';
		
		// DisplayOptions Fields
		$DisplayOptions = isset($DataArray['DisplayOptions']) ? $DataArray['DisplayOptions'] : array();
		$EmailHeaderImageURL = isset($DisplayOptions['EmailHeaderImageURL']) ? $DisplayOptions['EmailHeaderImageURL'] : '';
		$EmailMarketingImageURL = isset($DisplayOptions['EmailMarketingImageURL']) ? $DisplayOptions['EmailMarketingImageURL'] : '';
		$HeaderImageURL = isset($DisplayOptions['HeaderImageURL']) ? $DisplayOptions['HeaderImageURL'] : '';
		$BusinessName = isset($DisplayOptions['BusinessName']) ? $DisplayOptions['BusinessName'] : '';
		
		// InstitutionCustomer Fields
		$InstitutionCustomer = isset($DataArray['InstitutionCustomer']) ? $DataArray['InstitutionCustomer'] : array();
		$CountryCode = isset($InstitutionCustomer['CountryCode']) ? $InstitutionCustomer['CountryCode'] : '';
		$DisplayName = isset($InstitutionCustomer['DisplayName']) ? $InstitutionCustomer['DisplayName'] : '';
		$InstitutionCustomerEmail = isset($InstitutionCustomer['Email']) ? $InstitutionCustomer['Email'] : '';
		$FirstName = isset($InstitutionCustomer['FirstName']) ? $InstitutionCustomer['FirstName'] : '';
		$LastName = isset($InstitutionCustomer['LastName']) ? $InstitutionCustomer['LastName'] : '';
		$InstitutionCustomerID = isset($InstitutionCustomer['InstitutionCustomerID']) ? $InstitutionCustomer['InstitutionCustomerID'] : '';
		$InstitutionID = isset($InstitutionCustomer['InstitutionID']) ? $InstitutionCustomer['InstitutionID'] : '';
		
		// SenderOptions Fields
		$SenderOptions = isset($DataArray['SenderOptions']) ? $DataArray['SenderOptions'] : array();
		$RequireShippingAddressSelection = isset($SenderOptions['RequireShippingAddressSelection']) ? $SenderOptions['RequireShippingAddressSelection'] : '';
		$ReferrerCode = $this->APIButtonSource;
		
		// ReceiverOptions Fields
		$ReceiverOptionsXML = '';
		$ReceiverOptions = isset($DataArray['ReceiverOptions']) ? $DataArray['ReceiverOptions'] : array();
		foreach($ReceiverOptions as $ReceiverOption)
		{
			$Description = isset($ReceiverOption['Description']) ? $ReceiverOption['Description'] : '';
			$CustomID = isset($ReceiverOption['CustomID']) ? $ReceiverOption['CustomID'] : '';
			
			// Invoice Data
			$InvoiceData = isset($ReceiverOption['InvoiceData']) ? $ReceiverOption['InvoiceData'] : array();
			$TotalTax = isset($InvoiceData['TotalTax']) ? $InvoiceData['TotalTax'] : '';
			$TotalShipping = isset($InvoiceData['TotalShipping']) ? $InvoiceData['TotalShipping'] : '';
			
			// InvoiceItem Fields
			$InvoiceItems = isset($ReceiverOption['InvoiceItems']) ? $ReceiverOption['InvoiceItems'] : array();
			
			// ReceiverIdentifer Fields
			$ReceiverIdentifier = isset($ReceiverOption['ReceiverIdentifier']) ? $ReceiverOption['ReceiverIdentifier'] : array();
			$ReceiverIdentifierEmail = isset($ReceiverIdentifier['Email']) ? $ReceiverIdentifier['Email'] : '';
			$ReceiverIdentifierAccountID = isset($ReceiverIdentifier['AccountID']) ? $ReceiverIdentifier['AccountID'] : '';
			$PhoneCountryCode = isset($ReceiverIdentifier['PhoneCountryCode']) ? $ReceiverIdentifier['PhoneCountryCode'] : '';
			$PhoneNumber = isset($ReceiverIdentifier['PhoneNumber']) ? $ReceiverIdentifier['PhoneNumber'] : '';
			$PhoneExtension = isset($ReceiverIdentifier['PhoneExtension']) ? $ReceiverIdentifier['PhoneExtension'] : '';
			
			
				$ReceiverOptionsXML .= '<receiverOptions xmlns="">';
				$ReceiverOptionsXML .= $Description != '' ? '<description xmlns="">'.$Description.'</description>' : '';
				$ReceiverOptionsXML .= $CustomID != '' ? '<customId xmlns="">'.$CustomID.'</customId>' : '';
					
				if(!empty($InvoiceData))
				{
					$ReceiverOptionsXML .= '<invoiceData xmlns="">';
					$ReceiverOptionsXML .= $TotalTax != '' ? '<totalTax xmlns="">'.$TotalTax.'</totalTax>' : '';
					$ReceiverOptionsXML .= $TotalShipping != '' ? '<totalShipping xmlns="">'.$TotalShipping.'</totalShipping>' : '';
			
			
					foreach($InvoiceItems as $InvoiceItem)
					{
						$ReceiverOptionsXML .= '<item xmlns="">';
						$ReceiverOptionsXML .= $InvoiceItem['Name'] != '' ? '<name xmlns="">'.$InvoiceItem['Name'].'</name>' : '';
						$ReceiverOptionsXML .= $InvoiceItem['Identifier'] != '' ? '<identifier xmlns="">'.$InvoiceItem['Identifier'].'</identifier>' : '';
						$ReceiverOptionsXML .= $InvoiceItem['Price'] != '' ? '<price xmlns="">'.$InvoiceItem['Price'].'</price>' : '';
						$ReceiverOptionsXML .= $InvoiceItem['ItemPrice'] != '' ? '<itemPrice xmlns="">'.$InvoiceItem['ItemPrice'].'</itemPrice>' : '';
						$ReceiverOptionsXML .= $InvoiceItem['ItemCount'] != '' ? '<itemCount xmlns="">'.$InvoiceItem['ItemCount'].'</itemCount>' : '';
						$ReceiverOptionsXML .= '</item>';
					}
			
					$ReceiverOptionsXML .= '</invoiceData>';
				}
					
				if(count($ReceiverIdentifier) > 0)
				{
					$ReceiverOptionsXML .= '<receiver xmlns="">';
					$ReceiverOptionsXML .= $ReceiverIdentifierEmail != '' ? '<email xmlns="">'.$ReceiverIdentifierEmail.'</email>' : '';
					$ReceiverOptionsXML .= $ReceiverIdentifierAccountID != '' ? '<accountId xmlns="">'.$ReceiverIdentifierAccountID.'</accountId>' : '';
			
					if($PhoneNumber != '')
					{
						$ReceiverOptionsXML .= '<phone xmlns="">';
						$ReceiverOptionsXML .= $PhoneCountryCode != '' ? '<countryCode xmlns="">'.$PhoneCountryCode.'</countryCode>' : '';
						$ReceiverOptionsXML .= $PhoneNumber != '' ? '<phoneNumber xmlns="">'.$PhoneNumber.'</phoneNumber>' : '';
						$ReceiverOptionsXML .= $PhoneExtension != '' ? '<extension xmlns="">'.$PhoneExtension.'</extension>' : '';
						$ReceiverOptionsXML .= '</phone>';
					}
			
					$ReceiverOptionsXML .= '</receiver>';
				}
					
				$ReceiverOptionsXML .= '</receiverOptions>';
		}

		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<SetPaymentOptionsRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $PayKey != '' ? '<payKey xmlns="">'.$PayKey.'</payKey>' : '';
		$XMLRequest .= $ShippingAddressID != '' ? '<shippingAddressId xmlns="">'.$ShippingAddressID.'</shippingAddressId>' : '';
		
		if(count($InstitutionCustomer) > 0)
		{
			$XMLRequest .= '<initiatingEntity xmlns="">';
			$XMLRequest .= '<institutionCustomer xmlns="">';
			$XMLRequest .= $InstitutionID != '' ? '<institutionId xmlns="">'.$InstitutionID.'</institutionId>' : '';
			$XMLRequest .= $FirstName != '' ? '<firstName xmlns="">'.$FirstName.'</firstName>' : '';
			$XMLRequest .= $LastName != '' ? '<lastName xmlns="">'.$LastName.'</lastName>' : '';
			$XMLRequest .= $DisplayName != '' ? '<displayName xmlns="">'.$DisplayName.'</displayName>' : '';
			$XMLRequest .= $InstitutionCustomerID != '' ? '<institutionCustomerId xmlns="">'.$InstitutionCustomerID.'</institutionCustomerId>' : '';
			$XMLRequest .= $CountryCode != '' ? '<countryCode xmlns="">'.$CountryCode.'</countryCode>' : '';
			$XMLRequest .= $InstitutionCustomerEmail != '' ? '<email xmlns="">'.$InstitutionCustomerEmail.'</email>' : '';
			$XMLRequest .= '</institutionCustomer>';
			$XMLRequest .= '</initiatingEntity>';	
		}
		
		if(count($DisplayOptions) > 0)
		{
			$XMLRequest .= '<displayOptions xmlns="">';
			$XMLRequest .= $EmailHeaderImageURL != '' ? '<emailHeaderImageUrl xmlns="">'.$EmailHeaderImageURL.'</emailHeaderImageUrl>' : '';
			$XMLRequest .= $EmailMarketingImageURL != '' ? '<emailMarketingImageUrl xmlns="">'.$EmailMarketingImageURL.'</emailMarketingImageUrl>' : '';
			$XMLRequest .= $HeaderImageURL != '' ? '<headerImageUrl xmlns="">'.$HeaderImageURL.'</headerImageUrl>' : '';
			$XMLRequest .= $BusinessName != '' ? '<businessName xmlns="">'.$BusinessName.'</businessName>' : '';
			$XMLRequest .= '</displayOptions>';	
		}
		
		if(count($SenderOptions) > 0)
		{
			$XMLRequest .= '<senderOptions xmlns="">';
			$XMLRequest .= $RequireShippingAddressSelection != '' ? '<requireShippingAddressSelection xmlns="">'.$RequireShippingAddressSelection.'</requireShippingAddressSelection>' : '';
			$XMLRequest .= $ReferrerCode != '' ? '<referrerCode xmlns="">'.$ReferrerCode.'</referrerCode>' : '';
			$XMLRequest .= '</senderOptions>';	
		}
		
		$XMLRequest .= $ReceiverOptionsXML;
		$XMLRequest .= '</SetPaymentOptionsRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'SetPaymentOptions');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	}
	
	/**
	 * Submits Preapproval API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function Preapproval($DataArray)
	{	
		$PreapprovalFields = isset($DataArray['PreapprovalFields']) ? $DataArray['PreapprovalFields'] : array();
		$CancelURL = isset($PreapprovalFields['CancelURL']) ? $PreapprovalFields['CancelURL'] : '';
		$CurrencyCode = isset($PreapprovalFields['CurrencyCode']) ? $PreapprovalFields['CurrencyCode'] : '';
		$DateOfMonth = isset($PreapprovalFields['DateOfMonth']) ? $PreapprovalFields['DateOfMonth'] : '';
		$DayOfWeek = isset($PreapprovalFields['DayOfWeek']) ? $PreapprovalFields['DayOfWeek'] : '';
		$EndingDate = isset($PreapprovalFields['EndingDate']) ? $PreapprovalFields['EndingDate'] : '';
		$IPNNotificationURL = isset($PreapprovalFields['IPNNotificationURL']) ? $PreapprovalFields['IPNNotificationURL'] : '';
		$MaxAmountPerPayment = isset($PreapprovalFields['MaxAmountPerPayment']) ? $PreapprovalFields['MaxAmountPerPayment'] : '';
		$MaxNumberOfPayments = isset($PreapprovalFields['MaxNumberOfPayments']) ? $PreapprovalFields['MaxNumberOfPayments'] : '';
		$MaxNumberOfPaymentsPerPeriod = isset($PreapprovalFields['MaxNumberOfPaymentsPerPeriod']) ? $PreapprovalFields['MaxNumberOfPaymentsPerPeriod'] : '';
		$MaxTotalAmountOfAllPayments = isset($PreapprovalFields['MaxTotalAmountOfAllPayments']) ? $PreapprovalFields['MaxTotalAmountOfAllPayments'] : '';
		$Memo = isset($PreapprovalFields['Memo']) ? $PreapprovalFields['Memo'] : '';
		$PaymentPeriod = isset($PreapprovalFields['PaymentPeriod']) ? $PreapprovalFields['PaymentPeriod'] : '';
		$PinType = isset($PreapprovalFields['PinType']) ? $PreapprovalFields['PinType'] : '';
		$ReturnURL = isset($PreapprovalFields['ReturnURL']) ? $PreapprovalFields['ReturnURL'] : '';
		$SenderEmail = isset($PreapprovalFields['SenderEmail']) ? $PreapprovalFields['SenderEmail'] : '';
		$StartingDate = isset($PreapprovalFields['StartingDate']) ? $PreapprovalFields['StartingDate'] : '';
		$FeesPayer = isset($PreapprovalFields['FeesPayer']) ? $PreapprovalFields['FeesPayer'] : '';
		$DisplayMaxTotalAmount = isset($PreapprovalFields['DisplayMaxTotalAmount']) ? $PreapprovalFields['DisplayMaxTotalAmount'] : '';
        $RequireInstantFundingSource = isset($PreapprovalFields['RequireInstantFundingSource']) ? $PreapprovalFields['RequireInstantFundingSource'] : '';

        $ClientDetailsFields = isset($DataArray['ClientDetailsFields']) ? $DataArray['ClientDetailsFields'] : array();
		$CustomerID = isset($ClientDetailsFields['CustomerID']) ? $ClientDetailsFields['CustomerID'] : '';
		$CustomerType = isset($ClientDetailsFields['CustomerType']) ? $ClientDetailsFields['CustomerType'] : '';
		$GeoLocation = isset($ClientDetailsFields['GeoLocation']) ? $ClientDetailsFields['GeoLocation'] : '';
		$Model = isset($ClientDetailsFields['Model']) ? $ClientDetailsFields['Model'] : '';
		$PartnerName = isset($ClientDetailsFields['PartnerName']) ? $ClientDetailsFields['PartnerName'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<PreapprovalRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= '<cancelUrl xmlns="">' . $CancelURL . '</cancelUrl>';
		
		$XMLRequest .= '<clientDetails xmlns="">';
		$XMLRequest .= $this -> ApplicationID != '' ? '<applicationId xmlns="">' . $this -> ApplicationID . '</applicationId>' : '';
		$XMLRequest .= $CustomerID != '' ? '<customerId xmlns="">' . $CustomerID . '</customerId>' : '';
		$XMLRequest .= $CustomerType != '' ? '<customerType xmlns="">' . $CustomerType . '</customerType>' : '';
		$XMLRequest .= $this -> DeviceID != '' ? '<deviceId xmlns="">' . $this -> DeviceID . '</deviceId>' : '';
		$XMLRequest .= $GeoLocation != '' ? '<geoLocation xmlns="">' . $GeoLocation . '</geoLocation>' : '';
		$XMLRequest .= $this -> IPAddress != '' ? '<ipAddress xmlns="">' . $this -> IPAddress . '</ipAddress>' : '';
		$XMLRequest .= $Model != '' ? '<model xmlns="">' . $Model . '</model>' : '';
		$XMLRequest .= $PartnerName != '' ? '<partnerName xmlns="">' . $PartnerName . '</partnerName>' : '';
		$XMLRequest .= '</clientDetails>';
		
		$XMLRequest .= '<currencyCode xmlns="">' . $CurrencyCode . '</currencyCode>';
		$XMLRequest .= $DateOfMonth != '' ? '<dateOfMonth xmlns="">' . $DateOfMonth . '</dateOfMonth>' : '';
		$XMLRequest .= $DayOfWeek != '' ? '<dayOfWeek xmlns="">' . $DayOfWeek . '</dayOfWeek>' : '';
		$XMLRequest .= $EndingDate != '' ? '<endingDate xmlns="">' . $EndingDate . '</endingDate>' : '';
		$XMLRequest .= $IPNNotificationURL != '' ? '<ipnNotificationUrl xmlns="">' . $IPNNotificationURL . '</ipnNotificationUrl>' : '';
		$XMLRequest .= $MaxAmountPerPayment != '' ? '<maxAmountPerPayment xmlns="">' . $MaxAmountPerPayment . '</maxAmountPerPayment>' : '';
		$XMLRequest .= $MaxNumberOfPayments != '' ? '<maxNumberOfPayments xmlns="">' . $MaxNumberOfPayments . '</maxNumberOfPayments>' : '';
		$XMLRequest .= $MaxNumberOfPaymentsPerPeriod != '' ? '<maxNumberOfPaymentsPerPeriod xmlns="">' . $MaxNumberOfPaymentsPerPeriod . '</maxNumberOfPaymentsPerPeriod>' : '';
		$XMLRequest .= $MaxTotalAmountOfAllPayments != '' ? '<maxTotalAmountOfAllPayments xmlns="">' . $MaxTotalAmountOfAllPayments . '</maxTotalAmountOfAllPayments>' : '';
		$XMLRequest .= $Memo != '' ? '<memo xmlns="">' . $Memo . '</memo>' : '';
		$XMLRequest .= $PaymentPeriod != '' ? '<paymentPeriod xmlns="">' . $PaymentPeriod . '</paymentPeriod>' : '';
		$XMLRequest .= $PinType != '' ? '<pinType xmlns="">' . $PinType . '</pinType>' : '';
		$XMLRequest .= $FeesPayer != '' ? '<feesPayer xmlns="">' . $FeesPayer . '</feesPayer>' : '';
        $XMLRequest .= $RequireInstantFundingSource != '' ? '<requireInstantFundingSource xmlns="">' . $RequireInstantFundingSource . '</requireInstantFundingSource>' : '';
        $XMLRequest .= $DisplayMaxTotalAmount != '' ? '<displayMaxTotalAmount xmlns="">' . $DisplayMaxTotalAmount . '</displayMaxTotalAmount>' : '';
		$XMLRequest .= $ReturnURL != '' ? '<returnUrl xmlns="">' . $ReturnURL . '</returnUrl>' : '';
		$XMLRequest .= $SenderEmail != '' ? '<senderEmail xmlns="">' . $SenderEmail . '</senderEmail>' : '';
		$XMLRequest .= $StartingDate != '' ? '<startingDate xmlns="">' . $StartingDate . '</startingDate>' : '';
		$XMLRequest .= '</PreapprovalRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'Preapproval');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
						
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		$PreapprovalKey = $DOM -> getElementsByTagName('preapprovalKey') -> length > 0 ? $DOM -> getElementsByTagName('preapprovalKey') -> item(0) -> nodeValue: '';
		
		if($this -> Sandbox)
		{
			$RedirectURL = 'https://www.sandbox.paypal.com/webscr?cmd=_ap-preapproval&preapprovalkey=' . $PreapprovalKey;
		}
		else
		{
			$RedirectURL = 'https://www.paypal.com/webscr?cmd=_ap-preapproval&preapprovalkey=' . $PreapprovalKey;
		}
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'PreapprovalKey' => $PreapprovalKey, 
								   'RedirectURL' => $PreapprovalKey != '' ? $RedirectURL : '', 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	}
	
	/**
	 * Submit PreapprovalDetails API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function PreapprovalDetails($DataArray)
	{
		$PreapprovalDetailsFields = isset($DataArray['PreapprovalDetailsFields']) ? $DataArray['PreapprovalDetailsFields'] : array();
		$GetBillingAddress = isset($PreapprovalDetailsFields['GetBillingAddress']) ? $PreapprovalDetailsFields['GetBillingAddress'] : '';
		$PreapprovalKey = isset($PreapprovalDetailsFields['PreapprovalKey']) ? $PreapprovalDetailsFields['PreapprovalKey'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<PreapprovalDetailsRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $GetBillingAddress != '' ? '<getBillingAddress xmlns="">' . $GetBillingAddress . '</getBillingAddress>' : '';
		$XMLRequest .= $PreapprovalKey != '' ? '<preapprovalKey xmlns="">' . $PreapprovalKey . '</preapprovalKey>' : '';
		$XMLRequest .= '</PreapprovalDetailsRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'PreapprovalDetails');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
						
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$Approved = $DOM -> getElementsByTagName('approved') -> length > 0 ? $DOM -> getElementsByTagName('approved') -> item(0) -> nodeValue : '';
		$CancelURL = $DOM -> getElementsByTagName('cancelUrl') -> length > 0 ? $DOM -> getElementsByTagName('cancelUrl') -> item(0) -> nodeValue : '';
		$CurPayments = $DOM -> getElementsByTagName('curPayments') -> length > 0 ? $DOM -> getElementsByTagName('curPayments') -> item(0) -> nodeValue : '';
		$CurPaymentsAmount = $DOM -> getElementsByTagName('curPaymentsAmount') -> length > 0 ? $DOM -> getElementsByTagName('curPaymentsAmount') -> item(0) -> nodeValue : '';
		$CurPeriodAttempts = $DOM -> getElementsByTagName('curPeriodAttempts') -> length > 0 ? $DOM -> getElementsByTagName('curPeriodAttempts') -> item(0) -> nodeValue : '';
		$CurPeriodEndingDate = $DOM -> getElementsByTagName('curPeriodEndingDate') -> length > 0 ? $DOM -> getElementsByTagName('curPeriodEndingDate') -> item(0) -> nodeValue : '';
		$CurrencyCode = $DOM -> getElementsByTagName('currencyCode') -> length > 0 ? $DOM -> getElementsByTagName('currencyCode') -> item(0) -> nodeValue : '';
		$DateOfMonth = $DOM -> getElementsByTagName('dateOfMonth') -> length > 0 ? $DOM -> getElementsByTagName('dateOfMonth') -> item(0) -> nodeValue : '';
		$DayOfWeek = $DOM -> getElementsByTagName('dayOfWeek') -> length > 0 ? $DOM -> getElementsByTagName('dayOfWeek') -> item(0) -> nodeValue : '';
		$EndingDate = $DOM -> getElementsByTagName('endingDate') -> length > 0 ? $DOM -> getElementsByTagName('endingDate') -> item(0) -> nodeValue : '';
		$IPNNotificationURL = $DOM -> getElementsByTagName('ipnNotificationUrl') -> length > 0 ? $DOM -> getElementsByTagName('ipnNotificationUrl') -> item(0) -> nodeValue : '';
		$MaxAmountPerPayment = $DOM -> getElementsByTagName('maxAmountPerPayment') -> length > 0 ? $DOM -> getElementsByTagName('maxAmountPerPayment') -> item(0) -> nodeValue : '';
		$MaxNumberOfPayments = $DOM -> getElementsByTagName('maxNumberOfPayments') -> length > 0 ? $DOM -> getElementsByTagName('maxNumberOfPayments') -> item(0) -> nodeValue : '';
		$MaxNumberOfPaymentsPerPeriod = $DOM -> getElementsByTagName('maxNumberOfPaymentsPerPeriod') -> length > 0 ? $DOM -> getElementsByTagName('maxNumberOfPaymentsPerPeriod') -> item(0) -> nodeValue : '';
		$MaxTotalAmountOfAllPayments = $DOM -> getElementsByTagName('maxTotalAmountOfAllPayments') -> length > 0 ? $DOM -> getElementsByTagName('maxTotalAmountOfAllPayments') -> item(0) -> nodeValue : '';
		$Memo = $DOM -> getElementsByTagName('memo') -> length > 0 ? $DOM -> getElementsByTagName('memo') -> item(0) -> nodeValue : '';
		$PaymentPeriod = $DOM -> getElementsByTagName('paymentPeriod') -> length > 0 ? $DOM -> getElementsByTagName('paymentPeriod') -> item(0) -> nodeValue : '';
		$PinType = $DOM -> getElementsByTagName('pinType') -> length > 0 ? $DOM -> getElementsByTagName('pinType') -> item(0) -> nodeValue : '';
		$ReturnUrl = $DOM -> getElementsByTagName('returnUrl') -> length > 0 ? $DOM -> getElementsByTagName('returnUrl') -> item(0) -> nodeValue : '';
		$SenderEmail = $DOM -> getElementsByTagName('senderEmail') -> length > 0 ? $DOM -> getElementsByTagName('senderEmail') -> item(0) -> nodeValue : '';
		$StartingDate = $DOM -> getElementsByTagName('startingDate') -> length > 0 ? $DOM -> getElementsByTagName('startingDate') -> item(0) -> nodeValue : '';
		$Status = $DOM -> getElementsByTagName('status') -> length > 0 ? $DOM -> getElementsByTagName('status') -> item(0) -> nodeValue : '';
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'Approved' => $Approved, 
								   'CancelURL' => $CancelURL, 
								   'CurPayments' => $CurPayments, 
								   'CurPaymentsAmount' => $CurPaymentsAmount, 
								   'CurPeriodAttempts' => $CurPeriodAttempts, 
								   'CurPeriodEndingDate' => $CurPeriodEndingDate, 
								   'CurrencyCode' => $CurrencyCode, 
								   'DateOfMonth' => $DateOfMonth, 
								   'DayOfWeek' => $DayOfWeek, 
								   'EndingDate' => $EndingDate, 
								   'IPNNotificationURL' => $IPNNotificationURL, 
								   'MaxAmountPerPayment' => $MaxAmountPerPayment, 
								   'MaxNumberOfPayments' => $MaxNumberOfPayments, 
								   'MaxNumberOfPaymentsPerPeriod' => $MaxNumberOfPaymentsPerPeriod, 
								   'MaxTotalAmountOfAllPayments' => $MaxTotalAmountOfAllPayments, 
								   'Memo' => $Memo, 
								   'PaymentPeriod' => $PaymentPeriod, 
								   'PinType' => $PinType, 
								   'ReturnUrl' => $ReturnUrl, 
								   'SenderEmail' => $SenderEmail, 
								   'StartingDate' => $StartingDate, 
								   'Status' => $Status, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	}
	
	/**
	 * Submit CancelPreapproval API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function CancelPreapproval($DataArray)
	{
		$CancelPreapprovalFields = isset($DataArray['CancelPreapprovalFields']) ? $DataArray['CancelPreapprovalFields'] : array();
		$PreapprovalKey = isset($CancelPreapprovalFields['PreapprovalKey']) ? $CancelPreapprovalFields['PreapprovalKey'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<CancelPreapprovalRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $PreapprovalKey != '' ? '<preapprovalKey xmlns="">' . $PreapprovalKey . '</preapprovalKey>' : '';
		$XMLRequest .= '</CancelPreapprovalRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'CancelPreapproval');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
						
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	}
	
	/**
	 * Submit Refund API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function Refund($DataArray)
	{
		$RefundFields = isset($DataArray['RefundFields']) ? $DataArray['RefundFields'] : array();
		$CurrencyCode = isset($RefundFields['CurrencyCode']) ? $RefundFields['CurrencyCode'] : '';
		$PayKey = isset($RefundFields['PayKey']) ? $RefundFields['PayKey'] : '';
		$TransactionID = isset($RefundFields['TransactionID']) ? $RefundFields['TransactionID'] : '';
		$TrackingID = isset($RefundFields['TrackingID']) ? $RefundFields['TrackingID'] : '';
		
		$Receivers = isset($DataArray['Receivers']) ? $DataArray['Receivers'] : array();
		$Amount = isset($Receivers['Amount']) ? $Receivers['Amount'] : '';
		$Email = isset($Receivers['Email']) ? $Receivers['Email'] : '';
		$InvoiceID = isset($Receivers['InvoiceID']) ? $Receivers['InvoiceID'] : '';
		$Primary = isset($Receivers['Primary']) ? $Receivers['Primary'] : '';
		$PaymentType = isset($Receivers['PaymentType']) ? $Receivers['PaymentType'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<RefundRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $CurrencyCode != '' ? '<currencyCode xmlns="">' . $CurrencyCode . '</currencyCode>' : '';
		$XMLRequest .= $PayKey != '' ? '<payKey xmlns="">' . $PayKey . '</payKey>' : '';
		
		if(!empty($Receivers))
		{
			$XMLRequest .= '<receiverList xmlns="">';
			foreach($Receivers as $Receiver)
			{
				$XMLRequest .= '<receiver xmlns="">';
				$XMLRequest .= $Receiver['Amount'] != '' ? '<amount xmlns="">' . $Receiver['Amount'] . '</amount>' : '';
				$XMLRequest .= $Receiver['Email'] != '' ? '<email xmlns="">' . $Receiver['Email'] . '</email>' : '';
				$XMLRequest .= $Receiver['InvoiceID'] != '' ? '<invoiceId xmlns="">' . $Receiver['InvoiceID'] . '</invoiceId>' : '';
				$XMLRequest .= $Receiver['PaymentType'] != '' ? '<paymentType xmlns="">' . $Receiver['PaymentType'] . '</paymentType>' : '';
				$XMLRequest .= '</receiver>';
			}
			$XMLRequest .= '</receiverList>';
		}
		
		$XMLRequest .= $TransactionID != '' ? '<transactionId xmlns="">' . $TransactionID . '</transactionId>' : '';
		$XMLRequest .= $TrackingID != '' ? '<trackingId xmlns="">' . $TrackingID . '</trackingId>' : '';
		$XMLRequest .= '</RefundRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'Refund');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
						
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$EncryptedTransactionID = $DOM -> getElementsByTagName('encryptedRefundTransactionId') -> length > 0 ? $DOM -> getElementsByTagName('encryptedRefundTransactionId') -> item(0) -> nodeValue : '';
		$RefundFeeAmount = $DOM -> getElementsByTagName('refundFeeAmount') -> length > 0 ? $DOM -> getElementsByTagName('refundFeeAmount') -> item(0) -> nodeValue : '';
		$RefundGrossAmount = $DOM -> getElementsByTagName('refundGrossAmount') -> length > 0 ? $DOM -> getElementsByTagName('refundGrossAmount') -> item(0) -> nodeValue : '';
		$RefundHasBecomeFull = $DOM -> getElementsByTagName('refundHasBecomeFull') -> length > 0 ? $DOM -> getElementsByTagName('refundHasBecomeFull') -> item(0) -> nodeValue : '';
		$RefundNetAmount = $DOM -> getElementsByTagName('refundNetAmount') -> length > 0 ? $DOM -> getElementsByTagName('refundNetAmount') -> item(0) -> nodeValue : '';
		$RefundStatus = $DOM -> getElementsByTagName('refundStatus') -> length > 0 ? $DOM -> getElementsByTagName('refundStatus') -> item(0) -> nodeValue : '';
		$RefundTransactionStatus = $DOM -> getElementsByTagName('refundTransactionStatus') -> length > 0 ? $DOM -> getElementsByTagName('refundTransactionStatus') -> item(0) -> nodeValue : '';
		$TotalOfAllRefunds = $DOM -> getElementsByTagName('totalOfAllRefunds') -> length > 0 ? $DOM -> getElementsByTagName('totalOfAllRefunds') -> item(0) -> nodeValue : '';
		
		$Amount = $DOM -> getElementsByTagName('amount') -> length > 0 ? $DOM -> getElementsByTagName('amount') -> item(0) -> nodeValue : '';
		$Email = $DOM -> getElementsByTagName('email') -> length > 0 ? $DOM -> getElementsByTagName('email') -> item(0) -> nodeValue : '';
		$InvoiceID = $DOM -> getElementsByTagName('invoiceId') -> length > 0 ? $DOM -> getElementsByTagName('invoiceId') -> item(0) -> nodeValue : '';
		$PaymentType = $DOM -> getElementsByTagName('paymentType') -> length > 0 ? $DOM -> getElementsByTagName('paymentType') -> item(0) -> nodeValue : '';
		$Primary = $DOM -> getElementsByTagName('primary') -> length > 0 ? $DOM -> getElementsByTagName('primary') -> item(0) -> nodeValue : '';
		$Receiver = array(
						'Amount' => $Amount, 
						'Email' => $Email, 
						'InvoiceID' => $InvoiceID, 
						'PaymentType' => $PaymentType, 
						'Primary' => $Primary
						  );
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'EncryptedTransactionID' => $EncryptedTransactionID, 
								   'RefundFeeAmount' => $RefundFeeAmount, 
								   'RefundGrossAmount' => $RefundGrossAmount, 
								   'RefundHasBecomeFull' => $RefundHasBecomeFull, 
								   'RefundNetAmount' => $RefundNetAmount, 
								   'RefundStatus' => $RefundStatus, 
								   'RefundTransactionStatus' => $RefundTransactionStatus, 
								   'TotalOfAllRefunds' => $TotalOfAllRefunds, 
								   'Receiver' => $Receiver, 
								   'RawRequest' => $XMLRequest, 
								   'RawResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	}
	
	/**
	 * Submit ConvertCurrency API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function ConvertCurrency($DataArray)
	{
		$BaseAmountList = isset($DataArray['BaseAmountList']) ? $DataArray['BaseAmountList'] : array();
		$ConvertToCurrencyList = isset($DataArray['ConvertToCurrencyList']) ? $DataArray['ConvertToCurrencyList'] : array();
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<ConvertCurrencyRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= '<baseAmountList xmlns="">';
		foreach($BaseAmountList as $BaseAmount)
		{
			$XMLRequest .= '<currency xmlns="">';
			$XMLRequest .= '<code xmlns="">' . $BaseAmount['Code'] . '</code>';
			$XMLRequest .= '<amount xmlns="">' . $BaseAmount['Amount'] . '</amount>';
			$XMLRequest .= '</currency>';
		}
		$XMLRequest .= '</baseAmountList>';
		$XMLRequest .= '<convertToCurrencyList xmlns="">';
		
		foreach($ConvertToCurrencyList as $CurrencyCode)
		{
			$XMLRequest .= '<currencyCode xmlns="">' . $CurrencyCode . '</currencyCode>';
		}
		
		$XMLRequest .= '</convertToCurrencyList>';
		$XMLRequest .= '</ConvertCurrencyRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'ConvertCurrency');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
						
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$CurrencyConversionListArray = array();
		$CurrencyConversionListDOM = $DOM -> getElementsByTagName('currencyConversionList') -> length > 0 ? $DOM -> getElementsByTagName('currencyConversionList') : array();
		
		foreach($CurrencyConversionListDOM as $CurrencyConversionList)
		{
			$BaseAmountDOM = $CurrencyConversionList -> getElementsByTagName('baseAmount') -> length > 0 ? $CurrencyConversionList -> getElementsByTagName('baseAmount') : array();		
			foreach($BaseAmountDOM as $BaseAmount)
			{
				$BaseAmountCurrencyCode = $BaseAmount -> getElementsByTagName('code') -> length > 0 ? $BaseAmount -> getElementsByTagName('code') -> item(0) -> nodeValue : '';
				$BaseAmountValue = $BaseAmount -> getElementsByTagName('amount') -> length > 0 ? $BaseAmount -> getElementsByTagName('amount') -> item(0) -> nodeValue : '';
				$BaseAmountArray = array(
										 'Code' => $BaseAmountCurrencyCode, 
										 'Amount' => $BaseAmountValue
										 );
			}
			
			$CurrencyListArray = array();
			$CurrencyListDOM = $CurrencyConversionList -> getElementsByTagName('currency') -> length > 0 ? $CurrencyConversionList -> getElementsByTagName('currency') : array();
			foreach($CurrencyListDOM as $CurrencyList)
			{
				$ListCurrencyCode = $CurrencyList -> getElementsByTagName('code') -> length > 0 ? $CurrencyList -> getElementsByTagName('code') -> item(0) -> nodeValue : '';
				$ListCurrencyAmount = $CurrencyList -> getElementsByTagName('amount') -> length > 0 ? $CurrencyList -> getElementsByTagName('amount') -> item(0) -> nodeValue : '';
				$ListCurrencyCurrent = array(
											 'Code' => $ListCurrencyCode, 
											 'Amount' => $ListCurrencyAmount
											 );
				array_push($CurrencyListArray, $ListCurrencyCurrent);
			}
			
			$CurrencyConversionListCurrent = array(
												   'BaseAmount' => $BaseAmountArray, 
												   'CurrencyList' => $CurrencyListArray
												   );
			
			array_push($CurrencyConversionListArray, $CurrencyConversionListCurrent);
		}
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'CurrencyConversionList' => $CurrencyConversionListArray, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	}
	
	/**
	 * Submit CreateAccount API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	function CreateAccount($DataArray)
	{
		$CreateAccountFields = isset($DataArray['CreateAccountFields']) ? $DataArray['CreateAccountFields'] : array();
		$AccountType = isset($CreateAccountFields['AccountType']) ? $CreateAccountFields['AccountType'] : '';
		$CitizenshipCountryCode = isset($CreateAccountFields['CitizenshipCountryCode']) ? $CreateAccountFields['CitizenshipCountryCode'] : '';
		$ContactPhoneNumber = isset($CreateAccountFields['ContactPhoneNumber']) ? $CreateAccountFields['ContactPhoneNumber'] : '';
		$HomePhoneNumber = isset($CreateAccountFields['HomePhoneNumber']) ? $CreateAccountFields['HomePhoneNumber'] : '';
		$MobilePhoneNumber = isset($CreateAccountFields['MobilePhoneNumber']) ? $CreateAccountFields['MobilePhoneNumber'] : '';
		$CurrencyCode = isset($CreateAccountFields['CurrencyCode']) ? $CreateAccountFields['CurrencyCode'] : '';
		$DateOfBirth = isset($CreateAccountFields['DateOfBirth']) ? $CreateAccountFields['DateOfBirth'] : '';
		$EmailAddress = isset($CreateAccountFields['EmailAddress']) ? $CreateAccountFields['EmailAddress'] : '';
		$FunctionalArea = isset($CreateAccountFields['FunctionalArea']) ? $CreateAccountFields['FunctionalArea'] : '';
		$Salutation = isset($CreateAccountFields['Salutation']) ? $CreateAccountFields['Salutation'] : '';
		$FirstName = isset($CreateAccountFields['FirstName']) ? $CreateAccountFields['FirstName'] : '';
		$MiddleName = isset($CreateAccountFields['MiddleName']) ? $CreateAccountFields['MiddleName'] : '';
		$LastName = isset($CreateAccountFields['LastName']) ? $CreateAccountFields['LastName'] : '';
		$Suffix = isset($CreateAccountFields['Suffix']) ? $CreateAccountFields['Suffix'] : '';
		$NotificationURL = isset($CreateAccountFields['NotificationURL']) ? $CreateAccountFields['NotificationURL'] : '';
		$Occupation = isset($CreateAccountFields['Occupation']) ? $CreateAccountFields['Occupation'] : '';
		$PerformExtraVettingOnThisAccount  = isset($CreateAccountFields['PerformExtraVettingOnThisAccount']) ? $CreateAccountFields['PerformExtraVettingOnThisAccount'] : '';
		$PurposeOfAccount  = isset($CreateAccountFields['PurposeOfAccount']) ? $CreateAccountFields['PurposeOfAccount'] : '';
		$Profession  = isset($CreateAccountFields['Profession']) ? $CreateAccountFields['Profession'] : '';
		$PreferredLanguageCode = isset($CreateAccountFields['PreferredLanguageCode']) && $CreateAccountFields['PreferredLanguageCode'] != '' ? $CreateAccountFields['PreferredLanguageCode'] : 'en_US';
		$RegistrationType = isset($CreateAccountFields['RegistrationType']) ? $CreateAccountFields['RegistrationType'] : 'Web';
		$SuppressWelcomeEmail = isset($CreateAccountFields['SuppressWelcomeEmail']) ? $CreateAccountFields['SuppressWelcomeEmail'] : '';
		$TaxID = isset($CreateAccountFields['TaxID']) ? $CreateAccountFields['TaxID'] : '';
		
		$ReturnURL = isset($CreateAccountFields['ReturnURL']) ? $CreateAccountFields['ReturnURL'] : '';
		$ShowAddCreditCard = isset($CreateAccountFields['ShowAddCreditCard']) ? $CreateAccountFields['ShowAddCreditCard'] : '';
		$ShowMobileConfirm = isset($CreateAccountFields['ShowMobileConfirm']) ? $CreateAccountFields['ShowMobileConfirm'] : '';
		$ReturnURLDescription = isset($CreateAccountFields['ReturnURLDescription']) ? $CreateAccountFields['ReturnURLDescription'] : '';
		$UseMiniBrowser = isset($CreateAccountFields['UseMiniBrowser']) ? $CreateAccountFields['UseMiniBrowser'] : '';
		$ReminderEmailFrequency = isset($CreateAccountFields['ReminderEmailFrequency']) ? $CreateAccountFields['ReminderEmailFrequency'] : '';
		$ConfirmEmail = isset($CreateAccountFields['ConfirmEmail']) ? $CreateAccountFields['ConfirmEmail'] : '';

		$Address = isset($DataArray['Address']) ? $DataArray['Address'] : array();
		$Line1 = isset($Address['Line1']) ? $Address['Line1'] : '';
		$Line2 = isset($Address['Line2']) ? $Address['Line2'] : '';
		$City = isset($Address['City']) ? $Address['City'] : '';
		$State = isset($Address['State']) ? $Address['State'] : '';
		$PostalCode = isset($Address['PostalCode']) ? $Address['PostalCode'] : '';
		$CountryCode = isset($Address['CountryCode']) ? $Address['CountryCode'] : '';

		$BusinessInfo = isset($DataArray['BusinessInfo']) ? $DataArray['BusinessInfo'] : array();
		$AveragePrice = isset($BusinessInfo['AveragePrice']) ? $BusinessInfo['AveragePrice'] : '';
		$AverageMonthlyVolume = isset($BusinessInfo['AverageMonthlyVolume']) ? $BusinessInfo['AverageMonthlyVolume'] : '';
		$BusinessName = isset($BusinessInfo['BusinessName']) ? $BusinessInfo['BusinessName'] : '';
		$BusinessType = isset($BusinessInfo['BusinessType']) ? $BusinessInfo['BusinessType'] : '';
		$BusinessSubType = isset($BusinessInfo['BusinessSubType']) ? $BusinessInfo['BusinessSubType'] : '';
		$Category = isset($BusinessInfo['Category']) ? $BusinessInfo['Category'] : '';
		$CommercialRegistrationLocation = isset($BusinessInfo['CommercialRegistrationLocation']) ? $BusinessInfo['CommercialRegistrationLocation'] : '';
		$CompanyID = isset($BusinessInfo['CompanyID']) ? $BusinessInfo['CompanyID'] : '';
		$CustomerServiceEmail = isset($BusinessInfo['CustomerServiceEmail']) ? $BusinessInfo['CustomerServiceEmail'] : '';
		$CustomerServicePhone = isset($BusinessInfo['CustomerServicePhone']) ? $BusinessInfo['CustomerServicePhone'] : '';
		$DateOfEstablishment = isset($BusinessInfo['DateOfEstablishment']) ? $BusinessInfo['DateOfEstablishment'] : '';
		$DisputeEmail = isset($BusinessInfo['DisputeEmail']) ? $BusinessInfo['DisputeEmail'] : '';
		$DoingBusinessAs = isset($BusinessInfo['DoingBusinessAs']) ? $BusinessInfo['DoingBusinessAs'] : '';
		$EstablishmentCountryCode = isset($BusinessInfo['EstablishmentCountryCode']) ? $BusinessInfo['EstablishmentCountryCode'] : '';
		$EstablishmentState = isset($BusinessInfo['EstablishmentState']) ? $BusinessInfo['EstablishmentState'] : '';
		$IncorporationID = isset($BusinessInfo['IncorporationID']) ? $BusinessInfo['IncorporationID'] : '';
		$MerchantCategoryCode = isset($BusinessInfo['MerchantCategoryCode']) ? $BusinessInfo['MerchantCategoryCode'] : '';
		$PercentageRevenueFromOnline = isset($BusinessInfo['PercentageRevenueFromOnline']) ? $BusinessInfo['PercentageRevenueFromOnline'] : '';
		$SalesVenu = isset($BusinessInfo['SalesVenu']) ? $BusinessInfo['SalesVenu'] : '';
		$SalesVenuDesc = isset($BusinessInfo['SalesVenuDesc']) ? $BusinessInfo['SalesVenuDesc'] : '';
		$SubCategory = isset($BusinessInfo['SubCategory']) ? $BusinessInfo['SubCategory'] : '';
		$VatCountryCode = isset($BusinessInfo['VatCountryCode']) ? $BusinessInfo['VatCountryCode'] : '';
		$VatID = isset($BusinessInfo['VatID']) ? $BusinessInfo['VatID'] : '';
		$WebSite = isset($BusinessInfo['WebSite']) ? $BusinessInfo['WebSite'] : '';
		$WorkPhone = isset($BusinessInfo['WorkPhone']) ? $BusinessInfo['WorkPhone'] : '';

		$GovernmentIDPair = isset($DataArray['GovernmentIDPair']) ? $DataArray['GovernmentIDPair'] : array();
		$GovernmentIDValue = isset($GovernmentIDPair['Value']) ? $GovernmentIDPair['Value'] : '';
		$GovernmentIDType = isset($GovernmentIDPair['Type']) ? $GovernmentIDPair['Type'] : '';

		$LegalAgreement = isset($DataArray['LegalAgreement']) ? $DataArray['LegalAgreement'] : array();
		$LegalAgreementAccepted = isset($LegalAgreement['Accepted']) ? $LegalAgreement['Accepted'] : '';
		$LegalAgreementType = isset($LegalAgreement['Type']) ? $LegalAgreement['Type'] : '';
		
		$BusinessAddress = isset($DataArray['BusinessAddress']) ? $DataArray['BusinessAddress'] : array();
		$BusinessAddressLine1 = isset($BusinessAddress['Line1']) ? $BusinessAddress['Line1'] : '';
		$BusinessAddressLine2 = isset($BusinessAddress['Line2']) ? $BusinessAddress['Line2'] : '';
		$BusinessAddressCity = isset($BusinessAddress['City']) ? $BusinessAddress['City'] : '';
		$BusinessAddressState = isset($BusinessAddress['State']) ? $BusinessAddress['State'] : '';
		$BusinessAddressPostalCode = isset($BusinessAddress['PostalCode']) ? $BusinessAddress['PostalCode'] : '';
		$BusinessAddressCountryCode = isset($BusinessAddress['CountryCode']) ? $BusinessAddress['CountryCode'] : '';

		$PrinciplePlaceOfBusinessAddress = isset($DataArray['PrinciplePlaceOfBusinessAddress']) ? $DataArray['PrinciplePlaceOfBusinessAddress'] : array();
		$PrinciplePlaceOfBusinessAddressLine1 = isset($PrinciplePlaceOfBusinessAddress['Line1']) ? $PrinciplePlaceOfBusinessAddress['Line1'] : '';
		$PrinciplePlaceOfBusinessAddressLine2 = isset($PrinciplePlaceOfBusinessAddress['Line2']) ? $PrinciplePlaceOfBusinessAddress['Line2'] : '';
		$PrinciplePlaceOfBusinessAddressCity = isset($PrinciplePlaceOfBusinessAddress['City']) ? $PrinciplePlaceOfBusinessAddress['City'] : '';
		$PrinciplePlaceOfBusinessAddressState = isset($PrinciplePlaceOfBusinessAddress['State']) ? $PrinciplePlaceOfBusinessAddress['State'] : '';
		$PrinciplePlaceOfBusinessAddressPostalCode = isset($PrinciplePlaceOfBusinessAddress['PostalCode']) ? $PrinciplePlaceOfBusinessAddress['PostalCode'] : '';
		$PrinciplePlaceOfBusinessAddressCountryCode = isset($PrinciplePlaceOfBusinessAddress['CountryCode']) ? $PrinciplePlaceOfBusinessAddress['CountryCode'] : '';
		
		$RegisteredOfficeAddress = isset($DataArray['RegisteredOfficeAddress']) ? $DataArray['RegisteredOfficeAddress'] : array();
		$RegisteredOfficeAddressLine1 = isset($RegisteredOfficeAddress['Line1']) ? $RegisteredOfficeAddress['Line1'] : '';
		$RegisteredOfficeAddressLine2 = isset($RegisteredOfficeAddress['Line2']) ? $RegisteredOfficeAddress['Line2'] : '';
		$RegisteredOfficeAddressCity = isset($RegisteredOfficeAddress['City']) ? $RegisteredOfficeAddress['City'] : '';
		$RegisteredOfficeAddressState = isset($RegisteredOfficeAddress['State']) ? $RegisteredOfficeAddress['State'] : '';
		$RegisteredOfficeAddressPostalCode = isset($RegisteredOfficeAddress['PostalCode']) ? $RegisteredOfficeAddress['PostalCode'] : '';
		$RegisteredOfficeAddressCountryCode = isset($RegisteredOfficeAddress['CountryCode']) ? $RegisteredOfficeAddress['CountryCode'] : '';
		
		$BusinessStakeHolder = isset($DataArray['BusinessStakeHolder']) ? $DataArray['BusinessStakeHolder'] : array();
		$BusinessStakeHolderDateOfBirth = isset($BusinessStakeHolder['DateOfBirth']) ? $BusinessStakeHolder['DateOfBirth'] : '';
		$BusinessStakeHolderFullLegalName = isset($BusinessStakeHolder['FullLegalName']) ? $BusinessStakeHolder['FullLegalName'] : '';
		$BusinessStakeHolderSalutation = isset($BusinessStakeHolder['Salutation']) ? $BusinessStakeHolder['Salutation'] : '';
		$BusinessStakeHolderFirstName = isset($BusinessStakeHolder['FirstName']) ? $BusinessStakeHolder['FirstName'] : '';
		$BusinessStakeHolderMiddleName = isset($BusinessStakeHolder['MiddleName']) ? $BusinessStakeHolder['MiddleName'] : '';
		$BusinessStakeHolderLastName = isset($BusinessStakeHolder['LastName']) ? $BusinessStakeHolder['LastName'] : '';
		$BusinessStakeHolderSuffix = isset($BusinessStakeHolder['Suffix']) ? $BusinessStakeHolder['Suffix'] : '';
		$BusinessStakeHolderRole = isset($BusinessStakeHolder['Role']) ? $BusinessStakeHolder['Role'] : '';
		$BusinessStakeHolderCountryCode = isset($BusinessStakeHolder['CountryCode']) ? $BusinessStakeHolder['CountryCode'] : '';
		
		$BusinessStakeHolderAddress = isset($DataArray['BusinessStakeHolderAddress']) ? $DataArray['BusinessStakeHolderAddress'] : array();
		$BusinessStakeHolderAddressLine1 = isset($BusinessStakeHolderAddress['Line1']) ? $BusinessStakeHolderAddress['Line1'] : '';
		$BusinessStakeHolderAddressLine2 = isset($BusinessStakeHolderAddress['Line2']) ? $BusinessStakeHolderAddress['Line2'] : '';
		$BusinessStakeHolderAddressCity = isset($BusinessStakeHolderAddress['City']) ? $BusinessStakeHolderAddress['City'] : '';
		$BusinessStakeHolderAddressState = isset($BusinessStakeHolderAddress['State']) ? $BusinessStakeHolderAddress['State'] : '';
		$BusinessStakeHolderAddressPostalCode = isset($BusinessStakeHolderAddress['PostalCode']) ? $BusinessStakeHolderAddress['PostalCode'] : '';
		$BusinessStakeHolderAddressCountryCode = isset($BusinessStakeHolderAddress['CountryCode']) ? $BusinessStakeHolderAddress['CountryCode'] : '';
		
		$PartnerFields = isset($DataArray['PartnerFields']) ? $DataArray['PartnerFields'] : array();
		$PartnerField1 = isset($PartnerFields['Field1']) ? $PartnerFields['Field1'] : '';
		$PartnerField2 = isset($PartnerFields['Field2']) ? $PartnerFields['Field2'] : '';
		$PartnerField3 = isset($PartnerFields['Field3']) ? $PartnerFields['Field3'] : '';
		$PartnerField4 = isset($PartnerFields['Field4']) ? $PartnerFields['Field4'] : '';
		$PartnerField5 = isset($PartnerFields['Field5']) ? $PartnerFields['Field5'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<CreateAccountRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $AccountType != '' ? '<accountType xmlns="">' . $AccountType . '</accountType>' : '';
		$XMLRequest .= $EmailAddress != '' ? '<emailAddress xmlns="">' . $EmailAddress . '</emailAddress>' : '';
		$XMLRequest .= $FunctionalArea != '' ? '<functionalArea xmlns="">' . $FunctionalArea . '</functionalArea>' : '';
		
		if($Salutation != '' || $FirstName != '' || $MiddleName != '' || $LastName != '' || $Suffix != '')
		{
			$XMLRequest .= '<name xmlns="">';
			$XMLRequest .= $Salutation != '' ? '<salutation xmlns="">' . $Salutation . '</salutation>' : '';
			$XMLRequest .= $FirstName != '' ? '<firstName xmlns="">' . $FirstName . '</firstName>' : '';
			$XMLRequest .= $MiddleName != '' ? '<middleName xmlns="">' . $MiddleName . '</middleName>' : '';
			$XMLRequest .= $LastName != '' ? '<lastName xmlns="">' . $LastName . '</lastName>' : '';
			$XMLRequest .= $Suffix != '' ? '<suffix xmlns="">' . $Suffix . '</suffix>' : '';
			$XMLRequest .= '</name>';
		}
		
		$XMLRequest .= $DateOfBirth != '' ? '<dateOfBirth xmlns="">' . $DateOfBirth . '</dateOfBirth>' : '';
		
		if(!empty($Address))
		{
			$XMLRequest .= '<address xmlns="">';
			$XMLRequest .= $Line1 != '' ? '<line1 xmlns="">' . $Line1 . '</line1>' : '';
			$XMLRequest .= $Line2 != '' ? '<line2 xmlns="">' . $Line2 . '</line2>' : '';
			$XMLRequest .= $City != '' ? '<city xmlns="">' . $City . '</city>' : '';
			$XMLRequest .= $State != '' ? '<state xmlns="">' . $State . '</state>' : '';
			$XMLRequest .= $PostalCode != '' ? '<postalCode xmlns="">' . $PostalCode . '</postalCode>' : '';
			$XMLRequest .= $CountryCode != '' ? '<countryCode xmlns="">' . $CountryCode . '</countryCode>' : '';
			$XMLRequest .= '</address>';
		}
		
		$XMLRequest .= $ContactPhoneNumber != '' ? '<contactPhoneNumber xmlns="">' . $ContactPhoneNumber . '</contactPhoneNumber>' : '';
		$XMLRequest .= $HomePhoneNumber != '' ? '<homePhoneNumber xmlns="">' . $HomePhoneNumber . '</homePhoneNumber>' : '';
		$XMLRequest .= $MobilePhoneNumber != '' ? '<mobilePhoneNumber xmlns="">' . $MobilePhoneNumber . '</mobilePhoneNumber>' : '';
		$XMLRequest .= $CurrencyCode != '' ? '<currencyCode xmlns="">' . $CurrencyCode . '</currencyCode>' : '';
		$XMLRequest .= $CitizenshipCountryCode != '' ? '<citizenshipCountryCode xmlns="">' . $CitizenshipCountryCode . '</citizenshipCountryCode>' : '';
		$XMLRequest .= $PreferredLanguageCode != '' ? '<preferredLanguageCode xmlns="">' . $PreferredLanguageCode . '</preferredLanguageCode>' : '';
		$XMLRequest .= $NotificationURL != '' ? '<notificationURL xmlns="">' . $NotificationURL . '</notificationURL>' : '';
		$XMLRequest .= $Occupation != '' ? '<occupation xmlns="">' . $Occupation . '</occupation>' : '';
		$XMLRequest .= $PartnerField1 != '' ? '<partnerField1 xmlns="">' . $PartnerField1 . '</partnerField1>' : '';
		$XMLRequest .= $PartnerField2 != '' ? '<partnerField2 xmlns="">' . $PartnerField2 . '</partnerField2>' : '';
		$XMLRequest .= $PartnerField3 != '' ? '<partnerField3 xmlns="">' . $PartnerField3 . '</partnerField3>' : '';
		$XMLRequest .= $PartnerField4 != '' ? '<partnerField4 xmlns="">' . $PartnerField4 . '</partnerField4>' : '';
		$XMLRequest .= $PartnerField5 != '' ? '<partnerField5 xmlns="">' . $PartnerField5 . '</partnerField5>' : '';
		$XMLRequest .= $RegistrationType != '' ? '<registrationType xmlns="">' . $RegistrationType . '</registrationType>' : '';
		$XMLRequest .= $SuppressWelcomeEmail != '' ? '<suppressWelcomeEmail xmlns="">' . $SuppressWelcomeEmail . '</suppressWelcomeEmail>' : '';
		$XMLRequest .= $PerformExtraVettingOnThisAccount != '' ? '<performExtraVettingOnthisAccount xmlns="">' . $PerformExtraVettingOnThisAccount . '</performExtraVettingOnthisAccount>' : '';
		$XMLRequest .= $PurposeOfAccount != '' ? '<purposeOfAccount xmlns="">' . $PurposeOfAccount . '</purposeOfAccount>' : '';
		$XMLRequest .= $Profession != '' ? '<profession xmlns="">' . $Profession . '</profession>' : '';
		$XMLRequest .= $TaxID != '' ? '<taxId xmlns="">' . $TaxID . '</taxId>' : '';
		
		if($ReturnURL != '' || $ReturnURLDescription != '' || $ShowAddCreditCard != '' || $ShowMobileConfirm != '' || $UseMiniBrowser != '' || $ReminderEmailFrequency != '')
		{
			$XMLRequest .= '<createAccountWebOptions xmlns="">';
			$XMLRequest .= $ReturnURL != '' ? '<returnUrl xmlns="">' . $ReturnURL . '</returnUrl>' : '';
			$XMLRequest .= $ReturnURLDescription != '' ? '<returnUrlDescription xmlns="">' . $ReturnURLDescription . '</returnUrlDescription>' : '';
			$XMLRequest .= $ShowAddCreditCard != '' ? '<showAddCreditCard xmlns="">' . $ShowAddCreditCard . '</showAddCreditCard>' : '';
			$XMLRequest .= $ShowMobileConfirm != '' ? '<showMobileConfirm xmlns="">' . $ShowMobileConfirm . '</showMobileConfirm>' : '';
			$XMLRequest .= $UseMiniBrowser != '' ? '<useMiniBrowser xmlns="">' . $UseMiniBrowser . '</useMiniBrowser>' : '';
			$XMLRequest .= $ReminderEmailFrequency != '' ? '<reminderEmailFrequency xmlns="">' . $ReminderEmailFrequency . '</reminderEmailFrequency>' : '';
			$XMLRequest .= $ConfirmEmail != '' ? '<confirmEmail xmlns="">' . $ConfirmEmail . '</confirmEmail>' : '';
			$XMLRequest .= '</createAccountWebOptions>';
		}

		if(!empty($GovernmentIDPair))
		{
			$XMLRequest .= '<governmentId xmlns="">';
			$XMLRequest .= $GovernmentIDValue != '' ? '<value xmlns="">' . $GovernmentIDValue . '</value>' : '';
			$XMLRequest .= $GovernmentIDType != '' ? '<type xmlns="">' . $GovernmentIDType . '</type>' : '';
			$XMLRequest .= '</governmentId>';
		}

		if(!empty($LegalAgreement))
		{
			$XMLRequest .= '<legalAgreement xmlns="">';
			$XMLRequest .= $LegalAgreementAccepted != '' ? '<accepted xmlns="">' . $LegalAgreementAccepted . '</accepted>' : '';
			$XMLRequest .= $LegalAgreementType != '' ? '<type xmlns="">' . $LegalAgreementType . '</type>' : '';
			$XMLRequest .= '</legalAgreement>';
		}
		
		if(!empty($BusinessInfo))
		{
			$XMLRequest .= '<businessInfo xmlns="">';
			$XMLRequest .= $BusinessName != '' ? '<businessName xmlns="">'. $BusinessName . '</businessName>' : '';
			
			if(!empty($BusinessAddress))
			{
				$XMLRequest .= '<businessAddress xmlns="">';
				$XMLRequest .= $BusinessAddressLine1 != '' ? '<line1 xmlns="">' . $BusinessAddressLine1 . '</line1>' : '';
				$XMLRequest .= $BusinessAddressLine2 != '' ? '<line2 xmlns="">' . $BusinessAddressLine2 . '</line2>' : '';
				$XMLRequest .= $BusinessAddressCity != '' ? '<city xmlns="">' . $BusinessAddressCity . '</city>' : '';
				$XMLRequest .= $BusinessAddressState != '' ? '<state xmlns="">' . $BusinessAddressState . '</state>' : '';
				$XMLRequest .= $BusinessAddressPostalCode != '' ? '<postalCode xmlns="">' . $BusinessAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $BusinessAddressCountryCode != '' ? '<countryCode xmlns="">' . $BusinessAddressCountryCode . '</countryCode>' : '';
				$XMLRequest .= '</businessAddress>';
			}
			
			$XMLRequest .= $WorkPhone != '' ? '<workPhone xmlns="">'. $WorkPhone . '</workPhone>' : '';
			$XMLRequest .= $Category != '' ? '<category xmlns="">' . $Category . '</category>' : '';
			$XMLRequest .= $SubCategory != '' ? '<subCategory xmlns="">'. $SubCategory . '</subCategory>' : '';
			$XMLRequest .= $MerchantCategoryCode != '' ? '<merchantCategoryCode xmlns="">' . $MerchantCategoryCode . '</merchantCategoryCode>' : '';
			$XMLRequest .= $DoingBusinessAs != '' ? '<doingBusinessAs xmlns="">' . $DoingBusinessAs . '</doingBusinessAs>' : '';
			$XMLRequest .= $CustomerServicePhone != '' ? '<customerServicePhone xmlns="">' . $CustomerServicePhone . '</customerServicePhone>' : '';
			$XMLRequest .= $CustomerServiceEmail != '' ? '<customerServiceEmail xmlns="">' . $CustomerServiceEmail . '</customerServiceEmail>' : '';
			$XMLRequest .= $DisputeEmail != '' ? '<disputeEmail xmlns="">'. $DisputeEmail . '</disputeEmail>' : '';
			$XMLRequest .= $WebSite != '' ? '<webSite xmlns="">' . $WebSite . '</webSite>' : '';
			$XMLRequest .= $CompanyID != '' ? '<companyId xmlns="">' . $CompanyID . '</companyId>' : '';
			$XMLRequest .= $DateOfEstablishment != '' ? '<dateOfEstablishment xmlns="">' . $DateOfEstablishment . '</dateOfEstablishment>' : '';
			$XMLRequest .= $BusinessType != '' ? '<businessType xmlns="">' . $BusinessType . '</businessType>' : '';
			$XMLRequest .= $BusinessSubType != '' ? '<businessSubType xmlns="">' . $BusinessSubType . '</businessSubType>' : '';
			$XMLRequest .= $IncorporationID != '' ? '<incorporationId xmlns="">' . $IncorporationID . '</incorporationId>' : '';
			$XMLRequest .= $AveragePrice != '' ? '<averagePrice xmlns="">' . $AveragePrice . '</averagePrice>' : '';
			$XMLRequest .= $AverageMonthlyVolume != '' ? '<averageMonthlyVolume xmlns="">' . $AverageMonthlyVolume . '</averageMonthlyVolume>' : '';
			$XMLRequest .= $PercentageRevenueFromOnline != '' ? '<percentageRevenueFromOnline xmlns="">' . $PercentageRevenueFromOnline . '</percentageRevenueFromOnline>' : '';
			$XMLRequest .= $SalesVenu != '' ? '<salesVenue xmlns="">' . $SalesVenu . '</salesVenue>' : '';
			$XMLRequest .= $SalesVenuDesc != '' ? '<salesVenueDesc xmlns="">' . $SalesVenuDesc . '</salesVenueDesc>' : '';
			$XMLRequest .= $VatID != '' ? '<vatId xmlns="">' . $VatID . '</vatId>' : '';
			$XMLRequest .= $VatCountryCode != '' ? '<vatCountryCode xmlns="">' . $VatCountryCode . '</vatCountryCode>' : '';
			$XMLRequest .= $CommercialRegistrationLocation != '' ? '<commercialRegistrationLocation xmlns="">' . $CommercialRegistrationLocation . '</commercialRegistrationLocation>' : '';
			
			if(!empty($PrinciplePlaceOfBusinessAddress))
			{
				$XMLRequest .= '<principlePlaceOfBusinessAddress xmlns="">';
				$XMLRequest .= $PrinciplePlaceOfBusinessAddressLine1 != '' ? '<line1 xmlns="">' . $PrinciplePlaceOfBusinessAddressLine1 . '</line1>' : '';
				$XMLRequest .= $PrinciplePlaceOfBusinessAddressLine2 != '' ? '<line2 xmlns="">' . $PrinciplePlaceOfBusinessAddressLine2 . '</line2>' : '';
				$XMLRequest .= $PrinciplePlaceOfBusinessAddressCity != '' ? '<city xmlns="">' . $PrinciplePlaceOfBusinessAddressCity . '</city>' : '';
				$XMLRequest .= $PrinciplePlaceOfBusinessAddressState != '' ? '<state xmlns="">' . $PrinciplePlaceOfBusinessAddressState . '</state>' : '';
				$XMLRequest .= $PrinciplePlaceOfBusinessAddressPostalCode != '' ? '<postalCode xmlns="">' . $PrinciplePlaceOfBusinessAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $PrinciplePlaceOfBusinessAddressCountryCode != '' ? '<countryCode xmlns="">' . $PrinciplePlaceOfBusinessAddressCountryCode . '</countryCode>' : '';
				$XMLRequest .= '</principlePlaceOfBusinessAddress>';
			}
			
			if(!empty($RegisteredOfficeAddress))
			{
				$XMLRequest .= '<registeredOfficeAddress xmlns="">';
				$XMLRequest .= $RegisteredOfficeAddressLine1 != '' ? '<line1 xmlns="">' . $RegisteredOfficeAddressLine1 . '</line1>' : '';
				$XMLRequest .= $RegisteredOfficeAddressLine2 != '' ? '<line2 xmlns="">' . $RegisteredOfficeAddressLine2 . '</line2>' : '';
				$XMLRequest .= $RegisteredOfficeAddressCity != '' ? '<city xmlns="">' . $RegisteredOfficeAddressCity . '</city>' : '';
				$XMLRequest .= $RegisteredOfficeAddressState != '' ? '<state xmlns="">' . $RegisteredOfficeAddressState . '</state>' : '';
				$XMLRequest .= $RegisteredOfficeAddressPostalCode != '' ? '<postalCode xmlns="">' . $RegisteredOfficeAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $RegisteredOfficeAddressCountryCode != '' ? '<countryCode xmlns="">' . $RegisteredOfficeAddressCountryCode . '</countryCode>' : '';		
				$XMLRequest .= '</registeredOfficeAddress>';
			}
			
			$XMLRequest .= $EstablishmentCountryCode != '' ? '<establishmentCountryCode xmlns="">' . $EstablishmentCountryCode . '</establishmentCountryCode>' : '';
			$XMLRequest .= $EstablishmentState != '' ? '<establishmentState xmlns="">' . $EstablishmentState . '</establishmentState>' : '';
			
			if(!empty($BusinessStakeHolder))
			{
				$XMLRequest .= '<businessStakeHolder xmlns="">';
				$XMLRequest .= $BusinessStakeHolderRole != '' ? '<role xmlns="">' . $BusinessStakeHolderRole . '</role>' : '';
				
				if($BusinessStakeHolderSalutation != '' || $BusinessStakeHolderFirstName != '' || $BusinessStakeHolderMiddleName != '' || $BusinessStakeHolderLastName != '' || $BusinessStakeHolderSuffix != '')
				{
					$XMLRequest .= '<name xmlns="">';
					$XMLRequest .= $BusinessStakeHolderSalutation != '' ? '<salutation xmlns="">' . $BusinessStakeHolderSalutation . '</salutation>' : '';
					$XMLRequest .= $BusinessStakeHolderFirstName != '' ? '<firstName xmlns="">' . $BusinessStakeHolderFirstName . '</firstName>' : '';
					$XMLRequest .= $BusinessStakeHolderMiddleName != '' ? '<middleName xmlns="">' . $BusinessStakeHolderMiddleName . '</middleName>' : '';
					$XMLRequest .= $BusinessStakeHolderLastName != '' ? '<lastName xmlns="">' . $BusinessStakeHolderLastName . '</lastName>' : '';
					$XMLRequest .= $BusinessStakeHolderSuffix != '' ? '<suffix xmlns="">' . $BusinessStakeHolderSuffix . '</suffix>' : '';		
					$XMLRequest .= '</name>';
				}
				
				$XMLRequest .= $BusinessStakeHolderFullLegalName != '' ? '<fullLegalName xmlns="">' . $BusinessStakeHolderFullLegalName . '</fullLegalName>' : '';
				
				if(!empty($BusinessStakeHolderAddress))
				{
					$XMLRequest .= '<address xmlns="">';
					$XMLRequest .= $BusinessStakeHolderAddressLine1 != '' ? '<line1 xmlns="">' . $BusinessStakeHolderAddressLine1 . '</line1>' : '';
					$XMLRequest .= $BusinessStakeHolderAddressLine2 != '' ? '<line2 xmlns="">' . $BusinessStakeHolderAddressLine2 . '</line2>' : '';
					$XMLRequest .= $BusinessStakeHolderAddressCity != '' ? '<city xmlns="">' . $BusinessStakeHolderAddressCity . '</city>' : '';
					$XMLRequest .= $BusinessStakeHolderAddressState != '' ? '<state xmlns="">' . $BusinessStakeHolderAddressState . '</state>' : '';
					$XMLRequest .= $BusinessStakeHolderAddressPostalCode != '' ? '<postalCode xmlns="">' . $BusinessStakeHolderAddressPostalCode . '</postalCode>' : '';
					$XMLRequest .= $BusinessStakeHolderAddressCountryCode != '' ? '<countryCode xmlns="">' . $BusinessStakeHolderAddressCountryCode . '</countryCode>' : '';		
					$XMLRequest .= '</address>';
				}
				
				$XMLRequest .= $BusinessStakeHolderDateOfBirth != '' ? '<dateOfBirth xmlns="">' . $BusinessStakeHolderDateOfBirth . '</dateOfBirth>' : '';
				$XMLRequest .= '</businessStakeHolder>';
			}
			
			$XMLRequest .= '</businessInfo>';
		}
		
		$XMLRequest .= '</CreateAccountRequest>';
				
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptiveAccounts', 'CreateAccount');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$AccountID = $DOM -> getElementsByTagName('accountId') -> length > 0 ? $DOM -> getElementsByTagName('accountId') -> item(0) -> nodeValue : '';
		$CreateAccountKey = $DOM -> getElementsByTagName('createAccountKey') -> length > 0 ? $DOM -> getElementsByTagName('createAccountKey') -> item(0) -> nodeValue : '';
		$ExecStatus = $DOM -> getElementsByTagName('execStatus') -> length > 0 ? $DOM -> getElementsByTagName('execStatus') -> item(0) -> nodeValue : '';
		$RedirectURL = $DOM -> getElementsByTagName('redirectURL') -> length > 0 ? $DOM -> getElementsByTagName('redirectURL') -> item(0) -> nodeValue : '';
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'AccountID' => $AccountID, 
								   'CreateAccountKey' => $CreateAccountKey, 
								   'ExecStatus' => $ExecStatus, 
								   'RedirectURL' => $RedirectURL, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	}
	
	/**
	 * Submit AddBankAccount API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function AddBankAccount($DataArray)
	 {
		$AddBankAccountFields = isset($DataArray['AddBankAccountFields']) ? $DataArray['AddBankAccountFields'] : array();
		$EmailAddress = isset($AddBankAccountFields['EmailAddress']) ? $AddBankAccountFields['EmailAddress'] : '';
		$AccountID = isset($AddBankAccountFields['AccountID']) ? $AddBankAccountFields['AccountID'] : '';
		$CreateAccountKey = isset($AddBankAccountFields['CreateAccountKey']) ? $AddBankAccountFields['CreateAccountKey'] : '';
		$BankCountryCode = isset($AddBankAccountFields['BankCountryCode']) ? $AddBankAccountFields['BankCountryCode'] : '';
		$BankName = isset($AddBankAccountFields['BankName']) ? $AddBankAccountFields['BankName'] : '';
		$RoutingNumber = isset($AddBankAccountFields['RoutingNumber']) ? $AddBankAccountFields['RoutingNumber'] : '';
		$BankAccountType = isset($AddBankAccountFields['BankAccountType']) ? $AddBankAccountFields['BankAccountType'] : '';
		$BankAccountNumber = isset($AddBankAccountFields['BankAccountNumber']) ? $AddBankAccountFields['BankAccountNumber'] : '';
		$IBAN = isset($AddBankAccountFields['IBAN']) ? $AddBankAccountFields['IBAN'] : '';
		$CLABE = isset($AddBankAccountFields['CLABE']) ? $AddBankAccountFields['CLABE'] : '';
		$BSBNumber = isset($AddBankAccountFields['BSBNumber']) ? $AddBankAccountFields['BSBNumber'] : '';
		$BranchLocation = isset($AddBankAccountFields['BranchLocation']) ? $AddBankAccountFields['BranchLocation'] : '';
		$SortCode = isset($AddBankAccountFields['SortCode']) ? $AddBankAccountFields['SortCode'] : '';
		$BankTransitNumber = isset($AddBankAccountFields['BankTransitNumber']) ? $AddBankAccountFields['BankTransitNumber'] : '';
		$InstitutionNumber = isset($AddBankAccountFields['InstitutionNumber']) ? $AddBankAccountFields['InstitutionNumber'] : '';
		$BranchCode = isset($AddBankAccountFields['BranchCode']) ? $AddBankAccountFields['BranchCode'] : '';
		$AgencyNumber = isset($AddBankAccountFields['AgencyNumber']) ? $AddBankAccountFields['AgencyNumber'] : '';
		$BankCode = isset($AddBankAccountFields['BankCode']) ? $AddBankAccountFields['BankCode'] : '';
		$RibKey = isset($AddBankAccountFields['RibKey']) ? $AddBankAccountFields['RibKey'] : '';
		$ControlDigit = isset($AddBankAccountFields['ControlDigit']) ? $AddBankAccountFields['ControlDigit'] : '';
		$TaxIDType = isset($AddBankAccountFields['TaxIDType']) ? $AddBankAccountFields['TaxIDType'] : '';
		$TaxIDNumber = isset($AddBankAccountFields['TaxIDNumber']) ? $AddBankAccountFields['TaxIDNumber'] : '';
		$AccountHolderDateOfBirth = isset($AddBankAccountFields['AccountHolderDateOfBirth']) ? $AddBankAccountFields['AccountHolderDateOfBirth'] : '';
		$ConfirmationType = isset($AddBankAccountFields['ConfirmationType']) ? $AddBankAccountFields['ConfirmationType'] : '';

		$WebOptions = isset($DataArray['WebOptions']) ? $DataArray['WebOptions'] : array();
		$ReturnURL = isset($WebOptions['ReturnURL']) ? $WebOptions['ReturnURL'] : '';
		$CancelURL = isset($WebOptions['CancelURL']) ? $WebOptions['CancelURL'] : '';
		$ReturnURLDescription = isset($WebOptions['ReturnURLDescription']) ? $WebOptions['ReturnURLDescription'] : '';
		$CancelURLDescription = isset($WebOptions['CancelURLDescription']) ? $WebOptions['CancelURLDescription'] : '';
	
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<AddBankAccountRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $EmailAddress != '' ? '<emailAddress xmlns="">' . $EmailAddress . '</emailAddress>' : '';
		$XMLRequest .= $AccountID != '' ? '<accountId xmlns="">' . $AccountID . '</accountId>' : '';
		$XMLRequest .= $CreateAccountKey != '' ? '<createAccountKey xmlns="">' . $CreateAccountKey . '</createAccountKey>' : '';
		$XMLRequest .= $BankCountryCode != '' ? '<bankCountryCode xmlns="">' . $BankCountryCode . '</bankCountryCode>' : '';
		$XMLRequest .= $BankName != '' ? '<bankName xmlns="">' . $BankName . '</bankName>' : '';
		$XMLRequest .= $RoutingNumber != '' ? '<routingNumber xmlns="">' . $RoutingNumber . '</routingNumber>' : '';
		$XMLRequest .= $BankAccountType != '' ? '<bankAccountType xmlns="">' . $BankAccountType . '</bankAccountType>' : '';
		$XMLRequest .= $BankAccountNumber != '' ? '<bankAccountNumber xmlns="">' . $BankAccountNumber . '</bankAccountNumber>' : '';
		$XMLRequest .= $IBAN != '' ? '<iban xmlns="">' . $IBAN . '</iban>' : '';
		$XMLRequest .= $CLABE != '' ? '<clabe xmlns="">' . $CLABE . '</clabe>' : '';
		$XMLRequest .= $BSBNumber != '' ? '<bsbNumber xmlns="">' . $BSBNumber . '</bsbNumber>' : '';
		$XMLRequest .= $BranchLocation != '' ? '<branchLocation xmlns="">' . $BranchLocation . '</branchLocation>' : '';
		$XMLRequest .= $SortCode != '' ? '<sortCode xmlns="">' . $SortCode . '</sortCode>' : '';
		$XMLRequest .= $BankTransitNumber != '' ? '<bankTransitNumber xmlns="">' . $BankTransitNumber . '</bankTransitNumber>' : '';
		$XMLRequest .= $InstitutionNumber != '' ? '<institutionNumber xmlns="">' . $InstitutionNumber . '</institutionNumber>' : '';
		$XMLRequest .= $BranchCode != '' ? '<branchCode xmlns="">' . $BranchCode . '</branchCode>' : '';
		$XMLRequest .= $AgencyNumber != '' ? '<agencyNumber xmlns="">' . $AgencyNumber . '</agencyNumber>' : '';
		$XMLRequest .= $BankCode != '' ? '<bankCode xmlns="">' . $BankCode . '</bankCode>' : '';
		$XMLRequest .= $RibKey != '' ? '<ribKey xmlns="">' . $RibKey . '</ribKey>' : '';
		$XMLRequest .= $ControlDigit != '' ? '<controlDigit xmlns="">' . $ControlDigit . '</controlDigit>' : '';
		$XMLRequest .= $TaxIDType != '' ? '<taxIdType xmlns="">' . $TaxIDType . '</taxIdType>' : '';
		$XMLRequest .= $TaxIDNumber != '' ? '<taxIdNumber xmlns="">' . $TaxIDNumber . '</taxIdNumber>' : '';
		$XMLRequest .= $AccountHolderDateOfBirth != '' ? '<accountHolderDateOfBirth xmlns="">' . $AccountHolderDateOfBirth . '</accountHolderDateOfBirth>' : '';
		$XMLRequest .= $ConfirmationType != '' ? '<confirmationType xmlns="">' . $ConfirmationType . '</confirmationType>' : '';
		
		if(!empty($WebOptions))
		{
			$XMLRequest .= '<webOptions xmlns="">';
			$XMLRequest .= $ReturnURL != '' ? '<returnUrl xmlns="">' . $ReturnURL . '</returnUrl>' : '';
			$XMLRequest .= $CancelURL != '' ? '<cancelUrl xmlns="">' . $CancelURL . '</cancelUrl>' : '';
			$XMLRequest .= $ReturnURLDescription != '' ? '<returnUrlDescription xmlns="">' . $ReturnURLDescription . '</returnUrlDescription>' : '';
			$XMLRequest .= $CancelURLDescription != '' ? '<cancelUrlDescription xmlns="">' . $CancelURLDescription . '</cancelUrlDescription>' : '';	
			$XMLRequest .= '</webOptions>';
		}
		
		$XMLRequest .= '</AddBankAccountRequest>';
		 
		 // Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptiveAccounts', 'AddBankAccount', $this->PrintHeaders);
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

         $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
         $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$ExecStatus = $DOM -> getElementsByTagName('execStatus') -> length > 0 ? $DOM -> getElementsByTagName('execStatus') -> item(0) -> nodeValue : '';
		$FundingSourceKey = $DOM -> getElementsByTagName('fundingSourceKey') -> length > 0 ? $DOM -> getElementsByTagName('fundingSourceKey') -> item(0) -> nodeValue : '';
		$RedirectURL = $DOM -> getElementsByTagName('redirectURL') -> length > 0 ? $DOM -> getElementsByTagName('redirectURL') -> item(0) -> nodeValue : '';
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'ExecStatus' => $ExecStatus, 
								   'FundingSourceKey' => $FundingSourceKey, 
								   'RedirectURL' => $RedirectURL, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 /**
	 * Submit AddPaymentCard API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function AddPaymentCard($DataArray)
	 {
		$AddPaymentCardFields = isset($DataArray['AddPaymentCardFields']) ? $DataArray['AddPaymentCardFields'] : array();
		$EmailAddress = isset($AddPaymentCardFields['EmailAddress']) ? $AddPaymentCardFields['EmailAddress'] : '';
		$AccountID = isset($AddPaymentCardFields['AccountID']) ? $AddPaymentCardFields['AccountID'] : '';
		$CreateAccountKey = isset($AddPaymentCardFields['CreateAccountKey']) ? $AddPaymentCardFields['CreateAccountKey'] : '';
		$CardOwnerDateOfBirth = isset($AddPaymentCardFields['CardOwnerDateOfBirth']) ? $AddPaymentCardFields['CardOwnerDateOfBirth'] : '';
		$CardNumber = isset($AddPaymentCardFields['CardNumber']) ? $AddPaymentCardFields['CardNumber'] : '';
		$CardType = isset($AddPaymentCardFields['CardType']) ? $AddPaymentCardFields['CardType'] : '';
		$CardVerificationNumber = isset($AddPaymentCardFields['CardVerificationNumber']) ? $AddPaymentCardFields['CardVerificationNumber'] : '';
		$StartDate = isset($AddPaymentCardFields['StartDate']) ? $AddPaymentCardFields['StartDate'] : '';
		$IssueNumber = isset($AddPaymentCardFields['IssueNumber']) ? $AddPaymentCardFields['IssueNumber'] : '';
		$ConfirmationType = isset($AddPaymentCardFields['ConfirmationType']) ? $AddPaymentCardFields['ConfirmationType'] : '';

		$NameOnCard = isset($DataArray['NameOnCard']) ? $DataArray['NameOnCard'] : array();
		$Salutation = isset($NameOnCard['Salutation']) ? $NameOnCard['Salutation'] : '';
		$FirstName = isset($NameOnCard['FirstName']) ? $NameOnCard['FirstName'] : '';
		$MiddleName = isset($NameOnCard['MiddleName']) ? $NameOnCard['MiddleName'] : '';
		$LastName = isset($NameOnCard['LastName']) ? $NameOnCard['LastName'] : '';
		$Suffix = isset($NameOnCard['Suffix']) ? $NameOnCard['Suffix'] : '';
		
		$BillingAddress = isset($DataArray['BillingAddress']) ? $DataArray['BillingAddress'] : array();
		$Line1 = isset($BillingAddress['Line1']) ? $BillingAddress['Line1'] : '';
		$Line2 = isset($BillingAddress['Line2']) ? $BillingAddress['Line2'] : '';
		$City = isset($BillingAddress['City']) ? $BillingAddress['City'] : '';
		$State = isset($BillingAddress['State']) ? $BillingAddress['State'] : '';
		$PostalCode = isset($BillingAddress['PostalCode']) ? $BillingAddress['PostalCode'] : '';
		$CountryCode = isset($BillingAddress['CountryCode']) ? $BillingAddress['CountryCode'] : '';
		
		$ExpirationDate = isset($DataArray['ExpirationDate']) ? $DataArray['ExpirationDate'] : array();
		$Month = isset($ExpirationDate['Month']) ? $ExpirationDate['Month'] : '';
		$Year = isset($ExpirationDate['Year']) ? $ExpirationDate['Year'] : '';
		
		$WebOptions = isset($DataArray['WebOptions']) ? $DataArray['WebOptions'] : array();
		$ReturnURL = isset($WebOptions['ReturnURL']) ? $WebOptions['ReturnURL'] : '';
		$CancelURL = isset($WebOptions['CancelURL']) ? $WebOptions['CancelURL'] : '';
		$ReturnURLDescription = isset($WebOptions['ReturnURLDescription']) ? $WebOptions['ReturnURLDescription'] : '';
		$CancelURLDescription = isset($WebOptions['CancelURLDescription']) ? $WebOptions['CancelURLDescription'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<AddPaymentCardRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $EmailAddress != '' ? '<emailAddress xmlns="">' . $EmailAddress . '</emailAddress>' : '';
		$XMLRequest .= $AccountID != '' ? '<accountId xmlns="">' . $AccountID . '</accountId>' : '';
		$XMLRequest .= $CreateAccountKey != '' ? '<createAccountKey xmlns="">' . $CreateAccountKey . '</createAccountKey>' : '';
	
		if(!empty($NameOnCard))
		{
			$XMLRequest .= '<nameOnCard xmlns="">';
			$XMLRequest .= $Salutation != '' ? '<salutation xmlns="">' . $Salutation . '</salutation>' : '';
			$XMLRequest .= $FirstName != '' ? '<firstName xmlns="">' . $FirstName . '</firstName>' : '';
			$XMLRequest .= $MiddleName != '' ? '<middleName xmlns="">' . $MiddleName . '</middleName>' : '';
			$XMLRequest .= $LastName != '' ? '<lastName xmlns="">' . $LastName . '</lastName>' : '';
			$XMLRequest .= $Suffix != '' ? '<suffix xmlns="">' . $Suffix . '</suffix>' : '';
			$XMLRequest .= '</nameOnCard>';
		}
		
		if(!empty($BillingAddress))
		{
			$XMLRequest .= '<billingAddress xmlns="">';
			$XMLRequest .= $Line1 != '' ? '<line1 xmlns="">' . $Line1 . '</line1>' : '';
			$XMLRequest .= $Line2 != '' ? '<line2 xmlns="">' . $Line2 . '</line2>' : '';
			$XMLRequest .= $City != '' ? '<city xmlns="">' . $City . '</city>' : '';
			$XMLRequest .= $State != '' ? '<state xmlns="">' . $State . '</state>' : '';
			$XMLRequest .= $PostalCode != '' ? '<postalCode xmlns="">' . $PostalCode . '</postalCode>' : '';
			$XMLRequest .= $CountryCode != '' ? '<countryCode xmlns="">' . $CountryCode . '</countryCode>' : '';			
			$XMLRequest .= '</billingAddress>';	
		}
		
		$XMLRequest .= $CardOwnerDateOfBirth != '' ? '<cardOwnerDateOfBirth xmlns="">' . $CardOwnerDateOfBirth . '</cardOwnerDateOfBirth>' : '';
		$XMLRequest .= $CardNumber != '' ? '<cardNumber xmlns="">' . $CardNumber . '</cardNumber>' : '';
		$XMLRequest .= $CardType != '' ? '<cardType xmlns="">' . $CardType . '</cardType>' : '';

		if(!empty($ExpirationDate))
		{
			$XMLRequest .= '<expirationDate xmlns="">';
			$XMLRequest .= $Month != '' ? '<month xmlns="">' . $Month . '</month>' : '';
			$XMLRequest .= $Year != '' ? '<year xmlns="">' . $Year . '</year>' : '';			
			$XMLRequest .= '</expirationDate>';	
		}

		$XMLRequest .= $CardVerificationNumber != '' ? '<cardVerificationNumber xmlns="">' . $CardVerificationNumber . '</cardVerificationNumber>' : '';
		$XMLRequest .= $StartDate != '' ? '<startDate xmlns="">' . $StartDate . '</startDate>' : '';
		$XMLRequest .= $IssueNumber != '' ? '<issueNumber xmlns="">' . $IssueNumber . '</issueNumber>' : '';
		$XMLRequest .= $ConfirmationType != '' ? '<confirmationType xmlns="">' . $ConfirmationType . '</confirmationType>' : '';
		
		if(!empty($WebOptions))
		{
			$XMLRequest .= '<webOptions xmlns="">';
			$XMLRequest .= $ReturnURL != '' ? '<returnUrl xmlns="">' . $ReturnURL . '</returnUrl>' : '';
			$XMLRequest .= $CancelURL != '' ? '<cancelUrl xmlns="">' . $CancelURL . '</cancelUrl>' : '';
			$XMLRequest .= $ReturnURLDescription != '' ? '<returnUrlDescription xmlns="">' . $ReturnURLDescription . '</returnUrlDescription>' : '';
			$XMLRequest .= $CancelURLDescription != '' ? '<cancelUrlDescription xmlns="">' . $CancelURLDescription . '</cancelUrlDescription>' : '';	
			$XMLRequest .= '</webOptions>';
		}
	
		$XMLRequest .= '</AddPaymentCardRequest>';
				 
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptiveAccounts', 'AddPaymentCard');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

         $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
         $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$ExecStatus = $DOM -> getElementsByTagName('execStatus') -> length > 0 ? $DOM -> getElementsByTagName('execStatus') -> item(0) -> nodeValue : '';
		$FundingSourceKey = $DOM -> getElementsByTagName('fundingSourceKey') -> length > 0 ? $DOM -> getElementsByTagName('fundingSourceKey') -> item(0) -> nodeValue : '';
		$RedirectURL = $DOM -> getElementsByTagName('redirectURL') -> length > 0 ? $DOM -> getElementsByTagName('redirectURL') -> item(0) -> nodeValue : '';
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'ExecStatus' => $ExecStatus, 
								   'FundingSourceKey' => $FundingSourceKey, 
								   'RedirectURL' => $RedirectURL, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit SetFundingSourceConfirmed API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function SetFundingSourceConfirmed($DataArray)
	 {
		$SetFundingSourceConfirmedFields = isset($DataArray['SetFundingSourceConfirmedFields']) ? $DataArray['SetFundingSourceConfirmedFields'] : array();
		$EmailAddress = isset($SetFundingSourceConfirmedFields['EmailAddress']) ? $SetFundingSourceConfirmedFields['EmailAddress'] : '';
		$AccountID = isset($SetFundingSourceConfirmedFields['AccountID']) ? $SetFundingSourceConfirmedFields['AccountID'] : '';
		$FundingSourceKey = isset($SetFundingSourceConfirmedFields['FundingSourceKey']) ? $SetFundingSourceConfirmedFields['FundingSourceKey'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<SetFundingSourceConfirmedRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $EmailAddress != '' ? '<emailAddress xmlns="">' . $EmailAddress . '</emailAddress>' : '';
		$XMLRequest .= $AccountID != '' ? '<accountId xmlns="">' . $AccountID . '</accountId>' : '';
		$XMLRequest .= $FundingSourceKey != '' ? '<fundingSourceKey xmlns="">' . $FundingSourceKey . '</fundingSourceKey>' : '';		
		$XMLRequest .= '</SetFundingSourceConfirmedRequest>';
		 
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptiveAccounts', 'SetFundingSourceConfirmed');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

         $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
         $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit GetVerifiedStatus API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function GetVerifiedStatus($DataArray)
	 {
		$GetVerifiedStatusFields = isset($DataArray['GetVerifiedStatusFields']) ? $DataArray['GetVerifiedStatusFields'] : array();
		$EmailAddress = isset($GetVerifiedStatusFields['EmailAddress']) ? $GetVerifiedStatusFields['EmailAddress'] : '';
		$MatchCriteria = isset($GetVerifiedStatusFields['MatchCriteria']) ? $GetVerifiedStatusFields['MatchCriteria'] : '';
		$FirstName = isset($GetVerifiedStatusFields['FirstName']) ? $GetVerifiedStatusFields['FirstName'] : '';
		$LastName = isset($GetVerifiedStatusFields['LastName']) ? $GetVerifiedStatusFields['LastName'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<GetVerifiedStatusRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $EmailAddress != '' ? '<emailAddress xmlns="">' . $EmailAddress . '</emailAddress>' : '';
		$XMLRequest .= $MatchCriteria != '' ? '<matchCriteria xmlns="">' . $MatchCriteria . '</matchCriteria>' : '';
		$XMLRequest .= $FirstName != '' ? '<firstName xmlns="">' . $FirstName . '</firstName>' : '';
		$XMLRequest .= $LastName != '' ? '<lastName xmlns="">' . $LastName . '</lastName>' : '';
		$XMLRequest .= '</GetVerifiedStatusRequest>';

		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptiveAccounts', 'GetVerifiedStatus');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

         $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
         $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$AccountStatus = $DOM -> getElementsByTagName('accountStatus') -> length > 0 ? $DOM -> getElementsByTagName('accountStatus') -> item(0) -> nodeValue : '';
		$CountryCode = $DOM -> getElementsByTagName('countryCode') -> length > 0 ? $DOM -> getElementsByTagName('countryCode') -> item(0) -> nodeValue : '';
		$EmailAddress = $DOM -> getElementsByTagName('emailAddress') -> length > 0 ? $DOM -> getElementsByTagName('emailAddress') -> item(0) -> nodeValue : '';
		$AccountID = $DOM -> getElementsByTagName('accountId') -> length > 0 ? $DOM -> getElementsByTagName('accountId') -> item(0) -> nodeValue : '';
		$AccountType = $DOM -> getElementsByTagName('accountType') -> length > 0 ? $DOM -> getElementsByTagName('accountType') -> item(0) -> nodeValue : '';
		$BusinessName = $DOM -> getElementsByTagName('businessName') -> length > 0 ? $DOM -> getElementsByTagName('businessName') -> item(0) -> nodeValue : '';
		$Salutation = $DOM -> getElementsByTagName('salutation') -> length > 0 ? $DOM -> getElementsByTagName('salutation') -> item(0) -> nodeValue : '';
		$FirstName = $DOM -> getElementsByTagName('firstName') -> length > 0 ? $DOM -> getElementsByTagName('firstName') -> item(0) -> nodeValue : '';
		$MiddleName = $DOM -> getElementsByTagName('middleName') -> length > 0 ? $DOM -> getElementsByTagName('middleName') -> item(0) -> nodeValue : '';
		$LastName = $DOM -> getElementsByTagName('lastName') -> length > 0 ? $DOM -> getElementsByTagName('lastName') -> item(0) -> nodeValue : '';
		$Suffix = $DOM -> getElementsByTagName('suffix') -> length > 0 ? $DOM -> getElementsByTagName('suffix') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
           'Errors' => $Errors,
           'Ack' => $Ack,
           'Build' => $Build,
           'CorrelationID' => $CorrelationID,
           'Timestamp' => $Timestamp,
           'AccountStatus' => $AccountStatus,
           'CountryCode' => $CountryCode,
           'EmailAddress' => $EmailAddress,
           'AccountID' => $AccountID,
           'AccountType' => $AccountType,
           'BusinessName' => $BusinessName,
           'Salutation' => $Salutation,
           'FirstName' => $FirstName,
           'MiddleName' => $MiddleName,
           'LastName' => $LastName,
           'Suffix' => $Suffix,
           'XMLRequest' => $XMLRequest,
           'XMLResponse' => $XMLResponse
        );
		
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * GetUserAgreement.
	 *
	 * Retrieves the user agreement for the customer to approve the new PayPal account.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function GetUserAgreement($DataArray)
	 {
		$GetUserAgreementFields = isset($DataArray['GetUserAgreementFields']) ? $DataArray['GetUserAgreementFields'] : array();
		$CountryCode = isset($GetUserAgreementFields['CountryCode']) ? $GetUserAgreementFields['CountryCode'] : '';
		$CreateAccountKey = isset($GetUserAgreementFields['CreateAccountKey']) ? $GetUserAgreementFields['CreateAccountKey'] : '';
		$LanguageCode = isset($GetUserAgreementFields['LanguageCode']) ? $GetUserAgreementFields['LanguageCode'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<GetUserAgreementRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $CountryCode != '' ? '<countryCode xmlns="">' . $CountryCode . '</countryCode>' : '';
		$XMLRequest .= $CreateAccountKey != '' ? '<createAccountKey xmlns="">' . $CreateAccountKey . '</createAccountKey>' : '';
		$XMLRequest .= $LanguageCode != '' ? '<languageCode xmlns="">' . $LanguageCode . '</languageCode>' : '';
		$XMLRequest .= '</GetUserAgreementRequest>';

		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptiveAccounts', 'GetUserAgreement');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

         $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
         $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$Agreement = $DOM -> getElementsByTagName('agreement') -> length > 0 ? $DOM -> getElementsByTagName('agreement') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'Agreement' => $Agreement, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit GetFundingPlans API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function GetFundingPlans($DataArray)
	 {
		$GetFundingPlansFields = isset($DataArray['GetFundingPlansFields']) ? $DataArray['GetFundingPlansFields'] : array();
		$PayKey = isset($GetFundingPlansFields['PayKey']) ? $GetFundingPlansFields['PayKey'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<GetFundingPlansRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $PayKey != '' ? '<payKey xmlns="">' . $PayKey . '</payKey>' : '';
		$XMLRequest .= '</GetFundingPlansRequest>';

		 // Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptiveAccounts', 'GetFundingPlans');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

         $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
         $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$FundingPlanType = $DOM -> getElementsByTagName('fundingPlan') -> length > 0 ? $DOM -> getElementsByTagName('fundingPlan') : array();
		foreach($FundingPlanType as $FundingPlan)
		{
			$FundingPlanID = $FundingPlan -> getElementsByTagName('fundingPlanId') -> length > 0 ? $FundingPlan -> getElementsByTagName('fundingPlanId') -> item(0) -> nodeValue : '';
			$FundingAmountType = $FundingPlan -> getElementsByTagName('fundingAmount') -> length > 0 ? $FundingPlan -> getElementsByTagName('fundingAmount') : array();
			
			foreach($FundingAmountType as $FundingAmount)
			{
				$FundingAmountCode = $FundingAmount -> getElementsByTagName('code') -> length > 0 ? $FundingAmount -> getElementsByTagName('code') -> item(0) -> nodeValue : '';
				$FundingAmountAmount = $FundingAmount -> getElementsByTagName('amount') -> length > 0 ? $FundingAmount -> getElementsByTagName('amount') -> item(0) -> nodeValue : '';
			}
			
			$BackupFundingSourceType = $FundingPlan -> getElementsByTagName('backupFundingSource') -> length > 0 ? $FundingPlan -> getElementsByTagName('backupFundingSource') : array();
			foreach($BackupFundingSourceType as $BackupFundingSource)
			{
				$BackupFundingSource_LastFourOfAccountNumber = $BackupFundingSource -> getElementsByTagName('lastFourOfAccountNumber') -> length > 0 ? $BackupFundingSource -> getElementsByTagName('lastFourOfAccountNumber') -> item(0) -> nodeValue : '';
				$BackupFundingSource_Type = $BackupFundingSource -> getElementsByTagName('type') -> length > 0 ? $BackupFundingSource -> getElementsByTagName('type') -> item(0) -> nodeValue : '';
				$BackupFundingSource_DisplayName = $BackupFundingSource -> getElementsByTagName('displayName') -> length > 0 ? $BackupFundingSource -> getElementsByTagName('displayName') -> item(0) -> nodeValue : '';
				$BackupFundingSource_FundingSourceID = $BackupFundingSource -> getElementsByTagName('fundingSourceId') -> length > 0 ? $BackupFundingSource -> getElementsByTagName('fundingSourceId') -> item(0) -> nodeValue : '';
				$BackupFundingSource_Allowed = $BackupFundingSource -> getElementsByTagName('allowed') -> length > 0 ? $BackupFundingSource -> getElementsByTagName('allowed') -> item(0) -> nodeValue : '';
			}
			
			$SenderFeesType = $FundingPlan -> getElementsByTagName('senderFees') -> length > 0 ? $FundingPlan -> getElementsByTagName('senderFees') : array();
			foreach($SenderFeesType as $SenderFees)
			{
				$SenderFeesCode = $SenderFees -> getElementsByTagName('code') -> length > 0 ? $SenderFees -> getElementsByTagName('code') -> item(0) -> nodeValue : '';
				$SenderFeesAmount = $SenderFees -> getElementsByTagName('amount') -> length > 0 ? $SenderFees -> getElementsByTagName('amount') -> item(0) -> nodeValue : '';
			}
			
			$CurrencyConversionType = $FundingPlan -> getElementsByTagName('currencyConversion') -> length > 0 ? $FundingPlan -> getElementsByTagName('currencyConversion') : array();
			foreach($CurrencyConversionType as $CurrencyConversion)
			{
				$CurrencyConversion_From = $CurrencyConversion -> getElementsByTagName('from') -> length > 0 ? $CurrencyConversion -> getElementsByTagName('from') -> item(0) -> nodeValue : '';
				$CurrencyConversion_To = $CurrencyConversion -> getElementsByTagName('to') -> length > 0 ? $CurrencyConversion -> getElementsByTagName('to') -> item(0) -> nodeValue : '';
				$CurrencyConversion_ExchangeRate = $CurrencyConversion -> getElementsByTagName('exchangeRate') -> length > 0 ? $CurrencyConversion -> getElementsByTagName('exchangeRate') -> item(0) -> nodeValue : '';
			}
			
			$FundingPlanChargeType = $FundingPlan -> getElementsByTagName('charge') -> length > 0 ? $FundingPlan -> getElementsByTagName('charge') : array();
			foreach($FundingPlanChargeType as $FundingPlanCharge)
			{
				$FundingPlanChargeCode = $FundingPlanCharge -> getElementsByTagName('code') -> length > 0 ? $FundingPlanCharge -> getElementsByTagName('code') -> item(0) -> nodeValue : '';
				$FundingPlanChargeAmount = $FundingPlanCharge -> getElementsByTagName('amount') -> length > 0 ? $FundingPlanCharge -> getElementsByTagName('amount') -> item(0) -> nodeValue : '';
				$FundingPlanChargeFundingSourceType = $FundingPlanCharge -> getElementsByTagName('fundingSource') -> length > 0 ? $FundingPlanCharge -> getElementsByTagName('fundingSource') : array();
				foreach($FundingPlanChargeFundingSourceType as $FundingPlanChargeFundingSource)
				{
					$FundingPlanChargeFundingSource_LastFourOfAccountNumber = $FundingPlanChargeFundingSource -> getElementsByTagName('lastFourOfAccountNumber') -> length > 0 ? $FundingPlanChargeFundingSource -> getElementsByTagName('lastFourOfAccountNumber') -> item(0) -> nodeValue : '';
					$FundingPlanChargeFundingSource_Type = $FundingPlanChargeFundingSource -> getElementsByTagName('type') -> length > 0 ? $FundingPlanChargeFundingSource -> getElementsByTagName('type') -> item(0) -> nodeValue : '';
					$FundingPlanChargeFundingSource_DisplayName = $FundingPlanChargeFundingSource -> getElementsByTagName('displayName') -> length > 0 ? $FundingPlanChargeFundingSource -> getElementsByTagName('displayName') -> item(0) -> nodeValue : '';
					$FundingPlanChargeFundingSource_FundingSourceID = $FundingPlanChargeFundingSource -> getElementsByTagName('fundingSourceId') -> length > 0 ? $FundingPlanChargeFundingSource -> getElementsByTagName('fundingSourceId') -> item(0) -> nodeValue : '';
					$FundingPlanChargeFundingSource_Allowed = $FundingPlanChargeFundingSource -> getElementsByTagName('allowed') -> length > 0 ? $FundingPlanChargeFundingSource -> getElementsByTagName('allowed') -> item(0) -> nodeValue : '';			
				}
			}			
		}
						
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'FundingPlanID' => isset($FundingPlanID) ? $FundingPlanID : '', 
								   'FundingAmountCode' => isset($FundingAmountCode) ? $FundingAmountCode : '', 
								   'FundingAmountAmount' => isset($FundingAmountAmount) ? $FundingAmountAmount : '', 
								   'SenderFeesCode' => isset($SenderFeesCode) ? $SenderFeesCode : '', 
								   'SenderFeesAmount' => isset($SenderFeesAmount) ? $SenderFeesAmount : '', 
								   'BackupFundingSource_LastFourOfAccountNumber' => isset($BackupFundingSource_LastFourOfAccountNumber) ? $BackupFundingSource_LastFourOfAccountNumber : '', 
								   'BackupFundingSource_Type' => isset($BackupFundingSource_Type) ? $BackupFundingSource_Type : '', 
								   'BackupFundingSource_DisplayName' => isset($BackupFundingSource_DisplayName) ? $BackupFundingSource_DisplayName : '', 
								   'BackupFundingSource_FundingSourceID' => isset($BackupFundingSource_FundingSourceID) ? $BackupFundingSource_FundingSourceID : '', 
								   'BackupFundingSource_Allowed' => isset($BackupFundingSource_Allowed) ? $BackupFundingSource_Allowed : '', 
								   'CurrencyConversion_From' => isset($CurrencyConversion_From) ? $CurrencyConversion_From : '', 
								   'CurrencyConversion_To' => isset($CurrencyConversion_To) ? $CurrencyConversion_To : '', 
								   'CurrencyConversion_ExchangeRate' => isset($CurrencyConversion_ExchangeRate) ? $CurrencyConversion_ExchangeRate : '', 
								   'FundingPlanChargeCode' => isset($FundingPlanChargeCode) ? $FundingPlanChargeCode : '', 
								   'FundingPlanChargeAmount' => isset($FundingPlanChargeAmount) ? $FundingPlanChargeAmount : '', 
								   'FundingPlanChargeFundingSource_LastFourOfAccountNumber' => isset($FundingPlanChargeFundingSource_LastFourOfAccountNumber) ? $FundingPlanChargeFundingSource_LastFourOfAccountNumber : '', 
								   'FundingPlanChargeFundingSource_Type' => isset($FundingPlanChargeFundingSource_Type) ? $FundingPlanChargeFundingSource_Type : '', 
								   'FundingPlanChargeFundingSource_DisplayName' => isset($FundingPlanChargeFundingSource_DisplayName) ? $FundingPlanChargeFundingSource_DisplayName : '', 
								   'FundingPlanChargeFundingSource_FundingSourceID' => isset($FundingPlanChargeFundingSource_FundingSourceID) ? $FundingPlanChargeFundingSource_FundingSourceID : '', 
								   'FundingPlanChargeFundingSource_Allowed' => isset($FundingPlanChargeFundingSource_Allowed) ? $FundingPlanChargeFundingSource_Allowed : '', 
								   'XMLRequest' => $XMLRequest,
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit GetShippingAddresses API request to PayPal.
	 *
	 * @access	public
	 * @param	string	$Paykey				PayKey returned from a previous Pay request.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function GetShippingAddresses($PayKey)
	 {
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<GetShippingAddressesRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $PayKey != '' ? '<key xmlns="">' . $PayKey . '</key>' : '';
		$XMLRequest .= '</GetShippingAddressesRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'AdaptivePayments', 'GetShippingAddresses');
		
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$AddresseeName = $DOM -> getElementsByTagName('addresseeName') -> length > 0 ? $DOM -> getElementsByTagName('addresseeName') -> item(0) -> nodeValue : '';
		$AddressID = $DOM -> getElementsByTagName('addressId') -> length > 0 ? $DOM -> getElementsByTagName('addressId') -> item(0) -> nodeValue : '';
		$Line1 = $DOM -> getElementsByTagName('line1') -> length > 0 ? $DOM -> getElementsByTagName('line1') -> item(0) -> nodeValue : '';
		$Line2 = $DOM -> getElementsByTagName('line2') -> length > 0 ? $DOM -> getElementsByTagName('line2') -> item(0) -> nodeValue : '';
		$City = $DOM -> getElementsByTagName('city') -> length > 0 ? $DOM -> getElementsByTagName('city') -> item(0) -> nodeValue : '';
		$State = $DOM -> getElementsByTagName('state') -> length > 0 ? $DOM -> getElementsByTagName('state') -> item(0) -> nodeValue : '';
		$PostalCode = $DOM -> getElementsByTagName('postalCode') -> length > 0 ? $DOM -> getElementsByTagName('postalCode') -> item(0) -> nodeValue : '';
		$CountryCode = $DOM -> getElementsByTagName('countryCode') -> length > 0 ? $DOM -> getElementsByTagName('countryCode') -> item(0) -> nodeValue : '';
		$Type = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'AddresseeName' => $AddresseeName, 
								   'AddressID' => $AddressID, 
								   'Line1' => $Line1, 
								   'Line2' => $Line2, 
								   'City' => $City, 
								   'State' => $State, 
								   'PostalCode' => $PostalCode, 
								   'CountryCode' => $CountryCode, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit CreateInvoice API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function CreateInvoice($DataArray)
	 {
		$CreateInvoiceFields = isset($DataArray['CreateInvoiceFields']) ? $DataArray['CreateInvoiceFields'] : array();
		$MerchantEmail = isset($CreateInvoiceFields['MerchantEmail']) ? $CreateInvoiceFields['MerchantEmail'] : '';
		$PayerEmail = isset($CreateInvoiceFields['PayerEmail']) ? $CreateInvoiceFields['PayerEmail'] : '';
		$Number = isset($CreateInvoiceFields['Number']) ? $CreateInvoiceFields['Number'] : '';
		$CurrencyCode = isset($CreateInvoiceFields['CurrencyCode']) ? $CreateInvoiceFields['CurrencyCode'] : '';
		$InvoiceDate = isset($CreateInvoiceFields['InvoiceDate']) ? $CreateInvoiceFields['InvoiceDate'] : '';
		$DueDate = isset($CreateInvoiceFields['DueDate']) ? $CreateInvoiceFields['DueDate'] : '';
		$PaymentTerms = isset($CreateInvoiceFields['PaymentTerms']) ? $CreateInvoiceFields['PaymentTerms'] : '';
		$DiscountPercent = isset($CreateInvoiceFields['DiscountPercent']) ? $CreateInvoiceFields['DiscountPercent'] : '';
		$DiscountAmount = isset($CreateInvoiceFields['DiscountAmount']) ? $CreateInvoiceFields['DiscountAmount'] : '';
		$Terms = isset($CreateInvoiceFields['Terms']) ? $CreateInvoiceFields['Terms'] : '';
		$Note = isset($CreateInvoiceFields['Note']) ? $CreateInvoiceFields['Note'] : '';
		$MerchantMemo = isset($CreateInvoiceFields['MerchantMemo']) ? $CreateInvoiceFields['MerchantMemo'] : '';
		$ShippingAmount = isset($CreateInvoiceFields['ShippingAmount']) ? $CreateInvoiceFields['ShippingAmount'] : '';
		$ShippingTaxName = isset($CreateInvoiceFields['ShippingTaxName']) ? $CreateInvoiceFields['ShippingTaxName'] : '';
		$ShippingTaxRate = isset($CreateInvoiceFields['ShippingTaxRate']) ? $CreateInvoiceFields['ShippingTaxRate'] : '';
		$LogoURL = isset($CreateInvoiceFields['LogoURL']) ? $CreateInvoiceFields['LogoURL'] : '';
		
		$BusinessInfo = isset($DataArray['BusinessInfo']) ? $DataArray['BusinessInfo'] : array();
		$BusinessFirstName = isset($BusinessInfo['FirstName']) ? $BusinessInfo['FirstName'] : '';
		$BusinessLastName = isset($BusinessInfo['LastName']) ? $BusinessInfo['LastName'] : '';
		$BusinessBusinessName = isset($BusinessInfo['BusinessName']) ? $BusinessInfo['BusinessName'] : '';
		$BusinessPhone = isset($BusinessInfo['Phone']) ? $BusinessInfo['Phone'] : '';
		$BusinessFax = isset($BusinessInfo['Fax']) ? $BusinessInfo['Fax'] : '';
		$BusinessWebsite = isset($BusinessInfo['Website']) ? $BusinessInfo['Website'] : '';
		$BusinessCustom = isset($BusinessInfo['Custom']) ? $BusinessInfo['Custom'] : '';
		
		$BusinessInfoAddress = isset($DataArray['BusinessInfoAddress']) ? $DataArray['BusinessInfoAddress'] : array();
		$BusinessInfoAddressLine1 = isset($BusinessInfoAddress['Line1']) ? $BusinessInfoAddress['Line1'] : '';
		$BusinessInfoAddressLine2 = isset($BusinessInfoAddress['Line2']) ? $BusinessInfoAddress['Line2'] : '';
		$BusinessInfoAddressCity = isset($BusinessInfoAddress['City']) ? $BusinessInfoAddress['City'] : '';
		$BusinessInfoAddressState = isset($BusinessInfoAddress['State']) ? $BusinessInfoAddress['State'] : '';
		$BusinessInfoAddressPostalCode = isset($BusinessInfoAddress['PostalCode']) ? $BusinessInfoAddress['PostalCode'] : '';
		$BusinessInfoAddressCountryCode = isset($BusinessInfoAddress['CountryCode']) ? $BusinessInfoAddress['CountryCode'] : '';
		$BusinessInfoAddressType = isset($BusinessInfoAddress['AddressType']) ? $BusinessInfoAddress['AddressType'] : '';
		
		$BillingInfo = isset($DataArray['BillingInfo']) ? $DataArray['BillingInfo'] : array();
		$BillingFirstName = isset($BillingInfo['FirstName']) ? $BillingInfo['FirstName'] : '';
		$BillingLastName = isset($BillingInfo['LastName']) ? $BillingInfo['LastName'] : '';
		$BillingBusinessName = isset($BillingInfo['BusinessName']) ? $BillingInfo['BusinessName'] : '';
		$BillingPhone = isset($BillingInfo['Phone']) ? $BillingInfo['Phone'] : '';
		$BillingFax = isset($BillingInfo['Fax']) ? $BillingInfo['Fax'] : '';
		$BillingWebsite = isset($BillingInfo['Website']) ? $BillingInfo['Website'] : '';
		$BillingCustom = isset($BillingInfo['Custom']) ? $BillingInfo['Custom'] : '';
		
		$BillingInfoAddress = isset($DataArray['BillingInfoAddress']) ? $DataArray['BillingInfoAddress'] : array();
		$BillingInfoAddressLine1 = isset($BillingInfoAddress['Line1']) ? $BillingInfoAddress['Line1'] : '';
		$BillingInfoAddressLine2 = isset($BillingInfoAddress['Line2']) ? $BillingInfoAddress['Line2'] : '';
		$BillingInfoAddressCity = isset($BillingInfoAddress['City']) ? $BillingInfoAddress['City'] : '';
		$BillingInfoAddressState = isset($BillingInfoAddress['State']) ? $BillingInfoAddress['State'] : '';
		$BillingInfoAddressPostalCode = isset($BillingInfoAddress['PostalCode']) ? $BillingInfoAddress['PostalCode'] : '';
		$BillingInfoAddressCountryCode = isset($BillingInfoAddress['CountryCode']) ? $BillingInfoAddress['CountryCode'] : '';
		$BillingInfoAddressType = isset($BillingInfoAddress['AddressType']) ? $BillingInfoAddress['AddressType'] : '';
		
		$ShippingInfo = isset($DataArray['ShippingInfo']) ? $DataArray['ShippingInfo'] : array();
		$ShippingFirstName = isset($ShippingInfo['FirstName']) ? $ShippingInfo['FirstName'] : '';
		$ShippingLastName = isset($ShippingInfo['LastName']) ? $ShippingInfo['LastName'] : '';
		$ShippingBusinessName = isset($ShippingInfo['BusinessName']) ? $ShippingInfo['BusinessName'] : '';
		$ShippingPhone = isset($ShippingInfo['Phone']) ? $ShippingInfo['Phone'] : '';
		$ShippingFax = isset($ShippingInfo['Fax']) ? $ShippingInfo['Fax'] : '';
		$ShippingWebsite = isset($ShippingInfo['Website']) ? $ShippingInfo['Website'] : '';
		$ShippingCustom = isset($ShippingInfo['Custom']) ? $ShippingInfo['Custom'] : '';
		
		$ShippingInfoAddress = isset($DataArray['ShippingInfoAddress']) ? $DataArray['ShippingInfoAddress'] : array();
		$ShippingInfoAddressLine1 = isset($ShippingInfoAddress['Line1']) ? $ShippingInfoAddress['Line1'] : '';
		$ShippingInfoAddressLine2 = isset($ShippingInfoAddress['Line2']) ? $ShippingInfoAddress['Line2'] : '';
		$ShippingInfoAddressCity = isset($ShippingInfoAddress['City']) ? $ShippingInfoAddress['City'] : '';
		$ShippingInfoAddressState = isset($ShippingInfoAddress['State']) ? $ShippingInfoAddress['State'] : '';
		$ShippingInfoAddressPostalCode = isset($ShippingInfoAddress['PostalCode']) ? $ShippingInfoAddress['PostalCode'] : '';
		$ShippingInfoAddressCountryCode = isset($ShippingInfoAddress['CountryCode']) ? $ShippingInfoAddress['CountryCode'] : '';
		$ShippingInfoAddressType = isset($ShippingInfoAddress['AddressType']) ? $ShippingInfoAddress['AddressType'] : '';
				
		$InvoiceItems = isset($DataArray['InvoiceItems']) ? $DataArray['InvoiceItems'] : array();
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<CreateInvoiceRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= '<invoice xmlns="">';
		$XMLRequest .= $MerchantEmail != '' ? '<merchantEmail xmlns="">' . $MerchantEmail . '</merchantEmail>' : '';
		$XMLRequest .= $PayerEmail != '' ? '<payerEmail xmlns="">' . $PayerEmail . '</payerEmail>' : '';
		$XMLRequest .= $Number != '' ? '<number xmlns="">' . $Number . '</number>' : '';
		
		if(!empty($BusinessInfo))
		{
			$XMLRequest .= '<merchantInfo xmlns="">';
			$XMLRequest .= $BusinessFirstName != '' ? '<firstName xmlns="">' . $BusinessFirstName . '</firstName>' : '';
			$XMLRequest .= $BusinessLastName != '' ? '<lastName xmlns="">' . $BusinessLastName . '</lastName>' : '';
			$XMLRequest .= $BusinessBusinessName != '' ? '<businessName xmlns="">' . $BusinessBusinessName . '</businessName>' : '';
			$XMLRequest .= $BusinessPhone != '' ? '<phone xmlns="">' . $BusinessPhone . '</phone>' : '';
			$XMLRequest .= $BusinessFax != '' ? '<fax xmlns="">' . $BusinessFax . '</fax>' : '';
			$XMLRequest .= $BusinessWebsite != '' ? '<website xmlns="">' . $BusinessWebsite . '</website>' : '';
			$XMLRequest .= $BusinessCustom != '' ? '<customValue xmlns="">' . $BusinessCustom . '</customValue>' : '';
			
			if(!empty($BusinessInfoAddress))
			{
				$XMLRequest .= '<address xmlns="">';
				$XMLRequest .= $BusinessInfoAddressLine1 != '' ? '<line1 xmlns="">' . $BusinessInfoAddressLine1 . '</line1>' : '';
				$XMLRequest .= $BusinessInfoAddressLine2 != '' ? '<line2 xmlns="">' . $BusinessInfoAddressLine2 . '</line2>' : '';
				$XMLRequest .= $BusinessInfoAddressCity != '' ? '<city xmlns="">' . $BusinessInfoAddressCity . '</city>' : '';
				$XMLRequest .= $BusinessInfoAddressState != '' ? '<state xmlns="">' . $BusinessInfoAddressState . '</state>' : '';
				$XMLRequest .= $BusinessInfoAddressPostalCode != '' ? '<postalCode xmlns="">' . $BusinessInfoAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $BusinessInfoAddressCountryCode != '' ? '<countryCode xmlns="">' . $BusinessInfoAddressCountryCode . '</countryCode>' : '';
				$XMLRequest .= $BusinessInfoAddressType != '' ? '<type xmlns="">' . $BusinessInfoAddressType . '</type>' : '';
				$XMLRequest .= '</address>';
			}
			$XMLRequest .= '</merchantInfo>';
		}
		
		if(!empty($InvoiceItems))
		{
			$XMLRequest .= '<itemList xmlns="">';
			foreach($InvoiceItems as $InvoiceItem)
			{
				$XMLRequest .= '<item xmlns="">';
				$XMLRequest .= $InvoiceItem['Name'] != '' ? '<name xmlns="">' . $InvoiceItem['Name'] . '</name>' : '';
				$XMLRequest .= $InvoiceItem['Description'] != '' ? '<description xmlns="">' . $InvoiceItem['Description'] . '</description>' : '';
				$XMLRequest .= $InvoiceItem['Date'] != '' ? '<date xmlns="">' . $InvoiceItem['Date'] . '</date>' : '';
				$XMLRequest .= $InvoiceItem['Quantity'] != '' ? '<quantity xmlns="">' . $InvoiceItem['Quantity'] . '</quantity>' : '';
				$XMLRequest .= $InvoiceItem['UnitPrice'] != '' ? '<unitPrice xmlns="">' . $InvoiceItem['UnitPrice'] . '</unitPrice>' : '';
				$XMLRequest .= $InvoiceItem['TaxName'] != '' ? '<taxName xmlns="">' . $InvoiceItem['TaxName'] . '</taxName>' : '';
				$XMLRequest .= $InvoiceItem['TaxRate'] != '' ? '<taxRate xmlns="">' . $InvoiceItem['TaxRate'] . '</taxRate>' : '';
				$XMLRequest .= '</item>';	
			}
			$XMLRequest .= '</itemList>';
		}
		
		$XMLRequest .= $CurrencyCode != '' ? '<currencyCode xmlns="">' . $CurrencyCode . '</currencyCode>' : '';
		$XMLRequest .= $InvoiceDate != '' ? '<invoiceDate xmlns="">' . $InvoiceDate . '</invoiceDate>' : '';
		$XMLRequest .= $DueDate != '' ? '<dueDate xmlns="">' . $DueDate . '</dueDate>' : '';
		$XMLRequest .= $PaymentTerms != '' ? '<paymentTerms xmlns="">' . $PaymentTerms . '</paymentTerms>' : '';
		$XMLRequest .= $DiscountPercent != '' ? '<discountPercent xmlns="">' . $DiscountPercent . '</discountPercent>' : '';
		$XMLRequest .= $DiscountAmount != '' ? '<discountAmount xmlns="">' . $DiscountAmount . '</discountAmount>' : '';
		$XMLRequest .= $Terms != '' ? '<terms xmlns="">' . $Terms . '</terms>' : '';
		$XMLRequest .= $Note != '' ? '<note xmlns="">' . $Note . '</note>' : '';
		$XMLRequest .= $MerchantMemo != '' ? '<merchantMemo xmlns="">' . $MerchantMemo . '</merchantMemo>' : '';
		
		if(!empty($BillingInfo))
		{
			$XMLRequest .= '<billingInfo xmlns="">';
			$XMLRequest .= $BillingFirstName != '' ? '<firstName xmlns="">' . $BillingFirstName . '</firstName>' : '';
			$XMLRequest .= $BillingLastName != '' ? '<lastName xmlns="">' . $BillingLastName . '</lastName>' : '';
			$XMLRequest .= $BillingBusinessName != '' ? '<businessName xmlns="">' . $BillingBusinessName . '</businessName>' : '';
			$XMLRequest .= $BillingPhone != '' ? '<phone xmlns="">' . $BillingPhone . '</phone>' : '';
			$XMLRequest .= $BillingFax != '' ? '<fax xmlns="">' . $BillingFax . '</fax>' : '';
			$XMLRequest .= $BillingWebsite != '' ? '<website xmlns="">' . $BillingWebsite . '</website>' : '';
			$XMLRequest .= $BillingCustom != '' ? '<customValue xmlns="">' . $BillingCustom . '</customValue>' : '';
			
			if(!empty($BillingInfoAddress))
			{
				$XMLRequest .= '<address xmlns="">';
				$XMLRequest .= $BillingInfoAddressLine1 != '' ? '<line1 xmlns="">' . $BillingInfoAddressLine1 . '</line1>' : '';
				$XMLRequest .= $BillingInfoAddressLine2 != '' ? '<line2 xmlns="">' . $BillingInfoAddressLine2 . '</line2>' : '';
				$XMLRequest .= $BillingInfoAddressCity != '' ? '<city xmlns="">' . $BillingInfoAddressCity . '</city>' : '';
				$XMLRequest .= $BillingInfoAddressState != '' ? '<state xmlns="">' . $BillingInfoAddressState . '</state>' : '';
				$XMLRequest .= $BillingInfoAddressPostalCode != '' ? '<postalCode xmlns="">' . $BillingInfoAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $BillingInfoAddressCountryCode != '' ? '<countryCode xmlns="">' . $BillingInfoAddressCountryCode . '</countryCode>' : '';
				$XMLRequest .= $BillingInfoAddressType != '' ? '<type xmlns="">' . $BillingInfoAddressType . '</type>' : '';
				$XMLRequest .= '</address>';
			}
			$XMLRequest .= '</billingInfo>';
		}
		
		if(!empty($ShippingInfo))
		{
			$XMLRequest .= '<shippingInfo xmlns="">';
			$XMLRequest .= $ShippingFirstName != '' ? '<firstName xmlns="">' . $ShippingFirstName . '</firstName>' : '';
			$XMLRequest .= $ShippingLastName != '' ? '<lastName xmlns="">' . $ShippingLastName . '</lastName>' : '';
			$XMLRequest .= $ShippingBusinessName != '' ? '<businessName xmlns="">' . $ShippingBusinessName . '</businessName>' : '';
			$XMLRequest .= $ShippingPhone != '' ? '<phone xmlns="">' . $ShippingPhone . '</phone>' : '';
			$XMLRequest .= $ShippingFax != '' ? '<fax xmlns="">' . $ShippingFax . '</fax>' : '';
			$XMLRequest .= $ShippingWebsite != '' ? '<website xmlns="">' . $ShippingWebsite . '</website>' : '';
			$XMLRequest .= $ShippingCustom != '' ? '<customValue xmlns="">' . $ShippingCustom . '</customValue>' : '';
			
			if(!empty($ShippingInfoAddress))
			{
				$XMLRequest .= '<address xmlns="">';
				$XMLRequest .= $ShippingInfoAddressLine1 != '' ? '<line1 xmlns="">' . $ShippingInfoAddressLine1 . '</line1>' : '';
				$XMLRequest .= $ShippingInfoAddressLine2 != '' ? '<line2 xmlns="">' . $ShippingInfoAddressLine2 . '</line2>' : '';
				$XMLRequest .= $ShippingInfoAddressCity != '' ? '<city xmlns="">' . $ShippingInfoAddressCity . '</city>' : '';
				$XMLRequest .= $ShippingInfoAddressState != '' ? '<state xmlns="">' . $ShippingInfoAddressState . '</state>' : '';
				$XMLRequest .= $ShippingInfoAddressPostalCode != '' ? '<postalCode xmlns="">' . $ShippingInfoAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $ShippingInfoAddressCountryCode != '' ? '<countryCode xmlns="">' . $ShippingInfoAddressCountryCode . '</countryCode>' : '';
				$XMLRequest .= $ShippingInfoAddressType != '' ? '<type xmlns="">' . $ShippingInfoAddressType . '</type>' : '';
				$XMLRequest .= '</address>';
			}
			$XMLRequest .= '</shippingInfo>';
		}
		
		$XMLRequest .= $ShippingAmount != '' ? '<shippingAmount xmlns="">' . $ShippingAmount . '</shippingAmount>' : '';
		$XMLRequest .= $ShippingTaxName != '' ? '<shippingTaxName xmlns="">' . $ShippingTaxName . '</shippingTaxName>' : '';
		$XMLRequest .= $ShippingTaxRate != '' ? '<shippingTaxRate xmlns="">' . $ShippingTaxRate . '</shippingTaxRate>' : '';
		$XMLRequest .= $LogoURL != '' ? '<logoUrl xmlns="">' . $LogoURL . '</logoUrl>' : '';
		$XMLRequest .= '<referrerCode xmlns="">'.$this->APIButtonSource.'</referrerCode>';
		$XMLRequest .= '</invoice>';
		$XMLRequest .= '</CreateInvoiceRequest>';
		 
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'CreateInvoice');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$InvoiceID = $DOM -> getElementsByTagName('invoiceID') -> length > 0 ? $DOM -> getElementsByTagName('invoiceID') -> item(0) -> nodeValue : '';
		$InvoiceNumber = $DOM -> getElementsByTagName('invoiceNumber') -> length > 0 ? $DOM -> getElementsByTagName('invoiceNumber') -> item(0) -> nodeValue : '';
		$InvoiceURL = $DOM -> getElementsByTagName('invoiceURL') -> length > 0 ? $DOM -> getElementsByTagName('invoiceURL') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'InvoiceID' => $InvoiceID, 
								   'InvoiceNumber' => $InvoiceNumber, 
								   'InvoiceURL' => $InvoiceURL,  
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit SendInvoice API request to PayPal.
	 *
	 * @access	public
	 * @param	string	$InvoiceID	Invoice ID of the invoice to be sent.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function SendInvoice($InvoiceID)
	 {
		 // Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<SendInvoiceRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $InvoiceID != '' ? '<invoiceID xmlns="">' . $InvoiceID . '</invoiceID>' : '';
		$XMLRequest .= '</SendInvoiceRequest>';
		
		 // Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'SendInvoice');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
				
		$InvoiceID = $DOM -> getElementsByTagName('invoiceID') -> length > 0 ? $DOM -> getElementsByTagName('invoiceID') -> item(0) -> nodeValue : '';
		$InvoiceURL = $DOM -> getElementsByTagName('invoiceURL') -> length > 0 ? $DOM -> getElementsByTagName('invoiceURL') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'InvoiceID' => $InvoiceID, 
								   'InvoiceURL' => $InvoiceURL,  
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
				
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit CreateAndSendInvoice API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function CreateAndSendInvoice($DataArray)
	 {
		$CreateInvoiceFields = isset($DataArray['CreateInvoiceFields']) ? $DataArray['CreateInvoiceFields'] : array();
		$MerchantEmail = isset($CreateInvoiceFields['MerchantEmail']) ? $CreateInvoiceFields['MerchantEmail'] : '';
		$PayerEmail = isset($CreateInvoiceFields['PayerEmail']) ? $CreateInvoiceFields['PayerEmail'] : '';
		$Number = isset($CreateInvoiceFields['Number']) ? $CreateInvoiceFields['Number'] : '';
		$CurrencyCode = isset($CreateInvoiceFields['CurrencyCode']) ? $CreateInvoiceFields['CurrencyCode'] : '';
		$InvoiceDate = isset($CreateInvoiceFields['InvoiceDate']) ? $CreateInvoiceFields['InvoiceDate'] : '';
		$DueDate = isset($CreateInvoiceFields['DueDate']) ? $CreateInvoiceFields['DueDate'] : '';
		$PaymentTerms = isset($CreateInvoiceFields['PaymentTerms']) ? $CreateInvoiceFields['PaymentTerms'] : '';
		$DiscountPercent = isset($CreateInvoiceFields['DiscountPercent']) ? $CreateInvoiceFields['DiscountPercent'] : '';
		$DiscountAmount = isset($CreateInvoiceFields['DiscountAmount']) ? $CreateInvoiceFields['DiscountAmount'] : '';
		$Terms = isset($CreateInvoiceFields['Terms']) ? $CreateInvoiceFields['Terms'] : '';
		$Note = isset($CreateInvoiceFields['Note']) ? $CreateInvoiceFields['Note'] : '';
		$MerchantMemo = isset($CreateInvoiceFields['MerchantMemo']) ? $CreateInvoiceFields['MerchantMemo'] : '';
		$ShippingAmount = isset($CreateInvoiceFields['ShippingAmount']) ? $CreateInvoiceFields['ShippingAmount'] : '';
		$ShippingTaxName = isset($CreateInvoiceFields['ShippingTaxName']) ? $CreateInvoiceFields['ShippingTaxName'] : '';
		$ShippingTaxRate = isset($CreateInvoiceFields['ShippingTaxRate']) ? $CreateInvoiceFields['ShippingTaxRate'] : '';
		$LogoURL = isset($CreateInvoiceFields['LogoURL']) ? $CreateInvoiceFields['LogoURL'] : '';
		
		$BusinessInfo = isset($DataArray['BusinessInfo']) ? $DataArray['BusinessInfo'] : array();
		$BusinessFirstName = isset($BusinessInfo['FirstName']) ? $BusinessInfo['FirstName'] : '';
		$BusinessLastName = isset($BusinessInfo['LastName']) ? $BusinessInfo['LastName'] : '';
		$BusinessBusinessName = isset($BusinessInfo['BusinessName']) ? $BusinessInfo['BusinessName'] : '';
		$BusinessPhone = isset($BusinessInfo['Phone']) ? $BusinessInfo['Phone'] : '';
		$BusinessFax = isset($BusinessInfo['Fax']) ? $BusinessInfo['Fax'] : '';
		$BusinessWebsite = isset($BusinessInfo['Website']) ? $BusinessInfo['Website'] : '';
		$BusinessCustom = isset($BusinessInfo['Custom']) ? $BusinessInfo['Custom'] : '';
		
		$BusinessInfoAddress = isset($DataArray['BusinessInfoAddress']) ? $DataArray['BusinessInfoAddress'] : array();
		$BusinessInfoAddressLine1 = isset($BusinessInfoAddress['Line1']) ? $BusinessInfoAddress['Line1'] : '';
		$BusinessInfoAddressLine2 = isset($BusinessInfoAddress['Line2']) ? $BusinessInfoAddress['Line2'] : '';
		$BusinessInfoAddressCity = isset($BusinessInfoAddress['City']) ? $BusinessInfoAddress['City'] : '';
		$BusinessInfoAddressState = isset($BusinessInfoAddress['State']) ? $BusinessInfoAddress['State'] : '';
		$BusinessInfoAddressPostalCode = isset($BusinessInfoAddress['PostalCode']) ? $BusinessInfoAddress['PostalCode'] : '';
		$BusinessInfoAddressCountryCode = isset($BusinessInfoAddress['CountryCode']) ? $BusinessInfoAddress['CountryCode'] : '';
		$BusinessInfoAddressType = isset($BusinessInfoAddress['AddressType']) ? $BusinessInfoAddress['AddressType'] : '';
		
		$BillingInfo = isset($DataArray['BillingInfo']) ? $DataArray['BillingInfo'] : array();
		$BillingFirstName = isset($BillingInfo['FirstName']) ? $BillingInfo['FirstName'] : '';
		$BillingLastName = isset($BillingInfo['LastName']) ? $BillingInfo['LastName'] : '';
		$BillingBusinessName = isset($BillingInfo['BusinessName']) ? $BillingInfo['BusinessName'] : '';
		$BillingPhone = isset($BillingInfo['Phone']) ? $BillingInfo['Phone'] : '';
		$BillingFax = isset($BillingInfo['Fax']) ? $BillingInfo['Fax'] : '';
		$BillingWebsite = isset($BillingInfo['Website']) ? $BillingInfo['Website'] : '';
		$BillingCustom = isset($BillingInfo['Custom']) ? $BillingInfo['Custom'] : '';
		
		$BillingInfoAddress = isset($DataArray['BillingInfoAddress']) ? $DataArray['BillingInfoAddress'] : array();
		$BillingInfoAddressLine1 = isset($BillingInfoAddress['Line1']) ? $BillingInfoAddress['Line1'] : '';
		$BillingInfoAddressLine2 = isset($BillingInfoAddress['Line2']) ? $BillingInfoAddress['Line2'] : '';
		$BillingInfoAddressCity = isset($BillingInfoAddress['City']) ? $BillingInfoAddress['City'] : '';
		$BillingInfoAddressState = isset($BillingInfoAddress['State']) ? $BillingInfoAddress['State'] : '';
		$BillingInfoAddressPostalCode = isset($BillingInfoAddress['PostalCode']) ? $BillingInfoAddress['PostalCode'] : '';
		$BillingInfoAddressCountryCode = isset($BillingInfoAddress['CountryCode']) ? $BillingInfoAddress['CountryCode'] : '';
		$BillingInfoAddressType = isset($BillingInfoAddress['AddressType']) ? $BillingInfoAddress['AddressType'] : '';
		
		$ShippingInfo = isset($DataArray['ShippingInfo']) ? $DataArray['ShippingInfo'] : array();
		$ShippingFirstName = isset($ShippingInfo['FirstName']) ? $ShippingInfo['FirstName'] : '';
		$ShippingLastName = isset($ShippingInfo['LastName']) ? $ShippingInfo['LastName'] : '';
		$ShippingBusinessName = isset($ShippingInfo['BusinessName']) ? $ShippingInfo['BusinessName'] : '';
		$ShippingPhone = isset($ShippingInfo['Phone']) ? $ShippingInfo['Phone'] : '';
		$ShippingFax = isset($ShippingInfo['Fax']) ? $ShippingInfo['Fax'] : '';
		$ShippingWebsite = isset($ShippingInfo['Website']) ? $ShippingInfo['Website'] : '';
		$ShippingCustom = isset($ShippingInfo['Custom']) ? $ShippingInfo['Custom'] : '';
		
		$ShippingInfoAddress = isset($DataArray['ShippingInfoAddress']) ? $DataArray['ShippingInfoAddress'] : array();
		$ShippingInfoAddressLine1 = isset($ShippingInfoAddress['Line1']) ? $ShippingInfoAddress['Line1'] : '';
		$ShippingInfoAddressLine2 = isset($ShippingInfoAddress['Line2']) ? $ShippingInfoAddress['Line2'] : '';
		$ShippingInfoAddressCity = isset($ShippingInfoAddress['City']) ? $ShippingInfoAddress['City'] : '';
		$ShippingInfoAddressState = isset($ShippingInfoAddress['State']) ? $ShippingInfoAddress['State'] : '';
		$ShippingInfoAddressPostalCode = isset($ShippingInfoAddress['PostalCode']) ? $ShippingInfoAddress['PostalCode'] : '';
		$ShippingInfoAddressCountryCode = isset($ShippingInfoAddress['CountryCode']) ? $ShippingInfoAddress['CountryCode'] : '';
		$ShippingInfoAddressType = isset($ShippingInfoAddress['AddressType']) ? $ShippingInfoAddress['AddressType'] : '';
				
		$InvoiceItems = isset($DataArray['InvoiceItems']) ? $DataArray['InvoiceItems'] : array();
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<CreateAndSendInvoiceRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= '<invoice xmlns="">';
		$XMLRequest .= $MerchantEmail != '' ? '<merchantEmail xmlns="">' . $MerchantEmail . '</merchantEmail>' : '';
		$XMLRequest .= $PayerEmail != '' ? '<payerEmail xmlns="">' . $PayerEmail . '</payerEmail>' : '';
		$XMLRequest .= $Number != '' ? '<number xmlns="">' . $Number . '</number>' : '';
		
		if(!empty($BusinessInfo))
		{
			$XMLRequest .= '<merchantInfo xmlns="">';
			$XMLRequest .= $BusinessFirstName != '' ? '<firstName xmlns="">' . $BusinessFirstName . '</firstName>' : '';
			$XMLRequest .= $BusinessLastName != '' ? '<lastName xmlns="">' . $BusinessLastName . '</lastName>' : '';
			$XMLRequest .= $BusinessBusinessName != '' ? '<businessName xmlns="">' . $BusinessBusinessName . '</businessName>' : '';
			$XMLRequest .= $BusinessPhone != '' ? '<phone xmlns="">' . $BusinessPhone . '</phone>' : '';
			$XMLRequest .= $BusinessFax != '' ? '<fax xmlns="">' . $BusinessFax . '</fax>' : '';
			$XMLRequest .= $BusinessWebsite != '' ? '<website xmlns="">' . $BusinessWebsite . '</website>' : '';
			$XMLRequest .= $BusinessCustom != '' ? '<customValue xmlns="">' . $BusinessCustom . '</customValue>' : '';
			
			if(!empty($BusinessInfoAddress))
			{
				$XMLRequest .= '<address xmlns="">';
				$XMLRequest .= $BusinessInfoAddressLine1 != '' ? '<line1 xmlns="">' . $BusinessInfoAddressLine1 . '</line1>' : '';
				$XMLRequest .= $BusinessInfoAddressLine2 != '' ? '<line2 xmlns="">' . $BusinessInfoAddressLine2 . '</line2>' : '';
				$XMLRequest .= $BusinessInfoAddressCity != '' ? '<city xmlns="">' . $BusinessInfoAddressCity . '</city>' : '';
				$XMLRequest .= $BusinessInfoAddressState != '' ? '<state xmlns="">' . $BusinessInfoAddressState . '</state>' : '';
				$XMLRequest .= $BusinessInfoAddressPostalCode != '' ? '<postalCode xmlns="">' . $BusinessInfoAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $BusinessInfoAddressCountryCode != '' ? '<countryCode xmlns="">' . $BusinessInfoAddressCountryCode . '</countryCode>' : '';
				$XMLRequest .= $BusinessInfoAddressType != '' ? '<type xmlns="">' . $BusinessInfoAddressType . '</type>' : '';
				$XMLRequest .= '</address>';
			}
			$XMLRequest .= '</merchantInfo>';
		}
		
		if(!empty($InvoiceItems))
		{
			$XMLRequest .= '<itemList xmlns="">';
			foreach($InvoiceItems as $InvoiceItem)
			{
				$XMLRequest .= '<item xmlns="">';
				$XMLRequest .= $InvoiceItem['Name'] != '' ? '<name xmlns="">' . $InvoiceItem['Name'] . '</name>' : '';
				$XMLRequest .= $InvoiceItem['Description'] != '' ? '<description xmlns="">' . $InvoiceItem['Description'] . '</description>' : '';
				$XMLRequest .= $InvoiceItem['Date'] != '' ? '<date xmlns="">' . $InvoiceItem['Date'] . '</date>' : '';
				$XMLRequest .= $InvoiceItem['Quantity'] != '' ? '<quantity xmlns="">' . $InvoiceItem['Quantity'] . '</quantity>' : '';
				$XMLRequest .= $InvoiceItem['UnitPrice'] != '' ? '<unitPrice xmlns="">' . $InvoiceItem['UnitPrice'] . '</unitPrice>' : '';
				$XMLRequest .= $InvoiceItem['TaxName'] != '' ? '<taxName xmlns="">' . $InvoiceItem['TaxName'] . '</taxName>' : '';
				$XMLRequest .= $InvoiceItem['TaxRate'] != '' ? '<taxRate xmlns="">' . $InvoiceItem['TaxRate'] . '</taxRate>' : '';
				$XMLRequest .= '</item>';	
			}
			$XMLRequest .= '</itemList>';
		}
		
		$XMLRequest .= $CurrencyCode != '' ? '<currencyCode xmlns="">' . $CurrencyCode . '</currencyCode>' : '';
		$XMLRequest .= $InvoiceDate != '' ? '<invoiceDate xmlns="">' . $InvoiceDate . '</invoiceDate>' : '';
		$XMLRequest .= $DueDate != '' ? '<dueDate xmlns="">' . $DueDate . '</dueDate>' : '';
		$XMLRequest .= $PaymentTerms != '' ? '<paymentTerms xmlns="">' . $PaymentTerms . '</paymentTerms>' : '';
		$XMLRequest .= $DiscountPercent != '' ? '<discountPercent xmlns="">' . $DiscountPercent . '</discountPercent>' : '';
		$XMLRequest .= $DiscountAmount != '' ? '<discountAmount xmlns="">' . $DiscountAmount . '</discountAmount>' : '';
		$XMLRequest .= $Terms != '' ? '<terms xmlns="">' . $Terms . '</terms>' : '';
		$XMLRequest .= $Note != '' ? '<note xmlns="">' . $Note . '</note>' : '';
		$XMLRequest .= $MerchantMemo != '' ? '<merchantMemo xmlns="">' . $MerchantMemo . '</merchantMemo>' : '';
		
		if(!empty($BillingInfo))
		{
			$XMLRequest .= '<billingInfo xmlns="">';
			$XMLRequest .= $BillingFirstName != '' ? '<firstName xmlns="">' . $BillingFirstName . '</firstName>' : '';
			$XMLRequest .= $BillingLastName != '' ? '<lastName xmlns="">' . $BillingLastName . '</lastName>' : '';
			$XMLRequest .= $BillingBusinessName != '' ? '<businessName xmlns="">' . $BillingBusinessName . '</businessName>' : '';
			$XMLRequest .= $BillingPhone != '' ? '<phone xmlns="">' . $BillingPhone . '</phone>' : '';
			$XMLRequest .= $BillingFax != '' ? '<fax xmlns="">' . $BillingFax . '</fax>' : '';
			$XMLRequest .= $BillingWebsite != '' ? '<website xmlns="">' . $BillingWebsite . '</website>' : '';
			$XMLRequest .= $BillingCustom != '' ? '<customValue xmlns="">' . $BillingCustom . '</customValue>' : '';
			
			if(!empty($BillingInfoAddress))
			{
				$XMLRequest .= '<address xmlns="">';
				$XMLRequest .= $BillingInfoAddressLine1 != '' ? '<line1 xmlns="">' . $BillingInfoAddressLine1 . '</line1>' : '';
				$XMLRequest .= $BillingInfoAddressLine2 != '' ? '<line2 xmlns="">' . $BillingInfoAddressLine2 . '</line2>' : '';
				$XMLRequest .= $BillingInfoAddressCity != '' ? '<city xmlns="">' . $BillingInfoAddressCity . '</city>' : '';
				$XMLRequest .= $BillingInfoAddressState != '' ? '<state xmlns="">' . $BillingInfoAddressState . '</state>' : '';
				$XMLRequest .= $BillingInfoAddressPostalCode != '' ? '<postalCode xmlns="">' . $BillingInfoAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $BillingInfoAddressCountryCode != '' ? '<countryCode xmlns="">' . $BillingInfoAddressCountryCode . '</countryCode>' : '';
				$XMLRequest .= $BillingInfoAddressType != '' ? '<type xmlns="">' . $BillingInfoAddressType . '</type>' : '';
				$XMLRequest .= '</address>';
			}
			$XMLRequest .= '</billingInfo>';
		}
		
		if(!empty($ShippingInfo))
		{
			$XMLRequest .= '<shippingInfo xmlns="">';
			$XMLRequest .= $ShippingFirstName != '' ? '<firstName xmlns="">' . $ShippingFirstName . '</firstName>' : '';
			$XMLRequest .= $ShippingLastName != '' ? '<lastName xmlns="">' . $ShippingLastName . '</lastName>' : '';
			$XMLRequest .= $ShippingBusinessName != '' ? '<businessName xmlns="">' . $ShippingBusinessName . '</businessName>' : '';
			$XMLRequest .= $ShippingPhone != '' ? '<phone xmlns="">' . $ShippingPhone . '</phone>' : '';
			$XMLRequest .= $ShippingFax != '' ? '<fax xmlns="">' . $ShippingFax . '</fax>' : '';
			$XMLRequest .= $ShippingWebsite != '' ? '<website xmlns="">' . $ShippingWebsite . '</website>' : '';
			$XMLRequest .= $ShippingCustom != '' ? '<customValue xmlns="">' . $ShippingCustom . '</customValue>' : '';
			
			if(!empty($ShippingInfoAddress))
			{
				$XMLRequest .= '<address xmlns="">';
				$XMLRequest .= $ShippingInfoAddressLine1 != '' ? '<line1 xmlns="">' . $ShippingInfoAddressLine1 . '</line1>' : '';
				$XMLRequest .= $ShippingInfoAddressLine2 != '' ? '<line2 xmlns="">' . $ShippingInfoAddressLine2 . '</line2>' : '';
				$XMLRequest .= $ShippingInfoAddressCity != '' ? '<city xmlns="">' . $ShippingInfoAddressCity . '</city>' : '';
				$XMLRequest .= $ShippingInfoAddressState != '' ? '<state xmlns="">' . $ShippingInfoAddressState . '</state>' : '';
				$XMLRequest .= $ShippingInfoAddressPostalCode != '' ? '<postalCode xmlns="">' . $ShippingInfoAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $ShippingInfoAddressCountryCode != '' ? '<countryCode xmlns="">' . $ShippingInfoAddressCountryCode . '</countryCode>' : '';
				$XMLRequest .= $ShippingInfoAddressType != '' ? '<type xmlns="">' . $ShippingInfoAddressType . '</type>' : '';
				$XMLRequest .= '</address>';
			}
			$XMLRequest .= '</shippingInfo>';
		}
		
		$XMLRequest .= $ShippingAmount != '' ? '<shippingAmount xmlns="">' . $ShippingAmount . '</shippingAmount>' : '';
		$XMLRequest .= $ShippingTaxName != '' ? '<shippingTaxName xmlns="">' . $ShippingTaxName . '</shippingTaxName>' : '';
		$XMLRequest .= $ShippingTaxRate != '' ? '<shippingTaxRate xmlns="">' . $ShippingTaxRate . '</shippingTaxRate>' : '';
		$XMLRequest .= $LogoURL != '' ? '<logoUrl xmlns="">' . $LogoURL . '</logoUrl>' : '';
		$XMLRequest .= '<referrerCode xmlns="">'.$this->APIButtonSource.'</referrerCode>';
		$XMLRequest .= '</invoice>';
		$XMLRequest .= '</CreateAndSendInvoiceRequest>';
		 
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'CreateAndSendInvoice');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$InvoiceID = $DOM -> getElementsByTagName('invoiceID') -> length > 0 ? $DOM -> getElementsByTagName('invoiceID') -> item(0) -> nodeValue : '';
		$InvoiceNumber = $DOM -> getElementsByTagName('invoiceNumber') -> length > 0 ? $DOM -> getElementsByTagName('invoiceNumber') -> item(0) -> nodeValue : '';
		$InvoiceURL = $DOM -> getElementsByTagName('invoiceURL') -> length > 0 ? $DOM -> getElementsByTagName('invoiceURL') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'InvoiceID' => $InvoiceID, 
								   'InvoiceNumber' => $InvoiceNumber, 
								   'InvoiceURL' => $InvoiceURL,  
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit UpdateInvoice API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function UpdateInvoice($DataArray)
	 {
		$UpdateInvoiceFields = isset($DataArray['UpdateInvoiceFields']) ? $DataArray['UpdateInvoiceFields'] : array();
		$InvoiceID = isset($UpdateInvoiceFields['InvoiceID']) ? $UpdateInvoiceFields['InvoiceID'] : '';
		$MerchantEmail = isset($UpdateInvoiceFields['MerchantEmail']) ? $UpdateInvoiceFields['MerchantEmail'] : '';
		$PayerEmail = isset($UpdateInvoiceFields['PayerEmail']) ? $UpdateInvoiceFields['PayerEmail'] : '';
		$Number = isset($UpdateInvoiceFields['Number']) ? $UpdateInvoiceFields['Number'] : '';
		$CurrencyCode = isset($UpdateInvoiceFields['CurrencyCode']) ? $UpdateInvoiceFields['CurrencyCode'] : '';
		$InvoiceDate = isset($UpdateInvoiceFields['InvoiceDate']) ? $UpdateInvoiceFields['InvoiceDate'] : '';
		$DueDate = isset($UpdateInvoiceFields['DueDate']) ? $UpdateInvoiceFields['DueDate'] : '';
		$PaymentTerms = isset($UpdateInvoiceFields['PaymentTerms']) ? $UpdateInvoiceFields['PaymentTerms'] : '';
		$DiscountPercent = isset($UpdateInvoiceFields['DiscountPercent']) ? $UpdateInvoiceFields['DiscountPercent'] : '';
		$DiscountAmount = isset($UpdateInvoiceFields['DiscountAmount']) ? $UpdateInvoiceFields['DiscountAmount'] : '';
		$Terms = isset($UpdateInvoiceFields['Terms']) ? $UpdateInvoiceFields['Terms'] : '';
		$Note = isset($UpdateInvoiceFields['Note']) ? $UpdateInvoiceFields['Note'] : '';
		$MerchantMemo = isset($UpdateInvoiceFields['MerchantMemo']) ? $UpdateInvoiceFields['MerchantMemo'] : '';
		$ShippingAmount = isset($UpdateInvoiceFields['ShippingAmount']) ? $UpdateInvoiceFields['ShippingAmount'] : '';
		$ShippingTaxName = isset($UpdateInvoiceFields['ShippingTaxName']) ? $UpdateInvoiceFields['ShippingTaxName'] : '';
		$ShippingTaxRate = isset($UpdateInvoiceFields['ShippingTaxRate']) ? $UpdateInvoiceFields['ShippingTaxRate'] : '';
		$LogoURL = isset($UpdateInvoiceFields['LogoURL']) ? $UpdateInvoiceFields['LogoURL'] : '';
		
		$BusinessInfo = isset($DataArray['BusinessInfo']) ? $DataArray['BusinessInfo'] : array();
		$BusinessFirstName = isset($BusinessInfo['FirstName']) ? $BusinessInfo['FirstName'] : '';
		$BusinessLastName = isset($BusinessInfo['LastName']) ? $BusinessInfo['LastName'] : '';
		$BusinessBusinessName = isset($BusinessInfo['BusinessName']) ? $BusinessInfo['BusinessName'] : '';
		$BusinessPhone = isset($BusinessInfo['Phone']) ? $BusinessInfo['Phone'] : '';
		$BusinessFax = isset($BusinessInfo['Fax']) ? $BusinessInfo['Fax'] : '';
		$BusinessWebsite = isset($BusinessInfo['Website']) ? $BusinessInfo['Website'] : '';
		$BusinessCustom = isset($BusinessInfo['Custom']) ? $BusinessInfo['Custom'] : '';
		
		$BusinessInfoAddress = isset($DataArray['BusinessInfoAddress']) ? $DataArray['BusinessInfoAddress'] : array();
		$BusinessInfoAddressLine1 = isset($BusinessInfoAddress['Line1']) ? $BusinessInfoAddress['Line1'] : '';
		$BusinessInfoAddressLine2 = isset($BusinessInfoAddress['Line2']) ? $BusinessInfoAddress['Line2'] : '';
		$BusinessInfoAddressCity = isset($BusinessInfoAddress['City']) ? $BusinessInfoAddress['City'] : '';
		$BusinessInfoAddressState = isset($BusinessInfoAddress['State']) ? $BusinessInfoAddress['State'] : '';
		$BusinessInfoAddressPostalCode = isset($BusinessInfoAddress['PostalCode']) ? $BusinessInfoAddress['PostalCode'] : '';
		$BusinessInfoAddressCountryCode = isset($BusinessInfoAddress['CountryCode']) ? $BusinessInfoAddress['CountryCode'] : '';
		$BusinessInfoAddressType = isset($BusinessInfoAddress['AddressType']) ? $BusinessInfoAddress['AddressType'] : '';
		
		$BillingInfo = isset($DataArray['BillingInfo']) ? $DataArray['BillingInfo'] : array();
		$BillingFirstName = isset($BillingInfo['FirstName']) ? $BillingInfo['FirstName'] : '';
		$BillingLastName = isset($BillingInfo['LastName']) ? $BillingInfo['LastName'] : '';
		$BillingBusinessName = isset($BillingInfo['BusinessName']) ? $BillingInfo['BusinessName'] : '';
		$BillingPhone = isset($BillingInfo['Phone']) ? $BillingInfo['Phone'] : '';
		$BillingFax = isset($BillingInfo['Fax']) ? $BillingInfo['Fax'] : '';
		$BillingWebsite = isset($BillingInfo['Website']) ? $BillingInfo['Website'] : '';
		$BillingCustom = isset($BillingInfo['Custom']) ? $BillingInfo['Custom'] : '';
		
		$BillingInfoAddress = isset($DataArray['BillingInfoAddress']) ? $DataArray['BillingInfoAddress'] : array();
		$BillingInfoAddressLine1 = isset($BillingInfoAddress['Line1']) ? $BillingInfoAddress['Line1'] : '';
		$BillingInfoAddressLine2 = isset($BillingInfoAddress['Line2']) ? $BillingInfoAddress['Line2'] : '';
		$BillingInfoAddressCity = isset($BillingInfoAddress['City']) ? $BillingInfoAddress['City'] : '';
		$BillingInfoAddressState = isset($BillingInfoAddress['State']) ? $BillingInfoAddress['State'] : '';
		$BillingInfoAddressPostalCode = isset($BillingInfoAddress['PostalCode']) ? $BillingInfoAddress['PostalCode'] : '';
		$BillingInfoAddressCountryCode = isset($BillingInfoAddress['CountryCode']) ? $BillingInfoAddress['CountryCode'] : '';
		$BillingInfoAddressType = isset($BillingInfoAddress['AddressType']) ? $BillingInfoAddress['AddressType'] : '';
		
		$ShippingInfo = isset($DataArray['ShippingInfo']) ? $DataArray['ShippingInfo'] : array();
		$ShippingFirstName = isset($ShippingInfo['FirstName']) ? $ShippingInfo['FirstName'] : '';
		$ShippingLastName = isset($ShippingInfo['LastName']) ? $ShippingInfo['LastName'] : '';
		$ShippingBusinessName = isset($ShippingInfo['BusinessName']) ? $ShippingInfo['BusinessName'] : '';
		$ShippingPhone = isset($ShippingInfo['Phone']) ? $ShippingInfo['Phone'] : '';
		$ShippingFax = isset($ShippingInfo['Fax']) ? $ShippingInfo['Fax'] : '';
		$ShippingWebsite = isset($ShippingInfo['Website']) ? $ShippingInfo['Website'] : '';
		$ShippingCustom = isset($ShippingInfo['Custom']) ? $ShippingInfo['Custom'] : '';
		
		$ShippingInfoAddress = isset($DataArray['ShippingInfoAddress']) ? $DataArray['ShippingInfoAddress'] : array();
		$ShippingInfoAddressLine1 = isset($ShippingInfoAddress['Line1']) ? $ShippingInfoAddress['Line1'] : '';
		$ShippingInfoAddressLine2 = isset($ShippingInfoAddress['Line2']) ? $ShippingInfoAddress['Line2'] : '';
		$ShippingInfoAddressCity = isset($ShippingInfoAddress['City']) ? $ShippingInfoAddress['City'] : '';
		$ShippingInfoAddressState = isset($ShippingInfoAddress['State']) ? $ShippingInfoAddress['State'] : '';
		$ShippingInfoAddressPostalCode = isset($ShippingInfoAddress['PostalCode']) ? $ShippingInfoAddress['PostalCode'] : '';
		$ShippingInfoAddressCountryCode = isset($ShippingInfoAddress['CountryCode']) ? $ShippingInfoAddress['CountryCode'] : '';
		$ShippingInfoAddressType = isset($ShippingInfoAddress['AddressType']) ? $ShippingInfoAddress['AddressType'] : '';
				
		$InvoiceItems = isset($DataArray['InvoiceItems']) ? $DataArray['InvoiceItems'] : array();
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<UpdateInvoiceRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $InvoiceID != '' ? '<invoiceID xmlns="">' . $InvoiceID . '</invoiceID>' : '';
		$XMLRequest .= '<invoice xmlns="">';
		$XMLRequest .= $MerchantEmail != '' ? '<merchantEmail xmlns="">' . $MerchantEmail . '</merchantEmail>' : '';
		$XMLRequest .= $PayerEmail != '' ? '<payerEmail xmlns="">' . $PayerEmail . '</payerEmail>' : '';
		$XMLRequest .= $Number != '' ? '<number xmlns="">' . $Number . '</number>' : '';
		
		if(!empty($BusinessInfo))
		{
			$XMLRequest .= '<merchantInfo xmlns="">';
			$XMLRequest .= $BusinessFirstName != '' ? '<firstName xmlns="">' . $BusinessFirstName . '</firstName>' : '';
			$XMLRequest .= $BusinessLastName != '' ? '<lastName xmlns="">' . $BusinessLastName . '</lastName>' : '';
			$XMLRequest .= $BusinessBusinessName != '' ? '<businessName xmlns="">' . $BusinessBusinessName . '</businessName>' : '';
			$XMLRequest .= $BusinessPhone != '' ? '<phone xmlns="">' . $BusinessPhone . '</phone>' : '';
			$XMLRequest .= $BusinessFax != '' ? '<fax xmlns="">' . $BusinessFax . '</fax>' : '';
			$XMLRequest .= $BusinessWebsite != '' ? '<website xmlns="">' . $BusinessWebsite . '</website>' : '';
			$XMLRequest .= $BusinessCustom != '' ? '<customValue xmlns="">' . $BusinessCustom . '</customValue>' : '';
			
			if(!empty($BusinessInfoAddress))
			{
				$XMLRequest .= '<address xmlns="">';
				$XMLRequest .= $BusinessInfoAddressLine1 != '' ? '<line1 xmlns="">' . $BusinessInfoAddressLine1 . '</line1>' : '';
				$XMLRequest .= $BusinessInfoAddressLine2 != '' ? '<line2 xmlns="">' . $BusinessInfoAddressLine2 . '</line2>' : '';
				$XMLRequest .= $BusinessInfoAddressCity != '' ? '<city xmlns="">' . $BusinessInfoAddressCity . '</city>' : '';
				$XMLRequest .= $BusinessInfoAddressState != '' ? '<state xmlns="">' . $BusinessInfoAddressState . '</state>' : '';
				$XMLRequest .= $BusinessInfoAddressPostalCode != '' ? '<postalCode xmlns="">' . $BusinessInfoAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $BusinessInfoAddressCountryCode != '' ? '<countryCode xmlns="">' . $BusinessInfoAddressCountryCode . '</countryCode>' : '';
				$XMLRequest .= $BusinessInfoAddressType != '' ? '<type xmlns="">' . $BusinessInfoAddressType . '</type>' : '';
				$XMLRequest .= '</address>';
			}
			$XMLRequest .= '</merchantInfo>';
		}
		
		if(!empty($InvoiceItems))
		{
			$XMLRequest .= '<itemList xmlns="">';
			foreach($InvoiceItems as $InvoiceItem)
			{
				$XMLRequest .= '<item xmlns="">';
				$XMLRequest .= $InvoiceItem['Name'] != '' ? '<name xmlns="">' . $InvoiceItem['Name'] . '</name>' : '';
				$XMLRequest .= $InvoiceItem['Description'] != '' ? '<description xmlns="">' . $InvoiceItem['Description'] . '</description>' : '';
				$XMLRequest .= $InvoiceItem['Date'] != '' ? '<date xmlns="">' . $InvoiceItem['Date'] . '</date>' : '';
				$XMLRequest .= $InvoiceItem['Quantity'] != '' ? '<quantity xmlns="">' . $InvoiceItem['Quantity'] . '</quantity>' : '';
				$XMLRequest .= $InvoiceItem['UnitPrice'] != '' ? '<unitPrice xmlns="">' . $InvoiceItem['UnitPrice'] . '</unitPrice>' : '';
				$XMLRequest .= $InvoiceItem['TaxName'] != '' ? '<taxName xmlns="">' . $InvoiceItem['TaxName'] . '</taxName>' : '';
				$XMLRequest .= $InvoiceItem['TaxRate'] != '' ? '<taxRate xmlns="">' . $InvoiceItem['TaxRate'] . '</taxRate>' : '';
				$XMLRequest .= '</item>';	
			}
			$XMLRequest .= '</itemList>';
		}
		
		$XMLRequest .= $CurrencyCode != '' ? '<currencyCode xmlns="">' . $CurrencyCode . '</currencyCode>' : '';
		$XMLRequest .= $InvoiceDate != '' ? '<invoiceDate xmlns="">' . $InvoiceDate . '</invoiceDate>' : '';
		$XMLRequest .= $DueDate != '' ? '<dueDate xmlns="">' . $DueDate . '</dueDate>' : '';
		$XMLRequest .= $PaymentTerms != '' ? '<paymentTerms xmlns="">' . $PaymentTerms . '</paymentTerms>' : '';
		$XMLRequest .= $DiscountPercent != '' ? '<discountPercent xmlns="">' . $DiscountPercent . '</discountPercent>' : '';
		$XMLRequest .= $DiscountAmount != '' ? '<discountAmount xmlns="">' . $DiscountAmount . '</discountAmount>' : '';
		$XMLRequest .= $Terms != '' ? '<terms xmlns="">' . $Terms . '</terms>' : '';
		$XMLRequest .= $Note != '' ? '<note xmlns="">' . $Note . '</note>' : '';
		$XMLRequest .= $MerchantMemo != '' ? '<merchantMemo xmlns="">' . $MerchantMemo . '</merchantMemo>' : '';
		
		if(!empty($BillingInfo))
		{
			$XMLRequest .= '<billingInfo xmlns="">';
			$XMLRequest .= $BillingFirstName != '' ? '<firstName xmlns="">' . $BillingFirstName . '</firstName>' : '';
			$XMLRequest .= $BillingLastName != '' ? '<lastName xmlns="">' . $BillingLastName . '</lastName>' : '';
			$XMLRequest .= $BillingBusinessName != '' ? '<businessName xmlns="">' . $BillingBusinessName . '</businessName>' : '';
			$XMLRequest .= $BillingPhone != '' ? '<phone xmlns="">' . $BillingPhone . '</phone>' : '';
			$XMLRequest .= $BillingFax != '' ? '<fax xmlns="">' . $BillingFax . '</fax>' : '';
			$XMLRequest .= $BillingWebsite != '' ? '<website xmlns="">' . $BillingWebsite . '</website>' : '';
			$XMLRequest .= $BillingCustom != '' ? '<customValue xmlns="">' . $BillingCustom . '</customValue>' : '';
			
			if(!empty($BillingInfoAddress))
			{
				$XMLRequest .= '<address xmlns="">';
				$XMLRequest .= $BillingInfoAddressLine1 != '' ? '<line1 xmlns="">' . $BillingInfoAddressLine1 . '</line1>' : '';
				$XMLRequest .= $BillingInfoAddressLine2 != '' ? '<line2 xmlns="">' . $BillingInfoAddressLine2 . '</line2>' : '';
				$XMLRequest .= $BillingInfoAddressCity != '' ? '<city xmlns="">' . $BillingInfoAddressCity . '</city>' : '';
				$XMLRequest .= $BillingInfoAddressState != '' ? '<state xmlns="">' . $BillingInfoAddressState . '</state>' : '';
				$XMLRequest .= $BillingInfoAddressPostalCode != '' ? '<postalCode xmlns="">' . $BillingInfoAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $BillingInfoAddressCountryCode != '' ? '<countryCode xmlns="">' . $BillingInfoAddressCountryCode . '</countryCode>' : '';
				$XMLRequest .= $BillingInfoAddressType != '' ? '<type xmlns="">' . $BillingInfoAddressType . '</type>' : '';
				$XMLRequest .= '</address>';
			}
			$XMLRequest .= '</billingInfo>';
		}
		
		if(!empty($ShippingInfo))
		{
			$XMLRequest .= '<shippingInfo xmlns="">';
			$XMLRequest .= $ShippingFirstName != '' ? '<firstName xmlns="">' . $ShippingFirstName . '</firstName>' : '';
			$XMLRequest .= $ShippingLastName != '' ? '<lastName xmlns="">' . $ShippingLastName . '</lastName>' : '';
			$XMLRequest .= $ShippingBusinessName != '' ? '<businessName xmlns="">' . $ShippingBusinessName . '</businessName>' : '';
			$XMLRequest .= $ShippingPhone != '' ? '<phone xmlns="">' . $ShippingPhone . '</phone>' : '';
			$XMLRequest .= $ShippingFax != '' ? '<fax xmlns="">' . $ShippingFax . '</fax>' : '';
			$XMLRequest .= $ShippingWebsite != '' ? '<website xmlns="">' . $ShippingWebsite . '</website>' : '';
			$XMLRequest .= $ShippingCustom != '' ? '<customValue xmlns="">' . $ShippingCustom . '</customValue>' : '';
			
			if(!empty($ShippingInfoAddress))
			{
				$XMLRequest .= '<address xmlns="">';
				$XMLRequest .= $ShippingInfoAddressLine1 != '' ? '<line1 xmlns="">' . $ShippingInfoAddressLine1 . '</line1>' : '';
				$XMLRequest .= $ShippingInfoAddressLine2 != '' ? '<line2 xmlns="">' . $ShippingInfoAddressLine2 . '</line2>' : '';
				$XMLRequest .= $ShippingInfoAddressCity != '' ? '<city xmlns="">' . $ShippingInfoAddressCity . '</city>' : '';
				$XMLRequest .= $ShippingInfoAddressState != '' ? '<state xmlns="">' . $ShippingInfoAddressState . '</state>' : '';
				$XMLRequest .= $ShippingInfoAddressPostalCode != '' ? '<postalCode xmlns="">' . $ShippingInfoAddressPostalCode . '</postalCode>' : '';
				$XMLRequest .= $ShippingInfoAddressCountryCode != '' ? '<countryCode xmlns="">' . $ShippingInfoAddressCountryCode . '</countryCode>' : '';
				$XMLRequest .= $ShippingInfoAddressType != '' ? '<type xmlns="">' . $ShippingInfoAddressType . '</type>' : '';
				$XMLRequest .= '</address>';
			}
			$XMLRequest .= '</shippingInfo>';
		}
		
		$XMLRequest .= $ShippingAmount != '' ? '<shippingAmount xmlns="">' . $ShippingAmount . '</shippingAmount>' : '';
		$XMLRequest .= $ShippingTaxName != '' ? '<shippingTaxName xmlns="">' . $ShippingTaxName . '</shippingTaxName>' : '';
		$XMLRequest .= $ShippingTaxRate != '' ? '<shippingTaxRate xmlns="">' . $ShippingTaxRate . '</shippingTaxRate>' : '';
		$XMLRequest .= $LogoURL != '' ? '<logoUrl xmlns="">' . $LogoURL . '</logoUrl>' : '';
		$XMLRequest .= '</invoice>';
		$XMLRequest .= '</UpdateInvoiceRequest>';
		 
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'UpdateInvoice');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$InvoiceID = $DOM -> getElementsByTagName('invoiceID') -> length > 0 ? $DOM -> getElementsByTagName('invoiceID') -> item(0) -> nodeValue : '';
		$InvoiceNumber = $DOM -> getElementsByTagName('invoiceNumber') -> length > 0 ? $DOM -> getElementsByTagName('invoiceNumber') -> item(0) -> nodeValue : '';
		$InvoiceURL = $DOM -> getElementsByTagName('invoiceURL') -> length > 0 ? $DOM -> getElementsByTagName('invoiceURL') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'InvoiceID' => $InvoiceID, 
								   'InvoiceNumber' => $InvoiceNumber, 
								   'InvoiceURL' => $InvoiceURL,  
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit UpdateInvoice API request to PayPal.
	 *
	 * @access	public
	 * @param	string	$InvoiceID			Invoice ID of which you would like to obtain details.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function GetInvoiceDetails($InvoiceID)
	 {
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<GetInvoiceDetailsRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $InvoiceID != '' ? '<invoiceID xmlns="">' . $InvoiceID . '</invoiceID>' : '';
		$XMLRequest .= '</GetInvoiceDetailsRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'GetInvoiceDetails');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
				
		$InvoiceURL = $DOM -> getElementsByTagName('invoiceURL') -> length > 0 ? $DOM -> getElementsByTagName('invoiceURL') -> item(0) -> nodeValue : '';

		$InvoiceType = $DOM -> getElementsByTagName('invoice') -> length > 0 ? $DOM -> getElementsByTagName('invoice') : array();
		foreach($InvoiceType as $Invoice)
		{
			$MerchantEmail = $Invoice -> getElementsByTagName('merchantEmail') -> length > 0 ? $Invoice -> getElementsByTagName('merchantEmail') -> item(0) -> nodeValue : '';
			$PayerEmail = $Invoice -> getElementsByTagName('payerEmail') -> length > 0 ? $Invoice -> getElementsByTagName('payerEmail') -> item(0) -> nodeValue : '';
			$Number = $Invoice -> getElementsByTagName('number') -> length > 0 ? $Invoice -> getElementsByTagName('number') -> item(0) -> nodeValue : '';
			$CurrencyCode = $Invoice -> getElementsByTagName('currencyCode') -> length > 0 ? $Invoice -> getElementsByTagName('currencyCode') -> item(0) -> nodeValue : '';
			$InvoiceDate = $Invoice -> getElementsByTagName('invoiceDate') -> length > 0 ? $Invoice -> getElementsByTagName('invoiceDate') -> item(0) -> nodeValue : '';
			$DueDate = $Invoice -> getElementsByTagName('dueDate') -> length > 0 ? $Invoice -> getElementsByTagName('dueDate') -> item(0) -> nodeValue : '';
			$PaymentTerms = $Invoice -> getElementsByTagName('paymentTerms') -> length > 0 ? $Invoice -> getElementsByTagName('paymentTerms') -> item(0) -> nodeValue : '';
			$DiscountPercent = $Invoice -> getElementsByTagName('discountPercent') -> length > 0 ? $Invoice -> getElementsByTagName('discountPercent') -> item(0) -> nodeValue : '';
			$DiscountAmount = $Invoice -> getElementsByTagName('discountAmount') -> length > 0 ? $Invoice -> getElementsByTagName('discountAmount') -> item(0) -> nodeValue : '';
			$Terms = $Invoice -> getElementsByTagName('terms') -> length > 0 ? $Invoice -> getElementsByTagName('terms') -> item(0) -> nodeValue : '';
			$InvoiceNote = $Invoice -> getElementsByTagName('note') -> length > 0 ? $Invoice -> getElementsByTagName('note') -> item(0) -> nodeValue : '';
			$MerchantMemo = $Invoice -> getElementsByTagName('merchantMemo') -> length > 0 ? $Invoice -> getElementsByTagName('merchantMemo') -> item(0) -> nodeValue : '';
			$ShippingAmount = $Invoice -> getElementsByTagName('shippingAmount') -> length > 0 ? $Invoice -> getElementsByTagName('shippingAmount') -> item(0) -> nodeValue : '';
			$ShippingTaxName = $Invoice -> getElementsByTagName('shippingTaxName') -> length > 0 ? $Invoice -> getElementsByTagName('shippingTaxName') -> item(0) -> nodeValue : '';
			$ShippingTaxRate = $Invoice -> getElementsByTagName('shippingTaxRate') -> length > 0 ? $Invoice -> getElementsByTagName('shippingTaxRate') -> item(0) -> nodeValue : '';
			$LogoURL = $Invoice -> getElementsByTagName('logoUrl') -> length > 0 ? $Invoice -> getElementsByTagName('logoUrl') -> item(0) -> nodeValue : '';
		}
		
		$InvoiceDetailsType = $DOM -> getElementsByTagName('invoiceDetails') -> length > 0 ? $DOM -> getElementsByTagName('invoiceDetails') : array();
		foreach($InvoiceDetailsType as $InvoiceDetails)
		{
			$Status = $InvoiceDetails -> getElementsByTagName('status') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('status') -> item(0) -> nodeValue : '';
			$TotalAmount = $InvoiceDetails -> getElementsByTagName('totalAmount') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('totalAmount') -> item(0) -> nodeValue : '';
			$Origin = $InvoiceDetails -> getElementsByTagName('origin') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('origin') -> item(0) -> nodeValue : '';
			$CreatedDate = $InvoiceDetails -> getElementsByTagName('createdDate') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('createdDate') -> item(0) -> nodeValue : '';
			$CreatedBy = $InvoiceDetails -> getElementsByTagName('createdBy') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('createdBy') -> item(0) -> nodeValue : '';
			$CanceledDate = $InvoiceDetails -> getElementsByTagName('canceledDate') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('canceledDate') -> item(0) -> nodeValue : '';
			$CanceledByActor = $InvoiceDetails -> getElementsByTagName('canceledByActor') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('canceledByActor') -> item(0) -> nodeValue : '';
			$CanceledBy = $InvoiceDetails -> getElementsByTagName('canceledBy') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('canceledBy') -> item(0) -> nodeValue : '';
			$LastUpdatedDate = $InvoiceDetails -> getElementsByTagName('lastUpdatedDate') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('lastUpdatedDate') -> item(0) -> nodeValue : '';
			$LastUpdatedBy = $InvoiceDetails -> getElementsByTagName('lastUpdatedBy') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('lastUpdatedBy') -> item(0) -> nodeValue : '';
			$FirstSentDate = $InvoiceDetails -> getElementsByTagName('firstSentDate') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('firstSentDate') -> item(0) -> nodeValue : '';
			$LastSentDate = $InvoiceDetails -> getElementsByTagName('lastSentDate') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('lastSentDate') -> item(0) -> nodeValue : '';
			$LastSentBy = $InvoiceDetails -> getElementsByTagName('lastSentBy') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('lastSentBy') -> item(0) -> nodeValue : '';
			$PaidDate = $InvoiceDetails -> getElementsByTagName('paidDate') -> length > 0 ? $InvoiceDetails -> getElementsByTagName('paidDate') -> item(0) -> nodeValue : '';
		}
		
		$InvoiceItems = array();
		$InvoiceItemsType = $DOM -> getElementsByTagName('item') -> length > 0 ? $DOM -> getElementsByTagName('item') : array();
		foreach($InvoiceItemsType as $InvoiceItem)
		{
			$ItemName = $InvoiceItem -> getElementsByTagName('name') -> length > 0 ? $InvoiceItem -> getElementsByTagName('name') -> item(0) -> nodeValue : '';
			$ItemDescription = $InvoiceItem -> getElementsByTagName('description') -> length > 0 ? $InvoiceItem -> getElementsByTagName('description') -> item(0) -> nodeValue : '';
			$ItemDate = $InvoiceItem -> getElementsByTagName('date') -> length > 0 ? $InvoiceItem -> getElementsByTagName('date') -> item(0) -> nodeValue : '';
			$ItemQuantity = $InvoiceItem -> getElementsByTagName('quantity') -> length > 0 ? $InvoiceItem -> getElementsByTagName('quantity') -> item(0) -> nodeValue : '';
			$ItemUnitPrice = $InvoiceItem -> getElementsByTagName('unitPrice') -> length > 0 ? $InvoiceItem -> getElementsByTagName('unitPrice') -> item(0) -> nodeValue : '';
			$ItemTaxName = $InvoiceItem -> getElementsByTagName('taxName') -> length > 0 ? $InvoiceItem -> getElementsByTagName('taxName') -> item(0) -> nodeValue : '';
			$ItemTaxRate = $InvoiceItem -> getElementsByTagName('taxRate') -> length > 0 ? $InvoiceItem -> getElementsByTagName('taxRate') -> item(0) -> nodeValue : '';
			$InvoiceItemArray = array(
									'Name' => $ItemName, 
									'Description' => $ItemDescription, 
									'Date' => $ItemDate, 
									'Quantity' => $ItemQuantity, 
									'UnitPrice' => $ItemUnitPrice, 
									'TaxName' => $ItemTaxName, 
									'TaxRate' => $ItemTaxRate
									);
			array_push($InvoiceItems,$InvoiceItemArray);
		}
		
		$MerchantInfoType = $DOM -> getElementsByTagName('merchantInfo') -> length > 0 ? $DOM -> getElementsByTagName('merchantInfo') : array();
		foreach($MerchantInfoType as $MerchantInfo)
		{
			$MerchantFirstName = $MerchantInfo -> getElementsByTagName('firstName') -> length > 0 ? $MerchantInfo -> getElementsByTagName('firstName') -> item(0) -> nodeValue : '';
			$MerchantLastName = $MerchantInfo -> getElementsByTagName('lastName') -> length > 0 ? $MerchantInfo -> getElementsByTagName('lastName') -> item(0) -> nodeValue : '';
			$MerchantBusinessName = $MerchantInfo -> getElementsByTagName('businessName') -> length > 0 ? $MerchantInfo -> getElementsByTagName('businessName') -> item(0) -> nodeValue : '';
			$MerchantPhone = $MerchantInfo -> getElementsByTagName('phone') -> length > 0 ? $MerchantInfo -> getElementsByTagName('phone') -> item(0) -> nodeValue : '';
			$MerchantFax = $MerchantInfo -> getElementsByTagName('fax') -> length > 0 ? $MerchantInfo -> getElementsByTagName('fax') -> item(0) -> nodeValue : '';
			$MerchantWebsite = $MerchantInfo -> getElementsByTagName('website') -> length > 0 ? $MerchantInfo -> getElementsByTagName('website') -> item(0) -> nodeValue : '';
			$MerchantCustom = $MerchantInfo -> getElementsByTagName('customValue') -> length > 0 ? $MerchantInfo -> getElementsByTagName('customValue') -> item(0) -> nodeValue : '';
		
			$MerchantInfoAddressType = $MerchantInfo -> getElementsByTagName('address') -> length > 0 ? $MerchantInfo -> getElementsByTagName('address') : array();
			foreach($MerchantInfoAddressType as $MerchantInfoAddress)
			{
				$MerchantAddressLine1 = $MerchantInfoAddress -> getElementsByTagName('line1') -> length > 0 ? $MerchantInfoAddress -> getElementsByTagName('line1') -> item(0) -> nodeValue : '';
				$MerchantAddressLine2 = $MerchantInfoAddress -> getElementsByTagName('line2') -> length > 0 ? $MerchantInfoAddress -> getElementsByTagName('line2') -> item(0) -> nodeValue : '';
				$MerchantAddressCity = $MerchantInfoAddress -> getElementsByTagName('city') -> length > 0 ? $MerchantInfoAddress -> getElementsByTagName('city') -> item(0) -> nodeValue : '';
				$MerchantAddressState = $MerchantInfoAddress -> getElementsByTagName('state') -> length > 0 ? $MerchantInfoAddress -> getElementsByTagName('state') -> item(0) -> nodeValue : '';
				$MerchantAddressPostalCode = $MerchantInfoAddress -> getElementsByTagName('postalCode') -> length > 0 ? $MerchantInfoAddress -> getElementsByTagName('postalCode') -> item(0) -> nodeValue : '';
				$MerchantAddressCountryCode = $MerchantInfoAddress -> getElementsByTagName('countryCode') -> length > 0 ? $MerchantInfoAddress -> getElementsByTagName('countryCode') -> item(0) -> nodeValue : '';
				$MerchantAddressType = $MerchantInfoAddress -> getElementsByTagName('type') -> length > 0 ? $MerchantInfoAddress -> getElementsByTagName('type') -> item(0) -> nodeValue : '';
			}
		}
		
		$BillingInfoType = $DOM -> getElementsByTagName('billingInfo') -> length > 0 ? $DOM -> getElementsByTagName('billingInfo') : array();
		foreach($BillingInfoType as $BillingInfo)
		{
			$BillingFirstName = $BillingInfo -> getElementsByTagName('firstName') -> length > 0 ? $BillingInfo -> getElementsByTagName('firstName') -> item(0) -> nodeValue : '';
			$BillingLastName = $BillingInfo -> getElementsByTagName('lastName') -> length > 0 ? $BillingInfo -> getElementsByTagName('lastName') -> item(0) -> nodeValue : '';
			$BillingBusinessName = $BillingInfo -> getElementsByTagName('businessName') -> length > 0 ? $BillingInfo -> getElementsByTagName('businessName') -> item(0) -> nodeValue : '';
			$BillingPhone = $BillingInfo -> getElementsByTagName('phone') -> length > 0 ? $BillingInfo -> getElementsByTagName('phone') -> item(0) -> nodeValue : '';
			$BillingFax = $BillingInfo -> getElementsByTagName('fax') -> length > 0 ? $BillingInfo -> getElementsByTagName('fax') -> item(0) -> nodeValue : '';
			$BillingWebsite = $BillingInfo -> getElementsByTagName('website') -> length > 0 ? $BillingInfo -> getElementsByTagName('website') -> item(0) -> nodeValue : '';
			$BillingCustom = $BillingInfo -> getElementsByTagName('customValue') -> length > 0 ? $BillingInfo -> getElementsByTagName('customValue') -> item(0) -> nodeValue : '';
		
			$BillingInfoAddressType = $BillingInfo -> getElementsByTagName('address') -> length > 0 ? $BillingInfo -> getElementsByTagName('address') : array();
			foreach($BillingInfoAddressType as $BillingInfoAddress)
			{
				$BillingAddressLine1 = $BillingInfoAddress -> getElementsByTagName('line1') -> length > 0 ? $BillingInfoAddress -> getElementsByTagName('line1') -> item(0) -> nodeValue : '';
				$BillingAddressLine2 = $BillingInfoAddress -> getElementsByTagName('line2') -> length > 0 ? $BillingInfoAddress -> getElementsByTagName('line2') -> item(0) -> nodeValue : '';
				$BillingAddressCity = $BillingInfoAddress -> getElementsByTagName('city') -> length > 0 ? $BillingInfoAddress -> getElementsByTagName('city') -> item(0) -> nodeValue : '';
				$BillingAddressState = $BillingInfoAddress -> getElementsByTagName('state') -> length > 0 ? $BillingInfoAddress -> getElementsByTagName('state') -> item(0) -> nodeValue : '';
				$BillingAddressPostalCode = $BillingInfoAddress -> getElementsByTagName('postalCode') -> length > 0 ? $BillingInfoAddress -> getElementsByTagName('postalCode') -> item(0) -> nodeValue : '';
				$BillingAddressCountryCode = $BillingInfoAddress -> getElementsByTagName('countryCode') -> length > 0 ? $BillingInfoAddress -> getElementsByTagName('countryCode') -> item(0) -> nodeValue : '';
				$BillingAddressType = $BillingInfoAddress -> getElementsByTagName('type') -> length > 0 ? $BillingInfoAddress -> getElementsByTagName('type') -> item(0) -> nodeValue : '';
			}
		}
		
		$ShippingInfoType = $DOM -> getElementsByTagName('shippingInfo') -> length > 0 ? $DOM -> getElementsByTagName('shippingInfo') : array();
		foreach($ShippingInfoType as $ShippingInfo)
		{
			$ShippingFirstName = $ShippingInfo -> getElementsByTagName('firstName') -> length > 0 ? $ShippingInfo -> getElementsByTagName('firstName') -> item(0) -> nodeValue : '';
			$ShippingLastName = $ShippingInfo -> getElementsByTagName('lastName') -> length > 0 ? $ShippingInfo -> getElementsByTagName('lastName') -> item(0) -> nodeValue : '';
			$ShippingBusinessName = $ShippingInfo -> getElementsByTagName('businessName') -> length > 0 ? $ShippingInfo -> getElementsByTagName('businessName') -> item(0) -> nodeValue : '';
			$ShippingPhone = $ShippingInfo -> getElementsByTagName('phone') -> length > 0 ? $ShippingInfo -> getElementsByTagName('phone') -> item(0) -> nodeValue : '';
			$ShippingFax = $ShippingInfo -> getElementsByTagName('fax') -> length > 0 ? $ShippingInfo -> getElementsByTagName('fax') -> item(0) -> nodeValue : '';
			$ShippingWebsite = $ShippingInfo -> getElementsByTagName('website') -> length > 0 ? $ShippingInfo -> getElementsByTagName('website') -> item(0) -> nodeValue : '';
			$ShippingCustom = $ShippingInfo -> getElementsByTagName('customValue') -> length > 0 ? $ShippingInfo -> getElementsByTagName('customValue') -> item(0) -> nodeValue : '';
		
			$ShippingInfoAddressType = $ShippingInfo -> getElementsByTagName('address') -> length > 0 ? $ShippingInfo -> getElementsByTagName('address') : array();
			foreach($ShippingInfoAddressType as $ShippingInfoAddress)
			{
				$ShippingAddressLine1 = $ShippingInfoAddress -> getElementsByTagName('line1') -> length > 0 ? $ShippingInfoAddress -> getElementsByTagName('line1') -> item(0) -> nodeValue : '';
				$ShippingAddressLine2 = $ShippingInfoAddress -> getElementsByTagName('line2') -> length > 0 ? $ShippingInfoAddress -> getElementsByTagName('line2') -> item(0) -> nodeValue : '';
				$ShippingAddressCity = $ShippingInfoAddress -> getElementsByTagName('city') -> length > 0 ? $ShippingInfoAddress -> getElementsByTagName('city') -> item(0) -> nodeValue : '';
				$ShippingAddressState = $ShippingInfoAddress -> getElementsByTagName('state') -> length > 0 ? $ShippingInfoAddress -> getElementsByTagName('state') -> item(0) -> nodeValue : '';
				$ShippingAddressPostalCode = $ShippingInfoAddress -> getElementsByTagName('postalCode') -> length > 0 ? $ShippingInfoAddress -> getElementsByTagName('postalCode') -> item(0) -> nodeValue : '';
				$ShippingAddressCountryCode = $ShippingInfoAddress -> getElementsByTagName('countryCode') -> length > 0 ? $ShippingInfoAddress -> getElementsByTagName('countryCode') -> item(0) -> nodeValue : '';
				$ShippingAddressType = $ShippingInfoAddress -> getElementsByTagName('type') -> length > 0 ? $ShippingInfoAddress -> getElementsByTagName('type') -> item(0) -> nodeValue : '';
			}
		}
		
		$ViaPayPal = $DOM -> getElementsByTagName('viaPayPal') -> length > 0 ? $DOM -> getElementsByTagName('viaPayPal') -> item(0) -> nodeValue : '';
		$PayPalPaymentDetailsType = $DOM -> getElementsByTagName('paypalPayment') -> length > 0 ? $DOM -> getElementsByTagName('paypalPayment') : array();
		foreach($PayPalPaymentDetailsType as $PayPalPaymentDetails)
		{
			$PayPalTransactionID = $PayPalPaymentDetails -> getElementsByTagName('transactionID') -> length > 0 ? $PayPalPaymentDetails -> getElementsByTagName('transactionID') -> item(0) -> nodeValue : '';
			$PayPalTransactionDate = $PayPalPaymentDetails -> getElementsByTagName('date') -> length > 0 ? $PayPalPaymentDetails -> getElementsByTagName('date') -> item(0) -> nodeValue : '';
		}
		
		$OtherPaymentDetailsType = $DOM -> getElementsByTagName('otherPayment') -> length > 0 ? $DOM -> getElementsByTagName('otherPayment') : array();
		foreach($OtherPaymentDetailsType as $OtherPaymentDetails)
		{
			$PaymentMethod = $OtherPaymentDetails -> getElementsByTagName('method') -> length > 0 ? $OtherPaymentDetails -> getElementsByTagName('method') -> item(0) -> nodeValue : '';
			$PaymentMethodNote = $OtherPaymentDetails -> getElementsByTagName('note') -> length > 0 ? $OtherPaymentDetails -> getElementsByTagName('note') -> item(0) -> nodeValue : '';
			$PaymentMethodDate = $OtherPaymentDetails -> getElementsByTagName('date') -> length > 0 ? $OtherPaymentDetails -> getElementsByTagName('date') -> item(0) -> nodeValue : '';
		}
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'InvoiceURL' => $InvoiceURL, 
								   'MerchantEmail' => isset($MerchantEmail) ? $MerchantEmail : '', 
								   'PayerEmail' => isset($PayerEmail) ? $PayerEmail : '', 
								   'Number' => isset($Number) ? $Number : '', 
								   'CurrencyCode' => isset($CurrencyCode) ? $CurrencyCode : '', 
								   'InvoiceDate' => isset($InvoiceDate) ? $InvoiceDate : '', 
								   'DueDate' => isset($DueDate) ? $DueDate : '', 
								   'PaymentTerms' => isset($PaymentTerms) ? $PaymentTerms : '', 
								   'DiscountPercent' => isset($DiscountPercent) ? $DiscountPercent : '', 
								   'DiscountAmount' => isset($DiscountAmount) ? $DiscountAmount : '', 
								   'Terms' => isset($Terms) ? $Terms : '', 
								   'InvoiceNote' => isset($InvoiceNote) ? $InvoiceNote : '', 
								   'MerchantMemo' => isset($MerchantMemo) ? $MerchantMemo : '', 
								   'ShippingAmount' => isset($ShippingAmount) ? $ShippingAmount : '', 
								   'ShippingTaxName' => isset($ShippingTaxName) ? $ShippingTaxName : '', 
								   'ShippingTaxRate' => isset($ShippingTaxRate) ? $ShippingTaxRate : '', 
								   'LogoURL' => isset($LogoURL) ? $LogoURL : '', 
								   'Status' => isset($Status) ? $Status : '', 
								   'TotalAmount' => isset($TotalAmount) ? $TotalAmount : '', 
								   'Origin' => isset($Origin) ? $Origin : '', 
								   'CreatedDate' => isset($CreatedDate) ? $CreatedDate : '', 
								   'CreatedBy' => isset($CreatedBy) ? $CreatedBy : '', 
								   'CanceledDate' => isset($CanceledDate) ? $CanceledDate : '', 
								   'CanceledByActor' => isset($CanceledByActor) ? $CanceledByActor : '', 
								   'CanceledBy' => isset($CanceledBy) ? $CanceledBy : '', 
								   'LastUpdatedDate' => isset($LastUpdatedDate) ? $LastUpdatedDate : '', 
								   'LastUpdatedBy' => isset($LastUpdatedBy) ? $LastUpdatedBy : '', 
								   'FistSentDate' => isset($FirstSentDate) ? $FirstSentDate : '', 
								   'LastSentDate' => isset($LastSentDate) ? $LastSentDate : '', 
								   'LastSentBy' => isset($LastSentBy) ? $LastSentBy : '', 
								   'PaidDate' => isset($PaidDate) ? $PaidDate : '', 
								   'InvoiceItems' => isset($InvoiceItems) ? $InvoiceItems : '', 
								   'MerchantFirstName' => isset($MerchantFirstName) ? $MerchantFirstName : '', 
								   'MerchantLastName' => isset($MerchantLastName) ? $MerchantLastName : '', 
								   'MerchantBusinessName' => isset($MerchantBusinessName) ? $MerchantBusinessName : '', 
								   'MerchantPhone' => isset($MerchantPhone) ? $MerchantPhone : '', 
								   'MerchantFax' => isset($MerchantFax) ? $MerchantFax : '', 
								   'MerchantWebsite' => isset($MerchantWebsite) ? $MerchantWebsite : '', 
								   'MerchantCustom' => isset($MerchantCustom) ? $MerchantCustom : '', 
								   'MerchantAddressLine1' => isset($MerchantAddressLine1) ? $MerchantAddressLine1 : '', 
								   'MerchantAddressLine2' => isset($MerchantAddressLine2) ? $MerchantAddressLine2 : '', 
								   'MerchantAddressCity' => isset($MerchantAddressCity) ? $MerchantAddressCity : '', 
								   'MerchantAddressState' => isset($MerchantAddressState) ? $MerchantAddressState : '', 
								   'MerchantAddressPostalCode' => isset($MerchantAddressPostalCode) ? $MerchantAddressPostalCode : '', 
								   'MerchantAddressCountryCode' => isset($MerchantAddressCountryCode) ? $MerchantAddressCountryCode : '', 
								   'MerchantAddressType' => isset($MerchantAddressType) ? $MerchantAddressType : '',
								   'BillingFirstName' => isset($BillingFirstName) ? $BillingFirstName : '', 
								   'BillingLastName' => isset($BillingLastName) ? $BillingLastName : '', 
								   'BillingBusinessName' => isset($BillingBusinessName) ? $BillingBusinessName : '', 
								   'BillingPhone' => isset($BillingPhone) ? $BillingPhone : '', 
								   'BillingFax' => isset($BillingFax) ? $BillingFax : '', 
								   'BillingWebsite' => isset($BillingWebsite) ? $BillingWebsite : '', 
								   'BillingCustom' => isset($BillingCustom) ? $BillingCustom : '', 
								   'BillingAddressLine1' => isset($BillingAddressLine1) ? $BillingAddressLine1 : '', 
								   'BillingAddressLine2' => isset($BillingAddressLine2) ? $BillingAddressLine2 : '', 
								   'BillingAddressCity' => isset($BillingAddressCity) ? $BillingAddressCity : '', 
								   'BillingAddressState' => isset($BillingAddressState) ? $BillingAddressState : '', 
								   'BillingAddressPostalCode' => isset($BillingAddressPostalCode) ? $BillingAddressPostalCode : '', 
								   'BillingAddressCountryCode' => isset($BillingAddressCountryCode) ? $BillingAddressCountryCode : '', 
								   'BillingAddressType' => isset($BillingAddressType) ? $BillingAddressType : '',
								   'ShippingFirstName' => isset($ShippingFirstName) ? $ShippingFirstName : '', 
								   'ShippingLastName' => isset($ShippingLastName) ? $ShippingLastName : '', 
								   'ShippingBusinessName' => isset($ShippingBusinessName) ? $ShippingBusinessName : '', 
								   'ShippingPhone' => isset($ShippingPhone) ? $ShippingPhone : '', 
								   'ShippingFax' => isset($ShippingFax) ? $ShippingFax : '', 
								   'ShippingWebsite' => isset($ShippingWebsite) ? $ShippingWebsite : '', 
								   'ShippingCustom' => isset($ShippingCustom) ? $ShippingCustom : '', 
								   'ShippingAddressLine1' => isset($ShippingAddressLine1) ? $ShippingAddressLine1 : '', 
								   'ShippingAddressLine2' => isset($ShippingAddressLine2) ? $ShippingAddressLine2 : '', 
								   'ShippingAddressCity' => isset($ShippingAddressCity) ? $ShippingAddressCity : '', 
								   'ShippingAddressState' => isset($ShippingAddressState) ? $ShippingAddressState : '', 
								   'ShippingAddressPostalCode' => isset($ShippingAddressPostalCode) ? $ShippingAddressPostalCode : '', 
								   'ShippingAddressCountryCode' => isset($ShippingAddressCountryCode) ? $ShippingAddressCountryCode : '', 
								   'ShippingAddressType' => isset($ShippingAddressType) ? $ShippingAddressType : '', 
								   'ViaPayPal' => isset($ViaPayPal) ? $ViaPayPal : '', 
								   'PayPalTransactionID' => isset($PayPalTransactionID) ? $PayPalTransactionID : '', 
								   'PayPalTransactionDate' => isset($PayPalTransactionDate) ? $PayPalTransactionDate : '', 
								   'PaymentMethod' => isset($PaymentMethod) ? $PaymentMethod : '', 
								   'PaymentMethodNote' => isset($PaymentMethodNote) ? $PaymentMethodNote : '', 
								   'PaymentMethodDate' => isset($PaymentMethodDate) ? $PaymentMethodDate : '', 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
				
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit CancelInvoice API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function CancelInvoice($DataArray)
	 {
		$CancelInvoiceFields = isset($DataArray['CancelInvoiceFields']) ? $DataArray['CancelInvoiceFields'] : array();
		$InvoiceID = isset($CancelInvoiceFields['InvoiceID']) ? $CancelInvoiceFields['InvoiceID'] : '';
		$Subject = isset($CancelInvoiceFields['Subject']) ? $CancelInvoiceFields['Subject'] : '';
		$NoteForPayer = isset($CancelInvoiceFields['NoteForPayer']) ? $CancelInvoiceFields['NoteForPayer'] : '';
		$SendCopyToMerchant = isset($CancelInvoiceFields['SendCopyToMerchant']) ? $CancelInvoiceFields['SendCopyToMerchant'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<CancelInvoiceRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $InvoiceID != '' ? '<invoiceID xmlns="">' . $InvoiceID . '</invoiceID>' : '';
		$XMLRequest .= $Subject != '' ? '<subject xmlns="">' . $Subject . '</subject>' : '';
		$XMLRequest .= $NoteForPayer != '' ? '<noteForPayer xmlns="">' . $NoteForPayer . '</noteForPayer>' : '';
		$XMLRequest .= $SendCopyToMerchant != '' ? '<sendCopyToMerchant xmlns="">' . $SendCopyToMerchant . '</sendCopyToMerchant>' : '';
		$XMLRequest .= '</CancelInvoiceRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'CancelInvoice');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$InvoiceID = $DOM -> getElementsByTagName('invoiceID') -> length > 0 ? $DOM -> getElementsByTagName('invoiceID') -> item(0) -> nodeValue : '';
		$InvoiceNumber = $DOM -> getElementsByTagName('invoiceNumber') -> length > 0 ? $DOM -> getElementsByTagName('invoiceNumber') -> item(0) -> nodeValue : '';
		$InvoiceURL = $DOM -> getElementsByTagName('invoiceURL') -> length > 0 ? $DOM -> getElementsByTagName('invoiceURL') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'InvoiceID' => $InvoiceID, 
								   'InvoiceNumber' => $InvoiceNumber, 
								   'InvoiceURL' => $InvoiceURL,  
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 /**
	 * Submit DeleteInvoice API request to PayPal.
	 *
	 * @access	public
	 * @param	string	$InvoiceID			Invoice ID of the invoice to be deleted.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function DeleteInvoice($InvoiceID)
	 {
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<DeleteInvoiceRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $InvoiceID != '' ? '<invoiceID xmlns="">' . $InvoiceID . '</invoiceID>' : '';
		$XMLRequest .= '</DeleteInvoiceRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'DeleteInvoice');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit GenerateInvoiceNumber API request to PayPal.
	 *
	 * @access	public
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function GenerateInvoiceNumber()
	 {		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<GenerateInvoiceNumberRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= '</GenerateInvoiceNumberRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'GenerateInvoiceNumber');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$InvoiceNumber = $DOM -> getElementsByTagName('invoiceNumber') -> length > 0 ? $DOM -> getElementsByTagName('invoiceNumber') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'InvoiceNumber' => $InvoiceNumber, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }
	 
	 
	 /**
	 * Submit SearchInvoices API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function SearchInvoices($DataArray)
	 {
		$SearchInvoicesFields = isset($DataArray['SearchInvoicesFields']) ? $DataArray['SearchInvoicesFields'] : array();
		$MerchantEmail = isset($SearchInvoicesFields['MerchantEmail']) ? $SearchInvoicesFields['MerchantEmail'] : '';
		$Page = isset($SearchInvoicesFields['Page']) ? $SearchInvoicesFields['Page'] : '';
		$PageSize = isset($SearchInvoicesFields['PageSize']) ? $SearchInvoicesFields['PageSize'] : '';		
		
		$Parameters = isset($DataArray['Parameters']) ? $DataArray['Parameters'] : array();
		$Email = isset($Parameters['Email']) ? $Parameters['Email'] : '';
		$RecipientName = isset($Parameters['RecipientName']) ? $Parameters['RecipientName'] : '';
		$BusinessName = isset($Parameters['BusinessName']) ? $Parameters['BusinessName'] : '';
		$InvoiceNumber = isset($Parameters['InvoiceNumber']) ? $Parameters['InvoiceNumber'] : '';
		$Status = isset($Parameters['Status']) ? $Parameters['Status'] : '';
		$LowerAmount = isset($Parameters['LowerAmount']) ? $Parameters['LowerAmount'] : '';
		$UpperAmount = isset($Parameters['UpperAmount']) ? $Parameters['UpperAmount'] : '';
		$CurrencyCode = isset($Parameters['CurrencyCode']) ? $Parameters['CurrencyCode'] : '';
		$Memo = isset($Parameters['Memo']) ? $Parameters['Memo'] : '';
		$Origin = isset($Parameters['Origin']) ? $Parameters['Origin'] : '';
		$InvoiceDate = isset($Parameters['InvoiceDate']) ? $Parameters['InvoiceDate'] : array();
		$DueDate = isset($Parameters['DueDate']) ? $Parameters['DueDate'] : array();
		$PaymentDate = isset($Parameters['PaymentDate']) ? $Parameters['PaymentDate'] : array();
		$CreationDate = isset($Parameters['CreationDate']) ? $Parameters['CreationDate'] : array();
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<SearchInvoicesRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $MerchantEmail != '' ? '<merchantEmail xmlns="">' . $MerchantEmail . '</merchantEmail>' : '';
		$XMLRequest .= $Page != '' ? '<page xmlns="">' . $Page . '</page>' : '';
		$XMLRequest .= $PageSize != '' ? '<pageSize xmlns="">' . $PageSize . '</pageSize>' : '';
		
		if(!empty($Parameters))
		{
			$XMLRequest .= '<parameters xmlns="">';
			$XMLRequest .= $Email != '' ? '<email xmlns="">' . $Email . '</email>' : '';
			$XMLRequest .= $RecipientName != '' ? '<recipientName xmlns="">' . $RecipientName . '</recipientName>' : '';
			$XMLRequest .= $BusinessName != '' ? '<businessName xmlns="">' . $BusinessName . '</businessName>' : '';
			$XMLRequest .= $InvoiceNumber != '' ? '<invoiceNumber xmlns="">' . $InvoiceNumber . '</invoiceNumber>' : '';
			$XMLRequest .= $Status != '' ? '<status xmlns="">' . $Status . '</status>' : '';
			$XMLRequest .= $LowerAmount != '' ? '<lowerAmount xmlns="">' . $LowerAmount . '</lowerAmount>' : '';
			$XMLRequest .= $UpperAmount != '' ? '<upperAmount xmlns="">' . $UpperAmount . '</upperAmount>' : '';
			$XMLRequest .= $CurrencyCode != '' ? '<currencyCode xmlns="">' . $CurrencyCode . '</currencyCode>' : '';
			$XMLRequest .= $Memo != '' ? '<memo xmlns="">' . $Memo . '</memo>' : '';
			$XMLRequest .= $Origin != '' ? '<origin xmlns="">' . $Origin . '</origin>' : '';
			
			if(!empty($InvoiceDate))
			{
				$XMLRequest .= '<invoiceDate xmlns="">';
				$XMLRequest .= '<startDate xmlns="">' . $InvoiceDate['StartDate'] . '</startDate>';
				$XMLRequest .= '<endDate xmlns="">' . $InvoiceDate['EndDate'] . '</endDate>';
				$XMLRequest .= '</invoiceDate>';	
			}
			
			if(!empty($DueDate))
			{
				$XMLRequest .= '<dueDate xmlns="">';
				$XMLRequest .= '<startDate xmlns="">' . $DueDate['StartDate'] . '</startDate>';
				$XMLRequest .= '<endDate xmlns="">' . $DueDate['EndDate'] . '</endDate>';
				$XMLRequest .= '</dueDate>';	
			}
			
			if(!empty($PaymentDate))
			{
				$XMLRequest .= '<paymentDate xmlns="">';
				$XMLRequest .= '<startDate xmlns="">' . $PaymentDate['StartDate'] . '</startDate>';
				$XMLRequest .= '<endDate xmlns="">' . $PaymentDate['EndDate'] . '</endDate>';
				$XMLRequest .= '</paymentDate>';	
			}
			
			if(!empty($CreationDate))
			{
				$XMLRequest .= '<creationDate xmlns="">';
				$XMLRequest .= '<startDate xmlns="">' . $CreationDate['StartDate'] . '</startDate>';
				$XMLRequest .= '<endDate xmlns="">' . $CreationDate['EndDate'] . '</endDate>';
				$XMLRequest .= '</creationDate>';	
			}
			
			$XMLRequest .= '</parameters>';	
		}
		
		$XMLRequest .= '</SearchInvoicesRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'SearchInvoices');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$Count = $DOM -> getElementsByTagName('count') -> length > 0 ? $DOM -> getElementsByTagName('count') -> item(0) -> nodeValue : '';
		$Page = $DOM -> getElementsByTagName('page') -> length > 0 ? $DOM -> getElementsByTagName('page') -> item(0) -> nodeValue : '';
		$HasNextPage = $DOM -> getElementsByTagName('hasNextPage') -> length > 0 ? $DOM -> getElementsByTagName('hasNextPage') -> item(0) -> nodeValue : '';
		$HasPreviousPage = $DOM -> getElementsByTagName('hasPreviousPage') -> length > 0 ? $DOM -> getElementsByTagName('hasPreviousPage') -> item(0) -> nodeValue : '';
		$InvoicesType = $DOM -> getElementsByTagName('invoice') -> length > 0 ? $DOM -> getElementsByTagName('invoice') : array();
		
		$Invoices = array();
		foreach($InvoicesType as $Invoice)
		{
			$InvoiceID = $Invoice -> getElementsByTagName('invoiceID') -> length > 0 ? $Invoice -> getElementsByTagName('invoiceID') -> item(0) -> nodeValue : '';		
			$MerchantEmail = $Invoice -> getElementsByTagName('merchantEmail') -> length > 0 ? $Invoice -> getElementsByTagName('merchantEmail') -> item(0) -> nodeValue : '';		
			$PayerEmail = $Invoice -> getElementsByTagName('payerEmail') -> length > 0 ? $Invoice -> getElementsByTagName('payerEmail') -> item(0) -> nodeValue : '';		
			$Number = $Invoice -> getElementsByTagName('number') -> length > 0 ? $Invoice -> getElementsByTagName('number') -> item(0) -> nodeValue : '';		
			$BillingBusinessName = $Invoice -> getElementsByTagName('billingBusinessName') -> length > 0 ? $Invoice -> getElementsByTagName('billingBusinessName') -> item(0) -> nodeValue : '';		
			$BillingFirstName = $Invoice -> getElementsByTagName('billingFirstName') -> length > 0 ? $Invoice -> getElementsByTagName('billingFirstName') -> item(0) -> nodeValue : '';		
			$BillingLastName = $Invoice -> getElementsByTagName('billingLastName') -> length > 0 ? $Invoice -> getElementsByTagName('billingLastName') -> item(0) -> nodeValue : '';		
			$ShippingBusinessName = $Invoice -> getElementsByTagName('shippingBusinessName') -> length > 0 ? $Invoice -> getElementsByTagName('shippingBusinessName') -> item(0) -> nodeValue : '';		
			$ShippingFirstName = $Invoice -> getElementsByTagName('shippingFirstName') -> length > 0 ? $Invoice -> getElementsByTagName('shippingFirstName') -> item(0) -> nodeValue : '';		
			$ShippingLastName = $Invoice -> getElementsByTagName('shippingLastName') -> length > 0 ? $Invoice -> getElementsByTagName('shippingLastName') -> item(0) -> nodeValue : '';		
			$TotalAmount = $Invoice -> getElementsByTagName('totalAmount') -> length > 0 ? $Invoice -> getElementsByTagName('totalAmount') -> item(0) -> nodeValue : '';		
			$CurrencyCode = $Invoice -> getElementsByTagName('currencyCode') -> length > 0 ? $Invoice -> getElementsByTagName('currencyCode') -> item(0) -> nodeValue : '';		
			$InvoiceDate = $Invoice -> getElementsByTagName('invoiceDate') -> length > 0 ? $Invoice -> getElementsByTagName('invoiceDate') -> item(0) -> nodeValue : '';		
			$DueDate = $Invoice -> getElementsByTagName('dueDate') -> length > 0 ? $Invoice -> getElementsByTagName('dueDate') -> item(0) -> nodeValue : '';		
			$Status = $Invoice -> getElementsByTagName('status') -> length > 0 ? $Invoice -> getElementsByTagName('status') -> item(0) -> nodeValue : '';		
			$Origin = $Invoice -> getElementsByTagName('origin') -> length > 0 ? $Invoice -> getElementsByTagName('origin') -> item(0) -> nodeValue : '';		
			
			$InvoiceItem = array(
								'InvoiceID' => $InvoiceID, 
								'MerchantEmail' => $MerchantEmail, 
								'PayerEmail' => $PayerEmail, 
								'Number' => $Number, 
								'BillingBusinessName' => $BillingBusinessName, 
								'BillingFirstName' => $BillingFirstName, 
								'BillingLastName' => $BillingLastName, 
								'ShippingBusinessName' => $ShippingBusinessName, 
								'ShippingFirstName' => $ShippingFirstName, 
								'ShippingLastName' => $ShippingLastName, 
								'TotalAmount' => $TotalAmount, 
								'CurrencyCode' => $CurrencyCode, 
								'InvoiceDate' => $InvoiceDate, 
								'DueDate' => $DueDate, 
								'Status' => $Status, 
								'Origin' => $Origin
								);
			array_push($Invoices, $InvoiceItem);
		}
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'Count' => $Count, 
								   'Page' => $Page, 
								   'HasNextPage' => $HasNextPage, 
								   'HasPreviousPage' => $HasPreviousPage, 
								   'Invoices' => $Invoices, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 
	 }
	 
	 
	 /**
	 * Submit MarkInvoiceAsPaid API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function MarkInvoiceAsPaid($DataArray)
	 {
		$MarkInvoiceAsPaidFields = isset($DataArray['MarkInvoiceAsPaidFields']) ? $DataArray['MarkInvoiceAsPaidFields'] : array();
		$InvoiceID = isset($MarkInvoiceAsPaidFields['InvoiceID']) ? $MarkInvoiceAsPaidFields['InvoiceID'] : '';
		$Method = isset($MarkInvoiceAsPaidFields['Method']) ? $MarkInvoiceAsPaidFields['Method'] : '';
		$Note = isset($MarkInvoiceAsPaidFields['Note']) ? $MarkInvoiceAsPaidFields['Note'] : '';
		$Date = isset($MarkInvoiceAsPaidFields['Date']) ? $MarkInvoiceAsPaidFields['Date'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<MarkInvoiceAsPaidRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $InvoiceID != '' ? '<invoiceID xmlns="">' . $InvoiceID . '</invoiceID>' : '';
		
		$XMLRequest .= '<payment xmlns="">';
		$XMLRequest .= $Method != '' ? '<method xmlns="">' . $Method . '</method>' : '';
		$XMLRequest .= $Note != '' ? '<note xmlns="">' . $Note . '</note>' : '';
		$XMLRequest .= $Date != '' ? '<date xmlns="">' . $Date . '</date>' : '';		
		$XMLRequest .= '</payment>';
		
		$XMLRequest .= '</MarkInvoiceAsPaidRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'MarkInvoiceAsPaid');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$InvoiceID = $DOM -> getElementsByTagName('invoiceID') -> length > 0 ? $DOM -> getElementsByTagName('invoiceID') -> item(0) -> nodeValue : '';
		$InvoiceNumber = $DOM -> getElementsByTagName('invoiceNumber') -> length > 0 ? $DOM -> getElementsByTagName('invoiceNumber') -> item(0) -> nodeValue : '';
		$InvoiceURL = $DOM -> getElementsByTagName('invoiceURL') -> length > 0 ? $DOM -> getElementsByTagName('invoiceURL') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'InvoiceID' => $InvoiceID, 
								   'InvoiceNumber' => $InvoiceNumber, 
								   'InvoiceURL' => $InvoiceURL,  
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
 
	 }
	 
	 /**
	 * Submit MarkInvoiceAsUnpaid API request to PayPal.
	 *
	 * @access	public
	 * @param	string	$InvoiceID			Invoice ID of the invoice to be marked as unpaid.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function MarkInvoiceAsUnpaid($InvoiceID = '')
	 {
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<MarkInvoiceAsUnpaidRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $InvoiceID != '' ? '<invoiceID xmlns="">' . $InvoiceID . '</invoiceID>' : '';
		$XMLRequest .= '</MarkInvoiceAsUnpaidRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'MarkInvoiceAsUnpaid');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$InvoiceID = $DOM -> getElementsByTagName('invoiceID') -> length > 0 ? $DOM -> getElementsByTagName('invoiceID') -> item(0) -> nodeValue : '';
		$InvoiceNumber = $DOM -> getElementsByTagName('invoiceNumber') -> length > 0 ? $DOM -> getElementsByTagName('invoiceNumber') -> item(0) -> nodeValue : '';
		$InvoiceURL = $DOM -> getElementsByTagName('invoiceURL') -> length > 0 ? $DOM -> getElementsByTagName('invoiceURL') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'InvoiceID' => $InvoiceID, 
								   'InvoiceNumber' => $InvoiceNumber, 
								   'InvoiceURL' => $InvoiceURL,  
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
 
	 }
	 
	 
	 /**
	 * Submit MarkInvoiceAsRefunded API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function MarkInvoiceAsRefunded($DataArray)
	 {
		$MarkInvoiceAsRefundedFields = isset($DataArray['MarkInvoiceAsRefundedFields']) ? $DataArray['MarkInvoiceAsRefundedFields'] : array();
		$InvoiceID = isset($MarkInvoiceAsRefundedFields['InvoiceID']) ? $MarkInvoiceAsRefundedFields['InvoiceID'] : '';
		$Note = isset($MarkInvoiceAsRefundedFields['Note']) ? $MarkInvoiceAsRefundedFields['Note'] : '';
		$Date = isset($MarkInvoiceAsRefundedFields['Date']) ? $MarkInvoiceAsRefundedFields['Date'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<MarkInvoiceAsRefundedRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $InvoiceID != '' ? '<invoiceID xmlns="">' . $InvoiceID . '</invoiceID>' : '';
		
		$XMLRequest .= '<refundDetail xmlns="">';
		$XMLRequest .= $Note != '' ? '<note xmlns="">' . $Note . '</note>' : '';
		$XMLRequest .= $Date != '' ? '<date xmlns="">' . $Date . '</date>' : '';		
		$XMLRequest .= '</refundDetail>';
		
		$XMLRequest .= '</MarkInvoiceAsRefundedRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'MarkInvoiceAsRefunded');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$InvoiceID = $DOM -> getElementsByTagName('invoiceID') -> length > 0 ? $DOM -> getElementsByTagName('invoiceID') -> item(0) -> nodeValue : '';
		$InvoiceNumber = $DOM -> getElementsByTagName('invoiceNumber') -> length > 0 ? $DOM -> getElementsByTagName('invoiceNumber') -> item(0) -> nodeValue : '';
		$InvoiceURL = $DOM -> getElementsByTagName('invoiceURL') -> length > 0 ? $DOM -> getElementsByTagName('invoiceURL') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'InvoiceID' => $InvoiceID, 
								   'InvoiceNumber' => $InvoiceNumber, 
								   'InvoiceURL' => $InvoiceURL,  
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
 
	 }
	 
	 
	 /**
	 * Submit RemindInvoice API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function RemindInvoice($DataArray)
	 {
		$RemindInvoiceFields = isset($DataArray['RemindInvoiceFields']) ? $DataArray['RemindInvoiceFields'] : array();
		$InvoiceID = isset($RemindInvoiceFields['InvoiceID']) ? $RemindInvoiceFields['InvoiceID'] : '';
		$Subject = isset($RemindInvoiceFields['Subject']) ? $RemindInvoiceFields['Subject'] : '';
		$NoteForPayer = isset($RemindInvoiceFields['NoteForPayer']) ? $RemindInvoiceFields['NoteForPayer'] : '';

		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<RemindInvoiceRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $InvoiceID != '' ? '<invoiceID xmlns="">' . $InvoiceID . '</invoiceID>' : '';
		$XMLRequest .= $Subject != '' ? '<subject xmlns="">' . $Subject . '</subject>' : '';
		$XMLRequest .= $NoteForPayer != '' ? '<noteForPayer xmlns="">' . $NoteForPayer . '</noteForPayer>' : '';
		$XMLRequest .= '</RemindInvoiceRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Invoice', 'RemindInvoice');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$InvoiceID = $DOM -> getElementsByTagName('invoiceID') -> length > 0 ? $DOM -> getElementsByTagName('invoiceID') -> item(0) -> nodeValue : '';
		$InvoiceNumber = $DOM -> getElementsByTagName('invoiceNumber') -> length > 0 ? $DOM -> getElementsByTagName('invoiceNumber') -> item(0) -> nodeValue : '';
		$InvoiceURL = $DOM -> getElementsByTagName('invoiceURL') -> length > 0 ? $DOM -> getElementsByTagName('invoiceURL') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'InvoiceID' => $InvoiceID, 
								   'InvoiceURL' => $InvoiceURL,  
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
 
	 }
	 
	 /**
	 * Submit RequestPermissions API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function RequestPermissions($DataArray)
	 {
		$RequestPermissionsFields = isset($DataArray['RequestPermissionsFields']) ? $DataArray['RequestPermissionsFields'] : array();
		$Scope = isset($RequestPermissionsFields['Scope']) ? $RequestPermissionsFields['Scope'] : array();
		$Callback = isset($RequestPermissionsFields['Callback']) ? $RequestPermissionsFields['Callback'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<RequestPermissionsRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		
		foreach($Scope as $Value)
		{
			$XMLRequest .= $Scope != '' ? '<scope xmlns="">' . $Value . '</scope>' : '';
		}
		
		$XMLRequest .= $Callback != '' ? '<callback xmlns="">' . $Callback . '</callback>' : '';
		$XMLRequest .= '</RequestPermissionsRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Permissions', 'RequestPermissions');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$Token = $DOM -> getElementsByTagName('token') -> length > 0 ? $DOM -> getElementsByTagName('token') -> item(0) -> nodeValue : '';
		$RedirectURL = $this->Sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_grant-permission&request_token=' . $Token : 'https://www.paypal.com/cgi-bin/webscr?cmd=_grant-permission&request_token=' . $Token;
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp, 
								   'Token' => $Token, 
								   'RedirectURL' => $RedirectURL,  
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray; 
	 }
	 
	 
	 /**
	 * Submit GetAccessToken API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$DataArray			Array structure of PayPal request data.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function GetAccessToken($DataArray)
	 {
 		$GetAccessTokenFields = isset($DataArray['GetAccessTokenFields']) ? $DataArray['GetAccessTokenFields'] : array();
		$Token = isset($GetAccessTokenFields['Token']) ? $GetAccessTokenFields['Token'] : '';
		$Verifier = isset($GetAccessTokenFields['Verifier']) ? $GetAccessTokenFields['Verifier'] : '';
		$SubjectAlias = isset($GetAccessTokenFields['SubjectAlias']) ? $GetAccessTokenFields['SubjectAlias'] : '';
		
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<GetAccessTokenRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $Token != '' ? '<token xmlns="">' . $Token . '</token>' : '';
		$XMLRequest .= $Verifier != '' ? '<verifier xmlns="">' . $Verifier . '</verifier>' : '';
		$XMLRequest .= $SubjectAlias != '' ? '<subjectAlias xmlns="">' . $SubjectAlias . '</subjectAlias>' : '';
		$XMLRequest .= '</GetAccessTokenRequest>';

		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Permissions', 'GetAccessToken');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$Scope = array();
		$Scopes = $DOM -> getElementsByTagName('scope') -> length > 0 ? $DOM -> getElementsByTagName('scope') : array();
		foreach($Scopes as $ScopeType)
		{
			array_push($Scope,$ScopeType->nodeValue);
		}
	
		$Token = $DOM -> getElementsByTagName('token') -> length > 0 ? $DOM -> getElementsByTagName('token') -> item(0) -> nodeValue : '';
		$TokenSecret = $DOM -> getElementsByTagName('tokenSecret') -> length > 0 ? $DOM -> getElementsByTagName('tokenSecret') -> item(0) -> nodeValue : '';
				
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'Scope' => $Scope,  
								   'Token' => $Token, 
								   'TokenSecret' => $TokenSecret, 
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;  
	 }
	 
	 
	 /**
	 * Submit GetPermissions API request to PayPal.
	 *
	 * @access	public
	 * @param	string	$Token				Token returned from a GetAccessToken request.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function GetPermissions($Token)
	 {
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<GetPermissionsRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $Token != '' ? '<token xmlns="">' . $Token . '</token>' : '';
		$XMLRequest .= '</GetPermissionsRequest>';

		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Permissions', 'GetPermissions');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$Scope = array();
		$Scopes = $DOM -> getElementsByTagName('scope') -> length > 0 ? $DOM -> getElementsByTagName('scope') : array();
		foreach($Scopes as $ScopeType)
		{
			array_push($Scope,$ScopeType->nodeValue);
		}

		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'Scope' => $Scope,
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray; 
	 }
	 
	 
	 /**
	 * Submit CancelPermissions API request to PayPal.
	 *
	 * @access	public
	 * @param	string	$Token				Token returned from a GetAccessToken request.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function CancelPermissions($Token)
	 {
		 // Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<CancelPermissionsRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= $Token != '' ? '<token xmlns="">' . $Token . '</token>' : '';
		$XMLRequest .= '</CancelPermissionsRequest>';
		
		 // Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Permissions', 'CancelPermissions');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray; 
	 }
	 
	 
	 /**
	 * Submit GetBasicPersonalData API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$AttributeList		Array structure of the list of attributes to obtain for a user.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function GetBasicPersonalData($AttributeList)
	 {
		// Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<GetBasicPersonalDataRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= '<attributeList xmlns="">';
		foreach($AttributeList as $Attribute)
		{
			$XMLRequest .= '<attribute xmlns="">' . $Attribute . '</attribute>';
		}
		$XMLRequest .= '</attributeList>';
		$XMLRequest .= '</GetBasicPersonalDataRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Permissions', 'GetBasicPersonalData');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$PersonalDataList = $DOM -> getElementsByTagName('personalData') -> length > 0 ? $DOM -> getElementsByTagName('personalData') : array();
		
		$PersonalData = array();
		foreach($PersonalDataList as $PersonalDataType)
		{
			$PersonalDataKey = $PersonalDataType -> getElementsByTagName('personalDataKey') -> length > 0 ? $PersonalDataType -> getElementsByTagName('personalDataKey') -> item(0) -> nodeValue : '';
			$PersonalDataValue = $PersonalDataType -> getElementsByTagName('personalDataValue') -> length > 0 ? $PersonalDataType -> getElementsByTagName('personalDataValue') -> item(0) -> nodeValue : '';
		
			$PersonalDataItem = array('PersonalDataKey' => $PersonalDataKey, 'PersonalDataValue' => $PersonalDataValue);
			array_push($PersonalData,$PersonalDataItem);
		}
		
		$PersonalDataKey = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		$PersonalDataValue = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'PersonalData' => $PersonalData,
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray; 
	 }
	 
	 
	 /**
	 * Submit GetAdvancedPersonalData API request to PayPal.
	 *
	 * @access	public
	 * @param	mixed[]	$AttributeList		Array structure of the list of attributes to obtain for a user.
	 * @return	mixed[] $ResponseDataArray	Returns XML result parsed as an array.
	 */
	 function GetAdvancedPersonalData($AttributeList)
	 {
		 // Generate XML Request
		$XMLRequest = '<?xml version="1.0" encoding="utf-8"?>';
		$XMLRequest .= '<GetAdvancedPersonalDataRequest xmlns="' . $this -> XMLNamespace . '">';
		$XMLRequest .= $this -> GetXMLRequestEnvelope();
		$XMLRequest .= '<attributeList xmlns="">';
		foreach($AttributeList as $Attribute)
		{
			$XMLRequest .= '<attribute xmlns="">' . $Attribute . '</attribute>';
		}
		$XMLRequest .= '</attributeList>';
		$XMLRequest .= '</GetAdvancedPersonalDataRequest>';
		
		// Call the API and load XML response into DOM
		$XMLResponse = $this -> CURLRequest($XMLRequest, 'Permissions', 'GetAdvancedPersonalData');
		$DOM = new DOMDocument();
		$DOM -> loadXML($XMLResponse);

        $this->Logger($this->LogPath, __FUNCTION__.'Request', $XMLRequest);
        $this->Logger($this->LogPath, __FUNCTION__.'Response', $XMLResponse);
		
		// Parse XML values
		$Fault = $DOM -> getElementsByTagName('FaultMessage') -> length > 0 ? true : false;
		$Errors = $this -> GetErrors($XMLResponse);
		$Ack = $DOM -> getElementsByTagName('ack') -> length > 0 ? $DOM -> getElementsByTagName('ack') -> item(0) -> nodeValue : '';
		$Build = $DOM -> getElementsByTagName('build') -> length > 0 ? $DOM -> getElementsByTagName('build') -> item(0) -> nodeValue : '';
		$CorrelationID = $DOM -> getElementsByTagName('correlationId') -> length > 0 ? $DOM -> getElementsByTagName('correlationId') -> item(0) -> nodeValue : '';
		$Timestamp = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';

		$PersonalDataList = $DOM -> getElementsByTagName('personalData') -> length > 0 ? $DOM -> getElementsByTagName('personalData') : array();
		
		$PersonalData = array();
		foreach($PersonalDataList as $PersonalDataType)
		{
			$PersonalDataKey = $PersonalDataType -> getElementsByTagName('personalDataKey') -> length > 0 ? $PersonalDataType -> getElementsByTagName('personalDataKey') -> item(0) -> nodeValue : '';
			$PersonalDataValue = $PersonalDataType -> getElementsByTagName('personalDataValue') -> length > 0 ? $PersonalDataType -> getElementsByTagName('personalDataValue') -> item(0) -> nodeValue : '';
		
			$PersonalDataItem = array('PersonalDataKey' => $PersonalDataKey, 'PersonalDataValue' => $PersonalDataValue);
			array_push($PersonalData,$PersonalDataItem);
		}
		
		$PersonalDataKey = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		$PersonalDataValue = $DOM -> getElementsByTagName('timestamp') -> length > 0 ? $DOM -> getElementsByTagName('timestamp') -> item(0) -> nodeValue : '';
		
		$ResponseDataArray = array(
								   'Errors' => $Errors, 
								   'Ack' => $Ack, 
								   'Build' => $Build, 
								   'CorrelationID' => $CorrelationID, 
								   'Timestamp' => $Timestamp,
								   'PersonalData' => $PersonalData,
								   'XMLRequest' => $XMLRequest, 
								   'XMLResponse' => $XMLResponse
								   );
		
		return $ResponseDataArray;
	 }

} // End Class PayPal_Adaptive
