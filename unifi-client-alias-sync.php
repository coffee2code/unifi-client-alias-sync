<?php

namespace UniFi_Client_Alias_Sync;

/**
 * A PHP script to synchronize client aliases between all sites managed by a UniFi Controller.
 *
 * See README.md for usage instructions.
 *
 * Copyright (c) 2018 by Scott Reilly (aka coffee2code)
 *
 * @package UniFi_Client_Alias_Sync
 * @author  Scott Reilly
 * @version 1.0
 */

// UniFi API client library.
require_once 'vendor/unifi-api-client.php';

/**
 * Class for syncing client aliases across a UniFi controllers sites.
 */
class Syncer {

	/**
	 * Full path to config file.
	 *
	 * @var string
	 */
	const CONFIG_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

	/**
	 * Required configuration options and their descriptions.
	 *
	 * @var array
	 */
	const REQUIRED_CONFIG = [
		'UNIFI_ALIAS_SYNC_CONTROLLER'        => 'Domain (or fully qualified URL) of the UniFi controller.',
		'UNIFI_ALIAS_SYNC_USER'              => 'Username of admin user.',
		'UNIFI_ALIAS_SYNC_PASSWORD'          => 'Password for admin user.',
	];

	/**
	 * Optional configuration options and their default values.
	 *
	 * @var array
	 */
	const OPTIONAL_CONFIG = [
		'UNIFI_ALIAS_SYNC_PORT'              => 8443,
		'UNIFI_ALIAS_SYNC_VERIFY_SSL'        => true,
		'UNIFI_ALIAS_SYNC_DRY_RUN'           => true,
		'UNIFI_ALIAS_SYNC_DEBUG'             => false,
		'UNIFI_ALIAS_SYNC_ALIASES'           => [],
		'UNIFI_ALIAS_SYNC_ALLOW_OVERWRITES'  => false,
		'UNIFI_ALIAS_SYNC_EXCLUDE_SITES'     => [],
		'UNIFI_ALIAS_SYNC_PRIORITIZED_SITES' => [],
		// Not for general use.
		'UNIFI_ALIAS_SYNC_TESTING'           => false,
	];


	/**
	 * The UniFi Controller client object.
	 *
	 * @var array
	 * @access private
	 */
	private static $unifi_connection;

	/**
	 * Memoized storage for sites.
	 *
	 * @var array
	 * @access protected
	 */
	protected static $sites;

	/**
	 * Memoized storage for clients per site.
	 *
	 * @var array
	 * @access protected
	 */
	protected static $clients;

	/**
	 * Memoized storage for client aliases by site.
	 *
	 * @var array
	 * @access protected
	 */
	protected static $client_aliases;

	/**
	 * Memoized storage for configuration settings.
	 *
	 * @var array
	 * @access protected
	 */
	protected static $config = [];

	/**
	 * The singleton instantiation of the class.
	 *
	 * @access protected
	 * @var Syncer
	 */
	protected static $instance;

	/**
	 * Returns the singleton instance of this class, creating one if necessary.
	 *
	 * @access public
	 *
	 * @return Syncer
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	/**
	 * Syncs client aliases across a controller's sites.
	 *
	 * @access public
	 */
	public function sync() {

	try {

		// Perform initialization.
		$this->init();

		// Get all of the sites on the controller.
		$sites = $this->get_sites();

		// Exceptions are made if aliases are defined via config.
		$has_config_aliases = (bool) count( $this->get_config( 'UNIFI_ALIAS_SYNC_ALIASES' ) );

		// Bail if there are less than two sites since that is the minimum needed in order to be able to sync client aliases across sites.
		switch ( count( $sites ) ) {
			case 0:
				$this->bail( "Error: No sites found." );
			case 1:
				// Bail unless there are aliases defined via config.
				if ( ! $has_config_aliases ) {
					$this->bail( "Notice: Only one site found so there is no need to sync aliases across any other sites." );
				}
		}

		$this->status( 'Sites found: ' . count( $sites ) );

		// Report on client aliases defined via config.
		if ( $has_config_aliases ) {
			$this->status( "\tUNIFI_ALIAS_SYNC_ALIASES has " . count( $this->get_config( 'UNIFI_ALIAS_SYNC_ALIASES' ) ) . ' client aliases defined.' );

			foreach ( $this->get_config( 'UNIFI_ALIAS_SYNC_ALIASES' ) as $mac => $alias ) {
				$this->status( "\t\t'{$mac}' => '{$alias}'" );
			}
		}

		// Get a list of aliased clients per site.
		$client_aliases = $this->get_aliased_clients();

		// Bail if there are no aliased clients on any site and no aliases defined
		// via config since there is nothing to sync.
		if ( ! $client_aliases && ! $has_config_aliases ) {
			$this->bail( "Notice: There are no clients with an alias on any site." );
		}

		// Sync client aliases across sites.
		$this->sync_aliases();

		$this->status( 'Done.' );

	} catch ( \Exception $e ) {
		// Do nothing; error message has already been displayed and script is ending.
	} // end try/catch

	}

	/**
	 * Performs initialization checks and actions.
	 *
	 * @access protected
	 */
	protected function init() {
		$this->verify_environment();

		if ( ! $this->is_testing() ) {
			require self::CONFIG_FILE;
		}

		$this->verify_config();

		$this->status( 'Environment and config file have been verified.' );

		if ( $this->get_config( 'UNIFI_ALIAS_SYNC_DRY_RUN' ) ) {
			$this->status( "UNIFI_ALIAS_SYNC_DRY_RUN mode enabled; aliases won't actually get synchronized." );
		}

		// Check for controller URL.
		$controller_url = $this->get_controller_url();

		if ( ! $this->is_testing() ) {
			self::$unifi_connection = new \UniFi_API\Client(
				$this->get_config( 'UNIFI_ALIAS_SYNC_USER' ),
				$this->get_config( 'UNIFI_ALIAS_SYNC_PASSWORD' ),
				$controller_url,
				'default',
				'',
				$this->get_config( 'UNIFI_ALIAS_SYNC_VERIFY_SSL' )
			);

			if ( $this->is_debug() ) {
				self::$unifi_connection->set_debug( true );
			}

			self::$unifi_connection->login();
		}
	}

	/**
	 * Returns the fully qualified URL for the UniFi controller.
	 *
	 * @access protected
	 *
	 * @return string
	 */
	protected function get_controller_url() {
		$controller = rtrim( $this->get_config( 'UNIFI_ALIAS_SYNC_CONTROLLER' ), '/' );

		// If protocol was omitted, add it.
		if ( 0 !== strpos( $controller, 'https://' ) ) {
			$controller = 'https://' . $controller;
		}

		// If controller URL includes port number, that takes precedence over
		// UNIFI_ALIAS_SYNC_PORT.
		if ( preg_match( '~(.+):([0-9]+)$~', $controller, $matches ) ) {
			$controller = $matches[1];
			$port = $matches[2];
		} else {
			$port = $this->get_config( 'UNIFI_ALIAS_SYNC_PORT' );
		}

		return $controller . ':' . $port;
	}

	/**
	 * Determines if debug mode is enabled.
	 *
	 * @access protected
	 *
	 * @return bool True if debug is enabled, false otherwise.
	 */
	protected function is_debug() {
		return (bool) $this->get_config( 'UNIFI_ALIAS_SYNC_DEBUG' );
	}

	/**
	 * Determines if testing mode is enabled.
	 *
	 * @access protected
	 *
	 * @return bool True if testing is enabled, false otherwise.
	 */
	protected function is_testing() {
		return (bool) $this->get_config( 'UNIFI_ALIAS_SYNC_TESTING' );
	}

	/**
	 * Verifies that the running environment is sufficient for the script to run
	 * and terminates the script with an error message if not.
	 *
	 * Checks that:
	 * - The config file exists
	 * - The PHP directive 'allow_url_fopen' is enabled.
	 *
	 * @access protected
	 */
	protected function verify_environment() {
		// Bail if the config file doesn't exist (unless unit testing).
		if ( ! $this->is_testing() && ! file_exists( self::CONFIG_FILE ) ) {
			$this->bail( "Error: Unable to locate config file: {self::CONFIG_FILE}\nCopy config-sample.php to that filename and customize." );
		}

		// Bail if 'allow_url_fopen' is not enabled.
		if ( ! ini_get( 'allow_url_fopen' ) ) {
			$this->bail( "Error: The PHP directive 'allow_url_fopen' is not enabled on this system." );
		}
	}

	/**
	 * Verifies that required constants are defined in config file and that
	 * optional constants get defined with default values if they aren't
	 * defined.
	 *
	 * @access protected
	 */
	protected function verify_config() {
		// Flag for determining if an error was encountered.
		$bail = false;

		// Check that required constants are defined. Don't bail immediately though,
		// so multiple missing constants can be reported to user at once.
		foreach ( self::REQUIRED_CONFIG as $constant => $description ) {
			$value = $this->get_config( $constant );
			// Required settings cannot be null or an empty string.
			if ( is_null( $value ) || '' === $value ) {
				$this->status( "Error: Required constant {$constant} was not defined: {$description}" );
				$bail = true;
			}
		}

		// Check that aliases are defined properly.
		$aliases = $this->get_config( 'UNIFI_ALIAS_SYNC_ALIASES' );
		if ( ! is_null( $aliases ) ) {
			if ( ! is_array( $aliases ) ) {
				$this->status( "Error: Invalid format for UNIFI_ALIAS_SYNC_ALIASES: {$aliases}" );
				$bail = true;
			} else {
				foreach ( $aliases as $mac => $alias ) {
					// Check MAC address.
					if ( ! preg_match( '/^(?:[[:xdigit:]]{2}([-:]))(?:[[:xdigit:]]{2}\1){4}[[:xdigit:]]{2}$/', $mac ) ) {
						$this->status( "Error: Invalid MAC address supplied in UNIFI_ALIAS_SYNC_ALIASES: {$mac}" );
						$bail = true;
					}
				}
			}
		}

		// Check that UNIFI_ALIAS_SYNC_PORT, if present, is a non-zero integer.
		$port = $this->get_config( 'UNIFI_ALIAS_SYNC_PORT' );
		if ( ! is_null( $port ) && ( ! is_numeric( $port ) || ! $port ) ) {
			$this->status( "Error: Invalid format for UNIFI_ALIAS_SYNC_PORT (must be integer): {$port}" );
			$bail = true;
		}

		// Check that UNIFI_ALIAS_SYNC_ALLOW_OVERWRITES, if present, is a boolean.
		$allow_overwrites = $this->get_config( 'UNIFI_ALIAS_SYNC_ALLOW_OVERWRITES' );
		if ( ! is_null( $allow_overwrites ) && ! is_bool( $allow_overwrites ) ) {
			$this->status( "Error: Invalid format for UNIFI_ALIAS_SYNC_ALLOW_OVERWRITES (must be boolean): {$allow_overwrites}" );
			$bail = true;
		}

		// Check that array settings, if present, are arrays.
		$arrays = [ 'UNIFI_ALIAS_SYNC_EXCLUDE_SITES', 'UNIFI_ALIAS_SYNC_PRIORITIZED_SITES' ];
		foreach ( $arrays as $array ) {
			$value = $this->get_config( $array );
			if ( ! is_null( $value ) && ! is_array( $value ) ) {
				$this->status( "Error: Invalid format for {$array} (must be array): {$value}" );
				$bail = true;
			}
		}

		// Truly bail if an error was encountered.
		if ( $bail ) {
			$this->bail( 'Terminating script for invalid config file.' );
		}

	}

	/**
	 * Returns list of sites for the controller.
	 *
	 * @access protected
	 *
	 * @return array Associative array of sites with site names as keys and site
	 *               objects as values, ordered in descending order by priority.
	 */
	protected function get_sites() {
		if ( self::$sites ) {
			$sites = self::$sites;
		} elseif ( $this->is_testing() ) {
			$sites = [];
		} else {
			$sites = [];
			$sites_resp = self::$unifi_connection->list_sites();

			foreach ( (array) $sites_resp as $site ) {
				if ( ! empty( $site->name ) ) {
					$sites[ $site->name ] = $site;
				}
			}

			self::$sites = $sites;
		}

		// Exclude any excluded sites.
		$excluded_sites = $this->get_config( 'UNIFI_ALIAS_SYNC_EXCLUDE_SITES' );
		if ( $excluded_sites ) {
			foreach ( $excluded_sites as $site ) {
				unset( $sites[ $site ] );
			}
		}

		return $this->prioritize_sites( $sites );
	}

	/**
	 * Prioritizes a list of sites by precendence.
	 *
	 * @access protected
	 *
	 * @param  array $sites Associative array of sites with site names as keys and
	 *                      site objects as values.
	 * @return array
	 */
	protected function prioritize_sites( $sites ) {
		// Get explicitly prioritized sites.
		$priority_sites = [];
		foreach ( $this->get_config( 'UNIFI_ALIAS_SYNC_PRIORITIZED_SITES' ) as $site ) {
			if ( isset( $sites[ $site ] ) ) {
				$priority_sites[ $site ] = $sites[ $site ];
				// Remove priority site from regular consideration.
				unset( $sites[ $site ] );
			}
		}

		// The site named 'default', if present, should take precedence.
		$default_site = $sites[ 'default' ] ?? '';
		unset( $sites['default'] );

		// Sort remaining sites alphabetically by site name.
		ksort( $sites );

		// Give precedence to default site over alphabetically prioritized sites.
		if ( $default_site ) {
			$sites = array_merge( [ 'default' => $default_site ], $sites );
		}

		// Give overall precedence to explicitly prioritized sites.
		if ( $priority_sites ) {
			$sites = array_merge( $priority_sites, $sites );
		}

		return $sites;
	}

	/**
	 * Returns the clients for a given site.
	 *
	 * @access protected
	 *
	 * @param  string $site_name The name of the site.
	 * @return array Array of the site's clients.
	 */
	protected function get_clients( $site_name ) {
		// If not already memoized, make the request for the site's clients.
		if ( ! empty( self::$clients[ $site_name ] ) ) {
			$clients = self::$clients[ $site_name ];
		} elseif ( $this->is_testing() ) {
			$clients = [];
		} else {
			self::$unifi_connection->set_site( $site_name );
			$clients = self::$unifi_connection->stat_allusers();

			// Memoize full list of clients.
			self::$clients[ $site_name ] = $clients;
		}

		return $clients;
	}

	/**
	 * Returns the aliased clients for each site.
	 *
	 * @access protected
	 *
	 * @return array Associative array of site names and their respective arrays
	 *               of aliased clients.
	 */
	protected function get_aliased_clients() {
		// Return value if memoized.
		if ( self::$client_aliases ) {
			return self::$client_aliases;
		}

		$client_aliases = [];

		$sites = $this->get_sites();

		// For each site, get a list of all clients with an alias.
		foreach ( $sites as $site ) {
			$clients = $this->get_clients( $site->name );

			// The client alias, if defined, is stored as "name".
			$aliased_clients = array_filter( $clients, function( $client ) {
				return ! empty( $client->name );
			} );

			if ( $aliased_clients ) {
				$client_aliases[ $site->name ] = $aliased_clients;
			}

			$this->status( sprintf(
				"\tSite %s has %d clients, %d of which are aliased.",
				$site->name,
				count( $clients ),
				count( $aliased_clients )
			) );
			foreach ( $aliased_clients as $ac ) {
				$this->status( "\t\t\"{$ac->mac}\" => \"{$ac->name}\"" );
			}
		}

		return self::$client_aliases = $client_aliases;
	}

	/**
	 * Returns the client aliases applicable to the given site.
	 *
	 * @access protected
	 *
	 * @param string $site_name Name of the site.
	 * @return array
	 */
	protected function get_client_aliases_for_site( $site_name ) {
		$macs = [];

		// Bail if unknown site name.
		if ( ! array_key_exists( $site_name, $this->get_sites() ) ) {
			return $macs;
		}

		// Get a list of aliased clients per site.
		$client_aliases = $this->get_aliased_clients();

		// Get an associative array of site names and their aliases (as arrays).
		$site_names = array_keys( $client_aliases );
		$site_names_with_aliases = [];
		foreach ( $client_aliases as $alias_site_name => $aliases ) {
			$a = [];
			foreach ( $aliases as $alias ) {
				$a[ $alias->mac ] = $alias->name;
			}
			$site_names_with_aliases[ $alias_site_name ] = $a;
		}
		// Position relative to other sites for the current site.
		$site_pos = array_search( $site_name, $site_names );

		// Get a list of all aliases that apply to the site.
		foreach ( $client_aliases as $alias_site_name => $aliases ) {

			// Skip site's own list of aliases.
			if ( $alias_site_name === $site_name ) {
				continue;
			}

			// Store the MAC address and alias mapping.
			foreach ( $aliases as $alias ) {

				// Only accept alias for aliased client from higher priority sites.
				if ( $site_pos > array_search( $alias_site_name, $site_names ) ) {
					// ...but only if an alias hasn't already been found.
					if ( empty( $macs[ $alias->mac ] ) ) {
						$macs[ $alias->mac ] = $alias->name;
					}
				}
				// Else if it the site already has an aliases for this client, do nothing.
				elseif ( ! empty( $site_names_with_aliases[ $site_name ][ $alias->mac ] ) ) {
					// Do nothing
				}
				// Else if an alias hasn't already been found.
				elseif ( empty( $macs[ $alias->mac ] ) ) {
					$macs[ $alias->mac ] = $alias->name;
				}

			}

		}

		// Aliases defined via constant take precedence and apply to all sites.
		foreach ( $this->get_config( 'UNIFI_ALIAS_SYNC_ALIASES' ) as $mac => $alias ) {
			$macs[ $mac ] = $alias;
		}

		return $macs;
	}

	/**
	 * Syncs client aliases across all sites.
	 *
	 * @access protected
	 */
	protected function sync_aliases() {
		// Get sites.
		$sites = $this->get_sites();

		// Report on excluded sites.
		$excluded_sites = $this->get_config( 'UNIFI_ALIAS_SYNC_EXCLUDE_SITES' );
		if ( $excluded_sites ) {
			foreach ( $excluded_sites as $site ) {
				$this->status( "Excluding site {$site}" );
			}
		}

		// Iterate through all sites.
		foreach ( $sites as $site ) {
			$this->status( "About to assign client aliases to site {$site->name}..." );

			// The number of clients on the site that were assigned an alias.
			$assigned_alias = 0;

			// Get MAC address to alias mappings.
			$macs = $this->get_client_aliases_for_site( $site->name );

			// Get clients for the site being iterated.
			$clients = $this->get_clients( $site->name );
			foreach ( $clients as $client ) {

				// Set the current site.
				if ( self::$unifi_connection ) {
					self::$unifi_connection->set_site( $site->name );
				}

				// If there is an alias for the client
				if ( isset( $macs[ $client->mac ] ) ) {

					// If client already has the given alias.
					if ( ! empty( $client->name ) && $client->name === $macs[ $client->mac ] ) {
						$this->status( "\tClient {$client->mac} already has the alias \"{$client->name}\"." );
					}

					// Elseif if the client doesn't already have an alias or its name can be overwritten
					elseif ( empty( $client->name ) || $this->get_config( 'UNIFI_ALIAS_SYNC_ALLOW_OVERWRITES' ) ) {
						$assigned_alias++;

						// Actually set the client alias unless doing a dry run.
						if ( $this->get_config( 'UNIFI_ALIAS_SYNC_DRY_RUN' ) ) {
							if ( empty( $client->name ) ) {
								$this->status( "\tWould have set alias for {$client->mac} to \"{$macs[ $client->mac ]}\"." );
							} else {
								$this->status( sprintf(
									"\tWould have set alias for %s to \"%s\" (overwriting existing alias of \"%s\").",
									$client->mac,
									$macs[ $client->mac ],
									$client->name
								) );
							}
						} else {
							if ( $this->is_testing() ) {
								// When testing, pretend setting client alias is successful.
								$result = true;
							} elseif ( self::$unifi_connection ) {
								self::$unifi_connection->set_sta_name( $client->_id, $macs[ $client->mac ] );
							}

							if ( ! $result ) {
								$this->status( sprintf(
									"\tWarning: Unable to set alias for %s to \"%s\" (%s).",
									$client->mac,
									$macs[ $client->mac ],
									self::$unifi_connection ? self::$unifi_connection->get_last_error_message() : 'No connection to controller'
								 ) );
								$assigned_alias--;
							} elseif ( $this->get_config( 'UNIFI_ALIAS_SYNC_ALLOW_OVERWRITES' ) ) {
								$this->status( sprintf(
									"\tSetting alias for %s to \"%s\" (overwriting existing alias of \"%s\").",
									$client->mac,
									$macs[ $client->mac ],
									$client->name
								) );
							} else {
								$this->status( "\tSetting alias for {$client->mac} to \"{$macs[ $client->mac ]}\"." );
							}
						}

					// Else an alias cannot be overridden.
					} else {
						$this->status( sprintf(
							"\tClient %s already aliased as \"%s\" (thus not getting aliased as \"%s\").",
							$client->mac,
							$client->name,
							$macs[ $client->mac ]
						) );
					}
				}
			}

			if ( $assigned_alias ) {
				$this->status( "\tClients assigned an alias: {$assigned_alias}." );
			} else {
				$this->status( "\tNo clients assigned an alias." );
			}

		}
	}

	/**
	 * Outputs a status message.
	 *
	 * Auto-appends a newline to the message.
	 *
	 * @access protected
	 *
	 * @param string $message The message to output.
	 */
	protected function status( $message ) {
		if ( ! $this->get_config( 'UNIFI_ALIAS_SYNC_DISABLE_STATUS' ) ) {
			echo $message . "\n";
		}

		return $message;
	}

	/**
	 * Outputs a message and throws an exception.
	 *
	 * @access protected
	 *
	 * @param string $message The message to output. No need to append newline.
	 *                        Default is ''.
	 */
	protected function bail( $message = '' ) {
		// Terminate the UniFi controller connection.
		if ( self::$unifi_connection ) {
			self::$unifi_connection->logout();
		}

		// Append a newline if a message was supplied.
		if ( $message ) {
			$this->status( $message );
		}

		throw new \Exception( $message );
	}

	/**
	 * Returns the value of a config option.
	 *
	 * If the config option wasn't explicitly defined, return the default value if
	 * that has been defined.
	 *
	 * @access protected
	 *
	 * @param  string @config_name Name of the config option.
	 * @return mixed
	 */
	public function get_config( $config_name ) {
		// Return memoized value, if already set.
		if ( isset( self::$config[ $config_name ] ) ) {
			return self::$config[ $config_name ];
		}

		$value = defined( $config_name ) ? constant( $config_name ) : null;

		if ( is_null( $value ) ) {
			$value = self::OPTIONAL_CONFIG[ $config_name ] ?? null;
		}

		return $this->set_config( $config_name, $value );
	}

	/**
	 * Sets the value of a config option.
	 *
	 * @access protected
	 *
	 * @param  string $config_name Name of the config option.
	 * @param  mixed  $value       Value for the config option.
	 * @return mixed  The value for the config option.
	 */
	public function set_config( $config_name, $value ) {
		return self::$config[ $config_name ] = $value;
	}

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
			unset( self::$config[ $config ] );
		} else {
			self::$config = [];
		}
	}

}

// Immediately invoke the script when executed from the command line.
if ( isset( $argv ) && $argv[0] === basename( __FILE__ ) ) {
	Syncer::get_instance()->sync();
}
