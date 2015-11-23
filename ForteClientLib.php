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
  private $pg_total_aount;
  private $pg_consumer_id; #use this to track your internal (not Forte) payee id
  private $pg_entered_by; #name or id of user that initiated the transaction

  # EFT/ACH information
  private $pg_entry_class_code; #NACHA entry class code   
  private $ecom_payment_check_trn; #routing number
  private $ecom_payment_check_account; #back account number
  private $ecom_payment_check_account_type; #account type (savings or checking) 

  # response fields
  private $pg_merchant_data;

  
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
   * Pass Forte PRODUCTION API credentials to instantiate the class.  The
   * following parameters must be passed:
   *
   *  $forte_merchant_id: The forte merchant ID.
   *  $forte_username: The forte username.
   *  $api_password: The forte API password.
   */
  public function __construct( $forte_merchant_id, $api_password ) {
    $this->merchant_id = $forte_merchant_id;
    $this->password = $api_password;

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

  }

  /*
   * Run an eft credit/refund transaction.  The following parameters must be
   * passed:
   *
   *  $payload: An associative array that contains all data necessary for the
   *  requrest.  It has the following keys and values:
   *    
   *    payment_amount: The transaction amount (eg 12.34).
   *    account_type: Savings (S) or checking (C).
   *    account_number: The bank account number (eg 1234567).
   *    routing_number: The bank routing number (eg 123456789).
   */
  public function processEftCredit($payload) {
   # TODO: add transaction code to payload
   execute( preparePayload( $payload ) ); 
  }



}


/*
 * You should be able to pass in a raw response from Forte to instantiate this
 * class.  Then you can check it to see what the result was.
 */
class ForteResponse {

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

