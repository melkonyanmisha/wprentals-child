<?php

/**
 * To add or remove callback functions into woocommerce hooks
 *
 * @return void
 */
function handle_woocommerce_hooks(): void
{
    global $wpestate_global_payments; // wp-content/plugins/wprentals-core/classes/wpestate_global_payments.php => Wpestate_Global_Payments

    // The text doesn't display correctly
    remove_filter(
        'woocommerce_thankyou_order_received_text',
        [$wpestate_global_payments, 'wpestate_woocommerce_thankyou_order_received_text']
    );
}

add_action('wp_loaded', 'handle_woocommerce_hooks');


