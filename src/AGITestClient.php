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


use LonSun\ForteGateway\AGI;


/*
 * This client should be used when testing against the Forte sandbox
 * environment.  All it does is extend the production ForteClient and
 * overwrites the API endpoint.  This makes it so that you must explicitely use
 * the correct client for the environment that you are working on.  This
 * pattern can also be extended to work on an environment that is meant to
 * mirror the Forte production system, though this is unlikely to be done by
 * anybody besides Forte themselves. ;)
 */
class AGITestClient extends AGIClient {
  const TEST_ENDPOINT = "https://www.paymentsgateway.net/cgi-bin/posttest.pl";

  /*
   * Pass Forte TEST API credentials to instantiate the class.  The following
   * parameters must be passed:
   *
   *  $forte_merchant_id: The forte merchant ID.
   *  $forte_username: The forte username.
   *  $api_password: The forte API password.
   */
  public function __construct( $forte_merchant_id, $api_password ) {
    parent::__construct( $forte_merchant_id, $api_password );

    $this->api_endpoint = self::TEST_ENDPOINT;
  }
}

