<?php

namespace CookieConsent\Factory;

use CookieConsent\Domain\Client;

defined( 'ABSPATH' ) || exit;

/**
 * Создание Client
 */
final class ClientFactory {

	/**
	 * Создаёт Client из массива сырых данных.
	 *
	 * @param array<string, mixed> $data
	 * @return Client
	 */
	public static function from_array( array $data ): Client {
		$email = sanitize_email( self::to_string( $data['email'] ?? '' ) );

		return new Client(
			self::to_string( $data['cookie_token'] ?? '' ),
			sanitize_text_field( self::to_string( $data['name'] ?? '' ) ),
			is_email( $email ) ? $email : '',
			sanitize_text_field( self::to_string( $data['phone'] ?? '' ) ),
			self::to_string( $data['ip_address'] ?? '' )
		);
	}

	/**
	 * Приводит произвольное значение к строке без возможности инъекции.
	 *
	 * @param mixed $value
	 * @return string
	 */
	private static function to_string( $value ): string {
		if ( is_string( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return (string) $value;
		}

		return '';
	}
}
