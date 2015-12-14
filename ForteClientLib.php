<?php
/**
 * Client library for Forte Advanced Gateway Inteface APIs. See 
 * http://www.forte.net/devdocs/pdf/agi_integration.pdf.
 *
 * @author Lon Sun <lon.sun@gmail.com>
 *
 * Forte AGI Reference Documentation version: 3.11
 * Tested on PHP version(s): 5.5.30
 */
namespace Forte;


error_reporting(-1);

/*
 * This client should be used in production.  Use ForteTestClient when testing
 * against the Forte sandbox environment.
 *
 * This class is a basically a wrapper for the Forte Advanced Gateway
 * Integration APIs.  It handles the construction and sending of API calls so
 * you don't have to worry about it in your procedural code.  It uses the raw
 * HTTP POST delivery method for connecting to the Forte APIs.
 */
class ForteClient {

  ## Instance names mirror Forte API field names wherever possible. ##

  protected $api_endpoint;

  # authentication fields
  public $pg_merchant_id;
  public $pg_password;

  # other
  public $debug = false;
  
  # API endpoints
  const PRODUCTION_ENDPOINT = 
    "https://www.paymentsgateway.net/cgi-bin/postauth.pl";

  # NACHA entry class codes
  const AGENT_PAYMENT_ECC = "PPD";

  # EFT tranasaction codes
  const EFT_SALE        = 20;
  const EFT_AUTH_ONLY   = 21;
  const EFT_CAPTURE     = 22;
  const EFT_CREDIT      = 23; //AKA refund
  const EFT_VOID        = 24;
  const EFT_FORCE       = 25;
  const EFT_VERIFY_ONLY = 26;

  # control characters
  const DELIMITER = "&";

  /*
   * Turn debug mode on.
   */
  public function debugOn() {
    $this->debug = true;
  }

  /*
   * Turn debug mode off.
   */
  public function debugOff() {
    $this->debug = false;
  }

  /*
   * Pass Forte PRODUCTION API credentials to instantiate the class.  The
   * following parameters must be passed:
   *
   *  $forte_merchant_id: The forte merchant ID.
   *  $forte_username: The forte username.
   *  $api_password: The forte API password.
   */
  public function __construct( $forte_merchant_id, $api_password ) {
    $this->pg_merchant_id = $forte_merchant_id;
    $this->pg_password = $api_password;

    $this->api_endpoint = self::PRODUCTION_ENDPOINT;
  }

  /*
   * Getter for internal API endpoint value.  This value cannot be set 
   * externally.
   */
  public function getApiEndpoint() {
    return $this->api_endpoint;
  }

  /****************************************************************************
   * Prepare the transaction for sending to the gateway.  Per the 
   * documentation, do the following steps when using raw HTTP POST delivery
   * method:
   *
   * 1. URL encodes the field values (to escape special characters).
   * 2. Concatenate message into an ampersand delimited string.
   * 3. Set the message to be passed as the “content” resource.
   * 4. Perform the POST (URL provided to approved merchants).
   * 5. Forte web server returns newline delimited response message (not HTML).
   ***************************************************************************/

  /*
   * Take an associative array and, as per the Forte specs, return an ampersand-
   * delimited string with all values urlencoded.
   *
   * Parameters:
   *
   *  $payload: An associative array containing the raw payload.
   */
  public function formatPayloadForSend($payload) {
    $result = "";

    $idx = 0;
    foreach($payload as $key => $val) {
      $idx++;

      $result .= "$key=" . urlencode($val);
      
      if($idx != count($payload)) $result .= self::DELIMITER;  
    }

    return $result;
  }

  /*
   * Send the request to Forte.
   *
   * Parametes:
   *
   *  $payload: The payload in a ready-to-send state.
   */
  private function execute($payload) {

    //TODO: check for exceptions in comms layer/curl
    $ch = curl_init();

    if( $this->debug ) echo "\ncURL initialized.  Sending request...\n\n";

    $c_opts = array(CURLOPT_URL => $this->api_endpoint,
                    CURLOPT_VERBOSE => $this->debug,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload);

    curl_setopt_array($ch, $c_opts);

    $response = curl_exec($ch);

    curl_close($ch);

    return new ForteResponse($response);
  }

  /*
   * Get auth data for a request.
   */
  protected function authData() {
    return array(
      "pg_merchant_id" => $this->pg_merchant_id,
      "pg_password" => $this->pg_password);
  }

  /*
   * Build eft transaction data part of the payload using input from the
   * calling program..
   *
   *  $payload: An associative array that contains all data necessary for the
   *  request.  It has the following keys and values:
   *
   *    payment_amount: The transaction amount (eg 12.34).
   *    account_type: Savings (S) or checking (C).
   *    account_number: The bank account number (eg 1234567).
   *    routing_number: The bank routing number (eg 123456789).
   *    client_first_name: The first name of the person you are paying.
   *    client_last_name: The last name of the person you are paying.
   */
  protected function eftData($eft_data) {
    return array(
      "pg_total_amount" => $eft_data['payment_amount'],
      "ecom_payment_check_account_type" => $eft_data['account_type'],
      "ecom_payment_check_account" => $eft_data['account_number'],
      "ecom_payment_check_trn" => $eft_data['routing_number'],
      "ecom_billto_postal_name_first" => $eft_data['client_first_name'],
      "ecom_billto_postal_name_last" => $eft_data['client_last_name']
    );
  }

  /*
   * Run an eft sale transaction.  The following parameters must be
   * passed:
   *
   *  $payload: An associative array that contains all data necessary for the
   *  request.  See the eftData() method for details.
   *    
   */
  public function processEftSale($tran_data) {
    $payload = array_merge(
      $this->authData(), 
      array('pg_transaction_type' => self::EFT_SALE), 
      $this->eftData($tran_data));

    return $this->execute( $this->formatPayloadForSend( $payload ) );
  }

  /*
   * Run an eft credit/refund transaction.  The following parameters must be
   * passed:
   *
   *  $payload: An associative array that contains all data necessary for the
   *  request.  See the eftData() method for details.
   */
  public function processEftCredit($tran_data) {
    $payload = array_merge(
      $this->authData(), 
      array('pg_transaction_type' => self::EFT_CREDIT), 
      $this->eftData($tran_data));

    return $this->execute( $this->formatPayloadForSend( $payload ) );
  }
}


/*
 * You should be able to pass in a raw response from Forte to instantiate this
 * class.  Then you can check it to see what the result was.
 *
 * A Forte AGI response consists of key, value pairs delimited by newline 
 * characters. The end of the response is indicated by the string "endofdata"
 * on it's own line.
 */
class ForteResponse {
  
  private $raw_response; //raw response from Forte
  
  # most commonly used response fields
  public $result;
  public $result_code;
  public $description;
  public $trace_number;
  public $auth_code;
  public $preauth_result;
  public $preauth_description;
  public $preauth_neg_report;
  public $client_id; # The agent ID

  const APPROVED = 'A';
  const DECLINED = 'D';
  const ERROR = 'E';

  # Forte response fields name to internal attribute mappings. Note that many
  # of these fields are not used currently so will have no corresponding
  # internal attribute.  They are included to ensure that each index is found
  # when evaluating the request.
  private $field_map = array(
    'pg_merchant_id' => null,
    'pg_transaction_type' => null,
    'pg_merchant_data_1' => null,
    'pg_merchant_data_1' => null,
    'pg_merchant_data_2' => null,
    'pg_merchant_data_3' => null,
    'pg_merchant_data_4' => null,
    'pg_merchant_data_5' => null,
    'pg_merchant_data_6' => null,
    'pg_merchant_data_7' => null,
    'pg_merchant_data_8' => null,
    'pg_merchant_data_9' => null,
    'pg_total_amount' => null,
    'pg_sales_tax_amount' => null,
    'pg_merchant_data_' => null,
    'pg_client_id' => 'client_id',
    'pg_consumer_id' => null,
    'ecom_consumerorderid' => null,
    'pg_payment_token' => null,
    'pg_payment_method_id' => null,
    'ecom_walletid' => null,
    'ecom_billto_postal_name_first' => null,
    'ecom_billto_postal_name_last' => null,
    'pg_billto_postal_name_company' => null,
    'ecom_billto_online_email' => null,
    'pg_response_type' => 'result',
    'pg_response_code' => 'result_code',
    'pg_response_description' => 'description',
    'pg_avs_result' => null,
    'pg_avs_code' => null,
    'pg_trace_number' => 'trace_number',
    'pg_authorization_code' => 'auth_code',
    'pg_preauth_code' => null,
    'pg_preauth_result' => 'preauth_result',
    'pg_preauth_description' => 'preauth_description',
    'pg_preauth_neg_report' => null,
    'pr_requested_amount' => null,
    'pg_available_card_balance' => null,
    'pg_cvv2_result' => null,
    'pg_cvv_code' => null
  );
  
  /*
   * Take a raw response from Forte and extract the info we need.  The raw
   * response will still be available at any time.
   *
   * Parameters:
   *  
   *  $raw_response: The raw response from Forte as a string.
   */
  public function __construct( $raw_response ) {
    $this->raw_response = $raw_response;

    $response = json_decode( self::rawResponseToJson( $raw_response ), true );

    foreach( $this->field_map as $response_field => $internal_attr ) {
      if( array_key_exists( $response_field, $response ) && $internal_attr ) {
        $this->$internal_attr = $response[$response_field];
      }
    }
  }

  /*
   * Convenience method to tell if the response was successful.
   */
  public function isSuccessful() {
    if( $this->result == self::APPROVED ) return true;
    
    return false; 
  }

  /*
   * Convenience method to tell if the response has an error.
   */
  public function hasError() {
    if( $this->result == self::ERROR ) return true;
    
    return false; 
  }

  /*
   * Convert a raw response from Forte to JSON for easier access. Note that
   * the "endofdata" string that appears at the end of Forte responses is
   * dropped so that only the key/value pairs in the response are returned.
   *
   * Parameters:
   *
   *  $raw_response: Should be a raw response from Forte as a string
   */
  public static function rawResponseToJson( $raw_response ) {
    $matches = array();

    preg_match_all( '/(\w+)=(.+)\n/', $raw_response, $matches ); 

    return json_encode( array_combine( $matches[1], $matches[2] ) );
  }
}

/*
 * This client should be used when testing against the Forte sandbox
 * environment.  All it does is extend the production ForteClient and
 * overwrites the API endpoint.  This makes it so that you must explicitely use
 * the correct client for the environment that you are working on.  This
 * pattern can also be extended to work on an environment that is meant to 
 * mirror the Forte production system, though this is unlikely to be done by
 * anybody besides Forte themselves. ;)
 */
class ForteTestClient extends ForteClient {
  const TEST_ENDPOINT = "https://www.paymentsgateway.net/cgi-bin/posttest.pl";

  /*
   * Pass Forte TEST API credentials to instantiate the class.  The following
   * parameters must be passed:
   *
   *  $forte_merchant_id: The forte merchant ID.
   *  $forte_username: The forte username.
   *  $api_password: The forte API password.
   */
  public function __construct( $forte_merchant_id, $api_password ) {
    parent::__construct( $forte_merchant_id, $api_password );

    $this->api_endpoint = self::TEST_ENDPOINT;
  }
}

