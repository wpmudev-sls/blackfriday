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

		/**
		 * Construct handler class.
		 *
		 * @since 1.0
		 */
		protected function __construct() {
			// Current screen actions.
			add_action( 'current_screen', array( $this, 'current_screen_actions' ) );
		}

		/**
		 * Initializes and returns the singleton instance.
		 *
		 * @since 1.0
		 *
		 * @return static
		 */
		public static function instance() {
			static $instance = null;

			if ( null === $instance ) {
				$instance = new self();
			}

			return $instance;
		}

		public function current_screen_actions() {
			// Check if BF Dashboard Notice should be shown.
			if ( ! $this->can_load() ) {
				return;
			}

			// Enqueue Black Friday js.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			// Render dashboard notice.
			add_action( 'admin_notices', array( $this, 'dashboard_notice' ) );
		}

		/**
		 * Enqueues scrupts required for BF banner.
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			$priority_plugin = $this->get_priority_plugin();
			$script_data     = include dirname( __FILE__ ) . '/assets/js/main.asset.php';
			$dependencies    = $script_data['dependencies'] ?? array(
					'react',
					'wp-element',
					'wp-i18n',
					'wp-is-shallow-equal',
					'wp-polyfill',
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
					'nonce'        => wp_create_nonce( 'wpmudev-bf-common' ),
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
			echo '<div class="sui-2-2"><div class=".sui-wrap"><div id="wpmudev-bf-common-notice"></div></div></div>';
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
			return function_exists( 'get_current_screen' ) && 'dashboard' === get_current_screen()->id;
		}

		/**
		 * Checks if offer has expired.
		 */
		public function event_expired() {
			// Expires on 29 Nov 2022.
			return time() > mktime( 0, 0, 0, 11, 29, 2022 );
		}

		/**
		 * Checks if Dashboard plugin is installed.
		 */
		public function dashboard_plugin_installed() {
			return class_exists( 'WPMUDEV_Dashboard' ) || file_exists( WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php' );
		}

		/**
		 * Checks plugin priority.
		 *
		 * @todo Check when plugins are network active as well.
		 */
		public function plugin_has_priority() {
			$active_plugins      = (array) get_option( 'active_plugins', array() );
			$cur_plugin_base_dir = explode( '/', plugin_basename( dirname( __FILE__ ) ) )[0];

			foreach ( $this->plugins_by_priority as $ordered_plugin ) {
				if ( in_array( $ordered_plugin, $active_plugins ) ) {
					if ( substr( $ordered_plugin, 0, strlen( $cur_plugin_base_dir ) ) === $cur_plugin_base_dir ) {
						return true;
					}

					return false;
				}
			}

			// Unnecessary but makes PhpStorm happy.
			return false;
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
					return get_plugin_data( trailingslashit( WP_PLUGIN_DIR ) . $priority_plugin_path, false, false
					);
				}
			}

			return null;
		}

		public function banner_shown_previously() {
			$options = get_option( $this->option_name );

			return ! empty( $options ) && ! empty( $options['shown_flag'] );
		}
	}

	// Initialize Black Friday module.
	Load::instance();
}
