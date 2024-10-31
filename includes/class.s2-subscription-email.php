<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements product features of S2 Subscription Email
 *
 * @class   S2_Subscription_Email
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Email' ) ) {

	class S2_Subscription_Email {

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Email
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \S2_Subscription_Email
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

			// email settings
			add_filter( 'woocommerce_email_classes', [ $this, 'add_woocommerce_emails' ] );
			add_action( 'woocommerce_init', [ $this, 'load_wc_mailer' ] );

		}

		/**
		 * Filters woocommerce available mails
		 *
		 * @param $emails array
		 *
		 * @access public
		 *
		 * @return array
		 */
		public function add_woocommerce_emails( $emails ) {

			require_once S2_WS_INC . 'emails/class.s2-customer-subscription.php';
			
			$emails['S2_Customer_Subscription_Activated'] = include_once S2_WS_INC . 'emails/class.s2-customer-subscription-activated.php';

			$emails['S2_Customer_Subscription_Paused']    = include_once S2_WS_INC . 'emails/class.s2-customer-subscription-paused.php';
			
			$emails['S2_Customer_Subscription_Resumed']   = include_once S2_WS_INC . 'emails/class.s2-customer-subscription-resumed.php';

			$emails['S2_Customer_Subscription_Overdue']   = include_once S2_WS_INC . 'emails/class.s2-customer-subscription-overdue.php';

			$emails['S2_Customer_Subscription_Suspended'] = include_once S2_WS_INC . 'emails/class.s2-customer-subscription-suspended.php';

			$emails['S2_Customer_Subscription_Expired']   = include_once S2_WS_INC . 'emails/class.s2-customer-subscription-expired.php';

			$emails['S2_Customer_Subscription_Cancelled'] = include_once S2_WS_INC . 'emails/class.s2-customer-subscription-cancelled.php';

			return $emails;
		}

		/**
		 * Loads WC Mailer when needed
		 *
		 * @access public
		 *
		 * @return void
		 */
		public function load_wc_mailer() {

			// Customers
			add_action(
				's2_customer_subscription_activated_mail',
				[
					'WC_Emails',
					'send_transactional_email',
				],
				10
			);

			add_action(
				's2_customer_subscription_paused_mail',
				[
					'WC_Emails',
					'send_transactional_email',
				],
				10
			);

			add_action(
				's2_customer_subscription_resumed_mail',
				[
					'WC_Emails',
					'send_transactional_email',
				],
				10
			);

			add_action(
				's2_customer_subscription_overdue_mail',
				[
					'WC_Emails',
					'send_transactional_email',
				],
				10
			);

			add_action(
				's2_customer_subscription_suspended_mail',
				[
					'WC_Emails',
					'send_transactional_email',
				],
				10
			);

			add_action(
				's2_customer_subscription_expired_mail',
				[
					'WC_Emails',
					'send_transactional_email',
				],
				10
			);

			add_action(
				's2_customer_subscription_cancelled_mail',
				[
					'WC_Emails',
					'send_transactional_email',
				],
				10
			);

		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Email class
*/
S2_Subscription_Email::get_instance();
