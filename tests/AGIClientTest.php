<?php

use LonSun\ForteGateway\AGIClient as AGIClient;
use LonSun\ForteGateway\AGITestClient as AGITestClient;


class ForteClientTests extends PHPUnit_Framework_TestCase
{
  # valid test credentials
  private static $mid = "181085";
  private static $password = "IT7yywK01";

  public function setUp() {
  }

  public function testProductionClientConstructor() {
    $fc = new AGIClient( self::$mid, self::$password );

    $this->assertEquals( $fc->pg_merchant_id, self::$mid );
    $this->assertEquals( $fc->pg_password, self::$password );

    # it should point to production.
    $this->assertEquals( $fc->getApiEndpoint(),
      AGIClient::PRODUCTION_ENDPOINT );
  }

  public function testTestClientConstructor() {
    $fc = new AGITestClient( self::$mid, self::$password );

    $this->assertEquals( $fc->pg_merchant_id, self::$mid );
    $this->assertEquals( $fc->pg_password, self::$password );

    # it should point to sandbox.
    $this->assertEquals( $fc->getApiEndpoint(),
      AGITestClient::TEST_ENDPOINT );
  }

  public function testPreparePayload() {
    $fc = new AGIClient( self::$mid, self::$password );

    $special_chars_val = "l!@#$%1";
    $urlencoded_val = urlencode($special_chars_val);
    $test_payload = array("eye" => "glasses", "one" => 1,
      "special_chars" => $special_chars_val);

    $result = $fc->formatPayloadForSend($test_payload);

    $this->assertEquals( $result,
      "eye=glasses&one=1&special_chars=$urlencoded_val");
  }

  public function testDebugOn() {
    $fc = new AGITestClient( self::$mid, self::$password );

    $fc->debugOn();

    $this->assertTrue( $fc->debug );
  }

  public function testDebugOff() {
    $fc = new AGITestClient( self::$mid, self::$password );

    $fc->debugOff();

    $this->assertFalse( $fc->debug );
  }
}

?>
