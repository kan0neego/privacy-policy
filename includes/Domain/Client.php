<?php

namespace CookieConsent\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Value Object, представляющий клиента (субъекта персональных данных).
 *
 * Не зависит от WordPress, принимает только примитивные, строго типизированные
 * значения. Формировать экземпляры следует через ClientFactory.
 */
final class Client {

	/**
	 * @param string $cookie_token  UUID v4 — первичный идентификатор (HTTP-only cookie).
	 * @param string $name          Имя клиента (опционально).
	 * @param string $email         Адрес электронной почты (опционально).
	 * @param string $phone         Номер телефона (опционально).
	 * @param string $ip_address    IP-адрес, с которого поступило согласие (опционально).
	 */
	public function __construct(
		public readonly string $cookie_token,
		public readonly string $name = '',
		public readonly string $email = '',
		public readonly string $phone = '',
		public readonly string $ip_address = ''
	) {
	}

	/**
	 * Возвращает представление объекта в виде ассоциативного массива.
	 *
	 * @return array<string, string>
	 */
	public function to_array(): array {
		return array(
			'cookie_token' => $this->cookie_token,
			'name'         => $this->name,
			'email'        => $this->email,
			'phone'        => $this->phone,
			'ip_address'   => $this->ip_address,
		);
	}
}
