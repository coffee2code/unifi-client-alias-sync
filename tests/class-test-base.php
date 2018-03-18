<?php
/**
 * Base test class for all unit tests for the UniFi Controller Client Alias
 * Sync script.
 *
 * Copyright (c) 2018 by Scott Reilly (aka coffee2code)
 *
 * @package UniFi_Client_Alias_Sync
 * @author  Scott Reilly
 */

use PHPUnit\Framework\TestCase;

class UniFiClientAliasTestBase extends TestCase {

	/**
	 * Default configuration values.
	 *
	 * @var array
	 * @access protected
	 */
	protected static $default_config = [
		'UNIFI_ALIAS_SYNC_CONTROLLER'        => 'https://example.com',
		'UNIFI_ALIAS_SYNC_PORT'              => 8443,
		'UNIFI_ALIAS_SYNC_USER'              => 'adminuser',
		'UNIFI_ALIAS_SYNC_PASSWORD'          => 'adminpassword',
		'UNIFI_ALIAS_SYNC_VERIFY_SSL'        => false,
		'UNIFI_ALIAS_SYNC_DRY_RUN'           => true,
		'UNIFI_ALIAS_SYNC_DEBUG'             => false,
		'UNIFI_ALIAS_SYNC_ALIASES'           => [],
		'UNIFI_ALIAS_SYNC_ALLOW_OVERWRITES'  => false,
		'UNIFI_ALIAS_SYNC_EXCLUDE_SITES'     => [],
		'UNIFI_ALIAS_SYNC_PRIORITIZED_SITES' => [],
		'UNIFI_ALIAS_SYNC_DISABLE_STATUS'    => false,
		'UNIFI_ALIAS_SYNC_TESTING'           => true,
	];

	/**
	 * Instance of the Syncer object.
	 *
	 * @var Syncer
	 */
	protected static $syncer;

	/**
	 * Mock sites.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $sites;

	/**
	 * Mock clients per site.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $clients;

	/**
	 * Actions to perform before each test.
	 */
	public function setUp() {
		$this->set_static_var( 'instance', null );
		$this->set_static_var( 'sites', null );
		$this->set_static_var( 'clients', null );
		$this->set_static_var( 'client_aliases', null );

		self::$syncer = UniFi_Client_Alias_Sync\Syncer::get_instance();

		// Reset settings to default values.
		self::reset_config();

		$this->mock_sites();
	}

	protected function mock_sites( $sites = null ) {
		if ( is_null( $sites ) ) {
			$sites = self::$sites = [
				'cd90qe2s' => (object) [
					'name' => 'cd90qe2s',
					'desc' => 'Charlie Site',
				],
				'default' => (object) [
					'name' => 'default',
					'desc' => 'Default',
				],
				'9lirxq5p' => (object) [
					'name' => '9lirxq5p',
					'desc' => 'Sample Site',
				],
				'a98ey4l5' => (object) [
					'name' => 'a98ey4l5',
					'desc' => 'Alpha Site',
				],
				'1qwe314gn' => (object) [
					'name' => '1qwe314gn',
					'desc' => 'Test Site',
				],
			];
		} else {
			$sites = [];
		}

		$this->set_static_var( 'sites', $sites );

		return $sites;
	}

	protected function mock_clients( $clients = null ) {
		if ( is_null( $clients ) ) {
			$clients = self::$clients = [
				'cd90qe2s' => [
					(object) [
						// Intentionally unaliased and not found on another site
						'mac'  => 'b3:ae:91:cd:3d:c1',
					],
					(object) [
						// Intentionally identical to client of 'default'
						'mac'  => '35:19:29:f5:4b:1e',
						'name' => "Brenda's Note 8",
					],
					(object) [
						// Aliased client on lowest priority site that doesn't appear on higher priority sites
						'mac'  => 'bc:3e:ea:26:8d:50',
						'name' => "Only on lowest priority site",
					],
				],
				'default' => [
					(object) [
						// Unaliased client that should get alias from lower priority site
						'mac'  => '9e:cc:a1:2f:0b:aa',
					],
					(object) [
						// Aliased client that has a different alias on a lower priority site
						'mac'  => '90:04:e3:51:9d:a1',
						'name' => "Adam's iPhone 8",
					],
					(object) [
						// Aliased client that has the same alias on a lower priority site
						// Also, the client is on a lower priority site without alias
						'mac'  => '35:19:29:f5:4b:1e',
						'name' => "Brenda's Note 8",
					],
					(object) [
						// Aliased client that should not be present elsewhere
						'mac'  => 'e4:d9:c7:cc:46:3b',
						'name' => "HP Inkjet Printer",
					],
				],
				'9lirxq5p' => [
					(object) [
						// Intentionally unaliased and not found on another site
						'mac'  => '33:4a:a0:5c:52:15',
					],
					(object) [
						// Intentionally unaliased and not found on another site
						'mac'  => 'e4:d9:c7:cc:46:3b',
					],
					(object) [
						// Intentionally unaliased client that is aliased on higher priority site
						'mac'  => '35:19:29:f5:4b:1e',
					],
					(object) [
						// Intentionally different alias than client of 'default'
						'mac'  => '90:04:e3:51:9d:a1',
						'name' => "iPhone 8 - Adam",
					],
					(object) [
						// Explicit alias for unaliased client of 'default'
						'mac'  => '9e:cc:a1:2f:0b:aa',
						'name' => "iPad X - Walter",
					],
				],
				// Site with multiple unaliased clients
				'a98ey4l5' => [
					(object) [
						// Only appearance of this client
						'mac'  => 'f2:ab:4e:e2:fa:fa',
					],
					(object) [
						// Only appearance of this client
						'mac'  => 'd5:67:1a:f8:7e:0a',
					],
				],
				// Site with no clients
				'1qwe314gn' => [
				],
			];
		} else {
			$clients = [];
		}

		$this->set_static_var( 'clients', $clients );

		return $clients;
	}

	/**
	 * Returns the ReflectionMethod instance for the given method.
	 *
	 * @access protected
	 *
	 * @param string $name The method name.
	 * @return ReflectionMethod
	 */
	protected static function get_method( $name ) {
		$class = new \ReflectionClass( 'UniFi_Client_Alias_Sync\Syncer' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );
		unset( $class );
		return $method;
	}

	/**
	 * Resets all settings to their default values.
	 *
	 * @access protected
	 */
	protected static function reset_config() {
		foreach ( self::$default_config as $setting => $value ) {
			self::set_config( $setting, $value );
		}
	}

	protected static function set_config( $setting, $value ) {
		self::$syncer->set_config( $setting, $value );
	}

	/**
	 * Sets the value of the an otherwise protected or private property for the
	 * UniFi_Client_Alias_Sync\Syncer class.
	 *
	 * @access protected
	 *
	 * @param string $name  Name of the class property.
	 * @param mixed  $value The value to assign the property.
	 * @return mixed The value assigned to the property.
	 */
	protected static function set_static_var( $name, $value ) {
		$class = new \ReflectionClass( 'UniFi_Client_Alias_Sync\Syncer' );
		$static_var = $class->getProperty( $name );
		$static_var->setAccessible( true );
		$static_var->setValue( $value );
		return $value;
	}

	/**
	 * Determine if two associative arrays are similar
	 *
	 * Both arrays must have the same indexes with identical values
	 * without respect to key ordering 
	 * 
	 * @param array $a The first array.
	 * @param array $b The second array.
	 * @return bool True if the two array have the same keys, in the same order,
	 *              with the value of each key the same in both array. Else false.
	 */
	protected function arrays_are_ordered( $a, $b ) {
		$this->assertEquals( $a, $b );

		// Check that keys are of the same order.
		$a_keys = array_keys( $a );
		$b_keys = array_keys( $b );
		foreach ( $a_keys as $i => $key ) {
			if ( $key !== $b_keys[ $i ] ) {
				return false;
			}
		}

		return true;
	}

}
