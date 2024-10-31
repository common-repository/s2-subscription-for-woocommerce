<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Implements features of S2 Subscription Log
 *
 * @class   S2_Subscription_Logger
 * @package S2 Subscription
 * @since   1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
class S2_Subscription_Logger {

	const LOG_FILENAME = WP_CONTENT_DIR . '/debug.log';

	/**
	 * Log message
	 *
	 * @since 1.0.0
	 */
	public static function log( $message ) {

		$settings = get_option( 's2ws_settings' );

		if ( empty( $settings ) || empty( $settings['enable_log'] ) || isset( $settings['enable_log'] ) && 'yes' !== $settings['enable_log'] ) {
			return;
		}

		$log_entry  = "\n" . '====S2 Subscription Version: ' . S2_WS_VERSION . '====' . "\n";
		$log_entry .= '====Start Log====' . "\n" . $message . "\n" . '====End Log====' . "\n\n";

		error_log( $log_entry, 3, self::LOG_FILENAME );

	}

}
