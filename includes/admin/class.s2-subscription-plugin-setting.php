<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements admin plugin setting of S2 Subscription
 *
 * @class   S2_Subscription_Plugin_Setting
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
if ( ! class_exists( 'S2_Subscription_Plugin_Setting' ) ) {

	class S2_Subscription_Plugin_Setting extends WC_Settings_API {

		/**
		 * Single instance of the class
		 *
		 * @var \S2_Subscription_Plugin_Setting
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @since  1.0.0
		 * @return \S2_Subscription_Plugin_Setting
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

			$this->plugin_id = 's2ws';

			// Load the settings.
			$this->init_form_fields();

			// $this->settings field has values from db
			$this->init_settings();

			// if $_POST is not empty save data in db
			if( ! empty( $_POST ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 's2ws-setting' ) ) {
				$this->process_admin_options();
			}

		}

		/**
		 * Initialize form fields for the admin
		 *
		 * @since  1.0.0
		 */
		public function init_form_fields() {
			$this->form_fields = include 'settings/general-settings.php';
		}

		/**
		 * Output the admin options table.
		 *
		 * @since  1.0.0
		 */
		public function admin_options() {
		?>

			<div class="wrap woocommerce">
				<form method="post" id="mainform" action="" enctype="multipart/form-data">
					<?php echo '<table class="form-table">' . $this->generate_settings_html( $this->get_form_fields(), false ) . '</table>'; ?>
					<p class="submit">
						<button name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 's2-subscription' ); ?>"><?php esc_html_e( 'Save changes', 's2-subscription' ); ?></button>
					</p>
					<?php wp_nonce_field( 's2ws-setting' ); ?>
				</form>
			</div>

		<?php
		}

	}

}

/**
 * Unique access to instance of S2_Subscription_Plugin_Setting class
 *
 * @return \S2_Subscription_Plugin_Setting
 */
function S2_Subscription_Plugin_Setting() {
	return S2_Subscription_Plugin_Setting::get_instance();
}
