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
		$resp = $foo->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );
		$this->assertFalse( $resp );
	}

	public function test_is_debug_when_setting_is_true() {
		UniFi_Client_Alias_Sync\TestSyncer::get_instance()->set_config( 'UNIFI_ALIAS_SYNC_DEBUG' , true );

		$foo = self::get_method( 'is_debug' );
		$resp = $foo->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );
		$this->assertTrue( $resp );
	}

	public function test_status() {
		$message = "This is a message.";
		$test = self::get_method( 'status' );

		ob_start();
		$test->invokeArgs( UniFi_Client_Alias_Sync\TestSyncer::get_instance(), array( $message ) );
		$status = ob_get_contents();
		ob_end_clean();

		$this->assertEquals( $message . "\n", $status );
	}

	public function test_disabled_status() {
		UniFi_Client_Alias_Sync\TestSyncer::get_instance()->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', true );

		$message = "This is a message.";
		$test = self::get_method( 'status' );

		ob_start();
		$test->invokeArgs( UniFi_Client_Alias_Sync\TestSyncer::get_instance(), array( $message ) );
		$status = ob_get_contents();
		ob_end_flush();

		$this->assertEmpty( $status );
	}

}