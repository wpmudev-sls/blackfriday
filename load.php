<?php
/**
 * WPMUDEV Black Friday common module
 *
 * Used by wordpress.org free plugins only to show Black Friday deal on admin dashboard.
 *
 * @since   1.0
 * @author  WPMUDEV
 * @package WPMUDEV\BlackFriday
 */

namespace WPMUDEV\BlackFriday;

use WP_Error;
use function get_current_screen;

if ( ! class_exists( __NAMESPACE__ . '\\Load' ) ) {
	/**
	 * Class Load.
	 *
	 * @since   1.0
	 * @package  WPMUDEV\BlackFriday\Load
	 */
	class Load {
		/**
		 * Version number.
		 *
		 * @since 1.0
		 * @var string $version
		 *
		 */
		private $version = '1.0.0';

		/**
		 * Option name to store data.
		 *
		 * @since 1.0
		 * @var string $option_name
		 *
		 */
		protected $option_name = 'wpmudev_blackfriday';

		/**
		 * Hub endpoint
		 *
		 * @since 1.0
		 * @var string $hub_endpoint
		 */
		protected $hub_endpoint = '/api/black-friday/v1/plugin';

		/**
		 * Nonce.
		 *
		 * @since 1.0
		 * @var string $nonce
		 */
		protected $nonce = 'wpmudev-bf-common';

		/**
		 * Registered plugins and their data. Used when checking for priority.
		 *
		 * @since 1.0
		 * @var array $plugins_by_priority
		 *
		 */
		private $plugins_by_priority = array(
			'smush'       => 'wp-smushit/wp-smush.php',
			'forminator'  => 'forminator/forminator.php',
			'hummingbird' => 'hummingbird-performance/wp-hummingbird.php',
			'hustle'      => 'wordpress-popup/popover.php',
			'defender'    => 'defender-security/wp-defender.php',
			'smartcrawl'  => 'smartcrawl-seo/wpmu-dev-seo.php',
			'branda'      => 'branda-white-labeling/ultimate-branding.php',
			'beehive'     => 'beehive-analytics/beehive-analytics.php',
		);

		const SMUSH = 0;
		const FORMINATOR = 10;
		const HUMMIGNBIRD = 20;
		const HUSTLE = 30;
		const DEFENDER = 40;
		const SMARTCRAWL = 50;
		const BRANDA = 60;
		const BEEHIVE = 70;

		private static $printed = false;

		private $plugin_id;
		private $utm;
		private $priority = 0;

		/**
		 * Construct handler class.
		 *
		 * @since 1.0
		 */
		public function __construct( string $plugin_id = '', string $utm = '', int $priority = 10 ) {
			if ( empty( $plugin_id ) || empty( $utm ) ) {
				return;
			}

			$this->plugin_id = $plugin_id;
			$this->utm = $utm;
			$this->priority = $priority;

			// Current screen actions.
			add_action( 'current_screen', array( $this, 'current_screen_actions' ) );

			add_action( 'wp_ajax_wpmudev_bf_act', array( $this, 'send_deal_request' ), 5 );
			add_action( 'wp_ajax_wpmudev_bf_dismiss', array( $this, 'dismiss_deal' ), 5 );
		}

		public function current_screen_actions() {
			if ( ! $this->can_load() ) {
				return;
			}

			// Enqueue Black Friday js.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			// Render dashboard notice.
			add_action( 'admin_notices', array( $this, 'dashboard_notice' ), $this->priority );
		}

		/**
		 * Enqueues scrupts required for BF banner.
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			if ( self::$printed ) {
				return;
			}

			$priority_plugin = $this->get_priority_plugin();

			if ( is_wp_error( $priority_plugin ) ) {
				return;
			}

			$script_data  = include dirname( __FILE__ ) . '/assets/js/main.asset.php';
			$dependencies = $script_data['dependencies'] ?? array(
					'react',
					'wp-element',
					'wp-i18n',
				);

			wp_enqueue_script(
				'wpmudev-bf-common',
				plugin_dir_url( __FILE__ ) . '/assets/js/main.js',
				$dependencies,
				$this->version,
				true
			);

			wp_localize_script(
				'wpmudev-bf-common',
				'wpmudev_bf_common', array(
					'nonce'        => wp_create_nonce( $this->nonce ),
					'plugin_label' => $priority_plugin['Name'] ?? '',
				)
			);

			wp_enqueue_style(
				'wpmudev-bf-common',
				plugin_dir_url( __FILE__ ) . 'assets/css/style.css',
				array(),
				$this->version
			);
		}

		/**
		 * Loads the Dashboard Notice
		 *
		 * @return void
		 */
		public function dashboard_notice() {
			if ( self::$printed ) {
				return;
			}

			self::$printed = true;

			echo '<div class="sui-2-2">
					<div class="sui-wrap">
						<div 
						id="wpmudev-bf-common-notice" 
						data-bf-plugin-id="' . esc_html( $this->plugin_id ) . '"
						data-bf-plugin-utm="' . esc_html( $this->utm ) . '"
						>
						</div>
					</div>
				</div>';
		}

		/**
		 * Checks if plugin's Black Friday deal can be loaded.
		 *
		 * @return boolean
		 */
		public function can_load() {
			if (
				! $this->is_admin_dash() ||
				! current_user_can( 'delete_users' ) ||
				$this->event_expired() ||
				$this->dashboard_plugin_installed() ||
				$this->banner_shown_previously()
			) {
				return false;
			}

			return true;
		}

		/**
		 * Checks if current page is admin dashboard.
		 */
		public function is_admin_dash() {
			if ( ! is_multisite() || ( is_multisite() && is_main_site() ) ) {
				return function_exists( 'get_current_screen' ) && 'dashboard' === get_current_screen()->id;
			}

			return false;
		}

		/**
		 * Checks if offer has expired.
		 */
		public function event_expired() {
			// Expires on 29 Nov 2022.

			/*
			 * @todo Make sure to replace `15-11-2022` with `21-11-2022`
			*/
			return date_create( date_i18n( 'd-m-Y' ) ) < date_create( date_i18n( '15-11-2022' ) ) ||
			       date_create( date_i18n( 'd-m-Y' ) ) >= date_create( date_i18n( '29-11-2022' ) );
		}

		/**
		 * Checks if Dashboard plugin is installed.
		 */
		public function dashboard_plugin_installed() {
			return class_exists( 'WPMUDEV_Dashboard' ) || file_exists( WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php' );
		}

		/**
		 * Returns data of the active plugin with the highest priority.
		 *
		 * @return mixed|string|null
		 */
		public function get_priority_plugin() {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );

				if ( ! empty( $network_plugins ) ) {
					$active_plugins = wp_parse_args( $active_plugins, $network_plugins );
				}
			}

			foreach ( $this->plugins_by_priority as $priority_plugin_key => $priority_plugin_path ) {
				if ( in_array( $priority_plugin_path, $active_plugins ) ) {
					$plugin_data = get_plugin_data(
						trailingslashit( WP_PLUGIN_DIR ) . $priority_plugin_path, false, false
					);

					if ( empty( $plugin_data ) || ! is_array( $plugin_data ) ) {
						return new WP_Error( 'E_INVALID_PLUGIN', esc_html__( 'Invalid plugin found' ) );
					}

					$plugin_data['plugin_id'] = $priority_plugin_key;

					return $plugin_data;
				}
			}

			return null;
		}

		/**
		 * Checks if notice has been already shown.
		 *
		 * @return bool
		 */
		public function banner_shown_previously() {
			$options = get_option( $this->option_name );

			return ! empty( $options ) && ! empty( $options['dismissed_flag'] );
		}

		/**
		 * Sets
		 */
		public function send_deal_request() {
			$current_user = wp_get_current_user();
			$plugin_id    = filter_input( INPUT_POST, 'plugin_id', FILTER_SANITIZE_STRING );

			$response = wp_remote_post( $this->request_url, array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(),
					'body'        => array(
						'name'   => esc_html( $current_user->display_name ),
						'email'  => esc_html( $current_user->user_email ),
						'source' => $plugin_id
					),
					'cookies'     => array()
				)
			);

			if ( is_wp_error( $response ) ) {
			} else {
			}
		}


		/**
		 * Returns WPMUDEV API Server URL
		 *
		 * @return string
		 */
		public function wpmudev_base_url() {
			return defined( 'WPMUDEV_CUSTOM_API_SERVER' ) && WPMUDEV_CUSTOM_API_SERVER
				? trailingslashit( WPMUDEV_CUSTOM_API_SERVER )
				: 'https://wpmudev.com/';
		}

		/**
		 * Returns the hub's url.
		 *
		 * @return string
		 */
		public function request_url() {
			$url = null;

			if ( self::get_dashboard_api() instanceof WPMUDEV_Dashboard_Api ) {
				$site_id = self::get_site_id();

				$url = untrailingslashit( $this->wpmudev_base_url() . $this->hub_endpoint );
			}

			return $url;
		}

		public function dismiss_deal() {
			/**
			 * @todo Check with js part what key is used for nonce. For now using `security`.
			 */
			check_ajax_referer( $this->nonce, 'security' );

			wp_send_json_success( array( 'success' => true ) );
		}
	}
}
