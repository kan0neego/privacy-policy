<?php

namespace CookieConsent\Service;

use CookieConsent\Domain\Client;
use CookieConsent\Domain\Consent;
use CookieConsent\Logger\ConsentLogger;
use CookieConsent\Repository\ConsentRepositoryInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Единая точка входа в бизнес-логику cookie-согласий.
 *
 * Оркестрирует: создание Value Objects (Factory), сохранение (Repository)
 * и логирование (Logger). Не содержит грязной бизнес-логики — она живёт
 * в доменных объектах.
 */
final class CookieConsentService {

	public function __construct(
		private readonly ConsentRepositoryInterface $repository,
		private readonly ConsentLogger $logger
	) {
	}

	/**
	 * Регистрирует (создаёт или обновляет) согласие клиента.
	 *
	 * @param Client  $client
	 * @param Consent $consent
	 * @return int Идентификатор записи или 0 при ошибке.
	 */
	public function register_consent( Client $client, Consent $consent ): int {
		$id = $this->repository->save( $client, $consent );

		if ( $id > 0 ) {
			$this->logger->log(
				'consent_given',
				$client,
				array(
					'policy_version' => $consent->policy_version,
					'accepted'       => $consent->is_accepted(),
				)
			);

			do_action( 'cookie_consent_given', $client, $consent, $id );
		}

		return $id;
	}

	/**
	 * Возвращает согласие клиента по cookie_token.
	 *
	 * @param string $cookie_token
	 * @return array<string, mixed>|null
	 */
	public function get_consent_by_token( string $cookie_token ): ?array {
		return $this->repository->find_by_token( $cookie_token );
	}

	/**
	 * Отзывает/удаляет согласие клиента по cookie_token.
	 *
	 * @param string $cookie_token
	 * @return bool
	 */
	public function withdraw_consent( string $cookie_token ): bool {
		$client  = new Client( $cookie_token );
		$removed = $this->repository->delete_by_token( $cookie_token );

		if ( $removed ) {
			$this->logger->log( 'consent_withdrawn', $client );

			do_action( 'cookie_consent_withdrawn', $cookie_token );
		}

		return $removed;
	}
}
