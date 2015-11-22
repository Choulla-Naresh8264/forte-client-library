<?php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../ForteClientLib.php');

class ForteClientTests extends PHPUnit_Framework_TestCase
{
  # valid test credentials
  private static $mid = "181085";
  private static $username = "Q2Uvg3";
  private static $password = "IT7yywK01";

  public function setUp() {
  }

  public function testProductionClientConstructor() {
    $fc = new Forte\ForteClient( self::$mid, self::$username, self::$password );
    
    $this->assertEquals( $fc->merchant_id, self::$mid );
    $this->assertEquals( $fc->username, self::$username );
    $this->assertEquals( $fc->password, self::$password );

    # it should point to production.
    $this->assertEquals( $fc->getApiEndpoint(), Forte\ForteClient::PRODUCTION_ENDPOINT );
  }

  public function testTestClientConstructor() {
    $fc = new Forte\ForteClient( self::$mid, self::$username, self::$password );
    
    $this->assertEquals( $fc->merchant_id, self::$mid );
    $this->assertEquals( $fc->username, self::$username );
    $this->assertEquals( $fc->password, self::$password );

    # it should point to sandbox.
    $this->assertEquals( $fc->getApiEndpoint(), Forte\ForteTestClient::TEST_ENDPOINT );
  }

}

?>
