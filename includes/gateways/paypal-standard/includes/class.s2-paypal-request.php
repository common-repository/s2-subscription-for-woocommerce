<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Paypal API Request
 *
 * @class   S2_PayPal_Request
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_PayPal_Request' ) ) {

	class S2_PayPal_Request {

		/**
		 * Single instance of the class
		 *
		 * @var S2_PayPal_Request
		 */
		protected static $instance;

		/**
		 * Request fields
		 * @var array
		 */
		protected $request_fields = array();

		/**
		 * Returns single instance of the class
		 *
		 * @return S2_PayPal_Request
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
		 * @param $api_username
		 * @param $api_password
		 * @param $api_signature
		 * @param $api_version
		 * @param $api_subject
		 */
		public function __construct( $api_username, $api_password, $api_signature, $api_version, $api_subject = '' ) {

			$this->add_fields(
				array(
					'USER'      => $api_username,
					'PWD'       => $api_password,
					'SIGNATURE' => $api_signature,
					'VERSION'   => $api_version,
				)
			);

			if ( ! empty( $api_subject ) ) {
				$this->add_field( 'SUBJECT', $api_subject );
			}

		}

		/**
		 * Sets ManageRecurringPaymentsProfileStatus Request Fields
		 *
		 * @param string $subscription_id paypal subscription / profile id
		 * @param array  $args
		 *
		 * @return void
		 */
		public function manage_recurring_payments_profile_status( $subscription_id, $args = array() ) {

			$this->add_fields(
				array(
					'METHOD' 					=> 'ManageRecurringPaymentsProfileStatus',
					'PROFILEID'            		=> $subscription_id,
					'ACTION'  					=> $args['action'],
					'NOTE'     					=> $args['note'],
				)
			);

		}

		/**
		 * Sets UpdateRecurringPaymentsProfile Request Fields
		 *
		 * @param string $subscription_id paypal subscription / profile id
		 * @param array  $args
		 *
		 * @return void
		 */
		public function update_recurring_payments_profile( $subscription_id, $args = array() ) {

			$this->add_fields(
				array(
					'METHOD' 					=> 'UpdateRecurringPaymentsProfile',
					'PROFILEID'            		=> $subscription_id,
					'NOTE'     					=> $args['note'],
				)
			);

			if( ! empty( $args['amount'] ) ) {
				$this->add_field( 'AMT', $args['amount'] );
			}

			if( ! empty( $args['additional_billing_cycles'] ) ) {
				$this->add_field( 'ADDITIONALBILLINGCYCLES', $args['additional_billing_cycles'] );
			}

		}

		/**
		 * Bill outstanding amount request.
		 *
		 * @param string $subscription_id paypal subscription / profile id
		 * @param array  $args
		 *
		 * @return void
		 */
		public function bill_outstanding_amount( $subscription_id, $args = array() ) {

			$this->add_fields(
				array(
					'METHOD' 		=> 'BillOutstandingAmount',
					'PROFILEID' 	=> $subscription_id,
					'NOTE'          => html_entity_decode( wc_trim_string( $args['note'], 255 ), ENT_NOQUOTES, 'UTF-8' ),
				)
			);

			if ( ! empty( $args['amount'] ) ) {
				$this->add_field( 'AMT', number_format( $args['amount'], 2, '.', '' ) );
			}

		}

		/**
		 * Gets Details about a transaction
		 *
		 * @param $transaction_id
		 */
		public function get_transaction_details( $transaction_id ) {
			$this->add_field( 'TRANSACTIONID', $transaction_id );
			$this->add_field( 'METHOD', 'GetTransactionDetails' );
		}

		/**
		 * Refund Transaction Request.
		 *
		 * @param $args
		 */
		public function refund_transaction( $args ) {

			$this->add_fields(
				array(
					'METHOD' 		=> 'RefundTransaction',
					'TRANSACTIONID' => $args['transaction_id'],
					'NOTE'          => html_entity_decode( wc_trim_string( $args['note'], 255 ), ENT_NOQUOTES, 'UTF-8' ),
					'REFUNDTYPE'    => 'Full',
				)
			);

			if ( ! empty( $args['amount'] ) ) {
				$this->add_fields(
					array(
						'AMT'          => number_format( $args['amount'], 2, '.', '' ),
						'CURRENCYCODE' => $args['currency'],
						'REFUNDTYPE'   => 'Partial',
					)
				);
			}

		}

		/**
		 * Add field to general $request_fields
		 *
		 * @param $key
		 * @param $value
		 */
		private function add_field( $key, $value ) {
			$this->request_fields[ $key ] = $value;
		}

		/**
		 * Add a list of fields in the general $request_fields
		 *
		 * @param $fields
		 */
		private function add_fields( $fields ) {
			foreach ( $fields as $key => $field ) {
				$this->add_field( $key, $field );
			}
		}

		/**
		 * Returns the string of the request, if $safe is true
		 * the sensitive fields are masked by *
		 *
		 * @param bool $safe
		 *
		 * @return mixed|string
		 */
		public function get_body( $safe = false ) {
			// added for localhost testing with ngrok.io, added ipn notification url in paypal's account settings
			// if notify_url not set, paypal uses url from paypal's ipn setting(notification url) to send ipn from paypal
			WC_Gateway_Paypal::log( "get_body - HTTP_HOST - " . $_SERVER['HTTP_HOST'] );
			if( $_SERVER['HTTP_HOST'] == 'localhost' ) {
		 		// $this->request_fields['NOTIFYURL'] = '';   
		 		unset( $this->request_fields['NOTIFYURL'] );   
		 		unset( $this->request_fields['PAYMENTREQUEST_0_NOTIFYURL'] );   
			}

			$body = http_build_query( $this->request_fields, '', '&' );

			if ( $safe ) {
				$hide_fields    = array( 'USER', 'PWD', 'SIGNATURE' );
				$request_fields = $this->request_fields;
				foreach ( $hide_fields as $field ) {
					if ( isset( $request_fields[ $field ] ) ) {
						$request_fields[ $field ] = str_repeat( '*', strlen( $request_fields[ $field ] ) );
					}
				}

				$body = print_r( $request_fields, true );
			}

			WC_Gateway_Paypal::log( "get_body - " . $body );

			return $body;
		}

	}

}
