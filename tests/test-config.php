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

		foreach ( UniFi_Client_Alias_Sync\TestSyncer::REQUIRED_CONFIG as $setting => $description ) {
			$return[] = [ $setting, $description ];
		}

		return $return;
	}

	public static function optional_settings() {
		$return = [];

		foreach ( UniFi_Client_Alias_Sync\TestSyncer::OPTIONAL_CONFIG as $setting => $default ) {
			$return[] = [ $setting, $default ];
		}

		return $return;
	}

	public static function values_for_controller() {
		$strings = [
			"Error: The URL defined in UNIFI_ALIAS_SYNC_CONTROLLER does not include the protocol 'https://'.\n",
			"Error: The URL defined in UNIFI_ALIAS_SYNC_CONTROLLER does not include the port number. This is usually 8443 or 443.\n",
			self::$exception_message . "\n",
		];

		return [
		// Invalid
			// Invalid protocol
			[ 'http://example.com:8443',    $strings[0] . $strings[2] ],
			[ 'example.com:8443',           $strings[0] . $strings[2] ],
			[ 'http://example.com:8443',    $strings[0] . $strings[2] ],
			// Invalid port
			[ 'https://example.com',        $strings[1] . $strings[2] ],
			// Both invalid
			[ 'example.com',                implode( '', $strings ) ],
		// Valid
			[ 'https://example.com:8443',   '' ],
			[ 'https://example.com:8443/',  '' ],
			[ 'https://example.com:443',    '' ],
			[ 'https://example.com:443/',   '' ],
			[ 'https://example.com:10443',  '' ],
			[ 'https://example.com:10443/', '' ],
			
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
			UniFi_Client_Alias_Sync\TestSyncer::get_instance()->set_config( $setting, $val );

			$this->expectException( Exception::class );
			$this->expectExceptionMessage( self::$exception_message );
			$this->expectOutputString( sprintf( $error, $setting, $description ) );

			$test->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );
		}
	}

	/**
	 * @dataProvider optional_settings
	 */
	public function test_optional_settings_get_default_values( $setting, $default ) {
		$test = self::get_method( 'verify_config' );

		UniFi_Client_Alias_Sync\TestSyncer::get_instance()->set_config( $setting, null );

		$this->expectOutputString( '' );

		$test->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );

		$this->assertEquals( $default, UniFi_Client_Alias_Sync\TestSyncer::get_instance()->get_config( $setting ) );
	}

	/**
	 * @dataProvider values_for_controller
	 */
	public function test_controller_syntax( $url, $message ) {
		UniFi_Client_Alias_Sync\TestSyncer::get_instance()->set_config( 'UNIFI_ALIAS_SYNC_CONTROLLER', $url );

		$test = self::get_method( 'verify_config' );

		if ( $message ) {
			$this->expectException( Exception::class );
			$this->expectExceptionMessage( 'Terminating script for invalid config file.' );
		}
		$this->expectOutputString( $message );

		$test->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );
	}

	public function test_prioritized_sites_must_be_array() {
		$message = "Error: Invalid format for UNIFI_ALIAS_SYNC_PRIORITIZED_SITES (must be array): %s\n" . self::$exception_message . "\n";

		foreach ( [ '', true, false, 0, 1 ] as $value ) {
			UniFi_Client_Alias_Sync\TestSyncer::get_instance()->set_config( 'UNIFI_ALIAS_SYNC_PRIORITIZED_SITES', $value );

			$test = self::get_method( 'verify_config' );

			$this->expectException( Exception::class );
			$this->expectExceptionMessage( self::$exception_message );
			$this->expectOutputString( sprintf( $message, $value ) );

			$test->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );
		}
	}

	public function test_aliases_must_be_array() {
		$message = "Error: Invalid format for UNIFI_ALIAS_SYNC_ALIASES: %s\n" . self::$exception_message . "\n";

		foreach ( [ '', true, false, 0, 1 ] as $value ) {
			UniFi_Client_Alias_Sync\TestSyncer::get_instance()->set_config( 'UNIFI_ALIAS_SYNC_ALIASES', $value );

			$test = self::get_method( 'verify_config' );

			$this->expectException( Exception::class );
			$this->expectExceptionMessage( self::$exception_message );
			$this->expectOutputString( sprintf( $message, $value ) );

			$test->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );
		}
	}

}
