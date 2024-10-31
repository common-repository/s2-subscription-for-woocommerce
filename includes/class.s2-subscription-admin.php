<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements admin features of S2 Subscription
 *
 * @class   S2_Subscription_Admin
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Admin' ) ) {

	class S2_Subscription_Admin {

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Admin
		 */

		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_Admin
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			require_once S2_WS_INC . 'admin/class.s2-subscription-plugin-panel.php';
			require_once S2_WS_INC . 'admin/class.s2-subscription-plugin-setting.php';
			require_once S2_WS_INC . 'admin/class.s2-subscription-list.php';
			require_once S2_WS_INC . 'admin/product/class.s2-product-subscription.php';
			require_once S2_WS_INC . 'admin/product/class.s2-variable-product-subscription.php';

			add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );

		}

		/**
		 * Load admin scripts.
		 *
		 * @since 1.0.0
		 */
		public function admin_scripts() {
			/*if ( 's2-plugins_page_s2-subscription' !== get_current_screen()->id ) {
				return;
			}*/

			global $woocommerce;

			if ( function_exists( 'WC' ) || ! empty( $woocommerce ) ) {
				$woocommerce_version = function_exists( 'WC' ) ? WC()->version : $woocommerce->version;

				wp_enqueue_script( 'selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.full.min.js', [ 'jquery' ], $woocommerce_version , true );

				wp_enqueue_script( 'wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select.min.js', [ 'jquery' ], $woocommerce_version, true );

				wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css', [], $woocommerce_version );
			}

			wp_enqueue_script(
				's2_subscription_admin',
				S2_WS_ASSETS_URL . '/js/admin' . S2_WS_SUFFIX . '.js',
				[],
				S2_WS_VERSION,
				true
			);

			$s2_admin = [
				'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
				'woocommerce_currency_symbol' => get_woocommerce_currency_symbol(),
				'time_format'                 => apply_filters( 's2_time_format', 'Y-m-d H:i:s' ),
				'copy_billing'                => __( 'Copy billing information to shipping information? This will remove any currently entered shipping information.', 's2-subscription' ),
				'load_billing'                => __( "Load the customer's billing information? This will remove any currently entered billing information.", 's2-subscription' ),
				'no_customer_selected'        => __( 'User is not registered', 's2-subscription' ),
				'get_customer_details_nonce'  => wp_create_nonce( 'get-customer-details' ),
				'save_item_nonce'             => wp_create_nonce( 'save-item-nonce' ),
				'recalculate_nonce'           => wp_create_nonce( 'recalculate_nonce' ),
				'load_shipping'               => __( "Load the customer's shipping information? This will remove any currently entered shipping information.", 's2-subscription' ),
			];

			wp_localize_script( 's2_subscription_admin', 's2_admin', $s2_admin );
		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Admin class
*/
if ( is_admin() ) {
	S2_Subscription_Admin::get_instance();
}
