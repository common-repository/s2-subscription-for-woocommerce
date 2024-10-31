<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements mail features of S2 Customer Subscription Paused
 *
 * @class   S2_Customer_Subscription_Paused
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Customer_Subscription_Paused' ) ) {

	class S2_Customer_Subscription_Paused extends S2_Customer_Subscription {

		public function __construct() {
			$this->id          = 's2_customer_subscription_paused';
			$this->title       = __( 'Subscription Paused', 's2-subscription' );
			$this->description = __( 'This email is sent to the customer when subscription has been paused', 's2-subscription' );
			$this->email_type  = 'html';
			$this->heading     = __( 'Your subscription has been paused', 's2-subscription' );
			$this->subject     = __( 'Your {site_title} subscription been paused', 's2-subscription' );

			// Call parent constructor
			parent::__construct();

		}

	}

}

// returns instance of the S2_Customer_Subscription_Paused
return new S2_Customer_Subscription_Paused();
