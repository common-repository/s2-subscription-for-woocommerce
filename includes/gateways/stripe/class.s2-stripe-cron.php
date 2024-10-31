<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements product features of S2 Stripe Cron
 *
 * @class   S2_Stripe_Cron
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Stripe_Cron' ) ) {

	class S2_Stripe_Cron {

		/**
		 * @var string post_type
		 */
		public $post_type = 's2_subscription';

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Stripe_Cron
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Stripe_Cron
		 * @since 1.0.0
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
		 * @since  1.0.0
		 */
		public function __construct() {

			// stripe event
			require_once 'includes/class.s2-stripe-event.php';

			add_action( 'wp_loaded', array( $this, 'set_cron' ), 30 );

			add_action( 's2_check_stripe_subscription', array( $this, 's2_check_stripe_subscription' ) );

		}


		/**
		 * Set Cron
		 */
		public function set_cron() {

			$ve         = get_option( 'gmt_offset' ) > 0 ? '+' : '-';
			$time_start = strtotime( '00:00 ' . $ve . get_option( 'gmt_offset' ) . ' HOURS' );

			if ( ! wp_next_scheduled( 's2_check_stripe_subscription' ) ) {
				wp_schedule_event( $time_start, 'hourly', 's2_check_stripe_subscription' );
			}

		}

		/**
		 * Check stripe subscription payment
		 *
		 * @since  1.0.0
		 */
		public function s2_check_stripe_subscription() {

			// get last transaction id processed
			$stripe_last_transation_id = get_option( '_stripe_last_transation_id' );

			$stripe_event = new S2_Stripe_Event();

			$args = array(
						'limit' 		 => 100,
						// 'ending_before'  => 'evt_1IH5CzFVkaUA3zFuJ6qoTFTS',
						// 'starting_after' => 'evt_1IH5CzFVkaUA3zFuJ6qoTFTS',
						'types' 		 => [
												// 'customer.subscription.created',
												'customer.subscription.updated',
												'customer.subscription.deleted',
												'invoice.created',
												'invoice.finalized',
												'invoice.payment_succeeded',
												'charge.failed',
											],
					);
			if( ! empty( $stripe_last_transation_id ) ) $args['ending_before'] = $stripe_last_transation_id;

			$all_events = $stripe_event->get_all_events( $args );
			$all_events = $all_events->data;

			include_once 'includes/class.s2-stripe-custom-webhook-handler.php';
            $stripe_custom_webhook_handler = new S2_Stripe_Custom_Webhook_Handler();

            $all_events = array_reverse( $all_events );
			foreach ( $all_events as $event ) {
				$stripe_custom_webhook_handler->process_subscription_webhook( $event );
			}

		}

	}

}

/**
 * Unique access to instance of S2_Stripe_Cron class
 *
 * @return \S2_Stripe_Cron
 */
function S2_Stripe_Cron() {
	return S2_Stripe_Cron::get_instance();
}

S2_Stripe_Cron();
