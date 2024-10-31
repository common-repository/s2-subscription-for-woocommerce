<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Stripe Invoice
 *
 * @class   S2_Stripe_Invoice
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
class S2_Stripe_Invoice {

	/**
	 * Stripe invoice ID
	 * @var string
	 */
	private $id = '';

	/**
	 * WP Subscription ID
	 * @var integer
	 */
	private $subscription_id = 0;

	/**
	 * Constructor
	 *
	 * @param int $subscription_id The WP Subscription ID
	 */
	public function __construct( $subscription_id = 0, $stripe_invoice_id = 0 ) {
		if ( $subscription_id ) {
			$this->set_subscription_id( $subscription_id );
		}

		if ( $stripe_invoice_id ) {
			$this->set_id( $stripe_invoice_id );
		}
	}

	/**
	 * Get Stripe invoice ID.
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set Stripe invoice ID.
	 *
	 * @param [type] $id [description]
	 */
	public function set_id( $id ) {
		$this->id = wc_clean( $id );
	}

	/**
	 * Subscription ID in WordPress.
	 * @return int
	 */
	public function get_subscription_id() {
		return absint( $this->subscription_id );
	}

	/**
	 * Set Subscription ID used by WordPress.
	 *
	 * @param int $subscription_id
	 */
	public function set_subscription_id( $subscription_id ) {
		$this->subscription_id = absint( $subscription_id );
	}

	/**
	 * Retrieves the Stripe Invoice ID from the Subscription meta.
	 *
	 * @param int $subscription_id The ID of the WordPress Subscription.
	 *
	 * @return string|bool Either the Stripe ID or false.
	 */
	public function get_id_from_meta( $subscription_id, $meta_key ) {
		return get_post_meta( $subscription_id, $meta_key, true );
	}

	/**
	 * Updates the current Subscription with the right Stripe ID in the meta table.
	 *
	 * @param string $id The Stripe invoice ID.
	 */
	public function update_id_in_meta( $meta_key, $meta_value ) {
		$meta_data       = array();
		$subscription_id = $this->get_subscription_id();

		if ( metadata_exists( 'post', $subscription_id, $meta_key ) ) {
			$meta_data = $this->get_id_from_meta( $subscription_id, $meta_key );
		}

		$meta_data[] = $meta_value;

		update_post_meta( $subscription_id, $meta_key, $meta_data );
	}

	/**
	 * Create a invoice via API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function create_invoice( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'invoices' );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}

		$this->set_id( $response->id );

		if ( $this->get_subscription_id() ) {
			$this->update_id_in_meta( '_stripe_invoice_id', $response->id );

			//save response
			$invoice_response = array( $response->id => $response );
			$this->update_id_in_meta( '_stripe_invoice_response', $invoice_response );
		}

		return $response->id;
	}

	/**
	 * Updates the Stripe invoice through the API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function update_invoice( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'invoices/' . $this->get_id() );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}
	}

	/**
	 * Finalize the Stripe invoice through the API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function finalize_invoice( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'invoices/' . $this->get_id() . '/finalize' );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}
	}

	/**
	 * Pay the Stripe invoice through the API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function pay_invoice( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'invoices/' . $this->get_id() . '/pay' );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}
	}

	/**
	 * Retrieve the Stripe Invoice through the API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|Object
	 */
	public function retrieve_invoice( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'invoices/' . $this->get_id(), 'GET' );

		if ( ! empty( $response->error ) ) {
			// throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}

		return $response;
	}

}
