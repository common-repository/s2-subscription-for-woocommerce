<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
    exit;
}

/**
 * Metabox for Subscription Actions
 *
 * @package S2 Subscription\Templates
 * @version 1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
?>
<div id="submitpost" class="submitbox">

    <div id="minor-publishing">
    
        <select name="s2_subscription_actions">
    
            <option value=""><?php esc_html_e( 'Actions', 's2-subscription' ); ?></option>
			<?php if ( $payment_gateway->id == 'cod' 
                    && ( $subscription->status == 'active' || $subscription->status == 'overdue' || $subscription->status == 'suspended' ) ) { ?>
                <option value="payment-received"><?php esc_html_e( 'Payment Received', 's2-subscription' ); ?></option>
            <?php } ?>

            <?php if ( $payment_gateway->supports( 's2_subscription_paused' ) && $subscription->status == 'active' ) { ?>
                <option value="paused"><?php esc_html_e( 'Pause', 's2-subscription' ); ?></option>
			<?php } ?>

			<?php if ( $payment_gateway->supports( 's2_subscription_resumed' ) && $subscription->status == 'paused' ) { ?>
                <option value="resumed"><?php esc_html_e( 'Resume', 's2-subscription' ); ?></option>
			<?php } ?>

            <?php if ( $payment_gateway->id == 'cod' && $subscription->status == 'pending' ) { ?>
                <option value="active"><?php esc_html_e( 'Active', 's2-subscription' ); ?></option>
            <?php } ?>

			<?php if ( $payment_gateway->supports( 's2_subscription_cancelled' ) && $subscription->status != 'pending' ) { ?>
                <option value="cancelled"><?php esc_html_e( 'Cancel (with collecting outstanding amount)', 's2-subscription' ); ?></option>
			<?php } ?>

			<?php if ( $payment_gateway->supports( 's2_subscription_cancel_now' ) ) { ?>
                <option value="cancel-now"><?php esc_html_e( 'Cancel Now' . ( $subscription->status != 'pending' ? ' (without collecting outstanding amount)' : '' ), 's2-subscription' ); ?></option>
			<?php } ?>

			<?php if ( $payment_gateway->supports( 's2_subscription_cancel_with_refund' ) && $subscription->status != 'pending' ) { ?>
                <option value="cancel-with-refund"><?php esc_html_e( 'Cancel With Refund (Refund last transaction amount)', 's2-subscription' ); ?></option>
			<?php } ?>
    
        </select>
    
    </div>
    
    <div id="major-publishing-actions">
        <div id="publishing-action">
            <button type="submit" class="button button-primary" title="<?php esc_html_e( 'Apply', 's2-subscription' ); ?>" name="s2_subscription_button" value="actions"><?php esc_html_e( 'Processing', 's2-subscription' ); ?></button>
        </div>
    </div>
    
    <div class="clear"></div>

</div>
