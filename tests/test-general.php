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

	public function test_is_debug_false_by_default() {
		$foo = self::get_method( 'is_debug' );
		$resp = $foo->invoke( self::$syncer );
		$this->assertFalse( $resp );
	}

	public function test_is_debug_when_setting_is_true() {
		self::$syncer->set_config( 'UNIFI_ALIAS_SYNC_DEBUG' , true );

		$foo = self::get_method( 'is_debug' );
		$resp = $foo->invoke( self::$syncer );
		$this->assertTrue( $resp );
	}

	public function test_status() {
		$message = "This is a message.";
		$test = self::get_method( 'status' );

		$this->expectOutputString( $message . "\n" );

		$test->invokeArgs( self::$syncer, array( $message ) );
	}

	public function test_disabled_status() {
		self::$syncer->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', true );

		$message = "This is a message.";
		$test = self::get_method( 'status' );

		$this->expectOutputString( '' );

		$test->invokeArgs( self::$syncer, array( $message ) );
	}

	public function test_bail() {
		$message = "This is a test message.";

		$test = self::get_method( 'bail' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $message );
		$this->expectOutputString( $message . "\n" );

		$clients = $test->invokeArgs( self::$syncer, array( $message ) );
	}

	public function test_bail_when_output_disabled() {
		$message = "This is a test message.";

		$this->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', true );

		$test = self::get_method( 'bail' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $message );
		$this->expectOutputRegex( '/^$/' );

		$clients = $test->invokeArgs( self::$syncer, array( $message ) );

		$this->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', false );
	}

}
