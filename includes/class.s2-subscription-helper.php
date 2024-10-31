<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements product features of S2 Subscription Product
 *
 * @class   S2_Subscription_Helper
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Helper' ) ) {

	class S2_Subscription_Helper {

		/**
		 * @var string post_type
		 */
		public $post_type = 's2_subscription';

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Helper
		 */
		protected static $instance;

		/**
		 * Use to check metabox value saved
		 *
		 * @var bool saved_metabox
		 */
		protected $saved_metabox = false;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_Helper
		 *
		 * @since  1.0.0
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
		 * @param array $args
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			add_action( 'init', [ $this, 'init' ], 1 );

			// Add Capabilities to Administrator and Shop Manager
			add_action( 'admin_init', [ $this, 'add_subscription_capabilities' ] );

			add_action( 'add_meta_boxes', [ $this, 'show_info_subscription' ] );
			add_action( 'add_meta_boxes', [ $this, 'show_action_subscription' ], 10, 2 );
			add_action( 'add_meta_boxes', [ $this, 'show_activity_subscription' ] );
			add_action( 'add_meta_boxes', [ $this, 'show_product_subscription' ] );
			add_action( 'add_meta_boxes', [ $this, 'show_schedule_subscription' ], 10, 2 );

			add_action( 'admin_menu', [ $this, 'remove_publish_box' ] );
			add_action( 'save_post', [ $this, 'before_data_saving' ], 0, 2 );

			// added to update status of subscription
			// paypal send expired ipn immediately before complete payment ipn of last payment, so instead of updating through paypal ipn response update expired status on admin access
			// add_action( 's2_after_register_post_type', [ $this, 'update_subscription_status' ] );

		}

		/**
		 * Register s2_subscription post type
		 */
		public function init() {

			$supports = false;
			$is_debug_on = defined( 'WP_DEBUG' ) && WP_DEBUG;
			if ( $is_debug_on ) {
				$supports = [ 'custom-fields' ];
			}

			$labels = [
				'name'               => _x( 'Subscriptions', 'Post Type General Name', 's2-subscription' ),
				'singular_name'      => _x( 'Subscription', 'Post Type Singular Name', 's2-subscription' ),
				'menu_name'          => __( 'S2 Subscription', 's2-subscription' ),
				'parent_item_colon'  => __( 'Parent Item:', 's2-subscription' ),
				'all_items'          => __( 'All Subscriptions', 's2-subscription' ),
				'view_item'          => __( 'View Subscriptions', 's2-subscription' ),
				'add_new_item'       => __( 'Add New Subscription', 's2-subscription' ),
				'add_new'            => __( 'Add New Subscription', 's2-subscription' ),
				'edit_item'          => __( 'Edit Subscription', 's2-subscription' ),
				'update_item'        => __( 'Update Subscription', 's2-subscription' ),
				'search_items'       => __( 'Search Subscription', 's2-subscription' ),
				'not_found'          => __( 'Not found', 's2-subscription' ),
				'not_found_in_trash' => __( 'Not found in Trash', 's2-subscription' ),
			];

			$args = [
				'label'               => __( 's2_subscription', 's2-subscription' ),
				'labels'              => $labels,
				'supports'            => $supports,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-building',
				'capability_type'     => [ 's2_subscription', 's2_subscriptions' ],
				'capabilities'        => [
					'create_posts'        => false, // Removes support for the "Add New" function ( use 'do_not_allow' instead of false for multisite set ups )
					'edit_post'           => 'edit_s2_subscription',
					'edit_posts'          => 'edit_s2_subscriptions',
					'edit_others_posts'   => 'edit_others_s2_subscriptions',
					'delete_post'         => 'delete_s2_subscription',
					'delete_others_posts' => 'delete_others_s2_subscriptions',
				],
				'map_meta_cap'        => false,
			];

			register_post_type( $this->post_type, $args );

			do_action( 's2_after_register_post_type' );

		}

		/**
		 * Add subscription management capabilities to Admin and Shop Manager
		 */
		public function add_subscription_capabilities() {
			$caps = [
				'edit_post'           => 'edit_s2_subscription',
				'edit_posts'          => 'edit_s2_subscriptions',
				'edit_others_posts'   => 'edit_others_s2_subscriptions',
				'delete_post'         => 'delete_s2_subscription',
				'delete_others_posts' => 'delete_others_s2_subscriptions',
			];

			// gets the admin and shop_mamager roles
			$admin        = get_role( 'administrator' );
			$shop_manager = get_role( 'shop_manager' );

			foreach ( $caps as $key => $cap ) {
				$admin && $admin->add_cap( $cap );
				$shop_manager && $shop_manager->add_cap( $cap );
			}
		}

		/**
		 * Add the metabox to show the info of subscription
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function show_info_subscription() {
			add_meta_box( 
				's2-info-subscription', 
				__( 'Subscription Info', 's2-subscription' ), 
				[ $this, 'show_subscription_info_metabox' ], 
				$this->post_type, 
				'normal', 
				'high' 
			);
		}

		/**
		 * Metabox to show the info of the current subscription
		 *
		 * @access public
		 *
		 * @param object $post
		 *
		 * @return void
		 */
		public function show_subscription_info_metabox( $post ) {
			$subscription = s2_get_subscription( $post->ID );

			wc_get_template( 
				'admin/metabox/metabox-subscription-info-content.php', 
				[ 'subscription' => $subscription ], 
				'', 
				S2_WS_TEMPLATE_PATH . '/' 
			);
		}


		/**
		 * Add the metabox to show the info of subscription
		 *
		 * @access public
		 *
		 * @oaram  S2_Subscription
		 * @return void
		 */
		public function show_action_subscription( $post_type, $post ) {
		
			$subscription = s2_get_subscription( $post->ID );

			// hide action metabox if subscription is expired / cancelled
			if( $subscription->status == 'expired' || $subscription->status == 'cancelled' ) return;

			add_meta_box( 's2-action-subscription', __( 'Subscription Action', 's2-subscription' ), 
				[ $this, 'show_subscription_action_metabox' ], $this->post_type, 'side', 'high' );
		
		}

		/**
		 * Metabox to show the action of the current subscription
		 *
		 * @access public
		 *
		 * @param object $post
		 *
		 * @return void
		 */
		public function show_subscription_action_metabox( $post ) {
			$subscription = s2_get_subscription( $post->ID );

			$payment_gateways = WC()->payment_gateways();
			$payment_gateway  = $payment_gateways->payment_gateways()[ $subscription->payment_method ];

			wc_get_template( 
				'admin/metabox/metabox-subscription-action-content.php', 
				[ 'payment_gateway' => $payment_gateway, 'subscription' => $subscription ], 
				'', 
				S2_WS_TEMPLATE_PATH . '/' 
			);
		}

		/**
		 * Add the metabox to show the activities of subscription
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function show_activity_subscription() {
			add_meta_box( 
				's2-activity-subscription', 
				__( 'Subscription Activities', 's2-subscription' ), 
				[ $this, 'show_subscription_activity_metabox' ], 
				$this->post_type, 
				'side', 
				'high' 
			);
		}

		/**
		 * Metabox to show the activities of the current subscription
		 *
		 * @access public
		 *
		 * @param object $post
		 *
		 * @return void
		 */
		public function show_subscription_activity_metabox( $post ) {
			//S2_Subscription_Activity()->add_activity( $post->ID, 'new' );
			$activities = S2_Subscription_Activity()->get_activity_by_subscription( $post->ID );

			wc_get_template( 
				'admin/metabox/metabox-subscription-activity-content.php', 
				[ 'activities' => $activities ], 
				'', 
				S2_WS_TEMPLATE_PATH . '/' 
			);
		}

		/**
		 * Add the metabox to show the product of subscription
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function show_product_subscription() {
			add_meta_box( 
				's2-product-subscription', 
				__( 'Subscription Product', 's2-subscription' ), 
				[ $this, 'show_subscription_product_metabox' ], 
				$this->post_type, 
				'normal', 
				'high' 
			);
		}

		/**
		 * Metabox to show the product detail of the current subscription
		 *
		 * @access public
		 *
		 * @param $post object
		 *
		 * @return void
		 */
		public function show_subscription_product_metabox( $post ) {
			$subscription = s2_get_subscription( $post->ID );

			wc_get_template( 
				'admin/metabox/metabox-subscription-product.php', 
				[ 'subscription' => $subscription ], 
				'', 
				S2_WS_TEMPLATE_PATH . '/' 
			);
		}

		/**
		 * Add the metabox to show the product of subscription
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function show_schedule_subscription( $post_type, $post ) {

			$subscription = s2_get_subscription( $post->ID );

			if ( $subscription->payment_type != 'subscription' ) {
				return;
			}

			$payment_gateways = WC()->payment_gateways();
			$payment_gateway  = $payment_gateways->payment_gateways()[ $subscription->payment_method ];

			if ( $subscription->status != 'active' || ( ! $payment_gateway->supports( 's2_subscription_total_billing_cycle_changes' ) && ! $payment_gateway->supports( 's2_subscription_date_changes' ) && ! $payment_gateway->supports( 's2_subscription_upgrade_downgrade' ) && ! $payment_gateway->supports( 's2_subscription_price_changes' ) ) ) {
				return;
			}

			// check total_billing_cycle == completed_billing_cycle for paypal because paypal expires subscription immediately after last payment, so we can not update billing cycle / expiry date of paypal subscription
			if( ( $payment_gateway->id == 'paypal' || $payment_gateway->id == 's2-paypal-ec' ) && $subscription->total_billing_cycle == $subscription->completed_billing_cycle ) {
				return;
			}

			add_meta_box( 
				's2-schedule-subscription', 
				__( 'Subscription Schedule', 's2-subscription' ), 
				[ $this, 'show_subscription_schedule_metabox' ], 
				$this->post_type, 
				'normal', 
				'high' );
		}

		/**
		 * Metabox to show the product detail of the current subscription
		 *
		 * @access public
		 *
		 * @param $post object
		 *
		 * @return void
		 */
		public function show_subscription_schedule_metabox( $post ) {
			$subscription = s2_get_subscription( $post->ID );
			$product      = wc_get_product( ( $subscription->variation_id ) ? $subscription->variation_id : $subscription->product_id );
			$fields       = $this->get_schedule_data_subscription_fields( $subscription );

			$payment_gateways = WC()->payment_gateways();
			$payment_gateway  = $payment_gateways->payment_gateways()[ $subscription->payment_method ];

			wc_get_template( 
				'admin/metabox/metabox-subscription-schedule.php', 
				[ 'subscription' => $subscription, 'fields' => $fields, 'payment_gateway' => $payment_gateway ], 
				'', 
				S2_WS_TEMPLATE_PATH . '/' 
			);
		}

		/**
		 * @return mixed|void
		 */
		public function get_schedule_data_subscription_fields( $subscription = null ) {
			$fields = [
				'total_billing_cycle' => [
					"label"           => __( 'Total billing cycle', 's2-subscription' ),
					"type"            => 'number',
					"gateway_support" => 's2_subscription_total_billing_cycle_changes',
					"default"		  => ( $subscription ? $subscription->total_billing_cycle : '' ),
					"min"		  	  => ( $subscription ? ( $subscription->completed_billing_cycle + 1 ) : '' ),
				],
				'expired_date' => [
					"label"           => __( 'Expired date', 's2-subscription' ),
					"type"            => 'text',
					"gateway_support" => 's2_subscription_date_changes',
				],
				'line_total'   => [
					"label"           => __( 'Product Price', 's2-subscription' ),
					"type"            => 'text',
					"gateway_support" => 's2_subscription_price_changes',
				],
			];

			return apply_filters( 's2_schedule_data_subscription_fields', $fields );
		}

		/**
		 * Remove publish box from single page page of subscription
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function remove_publish_box() {
			remove_meta_box( 'submitdiv', $this->post_type, 'side' );
		}

		/**
		 * Save meta data
		 *
		 * @param $post_id
		 *
		 * @param $post
		 *
		 * @return mixed
		 * @throws Exception
		 */
		public function before_data_saving( $post_id, $post ) {

			if ( $post->post_type != $this->post_type || $this->saved_metabox ) {
				return;
			}

			// Dont' save meta boxes for revisions or autosaves
			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
				return;
			}

			// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
			if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
				return;
			}

			// Check user has permission to edit
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$subscription = s2_get_subscription( $post_id );

			// Save Billing and Shipping Meta if different from parent order __________________________________________//
			$meta                = $meta_billing = $meta_shipping = [];
			$billing_fields      = s2_get_order_fields_to_edit( 'billing' );
			$billing_order_meta  = $subscription->get_address_fields_from_order( 'billing', false, '_' );
			$shipping_fields     = s2_get_order_fields_to_edit( 'shipping' );
			$shipping_order_meta = $subscription->get_address_fields_from_order( 'shipping', false, '_' );

			foreach ( $billing_fields as $key => $billing_field ) {
				$field_id = '_billing_' . $key;
				if ( ! isset( $_POST[ $field_id ] ) ) {
					continue;
				}
				$meta[ $field_id ] = sanitize_text_field( $_POST[ $field_id ] );
			}

			foreach ( $shipping_fields as $key => $shipping_field ) {
				$field_id = '_shipping_' . $key;
				if ( ! isset( $_POST[ $field_id ] ) ) {
					continue;
				}
				$meta[ $field_id ] = sanitize_text_field( $_POST[ $field_id ] );
			}

			if ( isset( $_POST['customer_note'] ) ) {
				$meta['customer_note'] = sanitize_text_field( $_POST['customer_note'] );
			}

			$meta && $subscription->update_subscription_meta( $meta );

			if ( isset( $_POST['s2_subscription_actions'] ) && $_POST['s2_subscription_actions'] != '' ) {

				$new_status = sanitize_text_field( $_POST['s2_subscription_actions'] );
				$subscription->change_status( $new_status, 'administrator' );

			}

			$this->saved_metabox = true;

		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Helper class
*/
S2_Subscription_Helper::get_instance();
