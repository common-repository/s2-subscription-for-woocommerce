<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * HTML Template for Subscription Detail
 *
 * @package S2 Subscription\Templates
 * @version 1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
?>
<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
		<thead>
		<tr>
			<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Product', 's2-subscription' ); ?></th>
<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php esc_html_e( 'Subtotal', 's2-subscription' ); ?></th>
</tr>
</thead>
<tbody>
<tr>
	<td scope="col" style="text-align:left;">
		<a href="<?php echo esc_url( get_permalink( $subscription->product_id ) ); ?>"><?php echo wp_kses_post( $subscription->product_name ); ?></a><?php echo ' x ' . esc_html( $subscription->quantity ); ?>
		<?php
				$text_align  = is_rtl() ? 'right' : 'left';
				$margin_side = is_rtl() ? 'left' : 'right';
				$order       = wc_get_order( $subscription->order_id );
				if( ! empty( $order ) ) {

					$item        = $order->get_item( $subscription->order_item_id );

					wc_display_item_meta(
						$item,
						[
							'label_before' => '<strong class="wc-item-meta-label" style="float: ' . esc_attr( $text_align ) . '; margin-' . esc_attr( $margin_side ) . ': .25em; clear: both">',
						]
					);

				}
				?>
	</td>

	<td scope="col" style="text-align:left;"><?php echo wp_kses_post( wc_price( $subscription->line_total, [ 'currency' => $subscription->order_currency ] ) ); ?></td>
</tr>

</tbody>
<tfoot>
<?php if ( $subscription->line_tax != 0 ) : ?>
	<tr>
		<th scope="row"><?php esc_html_e( 'Item Tax:', 's2-subscription' ); ?></th>
		<td><?php echo wp_kses_post( wc_price( $subscription->line_tax, [ 'currency' => $subscription->order_currency ] ) ); ?></td>
	</tr>
<?php endif ?>
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
			<td colspan="2"><?php echo wp_kses_post( wc_price( $subscription->order_shipping_tax, [ 'currency' => $subscription->order_currency ] ) ); ?></td>
		</tr>
		<?php
	endif;
endif;
?>
<tr>
	<th scope="row"><?php esc_html_e( 'Total:', 's2-subscription' ); ?></th>
	<td colspan="2"><?php echo wp_kses_post( wc_price( $subscription->subscription_total, [ 'currency' => $subscription->order_currency ] ) ); ?></td>
</tr>
</tfoot>
</table>
