<?php
/**
 * UniFi Controller Client Alias Sync script unit tests related to client aliases.
 *
 * Copyright (c) 2018 by Scott Reilly (aka coffee2code)
 *
 * @package UniFi_Client_Alias_Sync
 * @author  Scott Reilly
 */

use PHPUnit\Framework\TestCase;

final class UniFiClientAliasAliasesTest extends UniFiClientAliasTestBase {

	public function setUp() {
		parent::setUp();

		$this->mock_clients();
		$this->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', true );
	}

	public function tearDown() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', false );
	}

	public function test_get_client_aliases_for_site_for_invalid_site() {
		$test = self::get_method( 'get_client_aliases_for_site' );

		$aliases = $test->invokeArgs( UniFi_Client_Alias_Sync\TestSyncer::get_instance(), array( 'invalid' ) );

		$this->assertEmpty( $aliases );
	}

	public function test_get_client_aliases_for_last_site() {
		$expected = [
			'90:04:e3:51:9d:a1' => "Adam's iPhone 8",
			'35:19:29:f5:4b:1e' => "Brenda's Note 8",
			'e4:d9:c7:cc:46:3b' => "HP Inkjet Printer",
			'9e:cc:a1:2f:0b:aa' => "iPad X - Walter",
		];

		$test = self::get_method( 'get_client_aliases_for_site' );

		$aliases = $test->invokeArgs( UniFi_Client_Alias_Sync\TestSyncer::get_instance(), array( 'cd90qe2s' ) );

		$this->assertEquals( $expected, $aliases );
	}

	public function test_get_client_aliases_for_first_site() {
		$expected = [
			'90:04:e3:51:9d:a1' => "iPhone 8 - Adam",
			'9e:cc:a1:2f:0b:aa' => "iPad X - Walter",
			'35:19:29:f5:4b:1e' => "Brenda's Note 8",
		];

		$test = self::get_method( 'get_client_aliases_for_site' );

		$aliases = $test->invokeArgs( UniFi_Client_Alias_Sync\TestSyncer::get_instance(), array( 'default' ) );

		$this->assertEquals( $expected, $aliases );
	}

}
