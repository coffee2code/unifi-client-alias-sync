<?php
/**
 * The configuration for the UniFi Controller Client Alias Sync script.
 *
 * NOTE: DO NOT EDIT config-sample.php. Instead, copy it to config.php and
 * customize that new file.
 *
 * @package UniFi_Client_Alias_Sync
 */


/********* REQUIRED SETTINGS *********/


/**
 * Fully qualified URL for the controller.
 *
 * Must include protocol ("https://") and port number (":8443" or ":443").
 * Example: https://example.com:8443
 *
 * @var string
 */
define( 'UNIFI_ALIAS_SYNC_CONTROLLER', '' );


/**
 * Username for the admin account for the controller.
 *
 * @var string
 */
define( 'UNIFI_ALIAS_SYNC_USER',       '' );


/**
 * Password for the admin account for the controller.
 *
 * @var string
 */
define( 'UNIFI_ALIAS_SYNC_PASSWORD',   '' );


/********* END OF REQUIRED SETTINGS *********/


/**
 * Boolean flag indicating if SSL connection to the controller should be
 * verified.
 *
 * This should be set to true unless you are on a secure local network and
 * trust non-SSL connections to the controller.
 *
 * @var bool
 */
define( 'UNIFI_ALIAS_SYNC_VERIFY_SSL', true );


/**
 * Boolean flag indicating if the script should only perform a dry run.
 *
 * A dry run will function in every way like a live run except that the final
 * step of actually syncing an alias to a client entry will not be performed.
 * This is especially useful for viewing the status messages during a dry run to
 * ensure everything operates as expected.
 *
 * @var bool
 */
define( 'UNIFI_ALIAS_SYNC_DRY_RUN',    true );


/**
 * Boolean flag indicating if debug mode should be enabled.
 *
 * Currently debug mode only sets the client API into debug mode, allowing
 * debugging of the underlying API but not yet the sync script itself.
 *
 * @var bool
 */
define( 'UNIFI_ALIAS_SYNC_DEBUG',      false );


/**
 * List of aliases to use for matching unaliased clients.
 *
 * Takes precedence over all sites in terms of defining the alias for matching
 * unaliased clients.
 *
 * An associative array of MAC addresses and their associated aliases.
 *
 * @var array
 */
define( 'UNIFI_ALIAS_SYNC_ALIASES',    [
	// "MAC address" => "Its Alias",
] );


/**
 * List of site names to exclude from consideration.
 *
 * Sites listed will not be used to find client aliases, nor will their clients
 * receive any aliases.
 *
 * Any sites listed here will be excluded, superceding their inclusion in any
 * other setting (such as UNIFI_ALIAS_SYNC_PRIORITIZED_SITES).
 *
 * @var array
 */
define( 'UNIFI_ALIAS_SYNC_EXCLUDE_SITES', [] );

/**
 * Boolean flag indicating if aliases can be overwritten.
 *
 * If enabled, client aliases defined at a higher priority will potentially
 * overwrite existing client aliases defined on lower priority sites.
 *
 * @var bool
 */
define( 'UNIFI_ALIAS_SYNC_ALLOW_OVERWRITES', false );


/**
 * Explicitly prioritized list of sites.
 *
 * Array of site names. The order of sites is important in determining where a
 * client alias is first obtained for syncing to other sites. By default, the
 * site with the name 'default' is given highest priority, then all remaining
 * sites are alphabetically listed after by name. This list explicitly places
 * the listed sites at the beginning of the list in the order provided.
 *
 * Sites that don't exist are ignored. Sites not listed will abide by the
 * default prioritization.
 *
 * @var array
 */
define( 'UNIFI_ALIAS_SYNC_PRIORITIZED_SITES', [] );
