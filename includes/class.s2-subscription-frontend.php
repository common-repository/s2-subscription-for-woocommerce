<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements features of S2 Subscription Frontend
 *
 * @class   S2_Subscription_Frontend
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Frontend' ) ) {

	class S2_Subscription_Frontend {

		/**
		 * Plugin settings
		 */
		public $s2ws_settings;

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Frontend
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_Frontend
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

			// Plugin settings
			$this->s2ws_settings = get_option( 's2ws_settings' );

			require_once S2_WS_INC . 'class.s2-subscription-product.php';
			require_once S2_WS_INC . 'class.s2-subscription-cart.php';
			require_once S2_WS_INC . 'class.s2-subscription-order.php';
			require_once S2_WS_INC . 'class.s2-subscription-my-account.php';

		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Frontend class
 */
S2_Subscription_Frontend::get_instance();
