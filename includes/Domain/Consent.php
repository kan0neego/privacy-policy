<?php

namespace CookieConsent\Domain;

use DateTimeImmutable;
use DateTimeInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Value Object, представляющий само согласие на обработку персональных данных.
 *
 * Содержит набор категорий (каждая — bool) и метаданные: версию политики
 * и момент предоставления согласия.
 */
final class Consent {

	/**
	 * @var array<string, bool>
	 */
	public readonly array $categories;

	/**
	 * @param array<string, bool> $categories        Категории согласия (normalized).
	 * @param string              $policyVersion     Версия политики обработки ПДн.
	 * @param DateTimeInterface   $consentTimestamp  Момент предоставления согласия.
	 */
	public function __construct(
		array $categories,
		public readonly string $policy_version,
		public readonly DateTimeInterface $consent_timestamp
	) {
		$this->categories = ConsentCategory::normalize( $categories );
	}

	/**
	 * Проверяет, дано ли согласие на конкретную категорию.
	 *
	 * @param string $category
	 * @return bool
	 */
	public function has_category( string $category ): bool {
		return isset( $this->categories[ $category ] ) && $this->categories[ $category ] === true;
	}

	/**
	 * Проверяет, дано ли согласие хотя бы на одну необязательную категорию.
	 *
	 * @return bool
	 */
	public function is_accepted(): bool {
		foreach ( $this->categories as $granted ) {
			if ( $granted ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Возвращает представление объекта в виде массива.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'consents'          => $this->categories,
			'policy_version'    => $this->policy_version,
			'consent_timestamp' => $this->consent_timestamp->format( DateTimeInterface::ATOM ),
		);
	}
}
