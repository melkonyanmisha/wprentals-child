<?php

function make_the_book($property_id, $owner_id, $booking_guest_no, $early_bird_percent, $early_bird_days, $taxes_value)
{
    $current_user  = wp_get_current_user();
    $userID        = $current_user->ID;
    $allowded_html = [];
    $comment       = isset($_POST['comment']) ? wp_kses($_POST['comment'], $allowded_html) : '';
    $status        = isset ($_POST['confirmed']) && intval($_POST['confirmed']) == 1 ? 'confirmed' : 'pending';
    $fromdate      = wpestate_convert_dateformat_twodig(wp_kses($_POST['fromdate'], $allowded_html));
    $to_date       = wpestate_convert_dateformat_twodig(wp_kses($_POST['todate'], $allowded_html));

    $booking_adults  = isset($_POST['booking_adults']) ? intval($_POST['booking_adults']) : 0;
    $booking_childs  = isset($_POST['booking_childs']) ? intval($_POST['booking_childs']) : 0;
    $booking_infants = isset($_POST['booking_infants']) ? intval($_POST['booking_infants']) : 0;

    $extra_options       = wp_kses($_POST['extra_options'], $allowded_html);
    $extra_options       = rtrim($extra_options, ",");
    $extra_options_array = array();
    if ($extra_options != '') {
        $extra_options_array = explode(',', $extra_options);
    }

    $event_name          = esc_html__('Booking Request', 'wprentals');
    $security_deposit    = get_post_meta($property_id, 'security_deposit', true);
    $full_pay_invoice_id = 0;
    $to_be_paid          = 0;

    $post       = array(
        'post_title'   => $event_name,
        'post_content' => $comment,
        'post_status'  => 'publish',
        'post_type'    => 'wpestate_booking',
        'post_author'  => $userID
    );
    $booking_id = wp_insert_post($post);

    $post = array(
        'ID'         => $booking_id,
        'post_title' => $event_name . ' ' . $booking_id
    );
    wp_update_post($post);


    update_post_meta($booking_id, 'booking_status', $status);
    update_post_meta($booking_id, 'booking_id', $property_id);
    update_post_meta($booking_id, 'owner_id', $owner_id);
    update_post_meta($booking_id, 'booking_from_date', $fromdate);
    update_post_meta($booking_id, 'booking_to_date', $to_date);
    update_post_meta($booking_id, 'booking_from_date_unix', strtotime($fromdate));
    update_post_meta($booking_id, 'booking_to_date_unix', strtotime($to_date));
    update_post_meta($booking_id, 'booking_invoice_no', 0);
    update_post_meta($booking_id, 'booking_pay_ammount', 0);
    update_post_meta($booking_id, 'booking_guests', $booking_guest_no);
    update_post_meta($booking_id, 'booking_adults', $booking_adults);
    update_post_meta($booking_id, 'booking_childs', $booking_childs);
    update_post_meta($booking_id, 'booking_infants', $booking_infants);
    update_post_meta($booking_id, 'extra_options', $extra_options);
    update_post_meta($booking_id, 'security_deposit', $security_deposit);
    update_post_meta($booking_id, 'full_pay_invoice_id', $full_pay_invoice_id);
    update_post_meta($booking_id, 'to_be_paid', $to_be_paid);
    update_post_meta($booking_id, 'early_bird_percent', $early_bird_percent);
    update_post_meta($booking_id, 'early_bird_days', $early_bird_days);
    update_post_meta($booking_id, 'booking_taxes', $taxes_value);


    // Re build the reservation array
    $reservation_array = get_post_meta($property_id, 'booking_dates', true);
    if ($reservation_array == '') {
        $reservation_array = wpestate_get_booking_dates($property_id);
    }
    update_post_meta($property_id, 'booking_dates', $reservation_array);

    // PREPARE get property details
    $invoice_id = 0;

    //get booking array
    $booking_array = wpestate_booking_price(
        $booking_guest_no,
        $invoice_id,
        $property_id,
        $fromdate,
        $to_date,
        $booking_id,
        $extra_options_array
    );
    $price         = $booking_array['total_price'];

    //todo@@@ get customized price
    $price = timeshare_discount_price_calc($price, $fromdate, $to_date);

    // updating the booking detail
    update_post_meta($booking_id, 'to_be_paid', $booking_array['deposit']);
    update_post_meta($booking_id, 'booking_taxes', $booking_array['taxes']);
    update_post_meta($booking_id, 'service_fee', $booking_array['service_fee']);
    update_post_meta($booking_id, 'taxes', $booking_array['taxes']);
    update_post_meta($booking_id, 'service_fee', $booking_array['service_fee']);
    update_post_meta($booking_id, 'youearned', $booking_array['youearned']);
    update_post_meta($booking_id, 'custom_price_array', $booking_array['custom_price_array']);
    update_post_meta($booking_id, 'balance', $booking_array['balance']);
    update_post_meta($booking_id, 'total_price', $booking_array['total_price']);

    $property_author = wpsestate_get_author($property_id);

    if ($userID != $property_author) {
        // update on API if is the case
        if ($booking_array['balance'] > 0) {
            update_post_meta($booking_id, 'booking_status_full', 'waiting');
        }
    }

    return [
        'booking_id'        => $booking_id,
        'price'             => $price,
        'reservation_array' => $reservation_array,
        'booking_array'     => $booking_array,
        'property_author'   => $property_author,
    ];
}
