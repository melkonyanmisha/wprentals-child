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

//todo@@@ need to uncomment before go to production
    // Fires after changing woocommerce order status
//    add_action('woocommerce_order_status_changed', 'update_timeshare_user_package', 99, 4);
}

add_action('wp_loaded', 'handle_woocommerce_hooks');

/**
 * To update a duration of Timeshare user package after a successful payment
 *
 * @param int $order_id
 * @param string $previous_status
 * @param string $next_status
 * @param object $order
 *
 * @return void
 */
function update_timeshare_user_package(int $order_id, string $previous_status, string $next_status, object $order): void
{
    // Check by "processing" because of the status can't be automatically changed to "completed" if the product is just virtual.
    if ( ! current_user_is_timeshare() || ! is_checkout() || $order->get_status() !== 'processing') {
        return;
    }

    $user_id                     = $order->get_user_id();
    $products                    = $order->get_items();
    $timeshare_user_data_encoded = get_user_meta($user_id, TIMESHARE_USER_DATA);

    // The case when the current user is not Timeshare or doesn't have a package
    if (empty($timeshare_user_data_encoded)) {
        return;
    }

    $timeshare_user_data_decoded = json_decode($timeshare_user_data_encoded[0], true);
    $timeshare_package_duration  = $timeshare_user_data_decoded[TIMESHARE_PACKAGE_DURATION] ?? 0;

    // The case when package duration is 0
    if ( ! $timeshare_package_duration) {
        return;
    }

    $accessible_days_count = 0;
    foreach ($products as $prod) {
        $product_id             = $prod['product_id'];
        $invoice_no             = intval(get_post_meta($product_id, '_invoice_id', true));
        $booking_full_data_json = get_post_meta($invoice_no, 'booking_full_data', true);

        if ( ! $booking_full_data_json) {
            return;
        }

        $booking_full_data     = json_decode($booking_full_data_json, true);
        $accessible_days_count = $booking_full_data['booking_instant_data']['make_the_book']['booking_array']['discount_price_calc']['timeshare_user_calc']['accessible_days_count'] ?? $accessible_days_count;
    }

    // The case when the current Timeshare user package duration is 0
    if ( ! $accessible_days_count) {
        return;
    }

    // Calculate the package new duration
    $timeshare_user_data_decoded[TIMESHARE_PACKAGE_DURATION] = $timeshare_package_duration - $accessible_days_count;
    // Set a new package duration for current user
    update_user_meta($user_id, TIMESHARE_USER_DATA, json_encode($timeshare_user_data_decoded));
}


function clear_cart_if_multiple_items()
{
    // Check if there's more than one item in the cart
    if (WC()->cart->get_cart_contents_count() > 1) {
        // Clear the cart
        WC()->cart->empty_cart();

        // Optionally, you can display a notice to the user
        wc_add_notice(__('You can only purchase one listing at a time.', 'woocommerce'), 'error');

        // Redirect to a different page if needed (e.g., shop page)
        wp_redirect(wc_get_page_permalink('shop'));
        exit;
    }
}

// Hook the function to run before the checkout page is displayed
add_action('template_redirect', 'clear_cart_if_multiple_items');