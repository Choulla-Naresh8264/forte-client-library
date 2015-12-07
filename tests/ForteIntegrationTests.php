<?php

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../ForteClientLib.php');


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
