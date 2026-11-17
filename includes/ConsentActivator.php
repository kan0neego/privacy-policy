<?php

namespace CookieConsent;

defined( 'ABSPATH' ) || exit;

final class ConsentActivator {

	public const TABLE_NAME = 'cookie_consents';

	private const DB_VERSION_KEY = 'cookie_consent_db_version';
	private const DB_VERSION     = '1.0';

	public static function install(): void {
		self::ensure_schema();
		add_option( Settings::OPTION_KEY, Settings::defaults() );
	}

	public static function deactivate(): void {
		// Consents and cookies stay in place.
	}

	public static function maybe_upgrade(): void {
		if ( get_option( self::DB_VERSION_KEY, '' ) === self::DB_VERSION ) {
			return;
		}

		self::ensure_schema();
	}

	public static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_NAME;
	}

	public static function schema_version(): string {
		return (string) get_option( self::DB_VERSION_KEY, '' );
	}

	public static function ensure_schema(): void {
		global $wpdb;

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			cookie_token VARCHAR(36) NOT NULL,
			name VARCHAR(255) NOT NULL DEFAULT '',
			email VARCHAR(255) NOT NULL DEFAULT '',
			phone VARCHAR(50) NOT NULL DEFAULT '',
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			consents LONGTEXT NOT NULL,
			policy_version VARCHAR(20) NOT NULL DEFAULT '',
			consent_timestamp DATETIME NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY uk_cookie_token (cookie_token),
			KEY idx_email (email),
			KEY idx_ip (ip_address)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$after = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( $after ) {
			update_option( self::DB_VERSION_KEY, self::DB_VERSION );
		}
	}

	public static function drop_schema(): void {
		global $wpdb;

		$table = self::table_name();
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		delete_option( self::DB_VERSION_KEY );
	}
}
