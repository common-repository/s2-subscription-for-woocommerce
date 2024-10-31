<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! defined( 'S2_WS_VERSION' ) ) {
	exit;
}

/**
 * Plugin Settings
 *
 * @package S2 Subscription
 * @since   1.0.30
 * @author  Shuban Studio <shuban.studio@gmail.com>
 */

$settings = [

	'general_settings'   								=> [
																'title' 	=> __( 'General settings', 's2-subscription' ),
																'type'  	=> 'title',
															],
	'enable_log'                                      	=> [
																'title'     => __( 'Enable Log', 's2-subscription' ),
																'desc'      => '',
																'type'      => 'checkbox',
																'default'   => 'no',
															],
	'customer_settings'   								=> [
																'title' 	=> __( 'Customer subscription settings', 's2-subscription' ),
																'type'  	=> 'title',
															],
	'allow_customer_cancel_subscription'              	=> [
																'title'      	=> __( 'Allow customer to cancel subscriptions', 's2-subscription'),
																'label'      	=> __( 'Allow customer to cancel subscriptions', 's2-subscription'),
																'description'   => '',
																'type' 			=> 'checkbox',
																'default'   	=> 'no',
																'desc_tip'    	=> true,
															],
	'customer_cancel_subscription_option'              	=> [
																'title'      	=> __( '', 's2-subscription'),
																'label'      	=> __( 'Cancel subscription option', 's2-subscription'),
																'description'   => 'Cancel subscription option, when customer cancel subscription selected option will be used to process cancel functionality',
																'type' 			=> 'select',
																'options'		=> [
																					'cancelled' => 'Cancel (with collecting outstanding amount)',
																					'cancel-now' => 'Cancel Now (without collecting outstanding amount)',
																					'cancel-with-refund' => 'Cancel With Refund'
																				],
															],
	'allow_customer_pause_subscription'              	=> [
																'title'      	=> __( 'Allow customer to pause subscriptions', 's2-subscription'),
																'label'      	=> __( 'Allow customer to pause subscriptions', 's2-subscription'),
																'description'   => '',
																'type' 			=> 'checkbox',
																'default'   	=> 'no',
																'desc_tip'    	=> true,
															],
	'allow_customer_resume_subscription'              	=> [
																'title'      	=> __( 'Allow customer to resume subscriptions', 's2-subscription'),
																'label'      	=> __( 'Allow customer to resume subscriptions', 's2-subscription'),
																'description'   => '',
																'type' 			=> 'checkbox',
																'default'   	=> 'no',
																'desc_tip'    	=> true,
															],

];

return $settings;
