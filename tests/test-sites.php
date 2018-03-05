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

	/**
	 * Mock site names and values.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $sites = [
		'default' => [
			'name' => 'Default',
		],
		'cd90qe2s' => [
			'name' => 'Charlie Site',
		],
		'9lirxq5p' => [
			'name' => 'Sample Site',
		],
		'a98ey4l5' => [
			'name' => 'Alpha Site',
		],
		'1qwe314gn' => [
			'name' => 'Test Site',
		],
	];

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
		$test = self::get_method( 'get_sites' );

		$sites = $test->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );

		$this->assertEmpty( $sites );
	}

	public function test_get_sites_with_sites() {
		$_sites = self::$sites;

		$this->set_static_var( 'sites', $_sites );

		$test = self::get_method( 'get_sites' );

		$sites = (array) $test->invoke( UniFi_Client_Alias_Sync\TestSyncer::get_instance() );

		$_sites = $this->sort_sites( $_sites );

		$this->assertEquals( $_sites, $sites );
		$this->assertTrue( $this->arrays_are_ordered( $_sites, $sites ) );
	}


}
