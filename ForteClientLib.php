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
 * This class is a basically a wrapper for the Forte Advanced Gateway Integration
 * APIs.  It handles the construction and sending of API calls so you don't have
 * to worry about it in your procedural code.
 */
class ForteClient {

  ## Instance names mirror Forte API field names wherever possible. ##

  # authentication fields
  public $merchant_id;
  public $username;
  public $password;
  protected $api_endpoint;

  # transaction fields
  private $transaction_type;
  private $total_aount;
  private $client_id; #cpay agent id
  private $entered_by; #cpay employee name

  # EFT/ACH information
  private $entry_class_code; #NACHA entry class code   
  private $ecom_payment_check_trn; #routing number
  private $ecom_payment_check_account; #back account number
  private $ecom_payment_check_account_type; #account type (savings or checking) 

  # response fields
  private $pg_merchant_data;

  
  # API endpoints
  const PRODUCTION_ENDPOINT = "";

  # NACHA entry class codes
  const AGENT_PAYMENT_ECC = "PPD";

  /*
   * Pass Forte PRODUCTION API credentials to instantiate the class.  The following
   * parameters must be passed:
   *
   *  $forte_merchant_id: The forte merchant ID.
   *  $forte_username: The forte username.
   *  $api_password: The forte API password.
   */
  public function __construct( $forte_merchant_id, $forte_username, $api_password ) {
    $this->merchant_id = $forte_merchant_id;
    $this->username = $forte_username;
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

}

/*
 * This client should be used when testing against the Forte sandbox environment.  All
 * it does is extend the production ForteClient and overwrites the API endpoint.  This
 * makes it so that you must explicitely use the correct client for the environment 
 * that you are working on.  This pattern can also be extended to work on any 
 * environment that is meant to mirror the Forte production system, though this is
 * unlikely to be done by anybody besides Forte themselves. ;)
 */
class ForteTestClient extends ForteClient {
  const TEST_ENDPOINT = "";

  /*
   * Pass Forte TEST API credentials to instantiate the class.  The following
   * parameters must be passed:
   *
   *  $forte_merchant_id: The forte merchant ID.
   *  $forte_username: The forte username.
   *  $api_password: The forte API password.
   */
  public function __construct( $forte_merchant_id, $forte_username, $api_password ) {
    parent::__construct( $forte_merchant_id, $forte_username, $api_password );

    $this->api_endpoint = self::TEST_ENDPOINT;
  }
}

