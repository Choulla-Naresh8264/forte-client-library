<?php namespace Lonsun\Fortegateway;
/**
 * This is part of the client library for Forte Advanced Gateway Inteface (AGI)
 * APIs. See http://www.forte.net/devdocs/pdf/agi_integration.pdf.
 *
 * @author Lon Sun <lon.sun@gmail.com>
 *
 * Forte AGI Reference Documentation version: 3.11
 * Tested on PHP version(s): 5.5.30
 */

use LonSun\ForteGateway\AGIResponse;

/*
 * This client should be used in production.  Use AGITestClient when testing
 * against the Forte sandbox environment.
 *
 * This class is a basically a wrapper for the Forte Advanced Gateway
 * Integration APIs.  It handles the construction and sending of API calls so
 * you don't have to worry about it in your procedural code.  It uses the raw
 * HTTP POST delivery method for connecting to the Forte APIs.
 */
class AGIClient {

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
   * Take an associative array and, as per the AGI specs, return an ampersand-
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

    return new AGIResponse($response);
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
