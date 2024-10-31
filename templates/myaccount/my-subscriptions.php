<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * My Account Subscriptions Section of S2 WooCommerce Subscription
 *
 * @package S2 Subscription\Templates
 * @version 1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
?>

<?php if ( empty( $subscriptions ) ) : ?>

	<p><?php esc_html_e( 'There is no active subscription for your account.', 's2-subscription' ); ?></p>

<?php else : ?>

	<table class="shop_table my_account_orders shop_table_responsive">
		<thead>
		<tr>
			<th class="subscription-product woocommerce-orders-table__header"><?php esc_html_e( 'Product', 's2-subscription' ); ?></th>
			<th class="subscription-status woocommerce-orders-table__header"><?php esc_html_e( 'Status', 's2-subscription' ); ?></th>
			<th class="subscription-recurring woocommerce-orders-table__header"><?php esc_html_e( 'Recurring', 's2-subscription' ); ?></th>
			<th class="subscription-payment-date woocommerce-orders-table__header"><?php esc_html_e( 'Next Payment', 's2-subscription' ); ?></th>
			<th class="subscription-action-view woocommerce-orders-table__header"></th>
			<?php if ( ! empty( $s2ws_settings['allow_customer_cancel_subscription'] ) && $s2ws_settings['allow_customer_cancel_subscription'] == 'yes' ) : ?>
				<th class="s2-subscription-action-delete"></th>
			<?php endif; ?>
		</tr>
		</thead>
		<tbody>
		<?php
		$payment_gateways = WC()->payment_gateways();
		$payment_gateways = $payment_gateways->payment_gateways();

		foreach ( $subscriptions as $subscription_id ) :
			$subscription          = new S2_Subscription( $subscription_id );
			$next_payment_due_date = ( ! in_array( $subscription->status, [ 'paused', 'cancelled' ] ) && $subscription->payment_due_date ) ? date_i18n( wc_date_format(), $subscription->payment_due_date ) : '';
			$start_date            = ( $subscription->start_date ) ? date_i18n( wc_date_format(), $subscription->start_date ) : '';

			$payment_gateway  = $payment_gateways[ $subscription->payment_method ];
			?>

			<tr class="order woocommerce-orders-table__row">
				<td class="subscription-product" data-title="<?php esc_attr_e( 'Product', 's2-subscription' ); ?>">
					<a href="<?php echo esc_url( get_permalink( $subscription->product_id ) ); ?>"><?php echo wp_kses_post( $subscription->product_name ); ?></a><?php echo ' x ' . esc_html( $subscription->quantity ); ?>
				</td>

				<td class="subscription-status" data-title="<?php esc_attr_e( 'Status', 's2-subscription' ); ?>">
					<span class="status <?php echo esc_attr( $subscription->status ); ?>"><?php echo esc_html( $subscription->status ); ?></span>
				</td>

				<td class="subscription-recurring" data-title="<?php esc_attr_e( 'Recurring', 's2-subscription' ); ?>">
					<?php echo wp_kses_post( $subscription->get_formatted_recurring() ); ?>
				</td>

				<td class="subscription-payment-date" data-title="<?php esc_attr_e( 'Next Payment Due Date', 's2-subscription' ); ?>">
					<?php echo wp_kses_post( $next_payment_due_date ); ?>
				</td>

				<td class="subscription-action-view" data-title="<?php esc_attr_e( 'View', 's2-subscription' ); ?>">
					<?php
					$actions = [
						'view' => [
							'url'  => $subscription->get_view_subscription_url(),
							'name' => __( 'View', 's2-subscription' ),
						],
					];

					if ( $actions = apply_filters( 'woocommerce_my_account_my_subscriptions_actions', $actions, $subscription ) ) {
						foreach ( $actions as $key => $action ) {
							echo '<a href="' . esc_url( $action['url'] ) . '" class="woocommerce-button button view">' . esc_html( $action['name'] ) . '</a>';
						}
					}
					?>
				</td>
				<?php if ( $payment_gateway->supports( 's2_subscription_cancelled' ) && ! empty( $s2ws_settings['allow_customer_cancel_subscription'] ) && $s2ws_settings['allow_customer_cancel_subscription'] == 'yes' ) : ?>

					<td class="s2-subscription-action-delete" data-title="<?php esc_attr_e( 'Cancel', 's2-subscription' ); ?>">

						<?php if( 'cancelled' != $subscription->status && 'expired' != $subscription->status ) : ?>
							
							<a href="<?php echo esc_url( $subscription->get_change_status_link( "cancelled" ) ); ?>" onclick="return confirm('Are you sure to cancel subscription?')" class="woocommerce-button button view"><?php echo esc_html( __( 'Cancel', 's2-subscription' ) ); ?></a>

						<?php endif; ?>

					</td>
				
				<?php endif; ?>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

<?php endif; ?>
