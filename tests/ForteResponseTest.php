<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../ForteClientLib.php';


class ForteResponseTests extends PHPUnit_Framework_TestCase
{

  public function setUp() {
  }

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
}

?>
