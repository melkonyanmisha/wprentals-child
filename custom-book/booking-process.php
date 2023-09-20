<?php

function wpestate_ajax_add_booking_instant()
{
    $booking_instant_data = [];
    $listing_id           = intval($_POST['listing_edit']);

    if (current_user_is_timeshare()) {
        $from_date      = new DateTime($_POST['fromdate']);
        $from_date_unix = $from_date->getTimestamp();

        //Case for Rooms. Timeshare users can book only grouped rooms
        if (check_has_room_group($_POST['listing_edit'])) {
            //Array of group IDs with ascending by Group Order
            $group_ids_by_room_group_order = get_group_ids_by_room_group_order();
            // Single available group by ASC order
            $group_data_to_book = get_group_data_to_book($group_ids_by_room_group_order, $from_date_unix);

            if ( ! empty($group_data_to_book['rooms_ids'])) {
                $booking_instant_rooms_group_data = [];

                foreach ($group_data_to_book['rooms_ids'] as $room_id) {
//        todo@@@@ need to continue and modify wpestate_child_ajax_add_booking_instant() function
                    $booking_instant_rooms_group_data[] = wpestate_child_ajax_add_booking_instant($room_id);
                }

                $generated_invoices_list = [];
                // To summarize prices
                $booking_array_summed_prices = [
                    'default_price'      => 0,
                    'total_price'        => 0,
                    'inter_price'        => 0,
                    'deposit'            => 0,
                    'cleaning_fee'       => 0,
                    'city_fee'           => 0,
                    'service_fee'        => 0,
                    'youearned'          => 0,
                    'week_price'         => 0,
                    'month_price'        => 0,
                    'balance'            => 0,
                    'custom_price_array' => []
                ];

                foreach ($booking_instant_rooms_group_data as $booking_instant_current_room_data) {
                    foreach ($booking_array_summed_prices as $price_type => $price) {
                        // Summarize prices
                        if ( ! is_array($price)) {
                            $booking_array_summed_prices[$price_type] += $booking_instant_current_room_data['booking_array'][$price_type];
                        } else {
                            // Summarize prices by per day
                            if ($price_type === 'custom_price_array' && ! empty($booking_instant_current_room_data['booking_array'][$price_type])) {
                                foreach ($booking_instant_current_room_data['booking_array'][$price_type] as $current_day => $current_day_price) {
                                    if (isset($booking_array_summed_prices[$price_type][$current_day])) {
                                        $booking_array_summed_prices[$price_type][$current_day] += $current_day_price;
                                    } else {
                                        $booking_array_summed_prices[$price_type][$current_day] = $current_day_price;
                                    }
//                                    var_dump(44444);
//                                    var_dump($current_day);
//                                    var_dump($current_day_price);
//                                    var_dump( $booking_array_summed_prices[$price_type]); exit;
                                }
                            }
                        }
                    }
                }

                $booking_instant_data_first_room                  = $booking_instant_rooms_group_data[0];
                $booking_instant_data_first_room['booking_array'] = array_merge(
                    $booking_instant_rooms_group_data[0]['booking_array'],
                    $booking_array_summed_prices
                );

                // Generate the invoice only for first room from group
                $generated_invoice_first_room = generate_the_invoice_step($booking_instant_data_first_room);
                show_the_money_step($booking_instant_data_first_room, $generated_invoice_first_room);
                update_necessary_metas($booking_instant_current_room_data, $generated_invoice_first_room);
                //todo@@@@ continue need to fix invoice generation. depends on set_session_timeshare_booking_data()
//                var_dump($generated_invoices_list);
//                var_dump($booking_instant_rooms_group_data);
//                var_dump($booking_array_summed_prices);
//
//                exit;
            }
        } else {
            //Case for Cottages
            $booking_instant_data = wpestate_child_ajax_add_booking_instant($listing_id);
            $generated_invoice    = generate_the_invoice_step($booking_instant_data);
            update_necessary_metas($booking_instant_data, $generated_invoice);
            show_the_money_step($booking_instant_data, $generated_invoice);
        }
    } else {
        $generated_invoice = generate_the_invoice_step($booking_instant_data);
        update_necessary_metas($booking_instant_data, $generated_invoice);
        show_the_money_step($booking_instant_data, $generated_invoice);
    }
}

/**
 * STEP 2 - generate the invoice
 *
 * @param array $booking_instant_data
 *
 * @return array
 */

function generate_the_invoice_step(array $booking_instant_data)
{
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

    return $generated_invoice;
}

/**
 * STEP 3 - show me the money
 *
 * @param $booking_instant_data
 * @param $generated_invoice
 *
 * @return void
 */
function show_the_money_step($booking_instant_data, $generated_invoice)
{
    $invoice_id                 = $generated_invoice['invoice_id'];
    $options_array_explanations = $generated_invoice['options_array_explanations'];

    echo show_the_money(
        $invoice_id,
        $booking_instant_data['booking_id'],
        $booking_instant_data['property_id'],
        $booking_instant_data['booking_array'],
        $booking_instant_data['rental_type'],
        $booking_instant_data['booking_type'],
        $booking_instant_data['extra_options_array'],
        $options_array_explanations,
        $booking_instant_data['extra_pay_options']
    );

    die();
}


function update_necessary_metas($booking_instant_data, $generated_invoice)
{
    $invoice_id = $generated_invoice['invoice_id'];

    if ($booking_instant_data['booking_array']['balance'] > 0) {
        update_post_meta($invoice_id, 'invoice_status_full', 'waiting');
    }

    if ($booking_instant_data['booking_array']['balance'] == 0) {
        update_post_meta($invoice_id, 'is_full_instant', 1);
        update_post_meta($booking_instant_data['booking_id'], 'is_full_instant', 1);
    }
}

/**
 * @param int $listing_id
 *
 * @return array|void
 * @throws Exception
 */
function wpestate_child_ajax_add_booking_instant(int $listing_id)
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

//    var_dump(111112222);
//    var_dump($make_the_book); exit;

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


