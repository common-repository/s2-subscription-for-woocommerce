<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements admin features of S2 Variable Product Subscription Admin
 *
 * @class   S2_Variable_Product_Subscription_Admin
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Variable_Product_Subscription_Admin' ) ) {

	class S2_Variable_Product_Subscription_Admin {

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

			// Custom fields for variable product.
			add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'add_fields_for_variable_products' ], 10, 3 );
			add_action( 'woocommerce_save_product_variation', [ $this, 'save_fields_for_variation_products' ], 10 );

		}

		/**
		 * Display subscription product content
		 *
		 * @param $loop
		 * @param $variation_data
		 * @param $variation
		 */
		public function add_fields_for_variable_products( $loop, $variation_data, $variation ) {
			wc_get_template( 
				'admin/product/variable-product-data-subscription.php', 
				[ 'variation' => $variation, 'loop' => $loop ], 
				'', 
				S2_WS_TEMPLATE_PATH . '/' 
			);
		}

		/**
		 * Save custom fields for product
		 *
		 * @param $variation_id
		 */
		public function save_fields_for_variation_products( $variation_id ) {

			if ( isset( $_POST['variable_post_id'] ) && ! empty( $_POST['variable_post_id'] ) ) {
				$current_variation_index = array_search( $variation_id, sanitize_text_field( $_POST['variable_post_id'] ) );
			}

			if ( $current_variation_index === false ) {
				return false;
			}

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
				update_post_meta( $variation_id, $meta, sanitize_text_field( $_POST[ $meta ][ $current_variation_index ] ) );
			}

		}

	}

}

/**
 * Unique access to instance of S2_Variable_Product_Subscription_Admin class
*/
S2_Variable_Product_Subscription_Admin::get_instance();
