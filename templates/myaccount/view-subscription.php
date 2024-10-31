<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Subscription details
 *
 * @package S2 Subscription\Templates
 * @version 1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */

wc_print_notices();

if ( $subscription->user_id != get_current_user_id() ) {
	esc_html_e( 'You do not have permissions necessary to access to this page', 's2-subscription' );

	return;
}

$next_payment_due_date = ( ! in_array( $subscription->status, [ 'paused', 'cancelled' ] ) && $subscription->payment_due_date ) ? esc_html( wc_date_format(), $subscription->payment_due_date ) : '';

$payment_gateways = WC()->payment_gateways();
$payment_gateways = $payment_gateways->payment_gateways();
$payment_gateway  = $payment_gateways[ $subscription->payment_method ];
?>

<h2><?php esc_html_e( 'Subscription Detail', 's2-subscription' ); ?></h2>
<p>
	<strong><?php esc_html_e( 'Recurring: ', 's2-subscription' ); ?></strong> <?php echo wp_kses_post( $subscription->get_formatted_recurring() ); ?>
	<br>
	<strong><?php esc_html_e( 'Status: ', 's2-subscription' ); ?></strong> <?php echo esc_html( $subscription->status ); ?>

	<?php if ( $subscription->start_date ) : ?>
		<br>
		<strong><?php esc_html_e( 'Start date: ', 's2-subscription' ); ?></strong> <?php echo esc_html( date_i18n( wc_date_format(), $subscription->start_date ) ); ?>
	<?php endif; ?>

	<?php if ( ! in_array( $subscription->status, [ 'paused', 'cancelled' ] ) && $subscription->payment_due_date ) : ?>
		<br>
		<strong><?php esc_html_e( 'Next Payment Due Date: ', 's2-subscription' ); ?></strong> <?php echo esc_html( date_i18n( wc_date_format(), $subscription->payment_due_date ) ); ?>
	<?php endif; ?>

	<?php if ( $subscription->status == 'paused' && $subscription->expired_pause_date ) : ?>
		<br><strong><?php esc_html_e( 'Pause expiry date: ', 's2-subscription' ); ?></strong> <?php echo esc_html( date_i18n( wc_date_format(), $subscription->expired_pause_date ) ); ?>
	<?php endif; ?>

	<?php if ( $subscription->expired_date ) : ?>
		<br>
		<strong><?php esc_html_e( 'Subscription expiry date: ', 's2-subscription' ); ?></strong> <?php echo esc_html( date_i18n( wc_date_format(), $subscription->expired_date ) ); ?>
	<?php endif; ?>

	<?php if ( $subscription->end_date ) : ?>
		<br>
		<strong><?php esc_html_e( 'End Date: ', 's2-subscription' ); ?></strong> <?php echo esc_html( date_i18n( wc_date_format(), $subscription->end_date ) ); ?>
	<?php endif; ?>

</p>

<p>
	<?php if ( $payment_gateway->supports( 's2_subscription_cancelled' ) && ! empty( $s2ws_settings['allow_customer_cancel_subscription'] ) && $s2ws_settings['allow_customer_cancel_subscription'] == 'yes' && 'cancelled' != $subscription->status && 'expired' != $subscription->status ) : ?>

		<td class="s2-subscription-action-delete" data-title="<?php esc_attr_e( 'Cancel', 's2-subscription' ); ?>">
		
			<a href="<?php echo esc_url( $subscription->get_change_status_link( "cancelled" ) ); ?>" onclick="return confirm('Are you sure to cancel subscription?')" class="woocommerce-button button view" data-id="<?php echo esc_attr( $subscription->id ); ?>"><?php echo esc_html( __( 'Cancel', 's2-subscription' ) ); ?></a>

		</td>
	
	<?php endif; ?>

	<?php if ( $payment_gateway->supports( 's2_subscription_paused' ) && ! empty( $s2ws_settings['allow_customer_pause_subscription'] ) && $s2ws_settings['allow_customer_pause_subscription'] == 'yes' && 'active' == $subscription->status ) : ?>

		<td class="s2-subscription-action-delete" data-title="<?php esc_attr_e( 'Pause', 's2-subscription' ); ?>">

			<a href="<?php echo esc_url( $subscription->get_change_status_link( 'paused' ) ); ?>" onclick="return confirm('Are you sure to pause subscription?')" class="woocommerce-button button view"><?php echo esc_html( __( 'Pause', 's2-subscription' ) ); ?></a>
		
		</td>
	
	<?php endif; ?>

	<?php if ( $payment_gateway->supports( 's2_subscription_resumed' ) && ! empty( $s2ws_settings['allow_customer_resume_subscription'] ) && $s2ws_settings['allow_customer_resume_subscription'] == 'yes' && 'paused' == $subscription->status ) : ?>

		<td class="s2-subscription-action-delete" data-title="<?php esc_attr_e( 'Resume', 's2-subscription' ); ?>">
		
			<a href="<?php echo esc_url( $subscription->get_change_status_link( 'resumed' ) ); ?>" onclick="return confirm('Are you sure to resume subscription?')" class="woocommerce-button button view"><?php echo esc_html( __( 'Resume', 's2-subscription' ) ); ?></a>
		
		</td>
	
	<?php endif; ?>
</p>

<table class="shop_table order_details">
	<thead>
	<tr>
		<th class="product-name"><?php esc_html_e( 'Product', 's2-subscription' ); ?></th>
		<th class="product-total"><?php esc_html_e( 'Total', 's2-subscription' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<tr class="order_item">
		<td class="product-name">
			<a href="<?php echo esc_url( get_permalink( $subscription->product_id ) ); ?>"><?php echo wp_kses_post( $subscription->product_name ); ?></a><?php echo ' x ' . esc_html( $subscription->quantity ); ?>
			<?php
			if ( $subscription->variation_id ) {
				$subscription->get_product_meta();
			}
			?>
		</td>
		<td class="product-total">
			<?php echo wp_kses_post( wc_price( $subscription->line_total, [ 'currency' => $subscription->order_currency ] ) ); ?>
		</td>
	</tr>

	</tbody>
	<tfoot>
	<?php if ( $subscription->line_tax != 0 ) : ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Item Tax:', 's2-subscription' ); ?></th>
			<td><?php echo wp_kses_post( wc_price( $subscription->line_tax, [ 'currency' => $subscription->order_currency ] ) ); ?></td>
		</tr>
	<?php endif; ?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Subtotal:', 's2-subscription' ); ?></th>
		<td><?php echo wp_kses_post( wc_price( $subscription->line_total + $subscription->line_tax, [ 'currency' => $subscription->order_currency ] ) ); ?></td>
	</tr>

	<?php
	if ( ! empty( $subscription->subscriptions_shippings ) ) :
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Shipping:', 's2-subscription' ); ?></th>
			<td><?php echo wp_kses_post( wc_price( $subscription->subscriptions_shippings['cost'], [ 'currency' => $subscription->order_currency ] ) . sprintf( __( '<small> via %s</small>', 's2-subscription' ), $subscription->subscriptions_shippings['name'] ) ); ?></td>
		</tr>
		<?php
		if ( ! empty( $subscription->order_shipping_tax ) ) :
			?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Shipping Tax:', 's2-subscription' ); ?></th>
				<td><?php echo wp_kses_post( wc_price( $subscription->order_shipping_tax, [ 'currency' => $subscription->order_currency ] ) ); ?></td>
			</tr>
			<?php
		endif;
	endif;
	?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Total:', 's2-subscription' ); ?></th>
		<td><?php echo wp_kses_post( wc_price( $subscription->subscription_total, [ 'currency' => $subscription->order_currency ] ) ); ?></td>
	</tr>
	</tfoot>
</table>

<?php if ( ! empty( $subscription->order_ids ) ) : ?>

	<h2><?php esc_html_e( 'Related Orders', 's2-subscription' ); ?></h2>
	<table class="shop_table my_account_orders shop_table_responsive">
		<thead>
		<tr>
			<th class="order-number woocommerce-orders-table__header"><?php esc_html_e( 'ID', 's2-subscription' ); ?></th>
			<th class="order-date woocommerce-orders-table__header"><?php esc_html_e( 'Date', 's2-subscription' ); ?></th>
			<th class="order-status woocommerce-orders-table__header"><?php esc_html_e( 'Status', 's2-subscription' ); ?></th>
			<th class="order-total woocommerce-orders-table__header"><?php esc_html_e( 'Total', 's2-subscription' ); ?></th>
			<th class="order-actions woocommerce-orders-table__header"></th>
		</tr>
		</thead>
		<tbody>

		<?php
		foreach ( $subscription->order_ids as $order_id ) :
			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				continue;
			}

			$item_count = $order->get_item_count();
			$order_date = $order->get_date_created();
			?>
			<tr>
				<td class="order-number woocommerce-orders-table__cell" data-title="<?php esc_attr_e( 'Order Number', 'woocommerce-subscriptions' ); ?>">
					<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
						<?php echo sprintf( esc_html_x( '#%s', 'hash before order number', 's2-subscriptions' ), esc_html( $order->get_order_number() ) ); ?>
					</a>
				</td>
				<td class="order-date woocommerce-orders-table__cell" data-title="<?php esc_attr_e( 'Date', 's2-subscription' ); ?>">
					<time datetime="<?php echo esc_attr( $order_date->date( 'Y-m-d' ) ); ?>" title="<?php echo esc_attr( $order_date->getTimestamp() ); ?>"><?php echo wp_kses_post( $order_date->date_i18n( wc_date_format() ) ); ?></time>
				</td>
				<td class="order-status woocommerce-orders-table__cell" data-title="<?php esc_attr_e( 'Status', 's2-subscription' ); ?>" style="white-space:nowrap;">
					<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
				</td>
				<td class="order-total woocommerce-orders-table__cell" data-title="<?php echo esc_attr_x( 'Total', 'Used in data attribute. Escaped', 's2-subscription' ); ?>">
					<?php
					// translators: $1: formatted order total for the order, $2: number of items bought
					echo wp_kses_post( sprintf( _n( '%1$s for %2$d item', '%1$s for %2$d items', $item_count, 's2-subscription' ), $order->get_formatted_order_total(), $item_count ) );
					?>
				</td>
				<td class="order-actions woocommerce-orders-table__cell">
					<?php
					$actions = [];

					if ( $order->needs_payment() ) {
						/*$actions['pay'] = [
							'url'  => $order->get_checkout_payment_url(),
							'name' => __( 'Pay', 's2-subscription' ),
						];*/
					}

					if ( in_array( $order->get_status(), apply_filters( 'woocommerce_valid_order_statuses_for_cancel', [ 'pending', 'failed' ], $order ) ) ) {
						/*$actions['cancel'] = [
							'url'        => $order->get_cancel_order_url( wc_get_page_permalink( 'myaccount' ) ),
							'name'       => __( 'Cancel', 's2-subscription' ),
							'attributes' => [
								'data-expired' => $next_payment_due_date,
							],
						];*/
					}

					$actions['view'] = [
						'url'  => $order->get_view_order_url(),
						'name' => __( 'View', 's2-subscription' ),
					];

					$actions = apply_filters( 'woocommerce_my_account_my_orders_actions', $actions, $order );

					if ( $actions ) {
						foreach ( $actions as $key => $action ) {
							$attribute_data = '';
							if ( isset( $action['attributes'] ) ) {
								foreach ( $action['attributes'] as $key1 => $attribute ) {
									$attribute_data .= ' ' . $key1 . '="' . $attribute . '"';
								}
							}
							echo '<a href="' . esc_url( $action['url'] ) . '" class="button ' . sanitize_html_class( $key ) . '" ' . $attribute_data . '>' . esc_html( $action['name'] ) . '</a>';
						}
					}
					?>
				</td>
			</tr>
		<?php endforeach ?>
		</tbody>
	</table>
<?php endif; ?>

<div class="woocommerce-customer-details">
	<header><h2><?php esc_html_e( 'Customer Details', 's2-subscription' ); ?></h2></header>

	<?php
	$billing_address  = $subscription->get_address_fields( 'billing', true );
	$shipping_address = $subscription->get_address_fields( 'shipping', true );
	?>

	<table class="shop_table shop_table_responsive customer_details">
		<?php if ( $billing_address['email'] ) : ?>
			<tr>
				<th><?php esc_html_e( 'Email:', 's2-subscription' ); ?></th>
				<td><?php echo esc_html( $billing_address['email'] ); ?></td>
			</tr>
		<?php endif; ?>

		<?php if ( $billing_address['phone'] ) : ?>
			<tr>
				<th><?php esc_html_e( 'Telephone:', 's2-subscription' ); ?></th>
				<td><?php echo esc_html( $billing_address['phone'] ); ?></td>
			</tr>
		<?php endif; ?>
	</table>

	<div class="col2-set addresses">
		<div class="col-1">
			<header class="title">
				<h3><?php esc_html_e( 'Billing Address', 's2-subscription' ); ?></h3>
			</header>
			<address>
				<?php echo wp_kses_post( WC()->countries->get_formatted_address( $billing_address ) ); ?>
			</address>
		</div>
		<div class="col-2">
			<header class="title">
				<h3><?php esc_html_e( 'Shipping Address', 's2-subscription' ); ?></h3>
			</header>
			<address>
				<?php echo wp_kses_post( WC()->countries->get_formatted_address( $shipping_address ) ); ?>
			</address>
		</div>
	</div>
</div>
