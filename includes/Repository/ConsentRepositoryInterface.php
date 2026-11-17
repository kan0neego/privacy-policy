<?php

namespace CookieConsent\Repository;

use CookieConsent\Domain\Client;
use CookieConsent\Domain\Consent;

defined( 'ABSPATH' ) || exit;

/**
 * Порт (интерфейс) слоя хранения согласий.
 *
 * Все операции привязаны к cookie_token как первичному идентификатору.
 */
interface ConsentRepositoryInterface {

	/**
	 * Сохраняет клиента и его согласие (INSERT или UPDATE по cookie_token).
	 *
	 * @param Client  $client
	 * @param Consent $consent
	 * @return int Идентификатор сохранённой записи.
	 */
	public function save( Client $client, Consent $consent ): int;

	/**
	 * Возвращает запись согласия по cookie_token.
	 *
	 * @param string $cookie_token
	 * @return array<string, mixed>|null
	 */
	public function find_by_token( string $cookie_token ): ?array;

	/**
	 * Удаляет/анонимизирует запись согласия по cookie_token.
	 *
	 * @param string $cookie_token
	 * @return bool
	 */
	public function delete_by_token( string $cookie_token ): bool;
}
