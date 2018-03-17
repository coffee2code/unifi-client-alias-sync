<?php
/**
 * Unit tests for the UniFi Controller Client Alias Sync script.
 *
 * Copyright (c) 2018 by Scott Reilly (aka coffee2code)
 *
 * @package UniFi_Client_Alias_Sync
 * @author  Scott Reilly
 */

use PHPUnit\Framework\TestCase;

final class UniFiClientAliasGeneralTest extends UniFiClientAliasTestBase {

	//
	//
	// DATA PROVIDERS
	//
	//


	public static function values_for_get_controller_url() {
		return [
			[ 'https://example.com:8443',  null,  'https://example.com:8443' ],
			[ 'https://example.com:8443/', null,  'https://example.com:8443' ],
			[ 'https://example.com:8443',  443,   'https://example.com:8443' ],
			[ 'https://example.com:8443/', 443,   'https://example.com:8443' ],
			[ 'https://example.com:8443',  '443', 'https://example.com:8443' ],
			[ 'https://example.com:8443/', '443', 'https://example.com:8443' ],
			[ 'https://example.com',       null,  'https://example.com:8443' ],
			[ 'https://example.com/',      null,  'https://example.com:8443' ],
			[ 'https://example.com',       443,   'https://example.com:443'  ],
			[ 'https://example.com/',      443,   'https://example.com:443'  ],
			[ 'https://example.com',       '443', 'https://example.com:443'  ],
			[ 'https://example.com/',      '443', 'https://example.com:443'  ],
			[ 'example.com:8443',          null,  'https://example.com:8443' ],
			[ 'example.com:8443/',         null,  'https://example.com:8443' ],
			[ 'example.com:8443',          443,   'https://example.com:8443' ],
			[ 'example.com:8443/',         443,   'https://example.com:8443' ],
			[ 'example.com:8443',          '443', 'https://example.com:8443' ],
			[ 'example.com:8443/',         '443', 'https://example.com:8443' ],
			[ 'example.com',               null,  'https://example.com:8443' ],
			[ 'example.com/',              null,  'https://example.com:8443' ],
			[ 'example.com',               443,   'https://example.com:443'  ],
			[ 'example.com/',              443,   'https://example.com:443'  ],
			[ 'example.com',               '443', 'https://example.com:443'  ],
			[ 'example.com/',              '443', 'https://example.com:443'  ],
		];
	}


	public function test_is_debug_false_by_default() {
		$foo = self::get_method( 'is_debug' );
		$resp = $foo->invoke( self::$syncer );
		$this->assertFalse( $resp );
	}

	public function test_is_debug_when_setting_is_true() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_DEBUG', true );

		$foo = self::get_method( 'is_debug' );
		$resp = $foo->invoke( self::$syncer );
		$this->assertTrue( $resp );
	}

	public function test_is_testing_when_setting_is_false() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_TESTING', false );

		$foo = self::get_method( 'is_testing' );
		$resp = $foo->invoke( self::$syncer );
		$this->assertFalse( $resp );
	}

	public function test_is_testing_when_setting_is_true() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_TESTING', true );

		$foo = self::get_method( 'is_testing' );
		$resp = $foo->invoke( self::$syncer );
		$this->assertTrue( $resp );
	}

	public function test_status() {
		$message = "This is a message.";
		$test = self::get_method( 'status' );

		$this->expectOutputString( $message . "\n" );

		$test->invokeArgs( self::$syncer, [ $message ] );
	}

	public function test_disabled_status() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', true );

		$message = "This is a message.";
		$test = self::get_method( 'status' );

		$this->expectOutputString( '' );

		$test->invokeArgs( self::$syncer, [ $message ] );
	}

	public function test_bail() {
		$message = "This is a test message.";

		$test = self::get_method( 'bail' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $message );
		$this->expectOutputString( $message . "\n" );

		$clients = $test->invokeArgs( self::$syncer, [ $message ] );
	}

	public function test_bail_when_output_disabled() {
		$message = "This is a test message.";

		$this->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', true );

		$test = self::get_method( 'bail' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $message );
		$this->expectOutputRegex( '/^$/' );

		$clients = $test->invokeArgs( self::$syncer, [ $message ] );

		$this->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', false );
	}

	/**
	 * @dataProvider values_for_get_controller_url
	 */
	public function test_get_controller_url( $url, $port, $expected ) {
		$test = self::get_method( 'get_controller_url' );

		$this->set_config( 'UNIFI_ALIAS_SYNC_CONTROLLER', $url );
		$this->set_config( 'UNIFI_ALIAS_SYNC_PORT', ( $port ?? null ) );

		$controller_url = $test->invoke( self::$syncer );

		$this->assertEquals( $expected, $controller_url );
	}

}
