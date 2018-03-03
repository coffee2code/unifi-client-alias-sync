<?php
/**
 * PHPUnit bootstrap file
 *
 * @package UniFi_Client_Alias_Sync
 */

ini_set( 'display_errors', 'on' );
error_reporting( E_ALL );

require dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'unifi-client-alias-sync.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'class-test-base.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'class-test-syncer.php';
