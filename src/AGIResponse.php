<?php namespace LonSun\ForteGateway;
/**
 * This is part of the client library for Forte Advanced Gateway Inteface (AGI)
 * APIs. See http://www.forte.net/devdocs/pdf/agi_integration.pdf.
 *
 * @author Lon Sun <lon.sun@gmail.com>
 *
 * Forte AGI Reference Documentation version: 3.11
 * Tested on PHP version(s): 5.5.30
 */


/*
 * A wrapper for an AGI API response.  It provides a simplified interface and
 * convenience methods for working with the response.
 *
 * A Forte AGI response consists of key, value pairs delimited by newline
 * characters. The end of the response is indicated by the string "endofdata"
 * on it's own line.
 */
class AGIResponse {

  private $raw_response; # unaltered response

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

  # AGI response fields name to internal attribute mappings. Note that many
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
   * Take a raw AGI response and extract the info we need.  The raw
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
   * Convert a raw AGI response to JSON for easier access. Note that
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
