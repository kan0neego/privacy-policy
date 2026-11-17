<?php
/**
 * Removes plugin options and the consents table on uninstall.
 *
 * @package CookieConsent
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'privacy_policy_settings' );
delete_option( 'cookie_consent_db_version' );

$table = $wpdb->prefix . 'cookie_consents';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
