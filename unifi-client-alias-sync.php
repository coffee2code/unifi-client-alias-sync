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
	 * @access private
	 */
	private static $sites;

	/**
	 * Memoized storage for clients per site.
	 *
	 * @var array
	 * @access private
	 */
	private static $clients;

	/**
	 * Memoized storage for client aliases by site.
	 *
	 * @var array
	 * @access private
	 */
	private static $client_aliases;

	protected static $instance;

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
	}

	/**
	 * Performs initialization checks and actions.
	 *
	 * @access private
	 */
	protected function init() {
		$this->verify_environment();

		require self::CONFIG_FILE;

		$this->verify_config();

		$this->status( 'Environment and config file have been verified.' );

		if ( $this->get_config( 'UNIFI_ALIAS_SYNC_DRY_RUN' ) ) {
			$this->status( "UNIFI_ALIAS_SYNC_DRY_RUN mode enabled; aliases won't actually get synchronized." );
		}

		// Check for controller URL.
		$controller_url = rtrim( $this->get_config( 'UNIFI_ALIAS_SYNC_CONTROLLER' ), '/' );

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

	/**
	 * Determines if debug mode is enabled.
	 *
	 * @access private
	 *
	 * @return bool True if debug is enabled, false otherwise.
	 */
	protected function is_debug() {
		return (bool) $this->get_config( 'UNIFI_ALIAS_SYNC_DEBUG' );
	}

	/**
	 * Verifies that the running environment is sufficient for the script to run
	 * and terminates the script with an error message if not.
	 *
	 * Checks that:
	 * - The config file exists
	 * - The PHP directive 'allow_url_fopen' is enabled.
	 *
	 * @access private
	 */
	protected function verify_environment() {
		if ( ! file_exists( self::CONFIG_FILE ) ) {
			$this->bail( "Error: Unable to locate config file: {self::CONFIG_FILE}\nCopy config-sample.php to that filename and customize." );
		}

		if ( ! ini_get( 'allow_url_fopen' ) ) {
			$this->bail( "Error: The PHP directive 'allow_url_fopen' is not enabled on this system." );
		}
	}

	/**
	 * Verifies that required constants are defined in config file and that
	 * optional constants get defined with default values if they aren't
	 * defined.
	 *
	 * @access private
	 */
	protected function verify_config() {
		// Required constants and their descriptions.
		$required_constants = array(
			'UNIFI_ALIAS_SYNC_CONTROLLER'        => 'URL of the UniFi controller, including full protocol and port number.',
			'UNIFI_ALIAS_SYNC_USER'              => 'Username of admin user.',
			'UNIFI_ALIAS_SYNC_PASSWORD'          => 'Password for admin user.',
		);

		// Optional constants and their default values.
		$optional_constants = array(
			'UNIFI_ALIAS_SYNC_VERIFY_SSL'        => true,
			'UNIFI_ALIAS_SYNC_DRY_RUN'           => true,
			'UNIFI_ALIAS_SYNC_DEBUG'             => false,
			'UNIFI_ALIAS_SYNC_ALIASES'           => [],
			'UNIFI_ALIAS_SYNC_PRIORITIZED_SITES' => [],
		);

		// Flag for determining if an error was encountered.
		$bail = false;

		// Check that required constants are defined. Don't bail immediately though,
		// so multiple missing constants can be reported to user at once.
		foreach ( $required_constants as $constant => $description ) {
			if ( is_null( $this->get_config( $constant ) ) ) {
				$this->status( "Error: Required constant {$constant} was not defined: {$description}" );
				$bail = true;
			}
		}

		// Check that full URL for controller was supplied.
		$controller = $this->get_config( 'UNIFI_ALIAS_SYNC_CONTROLLER' );
		if ( ! $controller ) {
			if ( 0 !== strpos( $controller, 'https://' ) ) {
				$this->status( "Error: The URL defined in UNIFI_ALIAS_SYNC_CONTROLLER does not include the protocol 'https://'." );
				$bail = true;
			}
			if ( ! preg_match( '~:[0-9]+/?$~', $controller ) ) {
				$this->status( "Error: The URL defined in UNIFI_ALIAS_SYNC_CONTROLLER does not include the port number. This is usually 8443 or 443." );
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

		// Truly bail if an error was encountered.
		if ( $bail ) {
			$this->bail( 'Terminating script for invalid config file.' );
		}

		// For optional constants, define them with default values if not defined.
		foreach ( $optional_constants as $constant => $default ) {
			if ( is_null( $this->get_config( $constant ) ) ) {
				$this->set_config( $constant, $default );
			}
		}
	}

	/**
	 * Returns list of sites for the controller.
	 *
	 * @access protected
	 *
	 * @return array Associative array of sites with site names as keys and site objects as values.
	 */
	protected function get_sites() {
		// Return value if memoized.
		if ( self::$sites ) {
			return self::$sites;
		}

		$sites = [];

		$sites_resp = self::$unifi_connection->list_sites();

		foreach ( (array) $sites_resp as $site ) {
			if ( ! empty( $site->name ) ) {
				$sites[ $site->name ] = $site;
			}
		}

		return self::$sites = $this->prioritize_sites( $sites );
	}

	/**
	 * Prioritizes a list of sites by precendence.
	 *
	 * @access private
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
		if ( empty( self::$clients[ $site_name ] ) ) {
			self::$unifi_connection->set_site( $site_name );
			self::$clients[ $site_name ] = self::$unifi_connection->stat_allusers();
		}

		return self::$clients[ $site_name ];
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

			$this->status( "\tSite {$site->name} has " . count( $clients ) . ' clients, ' . count( $aliased_clients ) . ' of which are aliased.' );
			foreach ( $aliased_clients as $ac ) {
				$this->status( "\t\t'{$ac->mac}' => '{$ac->name}'" );
			}
		}

		return self::$client_aliases = $client_aliases;
	}

	/**
	 * Returns the client aliases applicable to the given site.
	 *
	 * @access private
	 *
	 * @param string $site_name Name of the site.
	 * @return array
	 */
	protected function get_client_aliases_for_site( $site_name ) {
		$macs = [];

		// Get a list of aliased clients per site.
		$client_aliases = $this->get_aliased_clients();

		// Aliases defined via constant take precedence and apply to all sites.
		foreach ( $this->get_config( 'UNIFI_ALIAS_SYNC_ALIASES' ) as $mac => $alias ) {
			$macs[ $mac ] = $alias;
		}

		// Get a list of all aliases that apply to the site.
		foreach ( $client_aliases as $alias_site_name => $aliases ) {

			// Skip site's own list of aliases.
			if ( $alias_site_name === $site_name ) {
				continue;
			}

			// Store the MAC address and alias mapping.
			foreach ( $aliases as $alias ) {
				// Sites are ordered by precedence, so don't override existing alias mapping.
				if ( empty( $macs[ $alias->mac ] ) ) {
					$macs[ $alias->mac ] = $alias->name;
				}
			}

		}

		return $macs;
	}

	/**
	 * Syncs client aliases across all sites.
	 *
	 * @access private
	 */
	protected function sync_aliases() {
		// Get sites.
		$sites = $this->get_sites();

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
				self::$unifi_connection->set_site( $site->name );

				// If there is an alias for the client
				if ( isset( $macs[ $client->mac ] ) ) {

					// And if the client doesn't already have an alias, assign alias.
					if ( empty( $client->name ) ) {
						$assigned_alias++;

						// Actually set the client alias unless doing a dry run.
						if ( $this->get_config( 'UNIFI_ALIAS_SYNC_DRY_RUN' ) ) {
							$this->status( "\tWould have set alias for {$client->mac} to \"{$macs[ $client->mac ]}\"." );
						} else {
							$result = self::$unifi_connection->set_sta_name( $client->_id, $macs[ $client->mac ] );

							if ( ! $result ) {
								$this->status( sprintf(
									"\tWarning: Unable to set alias for %s to \"%s\" (%s).",
									$client->mac,
									$macs[ $client->mac ],
									self::$unifi_connection->get_last_error_message()
								 ) );
								$assigned_alias--;
							} else {
								$this->status( "\tSetting alias for {$client->mac} to \"{$macs[ $client->mac ]}\"." );
							}
						}

					// Else an alias cannot be overridden.
					} else {

						// Report if client already has the given alias.
						if ( $client->name === $macs[ $client->mac ] ) {
							$this->status( "\tClient {$client->mac} already has the alias \"{$client->name}\"." );
						// Else report client already has an alias that isn't being overridden.
						} else {
							$this->status( "\tClient {$client->mac} already aliased as \"{$client->name}\" (thus not getting aliased as \"{$macs[ $client->mac ]}\")." );
						}

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
	 * @access private
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
	 * Outputs a message and exits.
	 *
	 * @access private
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
			$message .= "\n";
		}

		die( $message );
	}

	/**
	 * Gets the value of a config option.
	 *
	 * @access protected
	 *
	 * @param  string @config_name Name of the config option.
	 * @return mixed
	 */
	protected function get_config( $config_name ) {
		return defined( $config_name ) ? constant( $config_name ) : null;
	}

	/**
	 * Sets the value of a config option.
	 *
	 * Since config options are implemented as constants, a given config option
	 * can only be set once.
	 *
	 * @access protected
	 *
	 * @param  string $config_name Name of the config option.
	 * @param  mixed  $value       Value for the config option.
	 * @return bool   True if the config option was assigned a value, else false.
	 */
	protected function set_config( $config_name, $value ) {
		if ( defined( $config_name ) ) {
			return false;
		}

		define( $config_name, $value );
		return true;
	}
}

Syncer::get_instance()->sync();
