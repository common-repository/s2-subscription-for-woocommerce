<?php
/**
 * Plugin Name: S2 Subscription for WooCommerce
 * Plugin URI: 
 * Description: <code><strong>S2 Subscription for WooCommerce</strong></code> allows enabling subscription on your products. Perfect for any kind of products like simple, variable and so on.
 * Version: 1.0.2
 * Author: Shuban Studio <shuban.studio@gmail.com>
 * Author URI: https://shubanstudio.github.io/
 * Text Domain: s2-subscription
 * Domain Path: /languages/
 * WC requires at least: 4.7.0
 * WC tested up to: 5.6.0
 */

/**
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Define constants __
! defined( 'S2_WS_DIR' ) 			&& define( 'S2_WS_DIR', plugin_dir_path( __FILE__ ) );
! defined( 'S2_WS_VERSION' ) 		&& define( 'S2_WS_VERSION', '1.0.2' );
! defined( 'S2_WS_FILE' ) 			&& define( 'S2_WS_FILE', __FILE__ );
! defined( 'S2_WS_URL' ) 			&& define( 'S2_WS_URL', plugins_url( '/', __FILE__ ) );
! defined( 'S2_WS_ASSETS_URL' ) 	&& define( 'S2_WS_ASSETS_URL', S2_WS_URL . 'assets' );
! defined( 'S2_WS_TEMPLATE_PATH' ) 	&& define( 'S2_WS_TEMPLATE_PATH', S2_WS_DIR . 'templates' );
! defined( 'S2_WS_INC' ) 			&& define( 'S2_WS_INC', S2_WS_DIR . '/includes/' );
! defined( 'S2_WS_TEST_ON' ) 		&& define( 'S2_WS_TEST_ON', ( defined( 'WP_DEBUG' ) && WP_DEBUG ) );
if ( ! defined( 'S2_WS_SUFFIX' ) ) {
	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	define( 'S2_WS_SUFFIX', $suffix );
}

/**
 * Print a notice if WooCommerce is not installed.
 *
 * @since  1.0.0
 */
function s2_subscription_install_woocommerce_admin_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'S2 Subscription for WooCommerce is enabled but not effective. It requires WooCommerce in order to work.', 's2-subscription' ); ?></p>
	</div>
	<?php
}

/**
 * Print a notice if WooCommerce Stripe is not installed.
 *
 * @since  1.0.0
 */
function s2_subscription_install_woocommerce_stripe_admin_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'In order to work with Stripe gateway S2 Subscription for WooCommerce required WooCommerce Stripe plugin to be installed and active.', 's2-subscription' ); ?></p>
	</div>
	<?php
}

/**
 * Check WC installation update the database if necessary.
 *
 * @since  1.0.0
 */
function s2_subscription_install() {
	if ( ! function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 's2_subscription_install_woocommerce_admin_notice' );
	} else {

		if ( ! class_exists( 'WC_Gateway_Stripe' ) ) {
			add_action( 'admin_notices', 's2_subscription_install_woocommerce_stripe_admin_notice' );
		}

		load_plugin_textdomain( 's2-subscription', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		
		require_once S2_WS_INC . 'functions.s2-subscription.php';
		require_once S2_WS_INC . 'class.s2-subscription-logger.php';
		require_once S2_WS_INC . 'class.s2-subscription-helper.php';
		require_once S2_WS_INC . 'class.s2-subscription.php';
		require_once S2_WS_INC . 'class.s2-subscription-activity.php';
		require_once S2_WS_INC . 'class.s2-subscription-coupons.php';
		require_once S2_WS_INC . 'class.s2-subscription-gateway.php';
		require_once S2_WS_INC . 'class.s2-subscription-email.php';
		require_once S2_WS_INC . 'class.s2-subscription-cron.php';
		require_once S2_WS_INC . 'class.s2-subscription-admin.php';
		require_once S2_WS_INC . 'class.s2-subscription-frontend.php';

		if ( ! get_option( 's2_subscription_queue_flush_rewrite_rules' ) ) {
			update_option( 's2_subscription_queue_flush_rewrite_rules', 'yes' );
			flush_rewrite_rules();
		}
	}
}
add_action( 'plugins_loaded', 's2_subscription_install', 11 );

/**
 * Remove flush rewrite rule option.
 *
 * @since  1.0.0
 */
function s2_subscription_remove_flush_rewrite_rule_option() {
	delete_option( 's2_subscription_queue_flush_rewrite_rules' );
}
register_deactivation_hook( __FILE__, 's2_subscription_remove_flush_rewrite_rule_option' );
