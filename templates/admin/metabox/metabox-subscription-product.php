<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Metabox for Subscription Items details
 *
 * @package S2 Subscription\Templates
 * @version 1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */

$product      = wc_get_product( $subscription->product_id );
$product_link = $product ? admin_url( 'post.php?post=' . $subscription->product_id . '&action=edit' ) : '';
$thumbnail    = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', [ 'title' => '' ], false ), $subscription->product_id, $product ) : '';

$order = wc_get_order( $subscription->order_id );
if ( ! $order ) {
	return;
}
?>
<div id="woocommerce-order-items">
    <div class="woocommerce_order_items_wrapper wc-order-items-editable">
        <table cellpadding="0" cellspacing="0" class="woocommerce_order_items s2_subscription_items_list">
            <thead>
            <tr>
                <th class="s2_subscription_items_list_item"
                    colspan="2"><?php esc_html_e( 'Item', 's2-subscription' ); ?></th>
                <th class="s2_subscription_items_list_quantity"><?php esc_html_e( 'Cost', 's2-subscription' ); ?></th>
                <th class="s2_subscription_items_list_quantity"><?php esc_html_e( 'Qty', 's2-subscription' ); ?></th>
                <th class="s2_subscription_items_list_total"><?php esc_html_e( 'Total', 's2-subscription' ); ?></th>
                <th class="s2_subscription_items_list_tax"><?php esc_html_e( 'Tax', 's2-subscription' ); ?></th>
                <th class="wc-order-edit-line-item" width="1%"></th>
            </tr>
            </thead>

            <tbody id="order_line_items">

            <tr class="item">
                <td class="thumb">
					<?php echo '<div class="wc-order-item-thumbnail">' . wp_kses_post( $thumbnail ) . '</div>'; ?>
                </td>
                <td class="name s2_subscription_items_list_item">
					<?php
					echo $product_link ? '<a href="' . esc_url( $product_link ) . '" class="wc-order-item-name">' . wp_kses_post( $subscription->product_name ) . '</a>' : '<div class="wc-order-item-name">' . esc_html( $subscription->product_name ) . '</div>';

					$text_align  = is_rtl() ? 'right' : 'left';
					$margin_side = is_rtl() ? 'left' : 'right';
					$item        = $order->get_item( $subscription->order_item_id );

					wc_display_item_meta( $item,
                                        [
                							'label_before' => '<strong class="wc-item-meta-label" style="float: ' . esc_attr( $text_align ) . '; margin-' . esc_attr( $margin_side ) . ': .25em; clear: both">',
                						] );

					if ( $product && $product->get_sku() ) {
						echo '<div class="wc-order-item-sku"><strong>' . esc_html__( 'SKU:', 's2-subscription' ) . '</strong> ' . esc_html( $product->get_sku() ) . '</div>';
					}

					if ( $subscription->variation_id ) {
						echo '<div class="wc-order-item-variation"><strong>' . esc_html__( 'Variation ID:', 's2-subscription' ) . '</strong> ';
						if ( 'product_variation' === get_post_type( $subscription->variation_id ) ) {
							echo esc_html( $subscription->variation_id ) . '<br>';
							$subscription->get_product_meta( $subscription->variation );
						} else {
							/* translators: %s: variation id */
							printf( esc_html__( '%s (No longer exists)', 's2-subscription' ), esc_html( $subscription->variation_id ) );
						}
						echo '</div>';
					}
					?>
                </td>

                <td class="s2_subscription_items_list_cost item_cost" width="1%">
                    <div class="view">
						<?php
						$cost = $subscription->quantity ? floatval( $subscription->line_subtotal ) / floatval( $subscription->quantity ) : 0;
						echo wp_kses_post( wc_price( $cost, [ 'currency' => $subscription->order_currency ] ) );
						?>
                    </div>
                </td>
                <td class="quantity" width="1%">
                    <div class="view">
						<?php
						echo wp_kses_post( '<small class="times">&times;</small> ' ) . esc_html( $subscription->quantity );
						?>
                    </div>
                </td>
                <td class="line_cost s2_subscription_items_list_total" width="1%">
                    <div class="view">
						<?php echo wp_kses_post( wc_price( $subscription->line_total, [ 'currency' => $subscription->order_currency ] ) ); ?>

                        <?php
                        if ( $subscription->line_subtotal !== $subscription->line_total ) {
                            
                            echo '<span class="wc-order-item-discount">' . sprintf( esc_html__( '%s discount', 's2-subscription' ), wc_price( wc_format_decimal( $subscription->line_subtotal - $subscription->line_total, '' ), [ 'currency' => $order->get_currency() ] ) ) . '</span>';

                        }
                        ?>
                    </div>
                </td>
                <td class="line_cost s2_subscription_items_list_total" width="1%">
                    <div class="view">
						<?php echo wp_kses_post( wc_price( $subscription->line_tax, [ 'currency' => $subscription->order_currency ] ) ); ?>
                    </div>
                </td>
            </tr>
            </tbody>
            <tbody class="order_shipping_line_items">
			<?php if ( $subscription->payment_type != 'split_pay' && ! empty( $subscription->subscriptions_shippings ) ) : ?>
                <tr class="shipping">
                    <td class="thumb">
                        <div></div>
                    </td>
					<?php if ( isset( $subscription->subscriptions_shippings['name'] ) ) : ?>
                        <td class="name">
                            <div class="view">
								<?php echo esc_html( $subscription->subscriptions_shippings['name'] ); ?>
                            </div>
                        </td>
					<?php endif; ?>
                    <td class="item_cost" width="1%">&nbsp;</td>
                    <td class="quantity" width="1%">&nbsp;</td>
                    <td class="line_cost" width="1%">
                        <div class="view">
							<?php echo wp_kses_post( wc_price( $subscription->order_shipping, [ 'currency' => $subscription->order_currency ] ) ); ?>
                        </div>
                    </td>
                    <td class="line_tax" width="1%">
                        <div class="view">
							<?php echo wp_kses_post( wc_price( $subscription->order_shipping_tax, [ 'currency' => $subscription->order_currency ] ) ); ?>
                        </div>
                    </td>
                </tr>
			<?php endif; ?>

            </tbody>
        </table>
    </div>

    <div class="wc-order-data-row wc-order-totals-items wc-order-items-editable">
        <table class="wc-order-totals">
			<?php if ( $subscription->payment_type != 'split_pay' && ! empty( $subscription->subscriptions_shippings ) ) : ?>
                <tr>
                    <td class="label"><?php esc_html_e( 'Shipping', 's2-subscription' ); ?>:</td>
                    <td width="1%"></td>
                    <td class="total">
						<?php
						echo wp_kses_post( wc_price( $subscription->order_shipping, [ 'currency' => $subscription->order_currency ] ) );
						?>
                    </td>
                </tr>
			<?php endif; ?>


			<?php if ( $subscription->payment_type != 'split_pay' && wc_tax_enabled() ) : ?>

                <tr>
                    <td class="label"><?php esc_html_e( 'Tax', 's2-subscription' ); ?>:</td>
                    <td width="1%"></td>
                    <td class="total">
						<?php
						echo wp_kses_post( wc_price( ( floatval( $subscription->order_shipping_tax ) + floatval( $subscription->order_tax ) ), [ 'currency' => $subscription->order_currency ] ) );
						?>
                    </td>
                </tr>

			<?php endif; ?>

            <tr>
                <td class="label"><?php esc_html_e( 'Total', 's2-subscription' ); ?>:</td>
                <td width="1%"></td>
                <td class="total">
					<?php
					echo wp_kses_post( wc_price( $subscription->subscription_total, [ 'currency' => $subscription->order_currency ] ) );
					?>
                </td>
            </tr>
        </table>
        <div class="clear"></div>
    </div>
</div>
