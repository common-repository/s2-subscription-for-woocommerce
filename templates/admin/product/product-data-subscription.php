<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Product subscription data panel.
 *
 * @package S2 Subscription\Templates
 * @version 1.0.0
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */
?>

<div id="subscription_product_data" class="subscription-data show_if_simple options_group">

    <div class="options_group show_if_simple">

		<?php
		global $thepostid, $product_object;

		$woocommerce_currency_symbol = get_woocommerce_currency_symbol();

		$regular_price = $product_object->get_regular_price( 'edit' );
		$sale_price    = $product_object->get_sale_price( 'edit' );

		$s2_payment_type_meta             = get_post_meta( $thepostid, 's2_payment_type', true );
		$s2_billing_frequency_meta        = get_post_meta( $thepostid, 's2_billing_frequency', true );
		$s2_price_per_meta                = get_post_meta( $thepostid, 's2_price_is_per', true );
		$s2_price_time_option_meta        = get_post_meta( $thepostid, 's2_price_time_option', true );
		$s2_max_length_meta               = get_post_meta( $thepostid, 's2_max_length', true );
		$s2_sign_up_fee_meta              = get_post_meta( $thepostid, 's2_sign_up_fee', true );
		$s2_trial_per_meta                = get_post_meta( $thepostid, 's2_trial_per', true );
		$s2_trial_time_option_meta        = get_post_meta( $thepostid, 's2_trial_time_option', true );
		$s2_trial_period_meta             = get_post_meta( $thepostid, 's2_trial_period', true );
		$s2_split_payment_meta            = get_post_meta( $thepostid, 's2_split_payment', true );
		$s2_limit_quantity_available_meta = get_post_meta( $thepostid, 's2_limit_quantity_available', true );
		$s2_quantity_limit_meta           = get_post_meta( $thepostid, 's2_quantity_limit', true );

		woocommerce_wp_select( [
				'id'            => "s2_payment_type",
				'name'          => "s2_payment_type",
				'value'         => $s2_payment_type_meta,
				'class'         => 's2_payment_type',
				'wrapper_class' => 'show_if_simple',
				'label'         => __( 'Payment Type', 's2-subscription' ),
				'options'       => s2_get_payment_type_options(),
				'desc_tip'      => true,
				'description'   => __( 'Select payment type for product.', 's2-subscription' ),
			] );
		?>

		<?php
		$s2_billing_frequency_options = s2_get_billing_frequency_options();
		$s2_billing_frequency_values  = [];
		foreach ( $s2_billing_frequency_options as $key => $options ) {
			$s2_billing_frequency_values[ $key ] = $options['name'];
		}

		woocommerce_wp_select( [
				'id'            => "s2_billing_frequency",
				'name'          => "s2_billing_frequency",
				'value'         => $s2_billing_frequency_meta,
				'class'         => 's2_billing_frequency',
				'wrapper_class' => 'show_if_subscription',
				'label'         => __( 'Billing Frequency', 's2-subscription' ),
				'options'       => $s2_billing_frequency_values,
				'desc_tip'      => true,
				'description'   => __( 'Billing Frequency', 's2-subscription' ),
			] );
		?>

        <p class="form-field show_if_subscription">
            <label for="s2_max_length"><?php echo __( 'Number of rebills', 's2-subscription' ); ?></label>
            <span class="wrap">
				<input type="number" style="width: 80px; display: inline-block;" class="s2_max_length" id="s2_max_length" name="s2_max_length" value="<?php echo esc_attr( $s2_max_length_meta ); ?>">
				<span class="s2_max_length_description" style="margin-left: 2px;">
					<?php echo __( 'Leave it empty for unlimited subscription.', 's2-subscription' ); ?>
				</span>
			</span>
			<?php echo wc_help_tip( __( 'Automatically expire the subscription after this length of time. This length is in addition to any free trial or amount of time provided before a synchronised first renewal date. This length can not exceed: 90 days, 52 weeks, 24 months or 5 years.', 's2-subscription' ) ); ?>
        </p>

		<?php
		woocommerce_wp_text_input( [
				'id'            => 's2_sign_up_fee',
				'value'         => $s2_sign_up_fee_meta,
				'class'         => 's2_sign_up_fee',
				'wrapper_class' => 'show_if_one_time_fee show_if_subscription',
				'label'         => __( 'Sign-up Fee', 's2-subscription' ) . ' (' . $woocommerce_currency_symbol . ')',
				'desc_tip'      => 'true',
				'style'         => 'width: 80px;',
				'description'   => __( 'Optionally include an amount to be charged at the outset of the subscription. The sign-up fee will be charged immediately, even if the product has a free trial or the payment dates are synced.', 's2-subscription' ),
			] );

		$s2_trial_period_options = s2_get_trial_period_options();
		$s2_trial_period_values  = [];
		foreach ( $s2_trial_period_options as $key => $options ) {
			$s2_trial_period_values[ $key ] = $options['name'];
		}

		woocommerce_wp_select( [
				'id'            => "s2_trial_period",
				'name'          => "s2_trial_period",
				'value'         => $s2_trial_period_meta,
				'class'         => 's2_trial_period',
				'wrapper_class' => 'show_if_one_time_fee show_if_subscription',
				'label'         => __( 'Trial Period', 's2-subscription' ),
				'options'       => $s2_trial_period_values,
				'desc_tip'      => true,
				'description'   => __( 'An optional period of time to wait before charging the first recurring payment. Any sign up fee will still be charged at the outset of the subscription.', 's2-subscription' ),
			] );

		$s2_split_payment_options = s2_get_split_payment_options();
		$s2_split_payment_values  = [];
		foreach ( $s2_split_payment_options as $key => $options ) {
			$s2_split_payment_values[ $key ] = $options['name'];
		}

		woocommerce_wp_select( [
				'id'            => "s2_split_payment",
				'name'          => "s2_split_payment",
				'value'         => $s2_split_payment_meta,
				'class'         => 's2_split_payment',
				'wrapper_class' => 'show_if_split_pay',
				'label'         => __( 'Number of payments <small>Including today</small>', 's2-subscription' ),
				'options'       => $s2_split_payment_values,
				'desc_tip'      => true,
				'description'   => __( 'An optional period of time to wait before charging the first recurring payment. Any sign up fee will still be charged at the outset of the subscription.', 's2-subscription' ),
			] );
		?>

        <p class="form-field show_if_one_time_fee show_if_subscription show_if_split_pay show_if_pay_your_own_price">
            <span class="description subscription-description"></span>
        </p>

    </div>

</div>
