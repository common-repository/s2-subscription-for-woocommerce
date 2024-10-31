/**
 * admin.js
 *
 * @package S2 Subscription
 * @since   1.0.0
 * @author Shuban Studio <shuban.studio@gmail.com>
 * @version 1.0.0
 */

/* global s2_admin */
jQuery(document).ready(function (e) {

    /* Subscription Metabox Content */
    var load_info = function (t, from, to, force) {
        var message = (from == to) ? 'load_' + from : 'copy_billing';

        if (true === force || window.confirm(s2_admin[message])) {
            // Get user ID to load data for
            var user_id = jQuery('#user_id').val();

            if (user_id == 0) {
                window.alert(s2_admin.no_customer_selected);
                return false;
            }

            var data = {
                user_id: user_id,
                action: 'woocommerce_get_customer_details',
                security: s2_admin.get_customer_details_nonce
            };

            jQuery.ajax({
                url: s2_admin.ajaxurl,
                data: data,
                type: 'POST',
                success: function (response) {
                    if (response && response[from]) {
                        jQuery.each(response[from], function (key, data) {
                            //  $( ':input#_'+to+'_' + key ).val( data ).change();
                            jQuery('#_' + to + '_' + key).val(data).change();
                        });
                    }

                }
            });
        }
        return false;
    };

    jQuery(document).on('click', '.load_customer_info', function (e) {
        e.preventDefault();
        var $t = jQuery(this),
            from = $t.data('from'),
            to = $t.data('to');
        load_info($t, from, to);
    });

    jQuery(document).on('click', 'a.edit_address', function (e) {

        e.preventDefault();
        var $t = jQuery(this),
            $edit_div = $t.closest('.order_data_column').find('div.edit_address'),
            $links = $t.closest('.order_data_column').find('a'),
            $show_div = $t.closest('.order_data_column').find('div.address');
        $show_div.toggle();
        $links.toggle();
        $edit_div.toggle();

    });
    /* Subscription Metabox Content */

    /* Product Subscription Tab js */
    //woocommerce simple, variable product subscription settings js
    /*jQuery( document ).on( 'change', '.split_in_two', function() {
        var current_element = jQuery( this );

        if( current_element.is( ":checked" ) ) {
            current_element.val( 'yes' );
        } else {
            current_element.val( '' );
            current_element.removeAttr( 'checked' );
        }
    } );*/

    /*jQuery( document ).on( 'change', '.s2_price_time_option', function() {
        var maxLength = jQuery( this ).find( ':selected' ).data( "max_length" );

        jQuery( "#s2_max_length" ).val( '' );
        jQuery( this ).parents( "p.s2_price_is_per" ).siblings( "p.s2_max_length" ).find( ".s2_max_length_description" ).text( jQuery( this ).val() + " (Max: " + maxLength + ")" );
    } );*/

    // create subscription function
    var create_subscription_description = function (current_element, regular_price, sale_price) {

        var woocommerce_currency_symbol = s2_admin.woocommerce_currency_symbol;

        var product_price = (sale_price ? sale_price : regular_price);
        product_price = product_price ? parseFloat(product_price) : 0;

        var s2_payment_type = current_element.find('.s2_payment_type').val();
        var s2_price_per = current_element.find('.s2_price_is_per').val();
        var s2_price_time_option = current_element.find('.s2_price_time_option').val();

        var s2_max_length = current_element.find('.s2_max_length').val();
        s2_max_length = s2_max_length ? parseInt(s2_max_length) : 0;

        var s2_sign_up_fee = current_element.find('.s2_sign_up_fee').val();
        s2_sign_up_fee = s2_sign_up_fee ? parseFloat(s2_sign_up_fee) : 0;
        var product_price_with_sign_up_fee = product_price + s2_sign_up_fee;

        var s2_trial_per = current_element.find('.s2_trial_per').val();
        var s2_trial_time_option = current_element.find('.s2_trial_time_option').val();
        var s2_billing_frequency = current_element.find('.s2_billing_frequency').val();
        var s2_trial_period = current_element.find('.s2_trial_period').val();

        var s2_split_payment = current_element.find('.s2_split_payment').val();
        s2_split_payment = parseInt(s2_split_payment);

        var s2_limit_quantity_available = current_element.find('.s2_limit_quantity_available').val();
        var s2_quantity_limit = current_element.find('.s2_quantity_limit').val();

        var subscription_description = '';
        if (s2_payment_type == 'one_time_fee') {

            subscription_description = 'Your customer will be charged ' + (s2_sign_up_fee ? ' with signup fee ' : '') +
                woocommerce_currency_symbol + (s2_trial_period && s2_trial_period == 'none' ? product_price_with_sign_up_fee.toFixed(2) : (s2_sign_up_fee ? s2_sign_up_fee : 0)) + ' immediately ' +
                (s2_trial_period && s2_trial_period != 'none' ? ' for their ' + s2_trial_period + ' trial' : '') + ', and then ' + woocommerce_currency_symbol + product_price;

        } else if (s2_payment_type == 'subscription') {

            subscription_description = 'Your customer will be charged ' + (s2_sign_up_fee ? ' with signup fee ' : '') +
                woocommerce_currency_symbol + (s2_trial_period && s2_trial_period == 'none' ? product_price_with_sign_up_fee.toFixed(2) : (s2_sign_up_fee ? s2_sign_up_fee : 0)) + ' immediately ' +
                (s2_trial_period && s2_trial_period != 'none' ? ' for their ' + s2_trial_period + ' trial' : '') + ', and then ' + woocommerce_currency_symbol + product_price + ' ' + s2_billing_frequency +
                (s2_max_length ? ', ' + s2_max_length + ' times.' + ' The total amount paid for the product will be ' + woocommerce_currency_symbol + (((product_price * (s2_max_length ? s2_max_length - 1 : 1)) + product_price_with_sign_up_fee).toFixed(2)) + '.' : ' until they cancel.');

        } else if (s2_payment_type == 'split_pay') {

            product_price = product_price / s2_split_payment;
            product_price = product_price.toFixed(2);

            subscription_description = 'Your customer will be charged ' + woocommerce_currency_symbol +
                product_price + ' immediately, and then ' + woocommerce_currency_symbol + product_price +
                ' every month for the next ' + (s2_split_payment - 1) + ((s2_split_payment - 1) == 1 ? ' month.' : ' months.') + ' The total amount paid for the product will be ' + woocommerce_currency_symbol + (product_price * s2_split_payment) + '.';

        }

        current_element.find('span.subscription-description').text(subscription_description);

    };

    jQuery(document).on('change keyup', '#subscription_product_data input, #subscription_product_data select', function () {

        var subscription_element = jQuery(this).parents('div.subscription-data');

        var regular_price = jQuery('div.product_data').find('.wc_input_price[name^=_regular_price]').val();
        var sale_price = jQuery('div.product_data').find('.wc_input_price[name^=_sale_price]').val();

        create_subscription_description(subscription_element, regular_price, sale_price);

    });

    jQuery(document).on('change keyup', '#subscription_variable_product_data input, #subscription_variable_product_data select, .wc_input_price[name^=variable_regular_price], .wc_input_price[name^=variable_sale_price]', function () {

        var current_element_parent = jQuery(this).parents('div.woocommerce_variable_attributes');

        var regular_price = current_element_parent.find('.wc_input_price[name^=variable_regular_price]').val();
        var sale_price = current_element_parent.find('.wc_input_price[name^=variable_sale_price]').val();

        create_subscription_description(current_element_parent, regular_price, sale_price);

    });

    jQuery(document).on('change', '.s2_payment_type', function () {
        var payment_type = '';

        jQuery.each(this, function (index, option) {
            payment_type = jQuery(option).val();
            jQuery(this).parents('p.form-field').siblings('.show_if_' + payment_type).hide();
        });

        payment_type = jQuery(this).val();
        if (payment_type) {
            jQuery(this).parents('p.form-field').siblings('.show_if_' + payment_type).show();

            jQuery(this).parents('p.form-field').siblings(':not(.show_if_' + payment_type + ')').find('input').val('');
            jQuery(this).parents('p.form-field').siblings(':not(.show_if_' + payment_type + ')').find('select').prop('selectedIndex', 0);
        }
    });

    // on document ready triger s2_payment_type
    jQuery('.s2_payment_type').trigger('change');

    // on variation added, loaded trigger s2_payment_type
    jQuery(document.body).on('woocommerce_variations_added woocommerce_variations_loaded', function (event) {
        jQuery('.s2_payment_type').trigger('change');
    });
    /* Product Subscription Tab js */

});
