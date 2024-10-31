<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Cash on Delivery Gateway.
 *
 * Provides a Cash on Delivery Payment Gateway.
 *
 * @class       S2_Gateway_COD
 * @extends     WC_Gateway_COD
 * @version     1.0.0
 * @package     WooCommerce\Classes\Payment
 */
if ( ! class_exists( 'S2_Gateway_COD' ) ) {

	class S2_Gateway_COD extends WC_Gateway_COD {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			
			parent::__construct();

			$this->supports = array(
	            'products',
	            // 'refunds',
	            's2_subscription',
	            's2_subscription_paused',
	            's2_subscription_resumed',
	            // 's2_subscription_cancelled',
	            's2_subscription_cancel_now',
	            // 's2_subscription_cancel_with_refund',
	        );

		}

	}

}
