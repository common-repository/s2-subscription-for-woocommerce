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
<div class="subscription_schedule">

    <p>
        <span class="description"><?php esc_html_e( 'This subscription schedule is editable. Upgrade or downgrade subscription', 's2-subscription' ); ?></span>
    </p>

	<?php if ( $payment_gateway->supports( 's2_subscription_upgrade_downgrade' ) ) { ?>

        <p>
            <label class="" for="s2_billing_frequency">Billing Frequency:</label>
        </p>

		<?php
		$s2_billing_frequency_options = s2_get_billing_frequency_options();
		$s2_billing_frequency_values  = [];
		foreach ( $s2_billing_frequency_options as $key => $options ) {
			$s2_billing_frequency_values[ $key ] = $options['name'];
		}

		woocommerce_wp_select( [
				'id'            => "s2_billing_frequency",
				'name'          => "s2_billing_frequency",
				'value'         => $subscription->billing_frequency,
				'class'         => 's2_billing_frequency',
				'wrapper_class' => 'show_if_subscription',
				'label'         => '',
				'options'       => $s2_billing_frequency_values,
			] );
		?>

	<?php } ?>

	<?php
	foreach ( $fields as $field_id => $field_details ) {

		if ( $payment_gateway->supports( $field_details['gateway_support'] ) ) {

			$field_id   = esc_attr( $field_id );
			$filed_type = $field_details['type'];

			$field_value = $subscription->$field_id;
			if ( ! empty( $field_value ) && $field_details['gateway_support'] == 's2_subscription_date_changes' ) {

				$field_value = date_i18n( 'Y-m-d', $subscription->$field_id, true );

			} else {

				$field_min   = $field_details['min'] ? $field_details['min'] : 0;
				$field_value = $field_details['default'] ? $field_details['default'] : $field_value;

			}
			?>
            <div class="">
                <p>
                    <label class="" for="s2_<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field_details['label'] ); ?>:</label>
                </p>
                <p>
                    <input type="<?php echo esc_attr( $filed_type ); ?>" <?php echo esc_attr( $filed_type == 'number' ? 'min = "' . $field_min . '"' : '' ); ?> class="" id="s2_<?php echo esc_attr( $field_id ); ?>" name="s2_<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $field_value ); ?>">
                </p>
            </div>

		<?php } ?>

	<?php } ?>

</div>
<div class="subscription_actions_footer">
    <p>
        <input type="hidden" name="s2_schedule_submit_field" id="s2_schedule_submit_field" value="schedule_subscription">
    </p>
    <p>
        <button type="submit" class="button button-primary" title="<?php esc_attr_e( 'Schedule', 's2-subscription' ); ?>" id="s2_schedule_subscription_button" name="s2_schedule_subscription_button" value="actions"><?php esc_html_e( 'Schedule', 's2-subscription' ); ?></button>
    </p>
</div>
