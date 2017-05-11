<?php
/**
 * Encapsulates payment processing functionalities for PictureMe
 *
 * For example purpose only. Form on signup.php should be tokenized for added security.
 * Only relevant validation shown in this example (e.g. credit card number, expiry),
 * other validation rules may be required depending on form data.
 *
 * @author  Vito Chin <vito@php.net>
 * @license PHP 3.01 http://www.php.net/license/3_01.txt 
 */
class Payment
{
    /**
     * Array containing request parameters
     *
     * @var array
     */
    protected $_request;

    public function __construct($request)
    {
        $this->_request = $request;
        $this->_validAndFiltered();
        return $this;
    }
    /**
     * Collates information required to send payment request to Payflow Pro, 
     * sends request and returns result from the request.
     * 
     * @return array with success/failure indicator and message
     */
    public function processSales()
    {
        $transactionParams = array(
            'USER'      => PAYFLOWPRO_USER,
            'VENDOR'    => PAYFLOWPRO_VENDOR,
            'PARTNER'   => PAYFLOWPRO_PARTNER,
            'PWD'       => PAYFLOWPRO_PASSWORD,
            'TENDER'    => 'C', 
            'TRXTYPE'   => 'S',
            'ACCT'      => $this->_request['cardNumber'],
            'EXPDATE'   => $this->_request['expiresOn'],
            'NAME'      => $this->_request['nameOnCard'],
            'AMT'       => ANNUAL_SUBSCRIPTION_RATE,
            'CURRENCY'  => ANNUAL_SUBSCRIPTION_CURRENCY,
            'COMMENT1'  => 'Annual payment for PictureMe subscription',
            'CVV2'      => $this->_request['csc'],
            'CLIENTIP' => '0.0.0.0',
            'VERBOSITY'=>'MEDIUM'
        );
        $requestHeader = array(
            "X-VPS-REQUEST-ID: ". md5($this->_request['cardNumber'].time()),
            "X-VPS-CLIENT-TIMEOUT: 45",
        );
        $result = $this->_convertToArray($this->_request($requestHeader, $transactionParams));
        if ($result['RESPMSG'] === 'Approved') {
            return array ('success' => TRUE, 'Message' => "Payment successfully made.<br>Reference No.:{$result['PNREF']}");
        } else {
            return array ('success' => FALSE, 'Message' => "Payment unsuccessful.<br>Reason:{$result['RESPMSG']}");
        }
    }
    /**
     * Initialize, configure and execute a curl call with the provided $header
     * and $transactionParams
     * 
     * @param $header array
     * @param $transactionParams array
     * 
     * @return string curl response
     */
    public function _request($header, $transactionParams)
    {
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, PAYFLOWPRO_URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_TIMEOUT, 45); // times out after 45 secs
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // this line makes it work under https
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_convertToString($transactionParams)); //adding POST data
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2); //verifies ssl certificate
        curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE); //forces closure of connection when done 
        curl_setopt($ch, CURLOPT_POST, 1); //data sent as POST 
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE); //data sent as POST 
        return curl_exec($ch);
    }
    /**
     * Filter and validate request parameters
     * 
     * @return bool 
     */
    public function _validAndFiltered()
    {
        $allowedParameters = array(
            'openID' => NULL,
            'cardNumber' => NULL,
            'nameOnCard' => NULL,
            'expiresOn' => NULL,
            'csc' => NULL,
        );
        $this->_request = array_intersect_key($this->_request, $allowedParameters);
        if (!Zend_Validate::is($this->_request['cardNumber'], 'Ccnum')) {
            throw new Exception("Invalid credit card number");
        }
        if (!Zend_Validate::is($this->_request['nameOnCard'], 'Alpha', array(TRUE)) ||  
            !Zend_Validate::is($this->_request['nameOnCard'], 'StringLength', array(2,26))
        ) {
            throw new Exception("Invalid name");
        }
        if (!Zend_Validate::is($this->_request['expiresOn'], 'Regex', array("/(0[1-9]|1[0-2])[0-9][0-9]/"))) {
            throw new Exception("Invalid expiry date.");
        }
        if (!Zend_Validate::is($this->_request['csc'], 'Digits') ||
            !Zend_Validate::is($this->_request['csc'], 'StringLength', array(3, 4))
        ) {
            throw new Exception("Invalid security code");
        }
        return TRUE;
    }
    /**
     * Convert parameters in array form to name-value string
     * 
     * @return string
     */
    function _convertToString($paramArray)
    {
        $returnString = "";
        foreach ($paramArray as $name => $value) {
            $returnString .= "{$name}=".urlencode($value)."&";
        }
        return substr($returnString, 0, -1);
    }
    /**
     * Convert return string to array
     * 
     * @return array
     */
    function _convertToArray($string)
    {
        $paramArray = array();
        foreach(explode('&', $string) as $param) {
            list ($name, $value) = explode('=', $param);
            $paramArray["{$name}"] = $value;
        }        
        return $paramArray;
    }
}
