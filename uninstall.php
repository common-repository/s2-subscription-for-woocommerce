<?php
// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/**
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */

$option_name = 's2ws_settings';

delete_option( $option_name );

wp_clear_scheduled_hook( 's2_check_subscription_expired' );
wp_clear_scheduled_hook( 's2_check_stripe_subscription' );
wp_clear_scheduled_hook( 's2_check_subscription_trial_period' );
