<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Paypal Response Payment
 *
 * @class   S2_PayPal_Response_Payment
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_PayPal_Response_Payment' ) ) {

	class S2_PayPal_Response_Payment extends S2_PayPal_Response {

		/**
		 * Success payments.
		 *
		 * @var array
		 */
		protected $success_payments = array( 'Completed', 'Processed', 'In-Progress' );

		/**
		 * Prefix
		 * @var string
		 */
		protected $prefix = 'PAYMENTINFO_0_';


		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @param array $response Response.
		 * @param string $prefix Prefix.
		 */
		public function __construct( $response, $prefix = 'PAYMENTINFO_0_' ) {
			parent::__construct( $response );
		}


		/**
		 * Checks if the transaction was approved
		 *
		 * @return bool
		 */
		public function transaction_approved() {
			return in_array( $this->get_response_payment_parameter( 'PAYMENTSTATUS' ), $this->success_payments );
		}

		/**
		 * Returns the name for this
		 *
		 * @param string $name Name.
		 *
		 * @return string
		 */
		protected function get_response_payment_parameter_name( $name ) {
			return $this->prefix . $name;
		}

		/**
		 * Returns a payment parameter
		 *
		 * @param string $name Name.
		 *
		 * @return string
		 */
		public function get_response_payment_parameter( $name ) {
			$name = $this->get_response_payment_parameter_name( $name );

			return $this->get_response_parameter( $name );
		}

	}

}
