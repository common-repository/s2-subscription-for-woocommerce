<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements mail features of S2 Customer Subscription Activated
 *
 * @class   S2_Customer_Subscription_Activated
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Customer_Subscription_Activated' ) ) {

	/**
	 * S2_Customer_Subscription_Activated
	 */
	class S2_Customer_Subscription_Activated extends S2_Customer_Subscription {

		/**
		 * Constructor method, used to return object of the class to WC
		 */
		public function __construct() {
			$this->id          = 's2_customer_subscription_activated';
			$this->title       = __( 'Subscription Activated', 's2-subscription' );
			$this->description = __( 'This email is sent to the customer when subscription activate', 's2-subscription' );
			$this->email_type  = 'html';
			$this->heading     = __( 'Your subscription has been activated', 's2-subscription' );
			$this->subject     = __( 'Your {site_title} subscription has been activated', 's2-subscription' );

			// Call parent constructor
			parent::__construct();

		}

	}
}

// returns instance of the S2_Customer_Subscription_Activated
return new S2_Customer_Subscription_Activated();
