<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Paypal API Exception
 *
 * @class   S2_PayPal_API_Exception
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
class S2_PayPal_API_Exception extends Exception {

	/**
	 * List of errors from PayPal API.
	 *
	 * @var array
	 */
	public $errors;

	/**
	 * Unique identifier of PayPal transaction.
	 *
	 * This identifies the PayPal application that processed the request and
	 * must be provided to Merchant Technical Support if you need their assistance
	 * with a specific transaction.
	 *
	 * @var string
	 */
	public $correlation_id;

	/**
	 * Constructor.
	 *
	 * This constructor takes the API response received from PayPal, parses out the
	 * errors in the response, then places those errors into the $errors property.
	 * It also captures correlation ID and places that in the $correlation_id property.
	 *
	 * @param array $response Response from PayPal API
	 */
	public function __construct( $response ) {
		parent::__construct( __( 'An error occurred while calling the PayPal API.', 's2-subscription' ) );

		$errors = array();
		foreach ( $response as $index => $value ) {
			if ( preg_match( '/^L_ERRORCODE(\d+)$/', $index, $matches ) ) {
				$errors[ $matches[1] ]['code'] = $value;
			} elseif ( preg_match( '/^L_SHORTMESSAGE(\d+)$/', $index, $matches ) ) {
				$errors[ $matches[1] ]['message'] = $value;
			} elseif ( preg_match( '/^L_LONGMESSAGE(\d+)$/', $index, $matches ) ) {
				$errors[ $matches[1] ]['long'] = $value;
			} elseif ( preg_match( '/^L_SEVERITYCODE(\d+)$/', $index, $matches ) ) {
				$errors[ $matches[1] ]['severity'] = $value;
			} elseif ( 'CORRELATIONID' === $index ) {
				$this->correlation_id = $value;
			}
		}

		// paypal sends error in body
		if ( empty( $errors ) ) {

			$response_body = explode( "&", $response['body'] );
			foreach ( $response_body as $response ) {

				$response = explode( "=", $response );
				$index	  = $response[0];
				$value	  = $response[1];

				if ( preg_match( '/^L_ERRORCODE(\d+)$/', $index, $matches ) ) {
					$errors[ $matches[1] ]['code'] = $value;
				} elseif ( preg_match( '/^L_SHORTMESSAGE(\d+)$/', $index, $matches ) ) {
					$errors[ $matches[1] ]['message'] = $value;
				} elseif ( preg_match( '/^L_LONGMESSAGE(\d+)$/', $index, $matches ) ) {
					$errors[ $matches[1] ]['long'] = $value;
				} elseif ( preg_match( '/^L_SEVERITYCODE(\d+)$/', $index, $matches ) ) {
					$errors[ $matches[1] ]['severity'] = $value;
				} elseif ( 'CORRELATIONID' === $index ) {
					$this->correlation_id = $value;
				}

			}

		}

		$this->errors   = array();
		$error_messages = array();
		foreach ( $errors as $value ) {
			$error          = new S2_PayPal_API_Error( $value['code'], $value['message'], $value['long'], $value['severity'] );
			$this->errors[] = $error;

			/* translators: placeholders are error code and message from PayPal */
			$error_messages[] = sprintf( __( 'PayPal error (%1$s): %2$s', 's2-subscription' ), $error->error_code, $error->maptoBuyerFriendlyError() );
		}

		if ( empty( $error_messages ) ) {
			$error_messages[] = __( 'An error occurred while calling the PayPal API.', 's2-subscription' );
		}

		$this->message = implode( PHP_EOL, $error_messages );
	}

}
