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

		$aliases = $test->invokeArgs( self::$syncer, [ 'invalid' ] );

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

		$aliases = $test->invokeArgs( self::$syncer, [ 'cd90qe2s' ] );

		$this->assertEquals( $expected, $aliases );
	}

	public function test_get_client_aliases_for_first_site() {
		$expected = [
			'9e:cc:a1:2f:0b:aa' => "iPad X - Walter",
			'bc:3e:ea:26:8d:50' => "Only on lowest priority site",
		];

		$test = self::get_method( 'get_client_aliases_for_site' );

		$aliases = $test->invokeArgs( self::$syncer, [ 'default' ] );

		$this->assertEquals( $expected, $aliases );
	}

	public function test_get_client_aliases_for_last_site_with_overwrites() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_ALLOW_OVERWRITES', true );

		$expected = [
			'90:04:e3:51:9d:a1' => "Adam's iPhone 8",
			'35:19:29:f5:4b:1e' => "Brenda's Note 8",
			'e4:d9:c7:cc:46:3b' => "HP Inkjet Printer",
			'9e:cc:a1:2f:0b:aa' => "iPad X - Walter",
		];

		$test = self::get_method( 'get_client_aliases_for_site' );

		$aliases = $test->invokeArgs( self::$syncer, [ 'cd90qe2s' ] );

		$this->assertEquals( $expected, $aliases );
	}

	public function test_get_client_aliases_for_first_site_with_overwrites() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_ALLOW_OVERWRITES', true );

		$expected = [
			'9e:cc:a1:2f:0b:aa' => "iPad X - Walter",
			'bc:3e:ea:26:8d:50' => "Only on lowest priority site",
		];

		$test = self::get_method( 'get_client_aliases_for_site' );

		$aliases = $test->invokeArgs( self::$syncer, [ 'default' ] );

		$this->assertEquals( $expected, $aliases );
	}

	public function test_sync_aliases() {
		// Get client aliases early just so it'll already be memoized and thus not
		// appear in output.
		$test = self::get_method( 'get_aliased_clients' );
		$test->invoke( self::$syncer );

		$expected = <<<TEXT
About to assign client aliases to site default...
	Would have set alias for 9e:cc:a1:2f:0b:aa to "iPad X - Walter".
	Clients assigned an alias: 1.
About to assign client aliases to site 1qwe314gn...
	No clients assigned an alias.
About to assign client aliases to site 9lirxq5p...
	Would have set alias for e4:d9:c7:cc:46:3b to "HP Inkjet Printer".
	Client 90:04:e3:51:9d:a1 already aliased as "iPhone 8 - Adam" (thus not getting aliased as "Adam's iPhone 8").
	Clients assigned an alias: 1.
About to assign client aliases to site a98ey4l5...
	No clients assigned an alias.
About to assign client aliases to site cd90qe2s...
	Client 35:19:29:f5:4b:1e already has the alias "Brenda's Note 8".
	No clients assigned an alias.

TEXT;

		$this->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', false );

		$test = self::get_method( 'sync_aliases' );

		$this->expectOutputString( $expected );

		$aliases = $test->invoke( self::$syncer );
	}

	public function test_sync_aliases_with_overwrites_allowed() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_ALLOW_OVERWRITES', true );

		// Get client aliases early just so it'll already be memoized and thus not
		// appear in output.
		$test = self::get_method( 'get_aliased_clients' );
		$test->invoke( self::$syncer );

		$expected = <<<TEXT
About to assign client aliases to site default...
	Would have set alias for 9e:cc:a1:2f:0b:aa to "iPad X - Walter".
	Clients assigned an alias: 1.
About to assign client aliases to site 1qwe314gn...
	No clients assigned an alias.
About to assign client aliases to site 9lirxq5p...
	Would have set alias for e4:d9:c7:cc:46:3b to "HP Inkjet Printer".
	Would have set alias for 90:04:e3:51:9d:a1 to "Adam's iPhone 8" (overwriting existing alias of "iPhone 8 - Adam").
	Clients assigned an alias: 2.
About to assign client aliases to site a98ey4l5...
	No clients assigned an alias.
About to assign client aliases to site cd90qe2s...
	Client 35:19:29:f5:4b:1e already has the alias "Brenda's Note 8".
	No clients assigned an alias.

TEXT;

		$this->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', false );

		$test = self::get_method( 'sync_aliases' );

		$this->expectOutputString( $expected );

		$aliases = $test->invoke( self::$syncer );
	}

	public function test_excluded_sites_do_not_contribute_client_aliases() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_EXCLUDE_SITES', [ '9lirxq5p' ] );

		// Get client aliases early just so it'll already be memoized and thus not
		// appear in output.
		$test = self::get_method( 'get_aliased_clients' );
		$test->invoke( self::$syncer );

		$expected = <<<TEXT
Excluding site 9lirxq5p
About to assign client aliases to site default...
	No clients assigned an alias.
About to assign client aliases to site 1qwe314gn...
	No clients assigned an alias.
About to assign client aliases to site a98ey4l5...
	No clients assigned an alias.
About to assign client aliases to site cd90qe2s...
	Client 35:19:29:f5:4b:1e already has the alias "Brenda's Note 8".
	No clients assigned an alias.

TEXT;

		$this->set_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS', false );

		$test = self::get_method( 'sync_aliases' );

		$this->expectOutputString( $expected );

		$aliases = $test->invoke( self::$syncer );
	}

	public function test_excluded_site_does_not_have_aliases() {
		$this->set_config( 'UNIFI_ALIAS_SYNC_EXCLUDE_SITES', [ 'default' ] );

		$test = self::get_method( 'get_client_aliases_for_site' );

		$aliases = $test->invokeArgs( self::$syncer, [ 'default' ] );

		$this->assertEmpty( $aliases );
	}

}
