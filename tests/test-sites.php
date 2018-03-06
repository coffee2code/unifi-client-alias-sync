<?php
/**
 * UniFi Controller Client Alias Sync script unit tests related to sites.
 *
 * Copyright (c) 2018 by Scott Reilly (aka coffee2code)
 *
 * @package UniFi_Client_Alias_Sync
 * @author  Scott Reilly
 */

use PHPUnit\Framework\TestCase;

final class UniFiClientAliasSitesTest extends UniFiClientAliasTestBase {

	protected function sort_sites( $sites ) {
		$sorted_sites = [];

		// Move default to the beginning.
		if ( ! empty( $sites['default'] ) ) {
			$sorted_sites['default'] = $sites['default'];
			unset( $sites['default'] );
		}

		ksort( $sites );

		foreach ( $sites as $site => $values ) {
			$sorted_sites[ $site ] = $values;
		}

		return $sorted_sites;
	}

	public function test_get_sites_with_no_sites() {
		// Unset mocked sites.
		$this->mock_sites( [] );

		$test = self::get_method( 'get_sites' );

		$sites = $test->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );

		$this->assertEmpty( $sites );
	}

	public function test_get_sites_with_sites() {
		$_sites = self::$sites;

		$test = self::get_method( 'get_sites' );

		$sites = $test->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );

		$_sites = $this->sort_sites( $_sites );

		$this->assertEquals( $_sites, $sites );
		$this->assertTrue( $this->arrays_are_ordered( $_sites, $sites ) );
	}

	public function test_prioritize_sites_puts_default_first() {
		$test = self::get_method( 'prioritize_sites' );

		$sites = $test->invokeArgs( UniFi_Client_Alias_Sync\TestSyncer::get_instance(), [ self::$sites ] );
		reset( $sites );

		$this->assertEquals( 'default', key( $sites ) );
	}

	public function test_prioritize_sites_single() {
		$_sites = self::$sites;

		$this->set_config( 'UNIFI_ALIAS_SYNC_PRIORITIZED_SITES', [ '9lirxq5p' ] );

		$test = self::get_method( 'prioritize_sites' );

		$sites = $test->invokeArgs( UniFi_Client_Alias_Sync\TestSyncer::get_instance(), [ $_sites ] );
		reset( $sites );

		$this->assertEquals( '9lirxq5p', key( $sites ) );

		// Get full ordered array.
		$site = $_sites[ '9lirxq5p' ];
		unset( $_sites[ '9lirxq5p' ] );
		$_sites = $this->sort_sites( $_sites );
		$_sites = array_merge( [ '9lirxq5p' => $site ], $_sites );

		$this->assertTrue( $this->arrays_are_ordered( $_sites, $sites ) );
	}

	public function test_prioritize_sites_multiple() {
		$_sites = self::$sites;

		$this->set_config( 'UNIFI_ALIAS_SYNC_PRIORITIZED_SITES', [ '1qwe314gn', 'cd90qe2s' ] );

		$test = self::get_method( 'prioritize_sites' );

		$sites = $test->invokeArgs( UniFi_Client_Alias_Sync\TestSyncer::get_instance(), [ $_sites ] );
		reset( $sites );

		$this->assertEquals( '1qwe314gn', key( $sites ) );
		$this->assertEquals( 'cd90qe2s', array_keys( $sites )[1] );
		$this->assertEquals( 'default', array_keys( $sites )[2] );

		// Get full ordered array.
		$p_sites = [ '1qwe314gn' => $_sites[ '1qwe314gn'], 'cd90qe2s' => $_sites[ 'cd90qe2s' ] ];
		unset( $_sites[ '1qwe314gn' ] );
		unset( $_sites[ 'cd90qe2s' ] );
		$_sites = $this->sort_sites( $_sites );
		$_sites = array_merge( $p_sites, $_sites );

		$this->assertTrue( $this->arrays_are_ordered( $_sites, $sites ) );
	}

}
