<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements mail features of S2 Customer Subscription
 *
 * @class   S2_Customer_Subscription
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Customer_Subscription' ) ) {

	class S2_Customer_Subscription extends WC_Email {

		/**
		 * Constructor method, used to return object of the class to WC
		 */
		public function __construct() {

			// Send a copy to admin?
			$this->send_to_admin = $this->get_option( 'send_to_admin' );

			// Triggers for this email
			$this->template_base = S2_WS_TEMPLATE_PATH . '/';
			$this->email_type    = 'html';
			$this->template_html = 'emails/' . $this->id . '.php';

			add_action( $this->id . '_mail_notification', [ $this, 'trigger' ], 15 );

			// Call parent constructor
			parent::__construct();

			$this->customer_email = true;
			// Other settings
			$this->recipient = $this->get_option( 'recipient' );

			if ( ! $this->recipient ) {
				$this->recipient = get_option( 'admin_email' );
			}

			if ( ! $this->email_type ) {
				$this->email_type = 'html';
			}

		}

		/**
		 * Method triggered to send email
		 *
		 * @param int $subscription
		 *
		 * @return void
		 */
		public function trigger( $subscription ) {

			$this->recipient = $subscription->get_billing_email();

			s2_subscription_log( $this->id . "--is_enabled--" . $this->is_enabled(), 'subscription_email' );
			s2_subscription_log( $this->id . "--get_recipient--" . $this->get_recipient(), 'subscription_email' );

			// Check if this email type is enabled, recipient is set
			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			$this->object = $subscription;

			$return       = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content_html(), $this->get_headers(), $this->get_attachments() );
		}

		/**
		 * Get HTML content for the mail
		 *
		 * @return string HTML content of the mail
		 */
		public function get_content_html() {
			ob_start();
			wc_get_template(
				$this->template_html,
				[
					'subscription'  => $this->object,
					'email_heading' => $this->get_heading(),
					'sent_to_admin' => true,
					'plain_text'    => false,
					'email'         => $this,
				],
				'',
				$this->template_base
			);

			return ob_get_clean();
		}

		/**
		 * Get email headers.
		 *
		 * @return string
		 */
		public function get_headers() {
			$header = 'Content-Type: ' . $this->get_content_type() . "\r\n";

			if ( $this->get_from_address() && $this->get_from_name() ) {
				$header .= 'Reply-to: ' . $this->get_from_name() . ' <' . $this->get_from_address() . ">\r\n";
			}

			if ( $this->send_to_admin == 'yes' ) {
				$admin_email = get_option('admin_email');
				s2_subscription_log( $this->id . "--admin_email--" . $admin_email, 'subscription_email' );

				$header .= 'Bcc: ' . $admin_email . "\r\n";
			}

			return $header;
		}

		/**
		 * Initialise settings form fields
		 *
		 * @access public
		 * @return void
		 */
		function init_form_fields() {
			$this->form_fields = [
				'enabled'       => [
					'title'   => __( 'Enable/Disable', 's2-subscription' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable notification for this type of emails', 's2-subscription' ),
					'default' => 'yes',
				],
				'subject'       => [
					'title'       => __( 'Subject', 's2-subscription' ),
					'type'        => 'text',
					'description' => sprintf( __( 'Defaults to %s' ), $this->subject ),
					'placeholder' => '',
					'default'     => '',
				],
				'send_to_admin' => [
					'title'   => __( 'Send to admin?', 's2-subscription' ),
					'type'    => 'checkbox',
					'label'   => __( 'Send a copy of this email to admin', 's2-subscription' ),
					'default' => 'no',
				],
				'heading'       => [
					'title'       => __( 'Email heading', 's2-subscription' ),
					'type'        => 'text',
					'description' => sprintf( __( 'Defaults to <code>%s</code>', 's2-subscription' ), $this->heading ),
					'placeholder' => '',
					'default'     => '',
				],

			];
		}
	}
}

