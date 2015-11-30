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

  # transaction fields
  private $pg_transaction_type;
  private $pg_total_amount;
  private $pg_consumer_id; #use this to track your internal (not Forte) payee id
  private $pg_entered_by; #name or id of user that initiated the transaction

  # EFT/ACH information
  private $pg_entry_class_code; #NACHA entry class code   
  private $ecom_payment_check_trn; #routing number
  private $ecom_payment_check_account; #back account number
  private $ecom_payment_check_account_type; #account type (savings or checking) 

  # customer/agent information
  private $ecom_billto_postal_name_first;
  private $ecom_billto_postal_name_last;

  # response fields
  private $pg_merchant_data;

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
   * Take an associative array and return an ampersand-delimited string with 
   * all values urlencoded.
   *
   * Parameters:
   *
   *  $payload: An associative array containing the raw payload.
   */
  public function preparePayload($payload) {
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

    return $response;
  }

  /*
   * Run an eft sale transaction.  The following parameters must be
   * passed:
   *
   *  $payload: An associative array that contains all data necessary for the
   *  request.  It has the following keys and values:
   *    
   *    payment_amount: The transaction amount (eg 12.34).
   *    account_type: Savings (S) or checking (C).
   *    account_number: The bank account number (eg 1234567).
   *    routing_number: The bank routing number (eg 123456789).
   *    payee_first_name: The first name of the person you are paying.
   *    payee_last_name: The last name of the person you are paying.
   */
  public function processEftSale($transaction_data) {
    $this->pg_total_amount = $transaction_data['payment_amount'];
    $this->ecom_payment_check_account_type = $transaction_data['account_type'];
    $this->ecom_payment_check_account = $transaction_data['account_number'];
    $this->ecom_payment_check_trn = $transaction_data['routing_number'];
    $this->ecom_billto_postal_name_first = $transaction_data['payee_first_name'];
    $this->ecom_billto_postal_name_last = $transaction_data['payee_last_name'];

    $payload = array(
      "pg_merchant_id" => $this->pg_merchant_id,
      "pg_password" => $this->pg_password,
      "pg_transaction_type" => self::EFT_SALE,
      "pg_total_amount" => $this->pg_total_amount,
      "ecom_payment_check_account_type" => $this->ecom_payment_check_account_type,
      "ecom_payment_check_account" => $this->ecom_payment_check_account,
      "ecom_payment_check_trn" => $this->ecom_payment_check_trn,
      "ecom_billto_postal_name_first" => $this->ecom_billto_postal_name_first,
      "ecom_billto_postal_name_last" => $this->ecom_billto_postal_name_last
    );

    return $this->execute( $this->preparePayload( $payload ) );
  }

  /*
   * Run an eft credit/refund transaction.  The following parameters must be
   * passed:
   *
   *  $payload: An associative array that contains all data necessary for the
   *  request.  It has the following keys and values:
   *    
   *    payment_amount: The transaction amount (eg 12.34).
   *    account_type: Savings (S) or checking (C).
   *    account_number: The bank account number (eg 1234567).
   *    routing_number: The bank routing number (eg 123456789).
   *    payee_first_name: The first name of the person you are paying.
   *    payee_last_name: The last name of the person you are paying.
   */
  public function processEftCredit($transaction_data) {
    $this->pg_total_amount = $transaction_data['payment_amount'];
    $this->ecom_payment_check_account_type = $transaction_data['account_type'];
    $this->ecom_payment_check_account = $transaction_data['account_number'];
    $this->ecom_payment_check_trn = $transaction_data['routing_number'];
    $this->ecom_billto_postal_name_first = $transaction_data['payee_first_name'];
    $this->ecom_billto_postal_name_last = $transaction_data['payee_last_name'];

    $payload = array(
      "pg_merchant_id" => $this->pg_merchant_id,
      "pg_password" => $this->pg_password,
      "pg_transaction_type" => self::EFT_CREDIT,
      "pg_total_amount" => $this->pg_total_amount,
      "ecom_payment_check_account_type" => $this->ecom_payment_check_account_type,
      "ecom_payment_check_account" => $this->ecom_payment_check_account,
      "ecom_payment_check_trn" => $this->ecom_payment_check_trn,
      "ecom_billto_postal_name_first" => $this->ecom_billto_postal_name_first,
      "ecom_billto_postal_name_last" => $this->ecom_billto_postal_name_last
    );

    return $this->execute( $this->preparePayload( $payload ) );
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
  public $type;
  public $code;
  public $description;
  public $trace_number;
  
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

    # apparently there is no such thing as a JSON object in PHP
    $response_arr = json_decode( self::rawResponseToJson( $raw_response ), true );

    $this->type = $response_arr['pg_response_type'];
    $this->code = $response_arr['pg_response_code'];
    $this->description = $response_arr['pg_response_description'];
    $this->trace_number = $response_arr['pg_trace_number'];
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

