<?php

function wpestate_ajax_add_booking_instant()
{
    if (current_user_is_timeshare() && check_has_room_group($_POST['listing_edit'])) {
        $from_date                     = new DateTime($_POST['fromdate']);
        $from_date_unix                = $from_date->getTimestamp();
        $group_ids_by_room_group_order = get_group_ids_by_room_group_order();


        $group_data_to_book = get_group_data_to_book($group_ids_by_room_group_order, $from_date_unix);
//        var_dump(c);
//var_dump($_POST);
//        var_dump($group_data_to_book);
//        exit;

        if ( ! empty($group_data_to_book['rooms_ids'])) {
            foreach ($group_data_to_book['rooms_ids'] as $room_id) {
//        todo@@@@ need to continue and modify wpestate_child_ajax_add_booking_instant() function

//                wpestate_child_ajax_add_booking_instant(true, $room_id);
            }
        }
    } else {
        wpestate_child_ajax_add_booking_instant();
    }
}

function wpestate_child_ajax_add_booking_instant($is_timeshare_user = false, $property_id = false)
{
    check_ajax_referer('wprentals_add_booking_nonce', 'security');
    $current_user  = wp_get_current_user();
    $allowded_html = array();
    $userID        = $current_user->ID;
    $from          = $current_user->user_login;
    $comment       = '';
    $status        = 'pending';

    if (isset($_POST['comment'])) {
        $comment = wp_kses($_POST['comment'], $allowded_html);
    }

    $booking_guest_no = 0;
    if (isset($_POST['booking_guest_no'])) {
        $booking_guest_no = intval($_POST['booking_guest_no']);
    }

    $booking_adults = 0;
    if (isset($_POST['booking_adults'])) {
        $booking_adults = intval($_POST['booking_adults']);
    }

    $booking_childs = 0;
    if (isset($_POST['booking_childs'])) {
        $booking_childs = intval($_POST['booking_childs']);
    }

    $booking_infants = 0;
    if (isset($_POST['booking_infants'])) {
        $booking_infants = intval($_POST['booking_infants']);
    }


    if (isset ($_POST['confirmed'])) {
        if (intval($_POST['confirmed']) == 1) {
            $status = 'confirmed';
        }
    }

//    todo@@@ booking continue..need to change $property_id if it called from wpestate_ajax_add_booking_instant()

    $property_id     = intval($_POST['listing_edit']);
    $instant_booking = floatval(get_post_meta($property_id, 'instant_booking', true));

    if ($instant_booking != 1) {
        die();
    }

    // PREPARE get property details
    $invoice_id = 0;
    $owner_id   = wpsestate_get_author($property_id);

    $early_bird_percent = floatval(get_post_meta($property_id, 'early_bird_percent', true));
    $early_bird_days    = floatval(get_post_meta($property_id, 'early_bird_days', true));
    $taxes_value        = floatval(get_post_meta($property_id, 'property_taxes', true));

    $fromdate = wp_kses($_POST['fromdate'], $allowded_html);
    $to_date  = wp_kses($_POST['todate'], $allowded_html);
    //$fromdate               =   wpestate_convert_dateformat($fromdate);
    //$to_date                =   wpestate_convert_dateformat($to_date);

    $fromdate = wpestate_convert_dateformat_twodig($fromdate);
    $to_date  = wpestate_convert_dateformat_twodig($to_date);

    $event_name          = esc_html__('Booking Request', 'wprentals');
    $security_deposit    = get_post_meta($property_id, 'security_deposit', true);
    $full_pay_invoice_id = 0;
    $to_be_paid          = 0;
    $extra_pay_options   = get_post_meta($property_id, 'extra_pay_options', true);
    $extra_options       = wp_kses($_POST['extra_options'], $allowded_html);
    $extra_options       = rtrim($extra_options, ",");

    $extra_options_array = array();
    if ($extra_options != '') {
        $extra_options_array = explode(',', $extra_options);
    }

    $booking_type = wprentals_return_booking_type($property_id);
    $rental_type  = wprentals_get_option('wp_estate_item_rental_type');

    // STEP1 -make the book

    ///todo@@@ custom
    $make_the_book     = make_the_book(
        $property_id,
        $owner_id,
        $booking_guest_no,
        $early_bird_percent,
        $early_bird_days,
        $taxes_value
    );
    $booking_id        = $make_the_book['booking_id'];
    $price             = $make_the_book['price'];
    $reservation_array = $make_the_book['reservation_array'];
    $booking_array     = $make_the_book['booking_array'];
    $property_author   = $make_the_book['property_author'];

//    var_dump(1111);
//    var_dump($make_the_book);
//    exit;

    //STEP 2 generate the invoice

    $generated_invoice = generate_the_invoice(
        $reservation_array,
        $property_id,
        $booking_array,
        $extra_options_array,
        $rental_type,
        $booking_type,
        $booking_guest_no,
        $booking_id,
        $price,
        $owner_id,
        $property_author,
        $early_bird_percent,
        $early_bird_days,
        $taxes_value
    );

    $invoice_id = $generated_invoice['invoice_id'];
    $options_array_explanations = $generated_invoice['options_array_explanations'];

//    var_dump(222222);
//    var_dump($generated_invoice);
//    exit;

    //STEP3 - show me the money

    show_the_money(
        $invoice_id,
        $booking_id,
        $booking_array,
        $rental_type,
        $booking_type,
        $extra_options_array,
        $options_array_explanations
    );
//    var_dump(33333);
//
//    exit;
    //    todo@@@  end custom

    if ($booking_array['balance'] > 0) {
        update_post_meta($invoice_id, 'invoice_status_full', 'waiting');
    }

    if ($booking_array['balance'] == 0) {
        update_post_meta($invoice_id, 'is_full_instant', 1);
        update_post_meta($booking_id, 'is_full_instant', 1);
    }

    die();
}