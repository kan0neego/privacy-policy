<?php

namespace CookieConsent\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Enum-like Value Object, описывающий допустимые категории согласия.
 *
 * Категории соответствуют требованиям cookie-баннеров:
 *  - NECESSARY — строго необходимые (не могут быть отключены пользователем).
 *  - ANALYTICS — аналитика (Яндекс.Метрика, GA и т.д.).
 *  - MARKETING — маркетинговые/рекламные cookies.
 */
final class ConsentCategory {

	public const NECESSARY = 'necessary';
	public const ANALYTICS = 'analytics';
	public const MARKETING = 'marketing';

	/**
	 * Полный перечень допустимых категорий.
	 *
	 * @var string[]
	 */
	public const ALL = array(
		self::NECESSARY,
		self::ANALYTICS,
		self::MARKETING,
	);

	/**
	 * Проверяет, является ли переданная строка допустимой категорией.
	 *
	 * @param mixed $category
	 * @return bool
	 */
	public static function is_valid( $category ): bool {
		return is_string( $category ) && in_array( $category, self::ALL, true );
	}

	/**
	 * Приводит произвольный массив категорий к каноническому виду:
	 * остаются только известные ключи, значения строго приводятся к bool.
	 *
	 * @param mixed $consents
	 * @return array<string, bool>
	 */
	public static function normalize( $consents ): array {
		$result = array();

		if ( ! is_array( $consents ) ) {
			return $result;
		}

		foreach ( self::ALL as $category ) {
			if ( array_key_exists( $category, $consents ) ) {
				$result[ $category ] = (bool) $consents[ $category ];
			}
		}

		$result[ self::NECESSARY ] = true;

		return $result;
	}

	private function __construct() {
		// Утилитарный, инстанцирование запрещено.
	}
}
