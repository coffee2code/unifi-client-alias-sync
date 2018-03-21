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

		$clients = $test->invokeArgs( self::$syncer, [ 'invalid' ] );

		$this->assertEmpty( $clients );
	}

	public function test_get_clients_for_site_with_no_clients() {
		$this->mock_clients( [] );

		$test = self::get_method( 'get_clients' );

		$clients = $test->invokeArgs( self::$syncer, [ '1qwe314gn' ] );

		$this->assertEmpty( $clients );
	}

	public function test_get_clients( $_clients = [] ) {
		if ( ! $_clients ) {
			$_clients = self::$clients;
		}

		$test = self::get_method( 'get_clients' );

		foreach ( array_keys( $_clients ) as $site ) {
			$clients = $test->invokeArgs( self::$syncer, [ $site ] );

			$this->assertEquals( $_clients[ $site ], $clients );
		}
	}

	public function test_get_aliased_clients_with_no_sites() {
		$this->mock_sites( [] );
		$this->mock_clients( [] );

		$test = self::get_method( 'get_aliased_clients' );

		$clients = $test->invoke( self::$syncer );

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
	Site 9lirxq5p has 5 clients, 2 of which are aliased.
		"90:04:e3:51:9d:a1" => "iPhone 8 - Adam"
		"9e:cc:a1:2f:0b:aa" => "iPad X - Walter"
	Site a98ey4l5 has 2 clients, 0 of which are aliased.
	Site cd90qe2s has 3 clients, 2 of which are aliased.
		"35:19:29:f5:4b:1e" => "Brenda's Note 8"
		"bc:3e:ea:26:8d:50" => "Only on lowest priority site"

TEXT;

		$this->expectOutputString( $string );

		$clients = $test->invoke( self::$syncer );

		$this->assertEquals( 3, count( $clients ) );
		$this->assertContains( 'a98ey4l5', array_diff( array_keys( self::$clients ), array_keys( $clients ) ) );
		$this->assertContains( '1qwe314gn', array_diff( array_keys( self::$clients ), array_keys( $clients ) ) );
	}

	public function test_excluded_site_does_not_have_clients() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_EXCLUDE_SITES', [ 'default' ] );

		$this->expectOutputRegex( '//' );

		$test = self::get_method( 'get_aliased_clients' );

		$clients = $test->invoke( self::$syncer );

		$this->assertFalse( array_key_exists( 'default', $clients ) );
	}

	public function test_exclude_clients_for_known_client() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_EXCLUDE_CLIENTS', [ '90:04:e3:51:9d:a1' ] );

		$_clients = self::$clients;

		// Unset the excluded client from the expected clients.
		foreach ( array_keys( $_clients ) as $site ) {
			$_clients[ $site ] = array_filter( $_clients[ $site ], function( $client ) {
				return '90:04:e3:51:9d:a1' !== $client->mac;
			} );
		}

		$this->test_get_clients( $_clients );

		$test = self::get_method( 'get_clients' );
		$clients = $test->invokeArgs( self::$syncer, ['default'] );

		$this->assertEquals( $_clients['default'], $clients );
		$this->assertEquals( 3, count( $clients ) );

		$clients = $test->invokeArgs( self::$syncer, ['9lirxq5p'] );

		$this->assertEquals( $_clients['9lirxq5p'], $clients );
		$this->assertEquals( 4, count( $clients ) );
	}

	public function test_exclude_multiple_clients() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_EXCLUDE_CLIENTS', [ '90:04:e3:51:9d:a1', 'e4:d9:c7:cc:46:3b' ] );

		$_clients = self::$clients;

		// Unset the excluded client from the expected clients.
		foreach ( array_keys( $_clients ) as $site ) {
			$_clients[ $site ] = array_filter( $_clients[ $site ], function( $client ) {
				return ! in_array( $client->mac, [ '90:04:e3:51:9d:a1', 'e4:d9:c7:cc:46:3b' ] );
			} );
		}

		$this->test_get_clients( $_clients );

		$test = self::get_method( 'get_clients' );
		$clients = $test->invokeArgs( self::$syncer, ['default'] );

		$this->assertEquals( $_clients['default'], $clients );
		$this->assertEquals( 2, count( $clients ) );

		$clients = $test->invokeArgs( self::$syncer, ['9lirxq5p'] );

		$this->assertEquals( $_clients['9lirxq5p'], $clients );
		$this->assertEquals( 3, count( $clients ) );
	}

	public function test_exclude_clients_for_unknown_client() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_EXCLUDE_CLIENTS', [ 'unknown' ] );

		$this->test_get_clients();
	}

	public function test_get_aliased_clients_with_excluded_client() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_EXCLUDE_CLIENTS', [ '90:04:e3:51:9d:a1' ] );

		$test = self::get_method( 'get_aliased_clients' );

		$string = <<<TEXT
	Site default has 3 clients, 2 of which are aliased.
		"35:19:29:f5:4b:1e" => "Brenda's Note 8"
		"e4:d9:c7:cc:46:3b" => "HP Inkjet Printer"
	Site 1qwe314gn has 0 clients, 0 of which are aliased.
	Site 9lirxq5p has 4 clients, 1 of which are aliased.
		"9e:cc:a1:2f:0b:aa" => "iPad X - Walter"
	Site a98ey4l5 has 2 clients, 0 of which are aliased.
	Site cd90qe2s has 3 clients, 2 of which are aliased.
		"35:19:29:f5:4b:1e" => "Brenda's Note 8"
		"bc:3e:ea:26:8d:50" => "Only on lowest priority site"

TEXT;

		$this->expectOutputString( $string );

		$clients = $test->invoke( self::$syncer );

		$this->assertEquals( 3, count( $clients ) );
	}

}
