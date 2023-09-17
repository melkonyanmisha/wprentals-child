<?php

/**
 * Handle ajax request add_action('wp_ajax_wpestate_show_confirmed_booking', 'wpestate_show_confirmed_booking' );
 * @return void
 */
function wpestate_show_confirmed_booking()
{
    check_ajax_referer('wprentals_booking_confirmed_actions_nonce', 'security');
    $current_user = wp_get_current_user();
    $userID       = $current_user->ID;

    if ( ! is_user_logged_in()) {
        exit('ko');
    }
    if ($userID === 0) {
        exit('out pls');
    }

    $invoice_id = intval($_POST['invoice_id']);
    $bookid     = intval($_POST['booking_id']);

    $the_post    = get_post($bookid);
    $book_author = $the_post->post_author;

    $the_post   = get_post($invoice_id);
    $inv_author = $the_post->post_author;

    if ($userID != $inv_author && $book_author != $userID) {
        exit('out pls');
    }

    wpestate_child_super_invoice_details($invoice_id);

    die();
}

/**
 * @param $invoice_id
 * @param $width_logo
 *
 * @return void
 */
function wpestate_child_super_invoice_details($invoice_id, $width_logo = '')
{
    $bookid            = esc_html(get_post_meta($invoice_id, 'item_id', true));
    $booking_from_date = esc_html(get_post_meta($bookid, 'booking_from_date', true));
    $booking_prop      = esc_html(get_post_meta($bookid, 'booking_id', true)); // property_id
    $booking_to_date   = esc_html(get_post_meta($bookid, 'booking_to_date', true));
    $booking_guests    = floatval(get_post_meta($bookid, 'booking_guests', true));
    $extra_options     = get_post_meta($bookid, 'extra_options', true);
    $booking_type      = wprentals_return_booking_type($booking_prop);
    $booked_days_count = get_booked_days_count($booking_from_date, $booking_to_date);

    $extra_options_array = array();
    if ($extra_options != '') {
        $extra_options_array = explode(',', $extra_options);
    }

    $manual_expenses = get_post_meta($invoice_id, 'manual_expense', true);
    $booking_array   = wpestate_booking_price(
        $booking_guests,
        $invoice_id,
        $booking_prop,
        $booking_from_date,
        $booking_to_date,
        $bookid,
        $extra_options_array,
        $manual_expenses
    );

    //#### Start of prices customization

    //Calculate separately to avoid Price calculation issues after timeshare_discount_price_calc().
    $booking_array['default_price'] = reset($booking_array['custom_price_array']);
    $booking_array['total_price']   = $booking_array['inter_price'] + $booked_days_count * $booking_array['cleaning_fee'];
    $booking_array['deposit']       = $booking_array['total_price'];
    $booking_array['youearned']     = $booking_array['total_price'];

    //#### End of prices customization

    $price_per_weekeend = floatval(get_post_meta($booking_prop, 'price_per_weekeend', true));
    $total_price        = floatval(get_post_meta($invoice_id, 'item_price', true));
    $default_price      = $booking_array['default_price'];

    $wpestate_currency       = esc_html(get_post_meta($invoice_id, 'invoice_currency', true));
    $wpestate_where_currency = esc_html(wprentals_get_option('wp_estate_where_currency_symbol', ''));
    $details                 = get_post_meta($invoice_id, 'renting_details', true);

    $depozit = floatval(get_post_meta($invoice_id, 'depozit_paid', true));
    $balance = $total_price - $depozit;

    $price_show              = wpestate_show_price_booking_for_invoice(
        $default_price,
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $price_per_weekeend_show = wpestate_show_price_booking_for_invoice(
        $price_per_weekeend,
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $total_price_show        = wpestate_show_price_booking_for_invoice(
        $total_price,
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $depozit_show            = wpestate_show_price_booking_for_invoice( //todo@@ keep as exist
        $depozit,
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $balance_show            = wpestate_show_price_booking_for_invoice( //todo@@ keep as exist
        $balance,
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $guest_price             = wpestate_show_price_booking_for_invoice(
        $booking_array['extra_price_per_guest'],
        $wpestate_currency,
        $wpestate_where_currency,
        1,
        1
    );

    $invoice_saved = esc_html(get_post_meta($invoice_id, 'invoice_type', true));

    wpestate_print_create_form_invoice(
        $guest_price,
        $booking_guests,
        $invoice_id,
        $invoice_saved,
        $booking_from_date,
        $booking_to_date,
        $booking_array,
        $price_show,
        $details,
        $wpestate_currency,
        $wpestate_where_currency,
        $total_price,
        $total_price_show,
        $depozit_show,
        $balance_show,
        $booking_prop,
        $price_per_weekeend_show,
        $booking_type,
        $width_logo
    );
}
