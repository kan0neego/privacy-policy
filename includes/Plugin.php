<?php

namespace CookieConsent;

use CookieConsent\Admin\SettingsPage;
use CookieConsent\Logger\ConsentLogger;
use CookieConsent\Repository\WPConsentRepository;
use CookieConsent\REST\CookieConsentController;
use CookieConsent\Service\CookieConsentService;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin loader: wires hooks, REST and admin UI.
 */
final class Plugin {

	private static ?self $instance = null;

	private CookieConsentService $service;

	private CookieConsentController $controller;

	private function __construct() {
		$repository        = new WPConsentRepository();
		$logger            = ConsentLogger::get_instance();
		$this->service     = new CookieConsentService( $repository, $logger );
		$this->controller  = new CookieConsentController( $this->service );
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		add_action( 'plugins_loaded', array( ConsentActivator::class, 'maybe_upgrade' ) );
		add_filter( 'rest_authentication_errors', array( $this, 'skip_rest_nonce' ), 101 );
		add_action( 'rest_api_init', array( $this, 'register_rest' ) );

		if ( is_admin() ) {
			$settings_page = new SettingsPage();
			add_action( 'admin_menu', array( $settings_page, 'register_menu' ) );
			add_action( 'admin_init', array( $settings_page, 'register_settings' ) );
		}
	}

	public function register_rest(): void {
		$this->controller->register_routes();
	}

	/**
	 * Public consent routes do not use the core cookie nonce.
	 *
	 * @param mixed $result
	 * @return mixed
	 */
	public function skip_rest_nonce( $result ) {
		if ( ! is_wp_error( $result ) || 'rest_cookie_invalid_nonce' !== $result->get_error_code() ) {
			return $result;
		}

		$route = '';
		if ( isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			$route = (string) $GLOBALS['wp']->query_vars['rest_route'];
		}

		$prefix = '/' . PRIVACY_POLICY_REST_NAMESPACE;
		if ( 0 === strpos( $route, $prefix ) ) {
			return true;
		}

		return $result;
	}

	public function service(): CookieConsentService {
		return $this->service;
	}

	/**
	 * Looks up current visitor consent by HTTP-only token.
	 *
	 * @return array<string, mixed>|null
	 */
	public function current_consent(): ?array {
		if ( empty( $_COOKIE[ PRIVACY_POLICY_COOKIE_NAME ] ) ) {
			return null;
		}

		$token = sanitize_text_field( wp_unslash( $_COOKIE[ PRIVACY_POLICY_COOKIE_NAME ] ) );

		return $this->service->get_consent_by_token( $token );
	}

	public function has_category( string $category ): bool {
		$item = $this->current_consent();

		if ( null === $item || empty( $item['consents'] ) || ! is_array( $item['consents'] ) ) {
			return false;
		}

		return ! empty( $item['consents'][ $category ] );
	}
}
