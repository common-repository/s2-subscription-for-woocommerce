<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements admin features of subscription in My Account page
 *
 * @class   S2_Subscription_My_Account
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_My_Account' ) ) {

	class S2_Subscription_My_Account {

		/**
		 * Plugin settings
		 */
		public $s2ws_settings;

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_My_Account
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_My_Account
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

			// Plugin settings
			$this->s2ws_settings = get_option('s2ws_settings');
			
			// Actions used to insert a new endpoint in the WordPress.
			add_action( 'init', [ $this, 'add_endpoints' ] );
			add_filter( 'query_vars', [ $this, 'add_query_vars' ], 0 );

			// Change the My Accout page title.
			add_filter( 'the_title', [ $this, 'endpoint_title' ] );

			// Insering your new tab/page into the My Account page.
			add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu_items' ] );
			add_action( 'woocommerce_account_subscriptions_endpoint', [ $this, 'subscriptions' ] );
			add_action( 'woocommerce_account_view-subscription_endpoint', [ $this, 'view_subscription' ] );
			add_filter( 'woocommerce_account_menu_item_classes', [ $this, 'maybe_add_active_class' ], 10, 2 );

			// change status of subscription
			add_action( 'wp_loaded', [ $this, 'myaccount_actions' ], 90 );

		}

		/**
		 * Register new endpoint to use inside My Account page.
		 *
		 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
		 */
		public function add_endpoints() {
			add_rewrite_endpoint( 'subscriptions', EP_ROOT | EP_PAGES );
			add_rewrite_endpoint( 'view-subscription', EP_ROOT | EP_PAGES );
		}

		/**
		 * Add new query var.
		 *
		 * @param array $vars
		 * @return array
		 */
		public function add_query_vars( $vars ) {
			$vars[] = 'subscriptions';
			$vars[] = 'view-subscription';

			return $vars;
		}

		/**
		 * Set endpoint title.
		 *
		 * @param string $title
		 * @return string
		 */
		public function endpoint_title( $title ) {
			global $wp_query;

			$is_endpoint = isset( $wp_query->query_vars[ 'subscriptions' ] );

			if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
				// New page title.
				$title = __( 'Subscriptions', 's2-subscription' );

				remove_filter( 'the_title', [ $this, 'endpoint_title' ] );
			}

			return $title;
		}


		/**
		 * Insert the new endpoint into the My Account menu.
		 *
		 * @param array $items
		 * @return array
		 */
		public function add_menu_items( $menu_items ) {

			// Add our menu item after the Orders tab if it exists, otherwise just add it to the end
			$orders_key_exist = array_key_exists( 'orders', $menu_items );
			if ( $orders_key_exist ) {
		
				$new_menu_items = [];

				foreach ( $menu_items as $key => $value ) {

					$new_menu_items[ $key ] = $value;

					if ( $key == 'orders' ) {
						$new_menu_items['subscriptions'] = "Subscriptions";
					}
				
				}

				$menu_items = $new_menu_items;
		
			} else {
		
				$menu_items['subscriptions'] = "Subscriptions";
		
			}

			return $menu_items;

		}

		/**
		 * Subscriptions Endpoint HTML content.
		 */
		public function subscriptions() {
			$subscriptions  = s2_get_user_subscriptions();

			wc_get_template( 
				'myaccount/my-subscriptions.php', 
				[ 'subscriptions' => $subscriptions, 's2ws_settings' => $this->s2ws_settings ], 
				'', 
				S2_WS_TEMPLATE_PATH . '/' 
			);
		}

		/**
		 * Show the subscription detail
		 */
		public function view_subscription() {
			global $wp;

			$subscription_id = $wp->query_vars['view-subscription'];
			$subscription    = new S2_Subscription( $subscription_id );
			
			wc_get_template( 
				'myaccount/view-subscription.php', 
				[ 'subscription' => $subscription, 'user' => get_user_by( 'id', get_current_user_id() ), 's2ws_settings' => $this->s2ws_settings ], 
				'', 
				S2_WS_TEMPLATE_PATH . '/' 
			);
		}

		/**
		 * Adds `is-active` class to Subscriptions label when we're viewing a single Subscription.
		 *
		 * @param array  $classes  The classes present in the current endpoint.
		 * @param string $endpoint The endpoint/label we're filtering.
		 *
		 * @return array
		 */
		public function maybe_add_active_class( $classes, $endpoint ) {
			global $wp;

			if ( 'subscriptions' === $endpoint && isset( $wp->query_vars['view-subscription'] ) ) {
				$classes[] = 'is-active';
			}

			return $classes;
		}

		/**
		 * Change the status of subscription from myaccount page
		 */
		public function myaccount_actions() {

			if ( isset( $_REQUEST['change_status'] ) && isset( $_REQUEST['subscription'] ) && isset( $_REQUEST['_wpnonce'] ) ) {

				$subscription = new S2_Subscription( sanitize_text_field( $_REQUEST['subscription'] ) );
				$new_status   = sanitize_text_field( $_REQUEST['change_status'] );

				if ( wp_verify_nonce( $_REQUEST['_wpnonce'], $subscription->id ) === false ) {
					wc_add_notice( __( 'This subscription cannot be updated. Contact us for info', 's2-subscription' ), 'error' );
				}

				$payment_gateways = WC()->payment_gateways();
				$payment_gateways = $payment_gateways->payment_gateways();
				$payment_gateway  = $payment_gateways[ $subscription->payment_method ];

				// check admin options enabled or not
				$change_status = false;
				if( ( $payment_gateway->supports( 's2_subscription_paused' ) && $new_status == "paused" && $subscription->status == "active" && ! empty( $this->s2ws_settings['allow_customer_pause_subscription'] ) && $this->s2ws_settings['allow_customer_pause_subscription'] == 'yes' ) 
						|| ( $payment_gateway->supports( 's2_subscription_resumed' ) && $new_status == "resumed" && $subscription->status == "paused" && ! empty( $this->s2ws_settings['allow_customer_resume_subscription'] ) && $this->s2ws_settings['allow_customer_resume_subscription'] == 'yes' ) 
						|| ( $payment_gateway->supports( 's2_subscription_cancelled' ) && $new_status == "cancelled" && ! empty( $this->s2ws_settings['allow_customer_cancel_subscription'] ) && $this->s2ws_settings['allow_customer_cancel_subscription'] == 'yes' ) ) {
					$change_status = true;
				}

				// if new_status is cancelled, check plugin setting option 'customer_cancel_subscription_option' to cancel subscription
				if( $change_status && $new_status == 'cancelled' ) {
					$new_status = ! empty( $this->s2ws_settings['customer_cancel_subscription_option'] ) ? $this->s2ws_settings['customer_cancel_subscription_option'] : 'cancelled';
				}
				
				if( $change_status ) $subscription->change_status( $new_status, 'customer' );
				
				wp_redirect( $subscription->get_view_subscription_url() );

				exit;

			}

		}

	}

}

/**
 * Unique access to instance of S2_Subscription_My_Account class
 */
S2_Subscription_My_Account::get_instance();
