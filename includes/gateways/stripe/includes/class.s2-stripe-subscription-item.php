<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Stripe Subscription Item
 *
 * @class   S2_Stripe_Subscription_Item
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
class S2_Stripe_Subscription_Item {

	/**
	 * Stripe subscription item ID
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
	public function __construct( $subscription_id = 0, $stripe_sub_item_id = 0 ) {
		if ( $subscription_id ) {
			$this->set_subscription_id( $subscription_id );
		}

		if ( $stripe_sub_item_id ) {
			$this->set_id( $stripe_sub_item_id );
		}
	}

	/**
	 * Get Stripe subscription item ID.
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set Stripe subscription item ID.
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
	 * Retrieves the Stripe Subscription Item ID from the Subscription meta.
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
	 * @param string $id The Stripe subscription Item ID.
	 */
	public function update_id_in_meta( $meta_key, $meta_value ) {
		
		$meta_data_array = array();
		$meta_data = '';
		$subscription_id  = $this->get_subscription_id();

		if ( metadata_exists( 'post', $subscription_id, $meta_key ) ) {
			$meta_data = $this->get_id_from_meta( $subscription_id, $meta_key );
		}

		if ( ! empty( $meta_data ) ) {
			if ( ! is_array( $meta_data ) ) $meta_data_array[] = $meta_data;
			else $meta_data_array = $meta_data;
		}

		$meta_data_array[] = $meta_value;

		update_post_meta( $subscription_id, $meta_key, $meta_data_array );
	}

	/**
	 * Create a subscription item via API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function create_subscription_item( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'subscription_items' );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}

		$this->set_id( $response->id );

		if ( $this->get_subscription_id() ) {
			$this->update_id_in_meta( '_stripe_subscription_item_id', $response->id );

			//save response
			$subscription_response = array( $response->id => $response );
			$this->update_id_in_meta( '_stripe_subscription_response', $subscription_response );
		}

		return $response->id;
	}

	/**
	 * Updates the Stripe subscription item through the API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function update_subscription_item( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'subscription_items/' . $this->get_id() );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}
	}

	/**
	 * Cancel / Delete the Stripe subscription item through the API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function cancel_subscription_item( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'subscription_items/' . $this->get_id(), 'DELETE' );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}
	}

}
