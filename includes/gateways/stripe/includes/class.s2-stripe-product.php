<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Stripe Product
 *
 * @class   S2_Stripe_Product
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
class S2_Stripe_Product {

	/**
	 * Stripe product ID
	 * @var string
	 */
	private $id = '';

	/**
	 * WP Product ID
	 * @var integer
	 */
	private $product_id = 0;

	/**
	 * Constructor
	 *
	 * @param int $product_id The WP Post ID
	 */
	public function __construct( $product_id = 0 ) {
		if ( $product_id ) {
			$this->set_product_id( $product_id );
			$this->set_id( $this->get_id_from_meta( $product_id, '_stripe_product_id' ) );
		}
	}

	/**
	 * Get Stripe product ID.
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set Stripe product ID.
	 *
	 * @param [type] $id [description]
	 */
	public function set_id( $id ) {
		$this->id = wc_clean( $id );
	}

	/**
	 * Product ID in WordPress.
	 * @return int
	 */
	public function get_product_id() {
		return absint( $this->product_id );
	}

	/**
	 * Set Product ID used by WordPress.
	 *
	 * @param int $product_id
	 */
	public function set_product_id( $product_id ) {
		$this->product_id = absint( $product_id );
	}

	/**
	 * Retrieves the Stripe Product ID from the product meta.
	 *
	 * @param int $product_id The ID of the WordPress post.
	 *
	 * @return string|bool Either the Stripe ID or false.
	 */
	public function get_id_from_meta( $product_id, $meta_key ) {
		return get_post_meta( $product_id, $meta_key, true );
	}

	/**
	 * Updates the current product with the right Stripe ID in the meta table.
	 *
	 * @param string $id The Stripe product ID.
	 */
	public function update_id_in_meta( $meta_key, $meta_value ) {
		update_post_meta( $this->get_product_id(), $meta_key, $meta_value );
	}

	/**
	 * Create a product via API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|String
	 */
	public function create_product( $args = array() ) {
		
		// if product exist in stripe return id
		if ( $this->id ) {
			$response = $this->retrieve_product( $this->id );

			if( ! empty( $response->id ) ) {
				return $this->id;
			}
		}

		// if product not exist in stripe create stripe product
		$response = WC_Stripe_API::request( $args, 'products' );

		if ( ! empty( $response->error ) ) {
			throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}

		$this->set_id( $response->id );

		if ( $this->get_product_id() ) {
			$this->update_id_in_meta( '_stripe_product_id', $response->id );
			$this->update_id_in_meta( '_stripe_product_response', $response );
		}

		return $response->id;

	}

	/**
	 * Retrieve the Stripe Product through the API.
	 *
	 * @param array $args
	 *
	 * @return WP_Error|Object
	 */
	public function retrieve_product( $args = array() ) {
		$response = WC_Stripe_API::request( $args, 'products/' . $this->get_id(), 'GET' );

		if ( ! empty( $response->error ) ) {
			// throw new WC_Stripe_Exception( print_r( $response, true ), $response->error->message );
		}

		return $response;
	}

}
