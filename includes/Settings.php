<?php

namespace CookieConsent;

defined( 'ABSPATH' ) || exit;

/**
 * Options API wrapper for plugin settings.
 */
final class Settings {

	public const OPTION_KEY  = 'privacy_policy_settings';
	public const OPTION_GROUP = 'privacy_policy';

	public const MIN_LIFETIME_DAYS = 1;
	public const MAX_LIFETIME_DAYS = 400;

	/**
	 * @return array{policy_version: string, cookie_lifetime_days: int, enable_consent_logging: bool}
	 */
	public static function defaults(): array {
		return array(
			'policy_version'          => '1.0',
			'cookie_lifetime_days'    => 365,
			'enable_consent_logging'  => true,
		);
	}

	/**
	 * @return array{policy_version: string, cookie_lifetime_days: int, enable_consent_logging: bool}
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return self::sanitize( array_merge( self::defaults(), $stored ) );
	}

	public static function policy_version(): string {
		return self::get()['policy_version'];
	}

	public static function cookie_lifetime_seconds(): int {
		return self::get()['cookie_lifetime_days'] * DAY_IN_SECONDS;
	}

	public static function logging_enabled(): bool {
		return (bool) self::get()['enable_consent_logging'];
	}

	/**
	 * @param mixed $input
	 * @return array{policy_version: string, cookie_lifetime_days: int, enable_consent_logging: bool}
	 */
	public static function sanitize( $input ): array {
		$defaults = self::defaults();

		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		$version = isset( $input['policy_version'] ) ? sanitize_text_field( (string) $input['policy_version'] ) : $defaults['policy_version'];
		if ( '' === $version ) {
			$version = $defaults['policy_version'];
		}

		$days = isset( $input['cookie_lifetime_days'] ) ? absint( $input['cookie_lifetime_days'] ) : $defaults['cookie_lifetime_days'];
		$days = max( self::MIN_LIFETIME_DAYS, min( self::MAX_LIFETIME_DAYS, $days ) );

		$logging = ! empty( $input['enable_consent_logging'] );

		return array(
			'policy_version'         => $version,
			'cookie_lifetime_days'   => $days,
			'enable_consent_logging' => $logging,
		);
	}
}
