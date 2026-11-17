<?php

namespace CookieConsent\REST;

use CookieConsent\Factory\ClientFactory;
use CookieConsent\Factory\ConsentFactory;
use CookieConsent\Service\CookieConsentService;
use CookieConsent\Settings;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * REST adapter: cookie-consent/v1/consent.
 */
final class CookieConsentController {

	public const NAMESPACE = PRIVACY_POLICY_REST_NAMESPACE;

	public const COOKIE_NAME = PRIVACY_POLICY_COOKIE_NAME;

	public function __construct(
		private readonly CookieConsentService $service
	) {
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/consent',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_consent' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/consent',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_consent' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/consent',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_consent' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);
	}

	private function resolve_token(): string {
		if ( ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			$token = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
			$valid = function_exists( 'wp_is_uuid' ) ? wp_is_uuid( $token ) : (bool) preg_match( '/^[a-f0-9-]{36}$/i', $token );
			if ( $valid ) {
				return $token;
			}
		}

		return wp_generate_uuid4();
	}

	private function with_cookie( WP_REST_Response $response, string $token, int $expires ): WP_REST_Response {
		setcookie(
			self::COOKIE_NAME,
			$token,
			array(
				'expires'  => $expires,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		$cookie = sprintf(
			'%s=%s; expires=%s; path=/; httponly; samesite=lax%s',
			self::COOKIE_NAME,
			$token,
			gmdate( 'D, d-M-Y H:i:s T', $expires ),
			is_ssl() ? '; secure' : ''
		);

		$response->header( 'Set-Cookie', $cookie );

		return $response;
	}

	public function handle_consent( WP_REST_Request $request ) {
		$data = $request->get_json_params();

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'invalid_body',
				'Тело запроса должно быть корректным JSON-объектом.',
				array( 'status' => 400 )
			);
		}

		$cookie_token         = $this->resolve_token();
		$data['cookie_token'] = $cookie_token;

		if ( empty( $data['policy_version'] ) ) {
			$data['policy_version'] = Settings::policy_version();
		}

		$ip = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip        = trim( $forwarded[0] );
		}
		if ( $ip && ! rest_is_ip_address( $ip ) ) {
			$ip = '';
		}

		$data['ip_address'] = $ip;

		$client  = ClientFactory::from_array( $data );
		$consent = ConsentFactory::from_array( $data );

		$id = $this->service->register_consent( $client, $consent );

		if ( $id <= 0 ) {
			return new WP_Error(
				'save_failed',
				'Не удалось сохранить согласие.',
				array( 'status' => 500 )
			);
		}

		$response = new WP_REST_Response(
			array(
				'success'      => true,
				'id'           => $id,
				'cookie_token' => $cookie_token,
			),
			201
		);

		return $this->with_cookie( $response, $cookie_token, time() + Settings::cookie_lifetime_seconds() );
	}

	public function get_consent( WP_REST_Request $request ) {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Согласие не найдено.',
				),
				404
			);
		}

		$cookie_token = $this->resolve_token();
		$item         = $this->service->get_consent_by_token( $cookie_token );

		if ( null === $item ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Согласие не найдено.',
				),
				404
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $item,
			),
			200
		);
	}

	public function delete_consent( WP_REST_Request $request ) {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Согласие не найдено.',
				),
				404
			);
		}

		$cookie_token = $this->resolve_token();
		$removed      = $this->service->withdraw_consent( $cookie_token );

		if ( ! $removed ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Запись не найдена или уже удалена.',
				),
				404
			);
		}

		$response = new WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);

		return $this->with_cookie( $response, '', time() - HOUR_IN_SECONDS );
	}

	public function permission_check(): bool {
		return true;
	}
}
