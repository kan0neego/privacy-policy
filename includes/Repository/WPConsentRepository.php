<?php

namespace CookieConsent\Repository;

use CookieConsent\ConsentActivator;
use CookieConsent\Domain\Client;
use CookieConsent\Domain\Consent;

defined( 'ABSPATH' ) || exit;

final class WPConsentRepository implements ConsentRepositoryInterface {

	private string $table;

	public function __construct( ?string $table = null ) {
		global $wpdb;

		$this->table = $table ?? ( $wpdb->prefix . ConsentActivator::TABLE_NAME );
	}

	public function save( Client $client, Consent $consent ): int {
		global $wpdb;

		$data = array(
			'cookie_token'      => $client->cookie_token,
			'name'              => $client->name,
			'email'             => $client->email,
			'phone'             => $client->phone,
			'ip_address'        => $client->ip_address,
			'consents'          => wp_json_encode( $consent->categories ),
			'policy_version'    => $consent->policy_version,
			'consent_timestamp' => $consent->consent_timestamp->format( 'Y-m-d H:i:s' ),
			'updated_at'        => current_time( 'mysql' ),
		);

		$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

		$existing = $this->find_by_token( $client->cookie_token );

		if ( $existing && isset( $existing['id'] ) ) {
			$found = $wpdb->update(
				$this->table,
				$data,
				array( 'cookie_token' => $client->cookie_token ),
				$formats,
				array( '%s' )
			);

			return false !== $found ? (int) $existing['id'] : 0;
		}

		$data['created_at'] = current_time( 'mysql' );
		$formats[]          = '%s';

		$found = $wpdb->insert( $this->table, $data, $formats );

		if ( false === $found || $wpdb->last_error ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	public function find_by_token( string $cookie_token ): ?array {
		global $wpdb;

		if ( '' === $cookie_token ) {
			return null;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE cookie_token = %s ORDER BY id DESC LIMIT 1",
				$cookie_token
			),
			'ARRAY_A'
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		$row['consents'] = $this->decode_consents( $row['consents'] ?? '' );

		return $row;
	}

	public function delete_by_token( string $cookie_token ): bool {
		global $wpdb;

		if ( '' === $cookie_token ) {
			return false;
		}

		return false !== $wpdb->delete(
			$this->table,
			array( 'cookie_token' => $cookie_token ),
			array( '%s' )
		);
	}

	private function decode_consents( string $json ): array {
		if ( '' === $json ) {
			return array();
		}

		$decoded = json_decode( $json, true );

		return is_array( $decoded ) ? $decoded : array();
	}
}
