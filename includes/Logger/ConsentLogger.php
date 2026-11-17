<?php

namespace CookieConsent\Logger;

use CookieConsent\Domain\Client;
use CookieConsent\Settings;

defined( 'ABSPATH' ) || exit;

final class ConsentLogger {

	private static ?self $instance = null;

	private function __construct() {
	}

	private function __clone() {
	}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param array<string, mixed> $context
	 */
	public function log( string $event, ?Client $client = null, array $context = array() ): void {
		if ( ! Settings::logging_enabled() ) {
			return;
		}

		$entry = array(
			'event'   => $event,
			'token'   => $client?->cookie_token ?? '',
			'time'    => current_time( 'mysql' ),
			'context' => $context,
		);

		error_log( '[cookie-consent] ' . wp_json_encode( $entry, JSON_UNESCAPED_UNICODE ) );
	}
}
