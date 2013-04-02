<?php

class SagePay {
	
	private $urls;
	private $Vendor;
	private $Basket = array();
	
	public $AccountType    = 'E';
	public $GiftAidPayment = 0;
	public $ApplyAVSCV2    = 0;
	public $Apply3DSecure  = 0;
	
	public $VendorTxCode;
	public $Amount;
	public $Currency;
	public $Description;
	public $CardHolder;
	public $CardNumber;
	public $StartDate;
	public $ExpiryDate;
	public $IssueNumber;
	public $CV2;
	public $CardType;
	public $BillingSurname;
	public $BillingFirstnames;
	public $BillingAddress1;
	public $BillingAddress2;
	public $BillingCity;
	public $BillingPostCode;
	public $BillingCountry;
	public $BillingState;
	public $BillingPhone;
	public $DeliverySurname;
	public $DeliveryFirstnames;
	public $DeliveryAddress1;
	public $DeliveryAddress2;
	public $DeliveryCity;
	public $DeliveryPostCode;
	public $DeliveryCountry;
	public $DeliveryState;
	public $DeliveryPhone;
	public $CustomerEmail;
	
	public $result = array('Status' => 'BEGIN');
	
	public function __construct($vendor='', $mode='test') {
		
		$sage_pay_urls = array(
			'live' => array(
				'register' => 'https://live.sagepay.com/gateway/service/vspdirect-register.vsp',
				'3dsecure' => 'https://live.sagepay.com/gateway/service/direct3dcallback.vsp'
			),
			'test' => array(
				'register' => 'https://test.sagepay.com/gateway/service/vspdirect-register.vsp',
				'3dsecure' => 'https://test.sagepay.com/gateway/service/direct3dcallback.vsp'
			),
			'simulator' => array(
				'register' => 'https://test.sagepay.com/Simulator/VSPDirectGateway.asp',
				'3dsecure' => 'https://test.sagepay.com/Simulator/VSPDirectCallback.asp'
			)
		);
		
		$this->urls = in_array($mode, array('live', 'test', 'simulator')) ? $sage_pay_urls[$mode] : $sage_pay_urls['test'];
		
		$this->Vendor = $vendor;
			
	}
	
	/*
	 * Register the transaction with SagePay
	 *
	 */
	public function register() {
		
		$errors                                                       = array();
		if (!$this->Vendor)             $errors['Vendor']             = 'The Vendor must be provided';
		if (!$this->VendorTxCode)       $errors['VendorTxCode']       = 'The Vendor must be provided';
		if (!is_numeric($this->Amount)) $errors['Amount']             = 'The Amount field must be specified, and must be numeric.';
		if (!$this->Currency)           $errors['Currency']           = 'Currency must be specified, eg GBP.';
		if (!$this->Description)        $errors['Description']        = 'Description must be specified.';
		if (!$this->CardHolder)         $errors['CardHolder']         = 'CardHolder must be specified.';
		if (!$this->CardNumber)         $errors['CardNumber']         = 'CardNumber must be specified.';
		if (!$this->ExpiryDate)         $errors['ExpiryDate']         = 'ExpiryDate must be specified.';
		if ($this->IssueNumber and !preg_match("/^\d{1,2}$/", $this->IssueNumber)) $errors['IssueNumber'] = 'IssueNumber is invalid.';
		if ($this->CardType=='AMEX' and !preg_match("/^\d{4}$/", $this->CV2)) $errors['CV2'] = 'CV2 must be 4 numbers long.';
		if ($this->CardType!='AMEX' and !preg_match("/^\d{3}$/", $this->CV2)) $errors['CV2'] = 'CV2 must be 3 numbers long.';
		if (!in_array($this->CardType, array('VISA', 'MC', 'DELTA', 'SOLO', 'MAESTRO', 'UKE', 'AMEX', 'DC', 'JCB', 'LASER'))) $errors['CardType'] = 'CardType must be one of VISA, MC, DELTA, SOLO, MAESTRO, UKE, AMEX, DC, JCB, LASER';
		if (!$this->BillingSurname)     $errors['BillingSurname']     = 'BillingSurname must be specified.';
		if (!$this->BillingFirstnames)  $errors['BillingFirstnames']  = 'BillingFirstnames must be specified.';
		if (!$this->BillingAddress1)    $errors['BillingAddress1']    = 'BillingAddress1 must be specified.';
		if (!$this->BillingCity)        $errors['BillingCity']        = 'BillingCity must be specified.';
		if (!$this->BillingPostCode)    $errors['BillingPostCode']    = 'BillingPostCode must be specified.';
		if (!$this->BillingCountry)     $errors['BillingCountry']     = 'BillingCountry must be specified.';
		if ($this->BillingCountry == 'US' and !$this->BillingState) $errors['BillingState'] = 'BillingState mut be specified.';
		if (!$this->DeliverySurname)    $errors['DeliverySurname']    = 'DeliverySurname must be specified.';
		if (!$this->DeliveryFirstnames) $errors['DeliveryFirstnames'] = 'DeliveryFirstnames must be specified.';
		if (!$this->DeliveryAddress1)   $errors['DeliveryAddress1']   = 'DeliveryAddress1 must be specified.';
		if (!$this->DeliveryCity)       $errors['DeliveryCity']       = 'DeliveryCity must be specified.';
		if (!$this->DeliveryPostCode)   $errors['DeliveryPostCode']   = 'DeliveryPostCode must be specified.';
		if (!$this->DeliveryCountry)    $errors['DeliveryCountry']    = 'DeliveryCountry must be specified.';
		if ($this->DeliveryCountry == 'US' and !$this->DeliveryState) $errors['DeliveryState'] = 'DeliveryState mut be specified.';
		if ($this->CustomerEmail and !preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+\.([a-zA-Z0-9\._-]+)+$/", $this->CustomerEmail)) $errors['CustomerEmail'] = 'CustomerEmail is invalid.';
		
		if (count($errors)) {
			$this->result = array('Status' => 'ERRORCHECKFAIL', 'Errors' => $errors);
			return 'ERRORCHECKFAIL';
		}
		
		$data = array(
			'VPSProtocol'        => 2.23,
			'TxType'             => 'PAYMENT',
			'Vendor'             => $this->Vendor,
			'VendorTxCode'       => $this->VendorTxCode,
			'Amount'             => number_format($this->Amount, 2, '.', ''),
			'Currency'           => $this->Currency,
			'Description'        => $this->Description,
			'CardHolder'         => $this->CardHolder,
			'CardNumber'         => $this->CardNumber,
			'StartDate'          => $this->StartDate,
			'ExpiryDate'         => $this->ExpiryDate,
			'IssueNumber'        => $this->IssueNumber,
			'CV2'                => $this->CV2,
			'CardType'           => $this->CardType,
			'BillingSurname'     => $this->BillingSurname,
			'BillingFirstnames'  => $this->BillingFirstnames,
			'BillingAddress1'    => $this->BillingAddress1,
			'BillingAddress2'    => $this->BillingAddress2,
			'BillingCity'        => $this->BillingCity,
			'BillingPostCode'    => $this->BillingPostCode,
			'BillingCountry'     => $this->BillingCountry,
			'BillingState'       => $this->BillingCountry == 'US' ? $this->BillingState : '',
			'BillingPhone'       => $this->BillingPhone,
			'DeliverySurname'    => $this->DeliverySurname,
			'DeliveryFirstnames' => $this->DeliveryFirstnames,
			'DeliveryAddress1'   => $this->DeliveryAddress1,
			'DeliveryAddress2'   => $this->DeliveryAddress2,
			'DeliveryCity'       => $this->DeliveryCity,
			'DeliveryPostCode'   => $this->DeliveryPostCode,
			'DeliveryCountry'    => $this->DeliveryCountry,
			'DeliveryState'      => $this->DeliveryCountry == 'US' ? $this->DeliveryState : '',
			'DeliveryPhone'      => $this->DeliveryPhone,
			'CustomerEmail'      => $this->CustomerEmail,
			'GiftAidPayment'     => $this->GiftAidPayment,
			'AccountType'        => $this->AccountType,
			'ClientIPAddress'    => $_SERVER['REMOTE_ADDR'],
			'ApplyAVSCV2'        => $this->ApplyAVSCV2,
			'Apply3DSecure'      => $this->Apply3DSecure
		);
		
		if (sizeof($this->Basket)) {
			$data['Basket'] = count($this->Basket);
			foreach($this->Basket as $line) {
				$data['Basket'] .= ':' . $line['description'];
				$data['Basket'] .= ':' . $line['quantity'];
				$data['Basket'] .= ':' . number_format($line['value'], 2, '.', '');
				$data['Basket'] .= ':' . number_format($line['tax'], 2, '.', '');
				$data['Basket'] .= ':' . number_format(($line['value'] + $line['tax']), 2, '.', '');
				$data['Basket'] .= ':' . number_format(($line['quantity'] * ($line['value'] + $line['tax'])), 2, '.', '');
			}
		}
		
		$this->result = $this->requestPost($this->urls['register'], $this->formatData($data));
		
		if (in_array($this->result['Status'], array('INVALID', 'MALFORMED', 'REJECTED', 'NOTAUTHED', 'ERROR'))) {
			$this->result['Errors'] = array();
			foreach(split("\n", $this->result['StatusDetail']) as $error) {
				$this->result['Errors'] = array_merge($this->result['Errors'], $this->getError($error));
			}
		}
		
		// unset the card details, as we're either complete,
		// or we're at 3dsecure, in any case, they're not needed
		// anymore, and in the case of 3dsecure, the instance will
		// be stored, so can't keep card details.
		unset($this->CardNumber);
		unset($this->CardHolder);
		unset($this->CV2);
		
		if ($this->result['Status'] == '3DAUTH') {
			$_SESSION['sagepay_obj'] = serialize($this);
		}
		
		return $this->result['Status'];
		
	}
	
	public function addLine($description, $quantity, $value, $tax=0) {
		$this->Basket[] = array(
			'description'  => $description,
			'quantity'     => $quantity,
			'value'        => $value,
			'tax'          => $tax
		);
	}
	
	public static function recover3d() {
		$sagepay = unserialize($_SESSION['sagepay_obj']);
		unset($_SESSION['sagepay_obj']);
		return $sagepay;
	}
	
	public static function is3dResponse() {
		if (isset($_REQUEST['PaRes']) and isset($_REQUEST['MD']) and isset($_SESSION['sagepay_obj'])) {
			return true;
		} else {
			return false;
		}
	}
	
	public function complete3d() {
		$data = array(
			'PARes' => $_REQUEST['PaRes'],
			'MD'    => $_REQUEST['MD']
		);
		
		$result = $this->requestPost($this->urls['3dsecure'], $this->formatData($data));
		$this->result = $result;
		return $this->result['Status'];
		
	}
	
	public function status() {
		return $this->result['Status'];
	}
	
	private function getError($message) {
		$chunks = split(' : ', $message, 2);
		if ($chunks[0] == '3048') { return array('CardNumber' => 'The card number is invalid.'); }
		if ($chunks[0] == '4022') { return array('CardNumber' => 'The card number is not valid for the card type selected.'); }
		if ($chunks[0] == '4023') { return array('CardNumber' => 'The issue number must be provided.'); }
		return array($message);
	}
	
	/*
	 * Send a post request with cURL
	 * $url = URL to send reuqest to
	 * $data = POST data to send (in URL encoded Key=value pairs)
	 *
	 */
	private function requestPost($url, $data){
		set_time_limit(60);
		$output = array();
		$curlSession = curl_init();	
		curl_setopt ($curlSession, CURLOPT_URL, $url);
		curl_setopt ($curlSession, CURLOPT_HEADER, 0);
		curl_setopt ($curlSession, CURLOPT_POST, 1);
		curl_setopt ($curlSession, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($curlSession, CURLOPT_TIMEOUT, 30); 
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 2);	
		$response = explode(chr(10), curl_exec($curlSession));
		if (curl_error($curlSession)){
			$output['Status'] = "FAIL";
			$output['StatusDetail'] = curl_error($curlSession);
		}
		curl_close ($curlSession);
		for ($i=0; $i<count($response); $i++){
			$splitAt = strpos($response[$i], "=");
			$output[trim(substr($response[$i], 0, $splitAt))] = trim(substr($response[$i], ($splitAt+1)));
		}
		return $output;
	}
	
	/*
	 * Format data for sending in POST request
	 * $data = data as an associative array
	 *
	 */
	private function formatData($data){
		$output = "";
		foreach($data as $key => $value){
			$output .= "&" . $key . "=". urlencode($value);
		}
		$output = substr($output,1);
		return $output;
	}
	
	public static function countries() {
		return array(
			'AF' => 'Afghanistan',
			'AX' => 'Åland Islands',
			'AL' => 'Albania',
			'DZ' => 'Algeria',
			'AS' => 'American Samoa',
			'AD' => 'Andorra',
			'AO' => 'Angola',
			'AI' => 'Anguilla',
			'AQ' => 'Antarctica',
			'AG' => 'Antigua and Barbuda',
			'AR' => 'Argentina',
			'AM' => 'Armenia',
			'AW' => 'Aruba',
			'AU' => 'Australia',
			'AT' => 'Austria',
			'AZ' => 'Azerbaijan',
			'BS' => 'Bahamas',
			'BH' => 'Bahrain',
			'BD' => 'Bangladesh',
			'BB' => 'Barbados',
			'BY' => 'Belarus',
			'BE' => 'Belgium',
			'BZ' => 'Belize',
			'BJ' => 'Benin',
			'BM' => 'Bermuda',
			'BT' => 'Bhutan',
			'BO' => 'Bolivia',
			'BA' => 'Bosnia and Herzegovina',
			'BW' => 'Botswana',
			'BV' => 'Bouvet Island',
			'BR' => 'Brazil',
			'IO' => 'British Indian Ocean Territory',
			'BN' => 'Brunei Darussalam',
			'BG' => 'Bulgaria',
			'BF' => 'Burkina Faso',
			'BI' => 'Burundi',
			'KH' => 'Cambodia',
			'CM' => 'Cameroon',
			'CA' => 'Canada',
			'CV' => 'Cape Verde',
			'KY' => 'Cayman Islands',
			'CF' => 'Central African Republic',
			'TD' => 'Chad',
			'CL' => 'Chile',
			'CN' => 'China',
			'CX' => 'Christmas Island',
			'CC' => 'Cocos (Keeling) Islands',
			'CO' => 'Colombia',
			'KM' => 'Comoros',
			'CG' => 'Congo',
			'CD' => 'Congo, the Democratic Republic of the',
			'CK' => 'Cook Islands',
			'CR' => 'Costa Rica',
			'HR' => 'Croatia',
			'CU' => 'Cuba',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'CI' => 'Côte d\'Ivoire',
			'DK' => 'Denmark',
			'DJ' => 'Djibouti',
			'DM' => 'Dominica',
			'DO' => 'Dominican Republic',
			'EC' => 'Ecuador',
			'EG' => 'Egypt',
			'SV' => 'El Salvador',
			'GQ' => 'Equatorial Guinea',
			'ER' => 'Eritrea',
			'EE' => 'Estonia',
			'ET' => 'Ethiopia',
			'FK' => 'Falkland Islands (Malvinas)',
			'FO' => 'Faroe Islands',
			'FJ' => 'Fiji',
			'FI' => 'Finland',
			'FR' => 'France',
			'GF' => 'French Guiana',
			'PF' => 'French Polynesia',
			'TF' => 'French Southern Territories',
			'GA' => 'Gabon',
			'GM' => 'Gambia',
			'GE' => 'Georgia',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'GL' => 'Greenland',
			'GD' => 'Grenada',
			'GP' => 'Guadeloupe',
			'GU' => 'Guam',
			'GT' => 'Guatemala',
			'GG' => 'Guernsey',
			'GN' => 'Guinea',
			'GW' => 'Guinea-Bissau',
			'GY' => 'Guyana',
			'HT' => 'Haiti',
			'HM' => 'Heard Island and McDonald Islands',
			'VA' => 'Holy See (Vatican City State)',
			'HN' => 'Honduras',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IS' => 'Iceland',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IR' => 'Iran, Islamic Republic of',
			'IQ' => 'Iraq',
			'IE' => 'Ireland',
			'IM' => 'Isle of Man',
			'IL' => 'Israel',
			'IT' => 'Italy',
			'JM' => 'Jamaica',
			'JP' => 'Japan',
			'JE' => 'Jersey',
			'JO' => 'Jordan',
			'KZ' => 'Kazakhstan',
			'KE' => 'Kenya',
			'KI' => 'Kiribati',
			'KP' => 'Korea, Democratic People\'s Republic of',
			'KR' => 'Korea, Republic of',
			'KW' => 'Kuwait',
			'KG' => 'Kyrgyzstan',
			'LA' => 'Lao People\'s Democratic Republic',
			'LV' => 'Latvia',
			'LB' => 'Lebanon',
			'LS' => 'Lesotho',
			'LR' => 'Liberia',
			'LY' => 'Libyan Arab Jamahiriya',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MO' => 'Macao',
			'MK' => 'Macedonia, the former Yugoslav Republic of',
			'MG' => 'Madagascar',
			'MW' => 'Malawi',
			'MY' => 'Malaysia',
			'MV' => 'Maldives',
			'ML' => 'Mali',
			'MT' => 'Malta',
			'MH' => 'Marshall Islands',
			'MQ' => 'Martinique',
			'MR' => 'Mauritania',
			'MU' => 'Mauritius',
			'YT' => 'Mayotte',
			'MX' => 'Mexico',
			'FM' => 'Micronesia, Federated States of',
			'MD' => 'Moldova, Republic of',
			'MC' => 'Monaco',
			'MN' => 'Mongolia',
			'ME' => 'Montenegro',
			'MS' => 'Montserrat',
			'MA' => 'Morocco',
			'MZ' => 'Mozambique',
			'MM' => 'Myanmar',
			'NA' => 'Namibia',
			'NR' => 'Nauru',
			'NP' => 'Nepal',
			'NL' => 'Netherlands',
			'AN' => 'Netherlands Antilles',
			'NC' => 'New Caledonia',
			'NZ' => 'New Zealand',
			'NI' => 'Nicaragua',
			'NE' => 'Niger',
			'NG' => 'Nigeria',
			'NU' => 'Niue',
			'NF' => 'Norfolk Island',
			'MP' => 'Northern Mariana Islands',
			'NO' => 'Norway',
			'OM' => 'Oman',
			'PK' => 'Pakistan',
			'PW' => 'Palau',
			'PS' => 'Palestinian Territory, Occupied',
			'PA' => 'Panama',
			'PG' => 'Papua New Guinea',
			'PY' => 'Paraguay',
			'PE' => 'Peru',
			'PH' => 'Philippines',
			'PN' => 'Pitcairn',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'PR' => 'Puerto Rico',
			'QA' => 'Qatar',
			'RE' => 'Reunion ﻿Réunion',
			'RO' => 'Romania',
			'RU' => 'Russian Federation',
			'RW' => 'Rwanda',
			'BL' => 'Saint Barthélemy',
			'SH' => 'Saint Helena',
			'KN' => 'Saint Kitts and Nevis',
			'LC' => 'Saint Lucia',
			'MF' => 'Saint Martin (French part)',
			'PM' => 'Saint Pierre and Miquelon',
			'VC' => 'Saint Vincent and the Grenadines',
			'WS' => 'Samoa',
			'SM' => 'San Marino',
			'ST' => 'Sao Tome and Principe',
			'SA' => 'Saudi Arabia',
			'SN' => 'Senegal',
			'RS' => 'Serbia',
			'SC' => 'Seychelles',
			'SL' => 'Sierra Leone',
			'SG' => 'Singapore',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'SB' => 'Solomon Islands',
			'SO' => 'Somalia',
			'ZA' => 'South Africa',
			'GS' => 'South Georgia and the South Sandwich Islands',
			'ES' => 'Spain',
			'LK' => 'Sri Lanka',
			'SD' => 'Sudan',
			'SR' => 'Suriname',
			'SJ' => 'Svalbard and Jan Mayen',
			'SZ' => 'Swaziland',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'SY' => 'Syrian Arab Republic',
			'TW' => 'Taiwan',
			'TJ' => 'Tajikistan',
			'TZ' => 'Tanzania, United Republic of',
			'TH' => 'Thailand',
			'TL' => 'Timor-Leste',
			'TG' => 'Togo',
			'TK' => 'Tokelau',
			'TO' => 'Tonga',
			'TT' => 'Trinidad and Tobago',
			'TN' => 'Tunisia',
			'TR' => 'Turkey',
			'TM' => 'Turkmenistan',
			'TC' => 'Turks and Caicos Islands',
			'TV' => 'Tuvalu',
			'UG' => 'Uganda',
			'UA' => 'Ukraine',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom',
			'US' => 'United States',
			'UM' => 'United States Minor Outlying Islands',
			'UY' => 'Uruguay',
			'UZ' => 'Uzbekistan',
			'VU' => 'Vanuatu',
			'VE' => 'Venezuela, Bolivarian Republic of',
			'VN' => 'Viet Nam',
			'VG' => 'Virgin Islands, British',
			'VI' => 'Virgin Islands, U.S.',
			'WF' => 'Wallis and Futuna',
			'EH' => 'Western Sahara',
			'YE' => 'Yemen',
			'ZM' => 'Zambia',
			'ZW' => 'Zimbabwe'
		);
	}
	public static function country($code) {
		$countries = self::countries();
		return $countries[$code];
	}
}