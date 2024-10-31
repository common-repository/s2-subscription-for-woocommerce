<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements admin features of S2 Product Subscription Admin
 *
 * @class   S2_Product_Subscription_Admin
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Product_Subscription_Admin' ) ) {

	class S2_Product_Subscription_Admin {

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

			// Custom tab & fields for single product.
			add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_fields_for_products' ] );
			add_action( 'woocommerce_process_product_meta', [ $this, 'save_fields_for_products' ], 10, 2 );

		}

		/*
		 * Display subscription product tab content
		 */
		public function add_fields_for_products() {
			wc_get_template( 
				'admin/product/product-data-subscription.php', 
				[], 
				'', 
				S2_WS_TEMPLATE_PATH . '/' 
			);
		}

		/**
		 * Save custom fields for single product
		 *
		 * @param $post_id
		 * @param $post
		 *
		 */
		public function save_fields_for_products( $post_id, $post ) {
			$fields = [
				's2_payment_type',
				's2_billing_frequency',
				's2_price_is_per',
				's2_price_time_option',
				's2_max_length',
				's2_sign_up_fee',
				's2_trial_per',
				's2_trial_time_option',
				's2_trial_period',
				's2_split_payment',
				's2_limit_quantity_available',
				's2_quantity_limit'
			];

			foreach ( $fields as $meta ) {
				update_post_meta( $post_id, $meta, sanitize_text_field( $_POST[ $meta ] ) );
			}
		}

	}

}

/**
 * Unique access to instance of S2_Product_Subscription_Admin class
*/
S2_Product_Subscription_Admin::get_instance();
