<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Paypal API Response
 *
 * @class   S2_PayPal_Response
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */

require_once WC()->plugin_path() . '/includes/gateways/paypal/includes/class-wc-gateway-paypal-response.php';

if ( ! class_exists( 'S2_PayPal_Response' ) ) {

	class S2_PayPal_Response extends WC_Gateway_Paypal_Response {

		/**
		 * List of parameters
		 * @var array
		 */
		protected $response_parameters = array();

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @param array $response Response.
		 */
		public function __construct( $response ) {
			// URL decode the response string and parse it.
			wp_parse_str( urldecode( $response ), $this->response_parameters );
		}

		/**
		 * Check if the response has errors
		 *
		 * @return bool
		 */
		public function has_error() {
			$has_error = false;

			if ( isset( $this->response_parameters['ACK'] ) ) {
				$success   = array( 'Success', 'SuccessWithWarning' );
				$has_error = ! in_array( $this->response_parameters['ACK'], $success );
			}

			return $has_error;
		}

		/**
		 * Get error code
		 *
		 * @return bool|mixed
		 */
		public function get_error_code() {
			$error = $this->get_errors( true );
			return isset( $error['code'] ) ? $error['code'] : false;
		}


		/**
		 * Get error message
		 *
		 * @return bool|mixed
		 */
		public function get_error_message() {
			$error = $this->get_errors( true );
			return isset( $error['message'] ) ? $error['message'] : false;
		}

		/**
		 * Return the error list if $first is true return the first element
		 *
		 * @param bool $first Flag for first.
		 *
		 * @return bool|array
		 */
		public function get_errors( $first = false ) {
			$errors = array();

			foreach ( $this->response_parameters as $index => $value ) {
				if ( preg_match( '/^L_ERRORCODE(\d+)$/', $index, $matches ) ) {
					$errors[ $matches[1] ]['code'] = $value;
				} elseif ( preg_match( '/^L_SHORTMESSAGE(\d+)$/', $index, $matches ) ) {
					$errors[ $matches[1] ]['message'] = $value;
				} elseif ( preg_match( '/^L_LONGMESSAGE(\d+)$/', $index, $matches ) ) {
					$errors[ $matches[1] ]['long'] = $value;
				} elseif ( preg_match( '/^L_SEVERITYCODE(\d+)$/', $index, $matches ) ) {
					$errors[ $matches[1] ]['severity'] = $value;
				}
			}

			return ( $first && ! empty( $errors ) ) ? current( $errors ) : $errors;
		}

		/**
		 * Returns the order WC_Order
		 * after  get_express_checkout_details request.
		 *
		 * @return int|mixed
		 */
		public function get_order() {
			return isset( $this->response_parameters['CUSTOM'] ) ? $this->get_paypal_order( $this->response_parameters['CUSTOM'] ) : false;
		}
		
		/**
		 * Returns the payer_id
		 * after  get_express_checkout_details request.
		 *
		 * @return int|mixed
		 */
		public function get_payer_id() {
			return isset( $this->response_parameters['PAYERID'] ) ? $this->response_parameters['PAYERID'] : 0;
		}

		/**
		 * Returns the payer_email
		 * after  get_express_checkout_details request.
		 *
		 * @return int|mixed
		 */
		public function get_payer_email() {
			return isset( $this->response_parameters['EMAIL'] ) ? $this->response_parameters['EMAIL'] : 0;
		}

		/**
		 * Get transaction id
		 * @return mixed|string
		 */
		public function get_transaction_id() {
			return isset( $this->response_parameters['TRANSACTIONID'] ) ? $this->response_parameters['TRANSACTIONID'] : '';
		}

		/**
		 * Returns the BILLINGAGREEMENTID parameter of the response
		 * after  get_express_checkout_details request.
		 *
		 * @return int|mixed
		 */
		public function get_billing_agreement_id() {
			return isset( $this->response_parameters['BILLINGAGREEMENTID'] ) ? $this->response_parameters['BILLINGAGREEMENTID'] : 0;
		}

		/**
		 * Returns the BILLINGAGREEMENTACCEPTEDSTATUS parameter of the response
		 * after  get_express_checkout_details request
		 *
		 * @return int|mixed
		 */
		public function get_billing_agreement_accepted_status() {
			return isset( $this->response_parameters['BILLINGAGREEMENTACCEPTEDSTATUS'] ) ? $this->response_parameters['BILLINGAGREEMENTACCEPTEDSTATUS'] : 0;
		}

		/**
		 * Returns the custom
		 * after  get_express_checkout_details request.
		 *
		 * @return int|mixed
		 */
		public function get_custom() {
			return $this->has_response_parameter( 'CUSTOM' ) ? $this->response_parameters['CUSTOM'] : 0;
		}

		/**
		 * Get response parameter
		 *
		 * @return array
		 */
		public function get_response_parameters() {
			return $this->response_parameters;
		}

		/**
		 * Get token
		 *
		 * @return mixed
		 */
		public function get_token() {
			return isset( $this->response_parameters['TOKEN'] ) ? $this->response_parameters['TOKEN'] : 0;
		}

		/**
		 * Get payment status
		 *
		 * @return mixed
		 */
		public function get_payment_status() {
			return isset( $this->response_parameters['PAYMENTSTATUS'] ) ? $this->response_parameters['PAYMENTSTATUS'] : '';
		}

		/**
		 * Get pending reason
		 *
		 * @return mixed
		 */
		public function get_pending_reason() {
			return isset( $this->response_parameters['PENDINGREASON'] ) ? $this->response_parameters['PENDINGREASON'] : '';
		}

		/**
		 * Get response parameter
		 *
		 * @param string $name Name.
		 *
		 * @return mixed|null
		 */
		public function get_response_parameter( $name ) {
			return $this->has_response_parameter( $name ) ? $this->response_parameters[ $name ] : null;
		}

		/**
		 * Check if there's a response parameter
		 *
		 * @param string $name Name.
		 *
		 * @return bool
		 */
		protected function has_response_parameter( $name ) {
			return ! empty( $this->response_parameters[ $name ] );
		}

		/**
		 * Get shipping details
		 *
		 * @return array
		 */
		public function get_shipping_details() {
			$shipping_details = array();
			$response         = $this->get_response_parameters();

			if ( isset( $response['FIRSTNAME'] ) ) {
				$shipping_details = array(
					'first_name' => $response['FIRSTNAME'],
					'last_name'  => isset( $response['LASTNAME'] ) ? $response['LASTNAME'] : '',
					'company'    => isset( $response['BUSINESS'] ) ? $response['BUSINESS'] : '',
					'email'      => isset( $response['EMAIL'] ) ? $response['EMAIL'] : '',
					'phone'      => isset( $response['PHONENUM'] ) ? $response['PHONENUM'] : '',
					'address_1'  => isset( $response['SHIPTOSTREET'] ) ? $response['SHIPTOSTREET'] : '',
					'address_2'  => isset( $response['SHIPTOSTREET2'] ) ? $response['SHIPTOSTREET2'] : '',
					'city'       => isset( $response['SHIPTOCITY'] ) ? $response['SHIPTOCITY'] : '',
					'postcode'   => isset( $response['SHIPTOZIP'] ) ? $response['SHIPTOZIP'] : '',
					'country'    => isset( $response['SHIPTOCOUNTRYCODE'] ) ? $response['SHIPTOCOUNTRYCODE'] : '',
					'state'      => ( isset( $response['SHIPTOCOUNTRYCODE'] ) && isset( $response['SHIPTOSTATE'] ) ) ? $response['SHIPTOSTATE'] : '',
					'shiptoname' => isset( $response['SHIPTONAME'] ) ? $response['SHIPTONAME'] : '',
				);
			}
			return $shipping_details;
		}

		/**
		 * Returns the PROFILEID parameter of the response
		 * after  create_recurring_payments_profile request
		 *
		 * @return int|mixed
		 */
		public function get_recurring_payments_profile_id() {
			return isset( $this->response_parameters['PROFILEID'] ) ? $this->response_parameters['PROFILEID'] : 0;
		}

	}

}
