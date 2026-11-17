<?php

namespace CookieConsent\Admin;

use CookieConsent\ConsentActivator;
use CookieConsent\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Native Settings API screen under Settings.
 */
final class SettingsPage {

	public function register_menu(): void {
		add_options_page(
			__( 'Согласия cookie', 'privacy-policy' ),
			__( 'Согласия cookie', 'privacy-policy' ),
			'manage_options',
			'privacy-policy',
			array( $this, 'render' )
		);
	}

	public function register_settings(): void {
		register_setting(
			Settings::OPTION_GROUP,
			Settings::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::defaults(),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'privacy_policy_main',
			__( 'Параметры согласия', 'privacy-policy' ),
			array( $this, 'render_section' ),
			'privacy-policy'
		);

		add_settings_field(
			'policy_version',
			__( 'Версия политики', 'privacy-policy' ),
			array( $this, 'render_policy_version' ),
			'privacy-policy',
			'privacy_policy_main'
		);

		add_settings_field(
			'cookie_lifetime_days',
			__( 'Срок cookie, дней', 'privacy-policy' ),
			array( $this, 'render_cookie_lifetime' ),
			'privacy-policy',
			'privacy_policy_main'
		);

		add_settings_field(
			'enable_consent_logging',
			__( 'Логирование', 'privacy-policy' ),
			array( $this, 'render_logging' ),
			'privacy-policy',
			'privacy_policy_main'
		);
	}

	public function render_section(): void {
		echo '<p>' . esc_html__( 'Значения подставляются в REST, если клиент не передал их явно.', 'privacy-policy' ) . '</p>';
	}

	public function render_policy_version(): void {
		$settings = Settings::get();
		printf(
			'<input type="text" class="regular-text" id="privacy_policy_policy_version" name="%1$s[policy_version]" value="%2$s" maxlength="20" />',
			esc_attr( Settings::OPTION_KEY ),
			esc_attr( $settings['policy_version'] )
		);
		echo '<p class="description">' . esc_html__( 'Фиксируется в журнале согласий (например 1.0, 2026-11).', 'privacy-policy' ) . '</p>';
	}

	public function render_cookie_lifetime(): void {
		$settings = Settings::get();
		printf(
			'<input type="number" class="small-text" id="privacy_policy_cookie_lifetime_days" name="%1$s[cookie_lifetime_days]" value="%2$d" min="%3$d" max="%4$d" />',
			esc_attr( Settings::OPTION_KEY ),
			(int) $settings['cookie_lifetime_days'],
			(int) Settings::MIN_LIFETIME_DAYS,
			(int) Settings::MAX_LIFETIME_DAYS
		);
		echo '<p class="description">' . esc_html__( 'Срок HTTP-only cookie cookie_consent_token.', 'privacy-policy' ) . '</p>';
	}

	public function render_logging(): void {
		$settings = Settings::get();
		printf(
			'<label><input type="checkbox" name="%1$s[enable_consent_logging]" value="1" %2$s /> %3$s</label>',
			esc_attr( Settings::OPTION_KEY ),
			checked( $settings['enable_consent_logging'], true, false ),
			esc_html__( 'Писать события в error_log', 'privacy-policy' )
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$privacy_url = get_privacy_policy_url();
		$count       = $this->records_count();
		$schema      = ConsentActivator::schema_version();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( Settings::OPTION_GROUP );
				do_settings_sections( 'privacy-policy' );
				submit_button();
				?>
			</form>
			<h2><?php esc_html_e( 'Состояние', 'privacy-policy' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Политика конфиденциальности', 'privacy-policy' ); ?></th>
					<td>
						<?php if ( $privacy_url ) : ?>
							<a href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html( $privacy_url ); ?></a>
						<?php else : ?>
							<p class="description"><?php esc_html_e( 'Страница не задана: Настройки → Конфиденциальность.', 'privacy-policy' ); ?></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Версия схемы БД', 'privacy-policy' ); ?></th>
					<td><?php echo esc_html( $schema !== '' ? $schema : '—' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Записей согласий', 'privacy-policy' ); ?></th>
					<td><?php echo esc_html( (string) $count ); ?></td>
				</tr>
			</table>
		</div>
		<?php
	}

	private function records_count(): int {
		global $wpdb;

		$table = ConsentActivator::table_name();
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( ! $found ) {
			return 0;
		}

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
