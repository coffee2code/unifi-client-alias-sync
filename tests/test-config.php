<?php
/**
 * UniFi Controller Client Alias Sync script unit tests related to configuration
 * options, their values, and related functions (mostly `verify_config()`).
 *
 * Copyright (c) 2018 by Scott Reilly (aka coffee2code)
 *
 * @package UniFi_Client_Alias_Sync
 * @author  Scott Reilly
 */

use PHPUnit\Framework\TestCase;

final class UniFiClientAliasConfigTest extends UniFiClientAliasTestBase {

	protected static $exception_message = "Terminating script for invalid config file.";

	//
	//
	// DATA PROVIDERS
	//
	//


	public static function required_settings() {
		$return = [];

		foreach ( UniFi_Client_Alias_Sync\Syncer::REQUIRED_CONFIG as $setting => $description ) {
			$return[] = [ $setting, $description ];
		}

		return $return;
	}

	public static function optional_settings() {
		$return = [];

		foreach ( UniFi_Client_Alias_Sync\Syncer::OPTIONAL_CONFIG as $setting => $default ) {
			$return[] = [ $setting, $default ];
		}

		return $return;
	}

	public static function values_for_port() {
		$error_msg = "Error: Invalid format for UNIFI_ALIAS_SYNC_PORT (must be integer): %s";

		return [
		// Invalid
			[ 0,      $error_msg ],
			[ "0",    $error_msg ],
			[ false,  $error_msg ],
			[ true,   $error_msg ],
			[ '',     $error_msg ],
			[ 'aa',   $error_msg ],
		// Valid
			[ 8443,   '' ],
			[ '8443', '' ],
			[ null,   '' ],
		];
	}

	//
	//
	// TESTS
	//
	//


	// verify_config()

	/**
	 * @dataProvider required_settings
	 */
	public function test_required_settings_are_required( $setting, $description ) {
		$error = "Error: Required constant %s was not defined: %s\n" . self::$exception_message . "\n";

		$test = self::get_method( 'verify_config' );

		foreach ( [ null, '' ] as $val ) {
			$this->set_config( $setting, $val );

			$this->expectException( Exception::class );
			$this->expectExceptionMessage( self::$exception_message );
			$this->expectOutputString( sprintf( $error, $setting, $description ) );

			$test->invoke( self::$syncer );
		}
	}

	/**
	 * @dataProvider optional_settings
	 */
	public function test_optional_settings_get_default_values( $setting, $default ) {
		$this->set_config( $setting, null );

		// The default for UNIFI_ALIAS_SYNC_TESTING is set to true for unit testing.
		if ( 'UNIFI_ALIAS_SYNC_TESTING' === $setting ) {
			$default = true;
		}

		$this->assertEquals( $default, self::$syncer->get_config( $setting ) );
	}

	/**
	 * @dataProvider values_for_port
	 */
	public function test_port_must_be_int_like( $port, $message ) {
		$this->set_config( 'UNIFI_ALIAS_SYNC_PORT', $port );

		$test = self::get_method( 'verify_config' );

		if ( $message ) {
			$this->expectException( Exception::class );
			$this->expectExceptionMessage( self::$exception_message );

			$message = sprintf( $message, $port ) . "\n" . self::$exception_message . "\n";
		}
		$this->expectOutputString( $message );

		$test->invoke( self::$syncer );
	}

	public function test_exclude_sites_must_be_array() {
		$message = "Error: Invalid format for UNIFI_ALIAS_SYNC_EXCLUDE_SITES (must be array): %s\n" . self::$exception_message . "\n";

		foreach ( [ '', true, false, 0, 1 ] as $value ) {
			$this->set_config( 'UNIFI_ALIAS_SYNC_EXCLUDE_SITES', $value );

			$test = self::get_method( 'verify_config' );

			$this->expectException( Exception::class );
			$this->expectExceptionMessage( self::$exception_message );
			$this->expectOutputString( sprintf( $message, $value ) );

			$test->invoke( self::$syncer );
		}
	}

	public function test_prioritized_sites_must_be_array() {
		$message = "Error: Invalid format for UNIFI_ALIAS_SYNC_PRIORITIZED_SITES (must be array): %s\n" . self::$exception_message . "\n";

		foreach ( [ '', true, false, 0, 1 ] as $value ) {
			$this->set_config( 'UNIFI_ALIAS_SYNC_PRIORITIZED_SITES', $value );

			$test = self::get_method( 'verify_config' );

			$this->expectException( Exception::class );
			$this->expectExceptionMessage( self::$exception_message );
			$this->expectOutputString( sprintf( $message, $value ) );

			$test->invoke( self::$syncer );
		}
	}

	public function test_aliases_must_be_array() {
		$message = "Error: Invalid format for UNIFI_ALIAS_SYNC_ALIASES: %s\n" . self::$exception_message . "\n";

		foreach ( [ '', true, false, 0, 1 ] as $value ) {
			$this->set_config( 'UNIFI_ALIAS_SYNC_ALIASES', $value );

			$test = self::get_method( 'verify_config' );

			$this->expectException( Exception::class );
			$this->expectExceptionMessage( self::$exception_message );
			$this->expectOutputString( sprintf( $message, $value ) );

			$test->invoke( self::$syncer );
		}
	}

}
