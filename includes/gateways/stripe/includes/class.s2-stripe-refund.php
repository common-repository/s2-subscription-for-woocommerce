<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Stripe Refund
 *
 * @class   S2_Stripe_Refund
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
class S2_Stripe_Refund {

	/**
	 * Stripe refund ID
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
	public function __construct( $subscription_id = 0, $stripe_refund_id = 0 ) {
		if ( $subscription_id ) {
			$this->set_subscription_id( $subscription_id );
		}

		if ( $stripe_refund_id ) {
			$this->set_id( $stripe_refund_id );
		}
	}

	/**
	 * Get Stripe refund ID.
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set Stripe refund ID.
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
	 * Retrieves the Stripe Refund ID from the Subscription meta.
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
	 * @param string $id The Stripe refund ID.
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
	 * Create a refund via API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function create_refund( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'refunds' );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}

		$this->set_id( $response->id );

		if ( $this->get_subscription_id() ) {
			$this->update_id_in_meta( '_stripe_refund_id', $response->id );

			//save response
			$refund_response = array( $response->id => $response );
			$this->update_id_in_meta( '_stripe_refund_response', $refund_response );
		}

		return $response->id;
	}

	/**
	 * Updates the Stripe refund through the API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function update_refund( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'refunds/' . $this->get_id() );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}
	}

}
