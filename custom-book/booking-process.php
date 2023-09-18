<?php

function wpestate_ajax_add_booking_instant()
{
    $booking_instant_data = [];
    $listing_id           = intval($_POST['listing_edit']);


    if (current_user_is_timeshare()) {
        $from_date      = new DateTime($_POST['fromdate']);
        $from_date_unix = $from_date->getTimestamp();

//        var_dump($_POST);
//        var_dump(check_has_room_group($_POST['listing_edit']));
//        exit;

        //Case for Rooms. Timeshare users can book only grouped rooms
        if (check_has_room_group($_POST['listing_edit'])) {
            //Array of group IDs with ascending by Group Order
            $group_ids_by_room_group_order = get_group_ids_by_room_group_order();
            // Single available group by ASC order
            $group_data_to_book = get_group_data_to_book($group_ids_by_room_group_order, $from_date_unix);

            if ( ! empty($group_data_to_book['rooms_ids'])) {
                foreach ($group_data_to_book['rooms_ids'] as $room_id) {
//        todo@@@@ need to continue and modify wpestate_child_ajax_add_booking_instant() function

//                    $booking_instant_data[] = wpestate_child_ajax_add_booking_instant($room_id, true);
                }
//                var_dump($booking_instant_data); exit;

            }
        } else {
            //Case for Cottages
            steps_after_book(wpestate_child_ajax_add_booking_instant($listing_id, true));
        }
    } else {
//        $booking_instant_data = wpestate_child_ajax_add_booking_instant($listing_id, false);
//        steps_after_book($booking_instant_data);

        steps_after_book(wpestate_child_ajax_add_booking_instant($listing_id, false));
    }
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

/**
 * @param int $listing_id
 * @param bool $is_timeshare_user
 *
 * @return array|void
 * @throws Exception
 */
function wpestate_child_ajax_add_booking_instant(int $listing_id, bool $is_timeshare_user)
{
    check_ajax_referer('wprentals_add_booking_nonce', 'security');
    $allowded_html    = array();
    $booking_guest_no = isset($_POST['booking_guest_no']) ? intval($_POST['booking_guest_no']) : 0;
    $instant_booking  = floatval(get_post_meta($listing_id, 'instant_booking', true));

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

    $allowded_html    = [];
    $from_date        = wpestate_convert_dateformat_twodig(wp_kses($_POST['fromdate'], $allowded_html));
    $to_date          = wpestate_convert_dateformat_twodig(wp_kses($_POST['todate'], $allowded_html));
    $discount_percent = get_discount_percent($from_date, $to_date);

    // STEP1 -make the book
    $make_the_book = make_the_book(
        $discount_percent,
        $listing_id,
        $owner_id,
        $booking_guest_no,
        $early_bird_percent,
        $early_bird_days,
        $taxes_value
    );

    // Set Timeshare user booking data into the SESSION
    set_session_timeshare_booking_data(
        get_current_user_id(),
        intval($discount_percent),
        $make_the_book
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

function wpestate_booking_insert_invoice(
    $billing_for,
    $type,
    $pack_id,
    $date,
    $user_id,
    $is_featured,
    $is_upgrade,
    $paypal_tax_id,
    $details,
    $price,
    $author_id = ''
) {
    $price = (double)round(floatval($price), 2);

    $post = array(
        'post_title'  => 'Invoice ',
        'post_status' => 'publish',
        'post_type'   => 'wpestate_invoice',

    );

    if ($author_id != '') {
        $post['post_author'] = intval($author_id);
    }

    $post_id = wp_insert_post($post);

    update_post_meta($post_id, 'invoice_type', $billing_for);
    update_post_meta($post_id, 'biling_type', $type);
    update_post_meta($post_id, 'item_id', $pack_id);
    update_post_meta($post_id, 'item_price', $price);
    update_post_meta($post_id, 'purchase_date', $date);
    update_post_meta($post_id, 'buyer_id', $user_id);
    update_post_meta($post_id, 'txn_id', '');
    update_post_meta($post_id, 'renting_details', $details);
    update_post_meta($post_id, 'invoice_status', 'issued');
    update_post_meta($post_id, 'invoice_percent', floatval(wprentals_get_option('wp_estate_book_down', '')));
    update_post_meta(
        $post_id,
        'invoice_percent_fixed_fee',
        floatval(wprentals_get_option('wp_estate_book_down_fixed_fee', ''))
    );

    $service_fee_fixed_fee = floatval(wprentals_get_option('wp_estate_service_fee_fixed_fee', ''));
    $service_fee           = floatval(wprentals_get_option('wp_estate_service_fee', ''));
    update_post_meta($post_id, 'service_fee_fixed_fee', $service_fee_fixed_fee);
    update_post_meta($post_id, 'service_fee', $service_fee);

    $property_id = get_post_meta($pack_id, 'booking_id', true);

    update_post_meta($post_id, 'for_property', $property_id);
    update_post_meta($post_id, 'rented_by', get_post_field('post_author', $pack_id));
    update_post_meta($post_id, 'prop_taxed', floatval(get_post_meta($property_id, 'property_taxes', true)));

    //$submission_curency_status = esc_html( wprentals_get_option('wp_estate_submission_curency','') );
    $submission_curency_status = wpestate_curency_submission_pick();
    update_post_meta($post_id, 'invoice_currency', $submission_curency_status);

    // Retrieve Timeshare user booking data from the Session
    $timeshare_session_info = get_session_timeshare_booking_data();

    // Price per day after discount
    if ( ! empty($timeshare_session_info[$user_id][$pack_id]['booking_instant']['booking_array']['custom_price_array'])) {
        //Get the first value(first day price) of assoc array
        $price_per_day = reset(
            $timeshare_session_info[$user_id][$pack_id]['booking_instant']['booking_array']['custom_price_array']
        );
    } else {
        //Get original price
        $price_per_day = get_post_meta($property_id, 'property_price', true);
    }

    update_post_meta($post_id, 'default_price', $price_per_day);

    $week_price = floatval(get_post_meta($property_id, 'property_price_per_week', true));
    update_post_meta($post_id, 'week_price', $week_price);

    $month_price = floatval(get_post_meta($property_id, 'property_price_per_month', true));
    update_post_meta($post_id, 'month_price', $month_price);

    $cleaning_fee = floatval(get_post_meta($property_id, 'cleaning_fee', true));
    update_post_meta($post_id, 'cleaning_fee', $cleaning_fee);

    $city_fee = floatval(get_post_meta($property_id, 'city_fee', true));
    update_post_meta($post_id, 'city_fee', $city_fee);

    $my_post = array(
        'ID'         => $post_id,
        'post_title' => 'Invoice ' . $post_id,
    );

    wp_update_post($my_post);

    return $post_id;
}
