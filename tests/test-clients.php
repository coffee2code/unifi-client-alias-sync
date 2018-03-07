<?php
/**
 * UniFi Controller Client Alias Sync script unit tests related to clients.
 *
 * Copyright (c) 2018 by Scott Reilly (aka coffee2code)
 *
 * @package UniFi_Client_Alias_Sync
 * @author  Scott Reilly
 */

use PHPUnit\Framework\TestCase;

final class UniFiClientAliasClientsTest extends UniFiClientAliasTestBase {

	public function setUp() {
		parent::setUp();

		$this->mock_clients();
	}

	public function test_get_clients_for_invalid_site() {
		$test = self::get_method( 'get_clients' );

		$clients = $test->invokeArgs( UniFi_Client_Alias_Sync\TestSyncer::get_instance(), array( 'invalid' ) );

		$this->assertEmpty( $clients );
	}

	public function test_get_clients_for_site_with_no_clients() {
		$this->mock_clients( [] );

		$test = self::get_method( 'get_clients' );

		$clients = $test->invokeArgs( UniFi_Client_Alias_Sync\TestSyncer::get_instance(), array( '1qwe314gn' ) );

		$this->assertEmpty( $clients );
	}

	public function test_get_clients() {
		$_clients = self::$clients;

		$test = self::get_method( 'get_clients' );

		foreach ( array_keys( $_clients ) as $site ) {
			$clients = $test->invokeArgs( UniFi_Client_Alias_Sync\TestSyncer::get_instance(), array( $site ) );

			$this->assertEquals( $_clients[ $site ], $clients );
		}
	}

	public function test_get_aliased_clients_with_no_sites() {
		$this->mock_sites( [] );
		$this->mock_clients( [] );

		$test = self::get_method( 'get_aliased_clients' );

		$clients = $test->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );

		$this->assertEmpty( $clients );
	}

	public function test_get_aliased_clients() {
		$test = self::get_method( 'get_aliased_clients' );

		$string = <<<TEXT
	Site default has 4 clients, 3 of which are aliased.
		"90:04:e3:51:9d:a1" => "Adam's iPhone 8"
		"35:19:29:f5:4b:1e" => "Brenda's Note 8"
		"e4:d9:c7:cc:46:3b" => "HP Inkjet Printer"
	Site 1qwe314gn has 0 clients, 0 of which are aliased.
	Site 9lirxq5p has 4 clients, 2 of which are aliased.
		"90:04:e3:51:9d:a1" => "iPhone 8 - Adam"
		"9e:cc:a1:2f:0b:aa" => "iPad X - Walter"
	Site a98ey4l5 has 2 clients, 0 of which are aliased.
	Site cd90qe2s has 3 clients, 2 of which are aliased.
		"35:19:29:f5:4b:1e" => "Brenda's Note 8"
		"bc:3e:ea:26:8d:50" => "Only on lowest priority site"

TEXT;

		$this->expectOutputString( $string );

		$clients = $test->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );

		$this->assertEquals( 3, count( $clients ) );
		$this->assertContains( 'a98ey4l5', array_diff( array_keys( self::$clients ), array_keys( $clients ) ) );
		$this->assertContains( '1qwe314gn', array_diff( array_keys( self::$clients ), array_keys( $clients ) ) );
	}

}
