<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Stripe Plan
 *
 * @class   S2_Stripe_Plan
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
class S2_Stripe_Plan {

	/**
	 * Stripe plan ID
	 * @var string
	 */
	private $id = '';

	/**
	 * WP Order ID
	 * @var integer
	 */
	private $order_id = 0;

	/**
	 * Constructor
	 *
	 * @param int $order_id The WP Order ID
	 */
	public function __construct( $order_id = 0, $stripe_plan_id = 0 ) {
		if ( $order_id ) {
			$this->set_order_id( $order_id );
		}

		if ( $stripe_plan_id ) {
			$this->set_id( $stripe_plan_id );
		}
	}

	/**
	 * Get Stripe Order ID.
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set Stripe Order ID.
	 *
	 * @param [type] $id [description]
	 */
	public function set_id( $id ) {
		$this->id = wc_clean( $id );
	}

	/**
	 * Order ID in WordPress.
	 * @return int
	 */
	public function get_order_id() {
		return absint( $this->order_id );
	}

	/**
	 * Set Order ID used by WordPress.
	 *
	 * @param int $order_id
	 */
	public function set_order_id( $order_id ) {
		$this->order_id = absint( $order_id );
	}

	/**
	 * Retrieves the Stripe Order ID from the Order meta.
	 *
	 * @param int $order_id The ID of the WordPress post.
	 *
	 * @return string|bool Either the Stripe ID or false.
	 */
	public function get_id_from_meta( $order_id, $meta_key ) {
		return get_post_meta( $order_id, $meta_key, true );
	}

	/**
	 * Updates the current Order with the right Stripe ID in the meta table.
	 *
	 * @param string $id The Stripe Order ID.
	 */
	public function update_id_in_meta( $meta_key, $meta_value ) {
		$meta_data_array = array();
		$meta_data = '';
		$order_id  = $this->get_order_id();

		if ( metadata_exists( 'post', $order_id, $meta_key ) ) {
			$meta_data = $this->get_id_from_meta( $order_id, $meta_key );
		}

		if ( ! empty( $meta_data ) ) {
			if ( ! is_array( $meta_data ) ) $meta_data_array[] = $meta_data;
			else $meta_data_array = $meta_data;
		}

		$meta_data_array[] = $meta_value;

		update_post_meta( $order_id, $meta_key, $meta_data_array );
	}

	/**
	 * Create a plan via API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function create_plan( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'plans' );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}

		$this->set_id( $response->id );

		if ( $this->get_order_id() ) {
			$this->update_id_in_meta( '_stripe_plan_id', $response->id );

			//save response
			$plan_response = array( $response->id => $response );
			$this->update_id_in_meta( '_stripe_plan_response', $plan_response );
		}

		return $response->id;
	}

	/**
	 * Updates the Stripe plan through the API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function update_plan( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'plans/' . $this->get_id() );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}
	}

	/**
	 * Cancel the Stripe subscription through the API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function cancel_plan( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'plans/' . $this->get_id(), 'DELETE' );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}
	}

}
