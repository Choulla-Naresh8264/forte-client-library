<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../ForteClientLib.php';


class ForteIntegrationTests extends PHPUnit_Framework_TestCase
{
  # valid test credentials
  private static $mid = "181085";
  private static $password = "IT7yywK01";

  public function setUp() {
  }
  
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

    # We can not guarantee that these will always be successful
    # without valid test creds
    $this->assertFalse( $response->hasError() );
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

    # We can not guarantee that these will always be successful
    # without valid test creds
    $this->assertFalse( $response->hasError() );
  }
}

?>
