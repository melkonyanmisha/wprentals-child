<?php

function wpestate_ajax_add_booking_instant()
{
    $booking_instant_data = [];
    $listing_id = intval($_POST['listing_edit']);
    
    
    if (current_user_is_timeshare()) {
        $from_date                     = new DateTime($_POST['fromdate']);
        $from_date_unix                = $from_date->getTimestamp();
        $group_ids_by_room_group_order = get_group_ids_by_room_group_order();


        $group_data_to_book = get_group_data_to_book($group_ids_by_room_group_order, $from_date_unix);
//        var_dump(c);
//var_dump($_POST);
//        var_dump($group_data_to_book);
//        exit;

        //Case for Rooms
        if (check_has_room_group($_POST['listing_edit']) && ! empty($group_data_to_book['rooms_ids'])) {
            foreach ($group_data_to_book['rooms_ids'] as $room_id) {
//        todo@@@@ need to continue and modify wpestate_child_ajax_add_booking_instant() function

                wpestate_child_ajax_add_booking_instant($room_id, true, false);
            }
        } else {
            //Case for Cottages
//            var_dump(44444); exit;
            $booking_instant_data = wpestate_child_ajax_add_booking_instant($listing_id, true, true);
        }
    } else {
        wpestate_child_ajax_add_booking_instant($listing_id, false, false  );
    }

    steps_after_book($booking_instant_data);
}

function steps_after_book($booking_instant_data)
{
    //STEP 2 generate the invoice

    $generated_invoice = generate_the_invoice(
        $booking_instant_data['reservation_array'],
        $booking_instant_data['property_id'],
        $booking_instant_data['booking_array'],
        $booking_instant_data['extra_options_array'],
        $booking_instant_data['rental_type'],
        $booking_instant_data['booking_type'],
        $booking_instant_data['booking_guest_no'],
        $booking_instant_data['booking_id'],
        $booking_instant_data['price'],
        $booking_instant_data['owner_id'],
        $booking_instant_data['property_author'],
        $booking_instant_data['early_bird_percent'],
        $booking_instant_data['early_bird_days'],
        $booking_instant_data['taxes_value'],
        $booking_instant_data['extra_pay_options']
    );

    $invoice_id                 = $generated_invoice['invoice_id'];
    $options_array_explanations = $generated_invoice['options_array_explanations'];

    //STEP3 - show me the money

    show_the_money(
        $invoice_id,
        $booking_instant_data['booking_id'],
        $booking_instant_data['booking_array'],
        $booking_instant_data['rental_type'],
        $booking_instant_data['booking_type'],
        $booking_instant_data['extra_options_array'],
        $options_array_explanations,
        $booking_instant_data['extra_pay_options']
    );

    if ($booking_instant_data['booking_array']['balance'] > 0) {
        update_post_meta($invoice_id, 'invoice_status_full', 'waiting');
    }

    if ($booking_instant_data['booking_array']['balance'] == 0) {
        update_post_meta($invoice_id, 'is_full_instant', 1);
        update_post_meta($booking_instant_data['booking_id'], 'is_full_instant', 1);
    }

    die();
}

function wpestate_child_ajax_add_booking_instant($listing_id ,$is_timeshare_user, $is_cottage )
{
    check_ajax_referer('wprentals_add_booking_nonce', 'security');
    $allowded_html    = array();
    $booking_guest_no = isset($_POST['booking_guest_no']) ? intval($_POST['booking_guest_no']) : 0;

//    todo@@@ booking continue..need to change $listing_id if it called from wpestate_ajax_add_booking_instant()

    $instant_booking = floatval(get_post_meta($listing_id, 'instant_booking', true));

    if ($instant_booking != 1) {
        die();
    }

    $owner_id           = wpsestate_get_author($listing_id);
    $early_bird_percent = floatval(get_post_meta($listing_id, 'early_bird_percent', true));
    $early_bird_days    = floatval(get_post_meta($listing_id, 'early_bird_days', true));
    $taxes_value        = floatval(get_post_meta($listing_id, 'property_taxes', true));
    $extra_pay_options  = get_post_meta($listing_id, 'extra_pay_options', true);
    $extra_options      = wp_kses($_POST['extra_options'], $allowded_html);
    $extra_options      = rtrim($extra_options, ",");

    $extra_options_array = array();
    if ($extra_options != '') {
        $extra_options_array = explode(',', $extra_options);
    }

    $booking_type = wprentals_return_booking_type($listing_id);
    $rental_type  = wprentals_get_option('wp_estate_item_rental_type');

    // STEP1 -make the book

    $make_the_book = make_the_book(
        $listing_id,
        $owner_id,
        $booking_guest_no,
        $early_bird_percent,
        $early_bird_days,
        $taxes_value
    );

    return [
        'reservation_array'   => $make_the_book['reservation_array'],
        'property_id'         => $listing_id,
        'booking_array'       => $make_the_book['booking_array'],
        'extra_options_array' => $extra_options_array,
        'rental_type'         => $rental_type,
        'booking_type'        => $booking_type,
        'booking_guest_no'    => $booking_guest_no,
        'booking_id'          => $make_the_book['booking_id'],
        'price'               => $make_the_book['price'],
        'owner_id'            => $owner_id,
        'property_author'     => $make_the_book['property_author'],
        'early_bird_percent'  => $early_bird_percent,
        'early_bird_days'     => $early_bird_days,
        'taxes_value'         => $taxes_value,
        'extra_pay_options'   => $extra_pay_options,
    ];
}