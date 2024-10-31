<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * HTML Template for Customer Detail
 *
 * @package S2 Subscription\Templates
 * @version 1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */

$billing_address = $subscription->get_address_fields( 'billing', true );

?>
<h3><?php esc_html_e( 'Customer\'s details', 's2-subscription' ); ?></h3>

<?php if ( ! empty( $billing_address ) ) : ?>
	<p>
		<strong><?php esc_html_e( 'Address:', 's2-subscription' ); ?></strong><br>
		<?php echo wp_kses_post( WC()->countries->get_formatted_address( $billing_address ) ); ?>
	</p>
<?php endif; ?>

<?php if ( $billing_email = $subscription->get_billing_email() ) : ?>
	<p>
		<strong><?php esc_html_e( 'Email:', 's2-subscription' ); ?></strong> <?php echo esc_html( $billing_email ); ?>
	</p>
<?php endif; ?>

<?php if ( $billing_phone = $subscription->get_billing_phone() ) : ?>
	<p>
		<strong><?php esc_html_e( 'Telephone:', 's2-subscription' ); ?></strong> <?php echo esc_html( $billing_phone ); ?>
	</p>
<?php endif; ?>
