<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements mail features of S2 Customer Subscription Overdue
 *
 * @class   S2_Customer_Subscription_Overdue
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Customer_Subscription_Overdue' ) ) {

	class S2_Customer_Subscription_Overdue extends S2_Customer_Subscription {

		/**
		 * Constructor method, used to return object of the class to WC
		 */
		public function __construct() {
			$this->id          = 's2_customer_subscription_overdue';
			$this->title       = __( 'Subscription Overdue', 's2-subscription' );
			$this->description = __( 'This email is sent to the customer when they fail to pay within Due date', 's2-subscription' );
			$this->email_type  = 'html';
			$this->heading     = __( 'Overdue payment', 's2-subscription' );
			$this->subject     = __( 'Payment for subscription is overdue', 's2-subscription' );

			// Call parent constructor
			parent::__construct();

		}

		/**
		 * Method triggered to send email
		 *
		 * @param int $subscription
		 *
		 * @return void
		 */
		public function trigger( $subscription ) {

			$this->recipient = $subscription->get_billing_email();

			// Check if this email type is enabled, recipient is set
			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			$order = wc_get_order( $subscription->order_id );

			if ( ! $order ) {
				return;
			}

			$this->object = $subscription;
			$this->order  = $order;
			$this->placeholders['{order_number}'] = $order->get_order_number();

			$this->template_variables = [
				'subscription'       => $this->object,
				'order'              => $this->order,
				'email_heading'      => $this->get_heading(),
				'sent_to_admin'      => false,
				'email'              => $this,
			];

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content_html(), $this->get_headers(), $this->get_attachments() );
		}

		/**
		 * Get HTML content for the mail
		 *
		 * @return string HTML content of the mail
		 */
		public function get_content_html() {
			ob_start();
			wc_get_template( $this->template_html, $this->template_variables, '', $this->template_base );
			return ob_get_clean();
		}


	}

}

// returns instance of the mail on file include
return new S2_Customer_Subscription_Overdue();
