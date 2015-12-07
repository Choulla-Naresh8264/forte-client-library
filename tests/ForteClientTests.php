<?php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../ForteClientLib.php');

class ForteClientTests extends PHPUnit_Framework_TestCase
{
  # valid test credentials
  private static $mid = "181085";
  private static $password = "IT7yywK01";

  public function setUp() {
  }

  ##                   ##
  ## ForteClient tests ##
  ##                   ##

  public function testProductionClientConstructor() {
    $fc = new Forte\ForteClient( self::$mid, self::$password );
    
    $this->assertEquals( $fc->pg_merchant_id, self::$mid );
    $this->assertEquals( $fc->pg_password, self::$password );

    # it should point to production.
    $this->assertEquals( $fc->getApiEndpoint(), 
      Forte\ForteClient::PRODUCTION_ENDPOINT );
  }

  public function testTestClientConstructor() {
    $fc = new Forte\ForteTestClient( self::$mid, self::$password );
    
    $this->assertEquals( $fc->pg_merchant_id, self::$mid );
    $this->assertEquals( $fc->pg_password, self::$password );

    # it should point to sandbox.
    $this->assertEquals( $fc->getApiEndpoint(), 
      Forte\ForteTestClient::TEST_ENDPOINT );
  }

  public function testPreparePayload() {
    $fc = new Forte\ForteClient( self::$mid, self::$password );

    $special_chars_val = "l!@#$%1";
    $urlencoded_val = urlencode($special_chars_val);
    $test_payload = array("eye" => "glasses", "one" => 1, 
      "special_chars" => $special_chars_val);

    $result = $fc->preparePayload($test_payload);

    $this->assertEquals( $result,
      "eye=glasses&one=1&special_chars=$urlencoded_val");
  }

  public function testDebugOn() {
    $fc = new Forte\ForteTestClient( self::$mid, self::$password );

    $fc->debugOn();

    $this->assertTrue( $fc->debug );
  }

  public function testDebugOff() {
    $fc = new Forte\ForteTestClient( self::$mid, self::$password );

    $fc->debugOff();

    $this->assertFalse( $fc->debug );
  }

  ##                     ##
  ## ForteResponse tests ##
  ##                     ##
  
  public function testForteResponseConstructor() {
    $raw_response = "pg_response_type=D
      pg_response_code=U19
      pg_total_amount=1.23
      pg_response_description=INVALID TRN
      pg_trace_number=4A06A2E2-11EF-484F-AC79-682588B7013F
      endofdata";

    $response = new Forte\ForteResponse( $raw_response );

    $this->assertEquals( $response->result, "D" );
    $this->assertEquals( $response->result_code, "U19" );
    $this->assertEquals( $response->description, "INVALID TRN" );
    $this->assertEquals( $response->trace_number,
      "4A06A2E2-11EF-484F-AC79-682588B7013F" );
  }

  public function testRawResponseToJson() {
    $raw_response = "pg_response_type=D
      pg_response_code=U19
      pg_total_amount=1.23
      pg_response_description=INVALID TRN
      pg_trace_number=4A06A2E2-11EF-484F-AC79-682588B7013F
      test_error_description=MANDITORY FIELD MISSING:pg_merchant_id,MANDITORY FIELD MISSING:ecom_billto_postal_name_first,MANDITORY FIELD MISSING:ecom_billto_postal_name_last
      endofdata";

    $expected_result = '{';
    $expected_result .= '"pg_response_type":"D",'; 
    $expected_result .= '"pg_response_code":"U19",'; 
    $expected_result .= '"pg_total_amount":"1.23",'; 
    $expected_result .= '"pg_response_description":"INVALID TRN",'; 
    $expected_result .= '"pg_trace_number":"4A06A2E2-11EF-484F-AC79-682588B7013F",'; 
    $expected_result .= '"test_error_description":"MANDITORY FIELD MISSING:pg_merchant_id,MANDITORY FIELD MISSING:ecom_billto_postal_name_first,MANDITORY FIELD MISSING:ecom_billto_postal_name_last"'; 
    $expected_result .= '}';

    $this->assertEquals( Forte\ForteResponse::rawResponseToJson( $raw_response ),
      $expected_result );
  }

  public function testIsSuccesful() {
    # success
    $raw_response = "
      pg_response_type=A
      pg_response_code=A01
      pg_response_description=APPROVED
      pg_authorization_code=14025988
      pg_trace_number=CC0D1E15-628F-46E3-A749-601FB80D1A0D
      endofdata";

    $response = new Forte\ForteResponse( $raw_response );

    $this->assertEquals( $response->isSuccessful(), true );

    # not successfull
    $raw_response = "pg_response_type=D
      pg_response_code=U19
      pg_total_amount=1.23
      pg_response_description=INVALID TRN
      pg_trace_number=4A06A2E2-11EF-484F-AC79-682588B7013F
      endofdata";

    $response = new Forte\ForteResponse( $raw_response );

    $this->assertEquals( $response->isSuccessful(), false );
  }

  public function testHasError() {
    # success
    $raw_response = "
      pg_response_type=A
      pg_response_code=A01
      pg_response_description=APPROVED
      pg_authorization_code=14025988
      pg_trace_number=CC0D1E15-628F-46E3-A749-601FB80D1A0D
      endofdata";

    $response = new Forte\ForteResponse( $raw_response );

    $this->assertEquals( $response->hasError(), false );

    # not successfull
    $raw_response = "pg_response_type=D
      pg_response_code=U19
      pg_total_amount=1.23
      pg_response_description=INVALID TRN
      pg_trace_number=4A06A2E2-11EF-484F-AC79-682588B7013F
      endofdata";

    $response = new Forte\ForteResponse( $raw_response );

    $this->assertEquals( $response->hasError(), false );

    # has an error
    $raw_response = "pg_response_type=E
      pg_response_code=E10
      pg_response_description=MANDITORY FIELD MISSING:pg_merchant_id,MANDITORY FIELD MISSING:ecom_billto_postal_name_first,MANDITORY FIELD MISSING:ecom_billto_postal_name_last
      pg_trace_number=E1BC65D5-EEC6-4187-A76E-65F8AE33EB48
      pg_transaction_type=23
      pg_total_amount=1.23
      endofdata
      ";

    $response = new Forte\ForteResponse( $raw_response );

    $this->assertEquals( $response->hasError(), true );
  }


  ##                   ##
  ## integration tests ##
  ##                   ##
  
  public function testRunEftCredit() {
    $fc = new Forte\ForteTestClient( self::$mid, self::$password );

    $payload = array( 
        "payment_amount" => 1.23,
        "account_type" => "C",
        "account_number" => "12345",
        "routing_number" => "265473812",
        "payee_first_name" => "Bob",
        "payee_last_name" => "Lablaw"
      );

    $response = $fc->processEftCredit($payload);

    #TODO: is this good enough?  really no exception should be thrown...
    $this->assertTrue( !empty( $response ) );
  }

  public function testRunEftSale() {
    $fc = new Forte\ForteTestClient( self::$mid, self::$password );

    $payload = array( 
        "payment_amount" => 1.23,
        "account_type" => "C",
        "account_number" => "12345",
        "routing_number" => "265473812",
        "payee_first_name" => "Bob",
        "payee_last_name" => "Lablaw"
      );

    $response = $fc->processEftSale($payload);

    #TODO: is this good enough?  really no exception should be thrown...
    $this->assertTrue( !empty( $response ) );
  }
}

?>
