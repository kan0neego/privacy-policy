<?php
/**
 * Plugin Name: Privacy Policy
 * Description: Cookie consent storage, REST API and native settings.
 * Version:     1.0.0
 * Author:      kan0ne
 * Text Domain: privacy-policy
 * Requires PHP: 8.1
 * Requires at least: 6.0
 *
 * @package CookieConsent
 */

defined( 'ABSPATH' ) || exit;

define( 'PRIVACY_POLICY_VERSION', '1.0.0' );
define( 'PRIVACY_POLICY_FILE', __FILE__ );
define( 'PRIVACY_POLICY_DIR', plugin_dir_path( __FILE__ ) );
define( 'PRIVACY_POLICY_COOKIE_NAME', 'cookie_consent_token' );
define( 'PRIVACY_POLICY_REST_NAMESPACE', 'cookie-consent/v1' );

require_once PRIVACY_POLICY_DIR . 'includes/autoload.php';

use CookieConsent\ConsentActivator;
use CookieConsent\Plugin;

register_activation_hook( PRIVACY_POLICY_FILE, array( ConsentActivator::class, 'install' ) );
register_deactivation_hook( PRIVACY_POLICY_FILE, array( ConsentActivator::class, 'deactivate' ) );

Plugin::instance()->boot();

/**
 * Returns merged plugin settings.
 *
 * @return array{policy_version: string, cookie_lifetime_days: int, enable_consent_logging: bool}
 */
function privacy_policy_get_settings(): array {
	return CookieConsent\Settings::get();
}

/**
 * Whether the visitor already has a consent token cookie.
 */
function privacy_policy_has_token(): bool {
	return ! empty( $_COOKIE[ PRIVACY_POLICY_COOKIE_NAME ] );
}

/**
 * Whether the current visitor granted a consent category.
 */
function privacy_policy_has_category( string $category ): bool {
	return Plugin::instance()->has_category( $category );
}

/**
 * REST URL of the consent endpoint.
 */
function privacy_policy_rest_url(): string {
	return rest_url( PRIVACY_POLICY_REST_NAMESPACE . '/consent' );
}
