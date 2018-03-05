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
		'UNIFI_ALIAS_SYNC_CONTROLLER'        => 'https://example.com:8443',
		'UNIFI_ALIAS_SYNC_USER'              => 'adminuser',
		'UNIFI_ALIAS_SYNC_PASSWORD'          => 'adminpassword',
		'UNIFI_ALIAS_SYNC_VERIFY_SSL'        => false,
		'UNIFI_ALIAS_SYNC_DRY_RUN'           => true,
		'UNIFI_ALIAS_SYNC_DEBUG'             => false,
		'UNIFI_ALIAS_SYNC_ALIASES'           => [],
		'UNIFI_ALIAS_SYNC_PRIORITIZED_SITES' => [],
		'UNIFI_ALIAS_SYNC_DISABLE_STATUS'    => false,
		'UNIFI_ALIAS_SYNC_TESTING'           => true,
	];

	/**
	 * Actions to perform before each test.
	 */
	public function setUp() {
		// Reset settings to default values.
		self::reset_config();
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
		$class = new \ReflectionClass( 'UniFi_Client_Alias_Sync\TestSyncer' );
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
		foreach ( self::$default_config as $config => $value ) {
			UniFi_Client_Alias_Sync\TestSyncer::get_instance()->set_config( $config, $value );
		}
	}

	/**
	 * Sets the value of the an otherwise protected or private property for the
	 * UniFi_Client_Alias_Sync\TestSyncer class.
	 *
	 * @access protected
	 *
	 * @param string $name  Name of the class property.
	 * @param mixed  $value The value to assign the property.
	 * @return mixed The value assigned to the property.
	 */
	protected static function set_static_var( $name, $value ) {
		$class = new \ReflectionClass( 'UniFi_Client_Alias_Sync\TestSyncer' );
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