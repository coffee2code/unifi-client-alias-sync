<?php
/**
 * Class that extends Syncer
 *
 * Copyright (c) 2018 by Scott Reilly (aka coffee2code)
 *
 * @package UniFi_Client_Alias_Sync
 * @author  Scott Reilly
 */

namespace UniFi_Client_Alias_Sync;

class TestSyncer extends Syncer {

	/**
	 * The GLOBALS array key name for array for in-memory storage of setting-value
	 * pairs.
	 *
	 * @access private
	 * @var string
	 */
	private static $globals_key = 'TEST_UNIFI_ALIAS_SYNC';

	/**
	 * Clears the value for a setting (or all settings if a specific one wasn't
	 * specified).
	 *
	 * @access public
	 *
	 * @param string $config The name of the setting to clear. If not provided or
	 *                       an empty string, then all settings will be cleared.
	 */
	public function clear_config( $config = '' ) {
		if ( $config ) {
			unset( $GLOBALS[ self::$globals_key ][ $config ] );
		} else {
			unset( $GLOBALS[ self::$globals_key ] );
		}
	}

	/**
	 * Returns the value for the specified setting.
	 *
	 * If the config option wasn't explicitly defined, return the default value if
	 * that has been defined.
	 *
	 * @access public
	 *
	 * @param string $config_name The setting name.
	 * @return mixed
	 */
	public function get_config( $config_name ) {
		return $GLOBALS[ self::$globals_key ][ $config_name ] ?? self::OPTIONAL_CONFIG[ $config_name ] ?? null;
	}

	/**
	 * Set the value for the specified setting.
	 *
	 * @access public
	 *
	 * @param string $config_name The setting name.
	 * @param mixed  $value       The value for the setting.
	 * @return mixed The value for the setting.
	 */
	public function set_config( $config_name, $value ) {
		return $GLOBALS[ self::$globals_key ][ $config_name ] = $value;
	}

}
