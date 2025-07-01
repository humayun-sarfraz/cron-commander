<?php
/**
 * Plugin Name:     Cron Commander
 * Plugin URI:      https://github.com/humayun-sarfraz/cron-commander
 * Description:     View all WP-Cron schedules and start/stop individual cron hooks from the admin.
 * Version:         1.0.0
 * Author:          Humayun Sarfraz
 * Author URI:      https://github.com/humayun-sarfraz
 * Text Domain:     cron-commander
 * Domain Path:     /languages
 * License:         GPLv2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Cron_Commander_Core', false ) ) {

	final class Cron_Commander_Core {

		/** @var Cron_Commander_Core */
		private static $instance;

		/** Nonce action for AJAX */
		private const NONCE_ACTION = 'cron_commander';

		/**
		 * Singleton
		 */
		public static function instance(): self {
			if ( null === self::$instance ) {
				self::$instance = new self();
				self::$instance->init_hooks();
			}
			return self::$instance;
		}

		private function __construct() {
			add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		}

		private function init_hooks(): void {
			add_action( 'admin_menu',       [ $this, 'add_admin_page' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
			add_action( 'wp_ajax_cc_toggle_cron', [ $this, 'ajax_toggle_cron' ] );
		}

		/**
		 * Load translations
		 */
		public function load_textdomain(): void {
			load_plugin_textdomain(
				'cron-commander',
				false,
				dirname( plugin_basename( __FILE__ ) ) . '/languages/'
			);
		}

		/**
		 * Add submenu under Tools
		 */
		public function add_admin_page(): void {
			add_management_page(
				__( 'Cron Commander', 'cron-commander' ),
				__( 'Cron Commander', 'cron-commander' ),
				'manage_options',
				'cron-commander',
				[ $this, 'render_admin_page' ]
			);
		}

		/**
		 * Enqueue simple JS for AJAX actions
		 */
		public function enqueue_assets( string $hook ): void {
			if ( 'tools_page_cron-commander' !== $hook ) {
				return;
			}
			wp_enqueue_script(
				'cron-commander-js',
				plugins_url( 'assets/cron-commander.js', __FILE__ ),
				[ 'jquery' ],
				'1.0.0',
				true
			);
			wp_localize_script( 'cron-commander-js', 'CCVars', [
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( self::NONCE_ACTION ),
				'toggleText' => [
					'stop'  => __( 'Stop', 'cron-commander' ),
					'start' => __( 'Start', 'cron-commander' ),
				],
			] );
			wp_enqueue_style(
				'cron-commander-css',
				plugins_url( 'assets/cron-commander.css', __FILE__ ),
				[],
				'1.0.0'
			);
		}

		/**
		 * Render the admin page
		 */
		public function render_admin_page(): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Insufficient privileges', 'cron-commander' ) );
			}

			$crons = _get_cron_array(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_cron_array

			echo '<div class="wrap"><h1>' . esc_html__( 'Cron Commander', 'cron-commander' ) . '</h1>';
			if ( empty( $crons ) ) {
				echo '<p>' . esc_html__( 'No scheduled tasks found.', 'cron-commander' ) . '</p></div>';
				return;
			}

			echo '<table class="widefat fixed"><thead>
				<tr>
					<th>' . esc_html__( 'Hook', 'cron-commander' ) . '</th>
					<th>' . esc_html__( 'Next Run', 'cron-commander' ) . '</th>
					<th>' . esc_html__( 'Recurrence', 'cron-commander' ) . '</th>
					<th>' . esc_html__( 'Action', 'cron-commander' ) . '</th>
				</tr>
				</thead><tbody>';

			foreach ( $crons as $timestamp => $hooks ) {
				foreach ( $hooks as $hook => $data ) {
					$recurrence = isset( $data['schedule'] ) ? esc_html( $data['schedule'] ) : esc_html__( 'One-time', 'cron-commander' );
					$next_run   = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
					$enabled    = ! empty( $data['schedule'] );
					$btn_label  = $enabled ? esc_html__( 'Stop', 'cron-commander' ) : esc_html__( 'Start', 'cron-commander' );
					printf(
						'<tr>
							<td>%1$s</td>
							<td>%2$s</td>
							<td>%3$s</td>
							<td><button class="button cc-toggle" data-hook="%4$s" data-timestamp="%5$d">%6$s</button></td>
						</tr>',
						esc_html( $hook ),
						esc_html( $next_run ),
						$recurrence,
						esc_attr( $hook ),
						intval( $timestamp ),
						$btn_label
					);
				}
			}

			echo '</tbody></table></div>';
		}

		/**
		 * AJAX handler to toggle a cron hook on/off
		 */
		public function ajax_toggle_cron(): void {
			check_ajax_referer( self::NONCE_ACTION, 'nonce' );
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( esc_html__( 'Permission denied', 'cron-commander' ) );
			}

			$hook      = sanitize_text_field( wp_unslash( $_POST['hook'] ?? '' ) );
			$timestamp = intval( $_POST['timestamp'] ?? 0 );

			if ( empty( $hook ) || $timestamp <= 0 ) {
				wp_send_json_error( esc_html__( 'Invalid parameters', 'cron-commander' ) );
			}

			$crons = _get_cron_array(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_cron_array
			if ( isset( $crons[ $timestamp ][ $hook ] ) ) {
				$event = $crons[ $timestamp ][ $hook ];
				// Stop: clear all schedules for this hook
				if ( ! empty( $event['schedule'] ) ) {
					wp_clear_scheduled_hook( $hook );
					wp_send_json_success( __( 'Stopped', 'cron-commander' ) );
				}
				// Start: re-schedule once after 60 seconds
				wp_schedule_single_event( time() + 60, $hook );
				wp_send_json_success( __( 'Started', 'cron-commander' ) );
			}

			wp_send_json_error( esc_html__( 'Hook not found', 'cron-commander' ) );
		}
	}

	Cron_Commander_Core::instance();
}
