<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Paypal API Handler
 *
 * @class   S2_PayPal_API_Handler
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_PayPal_API_Handler' ) ) {

	class S2_PayPal_API_Handler {

		/**
		 * The production endpoint
		 *
		 * @var string
		 */
		protected static $production_endpoint = 'https://api-3t.paypal.com/nvp';

		/**
		 * The sandbox endpoint
		 *
		 * @var string
		 */
		protected static $sandbox_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';

		/**
		 *  NVP API version
		 *
		 * @var string
		 */
		protected $api_version = '204';

		/** @var string request http version 1.1 default */
		protected $request_http_version = '1.1';

		/** @var string request user agent */
		protected $request_user_agent;

		/** @var object S2_PayPal_Request */
		protected $request;

		/** @var string request URI */
		protected $request_uri;

		/** @var string method used for the request */
		protected $request_method = 'POST';

		/** @var array request headers */
		protected $request_headers = array();

		/** @var string name of class for the response */
		protected $response_handler;

		/** @var array content of the resuls */
		protected $response_result;

		/** @var string API username */
		protected $api_username;

		/** @var string API password */
		protected $api_password;

		/** @var string API Signature */
		protected $api_signature;

		/**
		 * Single instance of the class
		 *
		 * @var \S2_PayPal_API_Handler
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_PayPal_API_Handler
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
		 */
		public function __construct( $testmode, $api_username, $api_password, $api_signature, $api_subject = '' ) {

			// set the REQUEST URI
			$this->request_uri = $testmode ? self::$sandbox_endpoint : self::$production_endpoint;

			// Set API credentials
			$this->api_username  = $api_username;
			$this->api_password  = $api_password;
			$this->api_signature = $api_signature;
			$this->api_subject   = $api_subject;

		}

		/**
		 * Prepares the request and send the response to get_response
		 *
		 * @param $request
		 *
		 * @return S2_PayPal_Response|WP_Error|S2_PayPal_Response_Payment
		 * @throws S2_PayPal_API_Exception
		 */
		protected function prepare_request( $request ) {

			// ensure API is in its default state
			$this->clear_response();

			$this->request = $request;
			WC_Gateway_Paypal::log( "prepare_request - request - " . print_r( $this->request, true ) );

			$response = wp_safe_remote_request( $this->request_uri, $this->get_default_request_args() );
			WC_Gateway_Paypal::log( "prepare_request - response - " . print_r( $response, true ) );
			
			try {
				$response = $this->get_response( $response );
			} catch ( Exception $exception ) {
				throw new S2_PayPal_API_Exception( $response );
			}

			return $response;
		}

		/**
		 * Gets the default request args
		 *
		 * @return array
		 */
		protected function get_default_request_args() {

			$args = array(
				'method'      => $this->request_method,
				'timeout'     => 1000,
				'redirection' => 0,
				'httpversion' => $this->request_http_version,
				'sslverify'   => true,
				'blocking'    => true,
				'user-agent'  => $this->get_request_user_agent(),
				'headers'     => $this->get_request_headers(),
				'body'        => $this->request->get_body(),
				'cookies'     => array(),
			);

			return apply_filters( 's2_paypal_default_paypal_request_args', $args );
		}

		/**
		 * Clear the API response
		 */
		protected function clear_response() {
			$this->response_result = null;
			$this->response        = null;
		}

		/**
		 * Get user agent
		 *
		 * @return mixed
		 * @since 1.0.0
		 */
		function get_request_user_agent() {
			return sprintf( '%s/%s', 'S2_PayPal', WC_STRIPE_CC_METHOD_VERSION );
		}

		/**
		 * @param null $order
		 * @return mixed|void
		 */
		public function needs_billing_agreement( $order = null ) {
			$needs_billing_agreement = false;

			if ( is_null( $order ) ) {
				if( function_exists( 's2_cart_has_subscriptions' ) ) {
					$needs_billing_agreement = s2_cart_has_subscriptions();
				}
			} else {
				if( function_exists( 's2_order_has_subscriptions' ) ) {
					$needs_billing_agreement = s2_order_has_subscriptions( $order );
				}
			}

			return $needs_billing_agreement;
		}

		/**
		 * Get a new request from the class S2_PayPal_Request
		 *
		 * @return S2_PayPal_Request
		 */
		function get_request() {
			return new S2_PayPal_Request( $this->api_username, $this->api_password, $this->api_signature, $this->api_version, $this->api_subject );
		}

		/**
		 * Get request headers
		 *
		 * @param boolean $sanitized
		 *
		 * @return mixed
		 */
		function get_request_headers( $sanitized = false ) {
			$headers = $this->request_headers;

			if ( $sanitized && ! empty( $headers['Authorization'] ) ) {
				$headers['Authorization'] = str_repeat( '*', strlen( $headers['Authorization'] ) );
			}

			return apply_filters( 's2_paypal_request_headers', $headers, $sanitized );
		}

		/**
		 *
		 * @param S2_PayPal_Response $response
		 *
		 * @return  $response S2_PayPal_Response
		 * @throws Exception
		 */
		protected function get_response( $response ) {

			/**@var WP_Error $response */
			if ( is_wp_error( $response ) ) {
				WC_Gateway_Paypal::log( 'Error response: ' . $response->get_error_message() );
				throw new Exception( $response->get_error_message(), 0 );
			}

			$this->response_result = array(
				'code'    => wp_remote_retrieve_response_code( $response ),
				'message' => wp_remote_retrieve_response_message( $response ),
				'body'    => wp_remote_retrieve_body( $response ),
				'headers' => wp_remote_retrieve_headers( $response ),
			);

			$handler_class = $this->response_handler;

			/*@var S2_PayPal_Response */
			$this->response = new $handler_class( $this->response_result['body'] );

			if ( $this->response->has_error() ) {
				throw new Exception( $this->response->get_error_message(), $this->response->get_error_code() );
			}
			
			return $this->response;
		}

		/**
		 * Manage Recurring Payments Profile Status
		 *
		 * @param string $subscription_id paypal subscription / profile id from create_recurring_payments_profile response
		 * @param array $args
		 *
		 * @return S2_PayPal_Response_Payment response object
		 * @throws S2_PayPal_API_Exception
		 */
		public function call_manage_recurring_payments_profile_status( $subscription_id, $args ) {

			$request = $this->get_request();

			$request->manage_recurring_payments_profile_status( $subscription_id, $args );

			$this->response_handler = 'S2_PayPal_Response_Payment';

			return $this->prepare_request( $request );
		}

		/**
		 * Update Recurring Payments Profile
		 *
		 * @param string $subscription_id paypal subscription / profile id from create_recurring_payments_profile response
		 * @param array $args
		 *
		 * @return S2_PayPal_Response_Payment response object
		 * @throws S2_PayPal_API_Exception
		 */
		public function call_update_recurring_payments_profile( $subscription_id, $args ) {

			$request = $this->get_request();

			$request->update_recurring_payments_profile( $subscription_id, $args );

			$this->response_handler = 'S2_PayPal_Response_Payment';

			return $this->prepare_request( $request );
		}

		/**
		 * Bill outstanding amount request.
		 *
		 * @param $args
		 *
		 * @return WP_Error|S2_PayPal_Response|S2_PayPal_Response_Payment
		 * @throws S2_PayPal_API_Exception
		 */
		public function call_bill_outstanding_amount( $subscription_id, $args ) {

			$request = $this->get_request();

			$request->bill_outstanding_amount( $subscription_id, $args );

			$this->response_handler = 'S2_PayPal_Response';

			return $this->prepare_request( $request );
		}

		/**
		 * Get transaction details by transaction id
		 *
		 * @param $transaction_id
		 *
		 * @return WP_Error|S2_PayPal_Response
		 * @throws S2_PayPal_API_Exception
		 */
		public function call_get_transaction_details( $transaction_id ) {

			$request = $this->get_request();

			$request->get_transaction_details( $transaction_id );

			$this->response_handler = 'S2_PayPal_Response';

			return $this->prepare_request( $request );
		}

		/**
		 * Call refund request.
		 *
		 * @param $args
		 *
		 * @return WP_Error|S2_PayPal_Response|S2_PayPal_Response_Payment
		 * @throws S2_PayPal_API_Exception
		 */
		public function call_refund_transaction( $args ) {

			$request = $this->get_request();

			$request->refund_transaction( $args );

			$this->response_handler = 'S2_PayPal_Response';

			return $this->prepare_request( $request );
		}

	}

}
