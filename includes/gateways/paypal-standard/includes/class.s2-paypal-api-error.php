<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Implements features of S2 Paypal API Error
 *
 * @class   S2_PayPal_API_Error
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Gateway_Paypal' ) ) {

	class S2_PayPal_API_Error {

		public $error_code;
		public $short_message;
		public $long_message;
		public $severity_code;

		/**
		 * Constructor
		 */
		public function __construct( $error_code, $short_message, $long_message, $severity_code ) {
			$this->error_code    = $error_code;
			$this->short_message = $short_message;
			$this->long_message  = $long_message;
			$this->severity_code = $severity_code;
		}

		public function mapToBuyerFriendlyError() {

			switch ( $this->error_code ) {

				case '-1':    return __( 'Unable to communicate with PayPal.  Please try your payment again.', 's2-subscription' );
				case '10407': return __( 'PayPal rejected your email address because it is not valid.  Please double-check your email address and try again.', 's2-subscription' );
				case '10409':
				case '10421':
				case '10410': return __( 'Your PayPal checkout session is invalid.  Please check out again.', 's2-subscription' );
				case '10411': return __( 'Your PayPal checkout session has expired.  Please check out again.', 's2-subscription' );
				case '11607':
				case '10415': return __( 'Your PayPal payment has already been completed.  Please contact the store owner for more information.', 's2-subscription' );
				case '10416': return __( 'Your PayPal payment could not be processed.  Please check out again or contact PayPal for assistance.', 's2-subscription' );
				case '10417': return __( 'Your PayPal payment could not be processed.  Please select an alternative method of payment or contact PayPal for assistance.', 's2-subscription' );
				case '10486':
				case '10422': return __( 'Your PayPal payment could not be processed.  Please return to PayPal and select a new method of payment.', 's2-subscription' );
				case '10485':
				case '10435': return __( 'You have not approved this transaction on the PayPal website.  Please check out again and be sure to complete all steps of the PayPal checkout process.', 's2-subscription' );
				case '10474': return __( 'Your shipping address may not be in a different country than your country of residence.  Please double-check your shipping address and try again.', 's2-subscription' );
				case '10537': return __( 'This store does not accept transactions from buyers in your country.  Please contact the store owner for assistance.', 's2-subscription' );
				case '10538': return __( 'The transaction is over the threshold allowed by this store.  Please contact the store owner for assistance.', 's2-subscription' );
				case '11611':
				case '10539': return __( 'Your transaction was declined.  Please contact the store owner for assistance.', 's2-subscription' );
				case '10725': return __( 'The country in your shipping address is not valid.  Please double-check your shipping address and try again.', 's2-subscription' );
				case '10727': return __( 'The street address in your shipping address is not valid.  Please double-check your shipping address and try again.', 's2-subscription' );
				case '10728': return __( 'The city in your shipping address is not valid.  Please double-check your shipping address and try again.', 's2-subscription' );
				case '10729': return __( 'The state in your shipping address is not valid.  Please double-check your shipping address and try again.', 's2-subscription' );
				case '10730': return __( 'The ZIP code or postal code in your shipping address is not valid.  Please double-check your shipping address and try again.', 's2-subscription' );
				case '10731': return __( 'The country in your shipping address is not valid.  Please double-check your shipping address and try again.', 's2-subscription' );
				case '10736': return __( 'PayPal rejected your shipping address because the city, state, and/or ZIP code are incorrect.  Please double-check that they are all spelled correctly and try again.', 's2-subscription' );
				case '13113':
				case '11084': return __( 'Your PayPal payment could not be processed.  Please contact PayPal for assistance.', 's2-subscription' );
				case '12126':
				case '12125': return __( 'The redemption code(s) you entered on PayPal cannot be used at this time.  Please return to PayPal and remove them.', 's2-subscription' );
				case '17203':
				case '17204':
				case '17200': return __( 'Your funding instrument is invalid.  Please check out again and select a new funding source.', 's2-subscription' );
				default:
					if( $this->long_message ) return __( urldecode( $this->long_message ), $this->error_code );
					else return sprintf( __( 'An error (%s) occurred while processing your PayPal payment.  Please contact the store owner for assistance.', 's2-subscription' ), $this->error_code );

			}

		}

	}

}
