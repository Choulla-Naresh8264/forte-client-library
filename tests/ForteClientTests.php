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

  public function testProductionClientConstructor() {
    $fc = new Forte\ForteClient( self::$mid, self::$password );
    
    $this->assertEquals( $fc->merchant_id, self::$mid );
    $this->assertEquals( $fc->password, self::$password );

    # it should point to production.
    $this->assertEquals( $fc->getApiEndpoint(), 
      Forte\ForteClient::PRODUCTION_ENDPOINT );
  }

  public function testTestClientConstructor() {
    $fc = new Forte\ForteTestClient( self::$mid, self::$password );
    
    $this->assertEquals( $fc->merchant_id, self::$mid );
    $this->assertEquals( $fc->password, self::$password );

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

  # integration test
  public function testRunEftCredit() {
    $fc = new Forte\ForteTestClient( self::$mid, self::$password );

    $payload = array( 
        "payment_amount" => 1.23,
        "account_type" => "C",
        "account_number" => "12345",
        "routing_number" => "123456789"
      );

    $response = $fc->processEftCredit($payload);

    #TODO: is this good enough?  really no exception should be thrown...
    $this->assertTrue( !empty( $response ) );
  }

}

?>
