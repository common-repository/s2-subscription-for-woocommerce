<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * This is the email sent to the customer when his subscription is in overdue
 *
 * @package S2 Subscription\Templates
 * @version 1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p><?php printf( esc_html( __( 'Your recent subscription on %s is late for payment.', 's2-subscription' ) ), wp_kses_post( get_option( 'blogname' ) ) ); ?></p>

<h2>
	<a class="link" href="<?php echo esc_url( $subscription->get_view_subscription_url() ); ?>">
		<?php printf( esc_html( __( 'Subscription #%s', 's2-subscription' ) ), esc_html( $subscription->id ) ); ?>
	</a>
	(<?php printf( '<time datetime="%s">%s</time>', esc_html( date_i18n( 'c', time() ) ), esc_html( date_i18n( wc_date_format(), time() ) ) ); ?>)
</h2>

<?php
wc_get_template( 'emails/email-subscription-detail-table.php', [ 'subscription' => $subscription ], '', S2_WS_TEMPLATE_PATH . '/' );
?>

<?php
wc_get_template( 'emails/email-subscription-customer-details.php', [ 'subscription' => $subscription ], '', S2_WS_TEMPLATE_PATH . '/' );
?>

<?php
do_action( 'woocommerce_email_footer', $email );
