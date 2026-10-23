<?php
/**
 * Runs when the plugin is deleted from wp-admin.
 * Applications are NOT deleted automatically — you have record-keeping
 * obligations. Define LENDLY_PURGE_ON_UNINSTALL as true in wp-config.php
 * only if you really want the table dropped.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'lendly_settings' );

if ( defined( 'LENDLY_PURGE_ON_UNINSTALL' ) && LENDLY_PURGE_ON_UNINSTALL ) {
	global $wpdb;
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'lendly_applications' ); // phpcs:ignore
	delete_option( 'lendly_db_version' );
}

$role = get_role( 'administrator' );
if ( $role ) {
	$role->remove_cap( 'lendly_manage_applications' );
}
