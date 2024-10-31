<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements mail features of S2 Customer Subscription Cancelled
 *
 * @class   S2_Customer_Subscription_Expired
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Customer_Subscription_Cancelled' ) ) {

	class S2_Customer_Subscription_Cancelled extends S2_Customer_Subscription {

		/**
		 * Constructor method, used to return object of the class to WC
		 */
		public function __construct() {
			$this->id          = 's2_customer_subscription_cancelled';
			$this->title       = __( 'Subscription Cancelled', 's2-subscription' );
			$this->description = __( 'This email is sent to the customer when subscription is cancelled', 's2-subscription' );
			$this->email_type  = 'html';
			$this->heading     = __( 'Your subscription has been cancelled', 's2-subscription' );
			$this->subject     = __( 'Your {site_title} subscription has been cancelled', 's2-subscription' );

			// Call parent constructor
			parent::__construct();

		}

	}

}

// returns instance of the S2_Customer_Subscription_Cancelled
return new S2_Customer_Subscription_Cancelled();
