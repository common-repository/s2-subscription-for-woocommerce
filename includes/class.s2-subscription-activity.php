<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements product features of S2 Subscription Activity
 *
 * @class   S2_Subscription_Activity
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Activity' ) ) {

	class S2_Subscription_Activity {

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Activity
		 */
		protected static $instance;

		/**
		 * @var array activities
		 */
		protected $activities;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_Activity
		 *
		 * @since  1.0.0
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
		 * @param array $args
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			$this->fill_activities();

			// remove activities if a subscription is deleted
			add_action( 'deleted_post', [ $this, 'delete_activities' ] );
		}

		/**
		 * Add new activity
		 *
		 * Initialize class and registers actions and filters to be used
		 *
		 * @param $subscription_id
		 * @param string $activity
		 * @param string $description
		 */
		public function add_activity( $subscription_id, $activity = '', $order_id = 0, $description = '' ) {
			$meta_data = get_post_meta( $subscription_id, 'activity', true );

			if ( empty( $meta_data ) ) {
				$meta_data = [];
			}

			$meta_value  = [
				'activity'       => $this->activities[ $activity ],
				'order_id'       => $order_id,
				'description'    => $description,
				'timestamp_date' => current_time( 'mysql' ),
			];
			$meta_data[] = $meta_value;

			update_post_meta( $subscription_id, 'activity', $meta_data );
		}

		/**
		 *
		 */
		public function fill_activities() {
			$this->activities = [
				'payment-received' => _x( 'Subscription Payment Received', 'subscription payment received', 's2-subscription' ),
				'new'            => _x( 'New Subscription', 'new subscription has been created', 's2-subscription' ),
				'renew-order'    => _x( 'Renewal Order', 'new order has been created for the subscription', 's2-subscription' ),
				'activated'      => _x( 'Subscription Activated', '', 's2-subscription' ),
				'trial'          => _x( 'Started Trial Period', '', 's2-subscription' ),
				'cancelled'      => _x( 'Cancelled Subscription', 'subscription cancelled by shop manager or customer', 's2-subscription' ),
				'auto-cancelled' => _x( 'Auto Cancelled Subscription', 'subscription cancelled by system', 's2-subscription' ),
				'expired'        => _x( 'Subscription Expired', 'subscription expired', 's2-subscription' ),
				'switched'       => _x( 'Subscription Switched to another subscription', 'subscription switched', 's2-subscription' ),
				'resumed'        => _x( 'Subscription Resumed', 'subscription resumed by shop manager or customer', 's2-subscription' ),
				'auto-resumed'   => _x( 'Subscription Automatic Resumed', 'subscription resumed for expired pause', 's2-subscription' ),
				'paused'         => _x( 'Subscription Paused', 'subscription paused by shop manager or customer', 's2-subscription' ),
				'suspended'      => _x( 'Subscription Suspended', 'subscription suspended automatically due to non-payment', 's2-subscription' ),
				'overdue'      	 => _x( 'Subscription Overdue', 'subscription overdue automatically due to non-payment', 's2-subscription' ),
				'failed-payment' => _x( 'Failed Payment', 'subscription failed payment', 's2-subscription' ),
				'trashed'        => _x( 'Subscription Trashed', 'subscription was trashed', 's2-subscription' ),
				'changed'        => _x( 'Subscription Changed', 'subscription was changed', 's2-subscription' ),

			];
		}

		/**
		 * @param $subscription_id
		 *
		 * @return array|null|string
		 */
		public function get_activity_by_subscription( $subscription_id ) {
			return get_post_meta( $subscription_id, 'activity', true );
		}


		/**
		 * @param $subscription_id
		 *
		 * @return bool
		 */
		public function remove_activities_of_subscription( $subscription_id ) {
			return delete_post_meta( $subscription_id, 'activity' );
		}

		/**
		 * Delete all activities of a subscription
		 *
		 * @param $post_id
		 *
		 * @internal param $post
		 */
		public function delete_activities( $post_id ) {
			$post = get_post( $post_id );
			if ( $post && $post->post_type == 's2_subscription' ) {
				$this->remove_activities_of_subscription( $post->ID );
			}
		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Activity class
 *
 * @return \S2_Subscription_Activity
 */
function S2_Subscription_Activity() {
	return S2_Subscription_Activity::get_instance();
}

S2_Subscription_Activity();
