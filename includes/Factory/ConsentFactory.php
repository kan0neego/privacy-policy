<?php

namespace CookieConsent\Factory;

use DateTimeImmutable;
use DateTimeInterface;
use CookieConsent\Domain\Consent;
use CookieConsent\Domain\ConsentCategory;

defined( 'ABSPATH' ) || exit;

/**
 * Cоздания Consent.
 */
final class ConsentFactory {

	/**
	 * Создаёт Consent из массива сырых данных.
	 *
	 * @param array<string, mixed> $data
	 * @return Consent
	 */
	public static function from_array( array $data ): Consent {
		return new Consent(
			ConsentCategory::normalize( $data['consents'] ?? array() ),
			self::to_string( $data['policy_version'] ?? '' ),
			self::to_datetime( $data['consent_timestamp'] ?? null )
		);
	}

	/**
	 * Приводит произвольное значение к строке.
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

	/**
	 * Разбирает timestamp из строки или числа, возвращает DateTimeInterface.
	 * При невозможности разобрать — возвращает "сейчас".
	 *
	 * @param mixed $value
	 * @return DateTimeInterface
	 */
	private static function to_datetime( $value ): DateTimeInterface {
		if ( $value instanceof DateTimeInterface ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			$timestamp = new DateTimeImmutable( '@' . (int) $value );
			return $timestamp->setTimezone( new \DateTimeZone( 'UTC' ) );
		}

		if ( is_string( $value ) && $value !== '' ) {
			try {
				return new DateTimeImmutable( $value );
			} catch ( \Exception $e ) {
				// fallback ниже
			}
		}

		return new DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
	}
}
