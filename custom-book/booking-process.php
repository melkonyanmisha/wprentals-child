<?php

/**
 *  Custom booking function. Works after ajax request
 *  add_action( 'wp_ajax_nopriv_wpestate_ajax_add_booking_instant', 'wpestate_ajax_add_booking_instant' );
 *  add_action( 'wp_ajax_wpestate_ajax_add_booking_instant', 'wpestate_ajax_add_booking_instant' );
 *
 * @return void
 * @throws Exception
 */
function wpestate_ajax_add_booking_instant(): void
{
    $listing_id       = intval($_POST['listing_edit']);
    $allowded_html    = [];
    $from_date        = wpestate_convert_dateformat_twodig(wp_kses($_POST['fromdate'], $allowded_html));
    $to_date          = wpestate_convert_dateformat_twodig(wp_kses($_POST['todate'], $allowded_html));
    $discount_percent = get_discount_percent($from_date, $to_date);

    if (current_user_is_timeshare()) {
        //The case for Rooms. Timeshare users can book only grouped rooms
        if (check_has_room_group($_POST['listing_edit'])) {
            group_booking($_POST['fromdate'], $discount_percent);
        } else {
            //The case for Cottages
            single_booking($listing_id, $discount_percent);
        }
    } else {
        // The case for Customer user
        single_booking($listing_id, $discount_percent);
    }
}

/**
 * @param string $from_date
 * @param float $discount_percent
 *
 * @return void
 * @throws Exception
 */
function group_booking(string $from_date, float $discount_percent): void
{
    $from_date      = new DateTime($from_date);
    $from_date_unix = $from_date->getTimestamp();

    //Array of group IDs with ascending by Group Order
    $group_ids_by_room_group_order = get_group_ids_by_room_group_order();
    // Single available group by ASC order
    $group_data_to_book = get_group_data_to_book($group_ids_by_room_group_order, $from_date_unix);

    if ( ! empty($group_data_to_book['rooms_ids'])) {
        $booking_instant_rooms_group_data = [];

        // STEP 1 - Start booking
        foreach ($group_data_to_book['rooms_ids'] as $room_id) {
            $booking_instant_data = wpestate_child_ajax_add_booking_instant($room_id);
            if ( ! empty($booking_instant_data)) {
                $booking_instant_rooms_group_data[] = $booking_instant_data;
            }
        }

        if (empty($booking_instant_rooms_group_data)) {
            wp_die('Error: Empty $booking_instant_rooms_group_data');
        }

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
                if (isset($booking_instant_current_room_data['make_the_book']['booking_array'][$price_type])) {
                    // Summarize prices
                    if ( ! is_array($price)) {
                        $booking_array_summed_prices[$price_type] += $booking_instant_current_room_data['make_the_book']['booking_array'][$price_type];
                    } else {
                        // Summarize prices by per day
                        if ($price_type === 'custom_price_array' && ! empty($booking_instant_current_room_data['make_the_book']['booking_array'][$price_type])) {
                            foreach ($booking_instant_current_room_data['make_the_book']['booking_array'][$price_type] as $current_day => $current_day_price) {
                                if (isset($booking_array_summed_prices[$price_type][$current_day])) {
                                    $booking_array_summed_prices[$price_type][$current_day] += $current_day_price;
                                } else {
                                    $booking_array_summed_prices[$price_type][$current_day] = $current_day_price;
                                }
                            }
                        }
                    }
                }
            }
        }

        $booking_instant_data_first_room_summarized                                   = $booking_instant_rooms_group_data[0];
        $booking_instant_data_first_room_summarized['make_the_book']['booking_array'] = array_merge(
            $booking_instant_rooms_group_data[0]['make_the_book']['booking_array'],
            $booking_array_summed_prices
        );

        // Set Timeshare user booking data into the SESSION
        set_session_timeshare_booking_data(
            get_current_user_id(),
            intval($discount_percent),
            $booking_instant_data_first_room_summarized['make_the_book']
        );

        // Generate the invoice only for first room from group
        //STEP 2 - generate the invoice
        $generated_invoice_first_room = generate_the_invoice_step($booking_instant_data_first_room_summarized);

        $booking_instant_rooms_group_data_with_first_room_summarized = [
            'booking_instant_data'             => $booking_instant_data_first_room_summarized,
            'booking_instant_rooms_group_data' => $booking_instant_rooms_group_data,
        ];

        update_necessary_metas(
            $booking_instant_rooms_group_data_with_first_room_summarized,
            $generated_invoice_first_room,
            true
        );

        // STEP 3 - Display confirmation popup
        display_booking_confirm_popup($booking_instant_data_first_room_summarized, $generated_invoice_first_room);
    }
}

/**
 * Booking single room for Customer and single cottage for all users
 *
 * @param int $listing_id
 * @param float $discount_percent
 *
 * @return void
 * @throws Exception
 */
function single_booking(int $listing_id, float $discount_percent): void
{
    // STEP 1 - Start booking
    $booking_instant_data = wpestate_child_ajax_add_booking_instant($listing_id);

    if ( ! empty($booking_instant_data)) {
        // Set Timeshare user booking data into the SESSION
        set_session_timeshare_booking_data(
            get_current_user_id(),
            intval($discount_percent),
            $booking_instant_data['make_the_book']
        );

        // STEP 2 - generate the invoice
        $generated_invoice = generate_the_invoice_step($booking_instant_data);
        update_necessary_metas(['booking_instant_data' => $booking_instant_data], $generated_invoice, false);
        // STEP 3 - show me the money
        display_booking_confirm_popup($booking_instant_data, $generated_invoice);
    }
}

/**
 * Start booking
 *
 * @param int $listing_id
 *
 * @return array
 * @throws Exception
 */
function wpestate_child_ajax_add_booking_instant(int $listing_id): array
{
    check_ajax_referer('wprentals_add_booking_nonce', 'security');
    $allowded_html    = array();
    $booking_guest_no = isset($_POST['booking_guest_no']) ? intval($_POST['booking_guest_no']) : 0;
    $instant_booking  = floatval(get_post_meta($listing_id, 'instant_booking', true));

    if ($instant_booking != 1) {
        return [];
    }

    $owner_id           = wpsestate_get_author($listing_id);
    $early_bird_percent = floatval(get_post_meta($listing_id, 'early_bird_percent', true));
    $early_bird_days    = floatval(get_post_meta($listing_id, 'early_bird_days', true));
    $taxes_value        = floatval(get_post_meta($listing_id, 'property_taxes', true));
    $extra_pay_options  = get_post_meta($listing_id, 'extra_pay_options', true);

    $extra_options = wp_kses($_POST['extra_options'], $allowded_html);
    $extra_options = rtrim($extra_options, ",");

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

    $make_the_book = make_the_book(
        $discount_percent,
        $listing_id,
        $owner_id,
        $booking_guest_no,
        $early_bird_percent,
        $early_bird_days,
        $taxes_value
    );

    return [
        'make_the_book'       => $make_the_book,
        'property_id'         => $listing_id,
        'extra_options_array' => $extra_options_array,
        'rental_type'         => $rental_type,
        'booking_type'        => $booking_type,
        'booking_guest_no'    => $booking_guest_no,
        'owner_id'            => $owner_id,
        'early_bird_percent'  => $early_bird_percent,
        'early_bird_days'     => $early_bird_days,
        'taxes_value'         => $taxes_value,
        'extra_pay_options'   => $extra_pay_options != '' ? $extra_pay_options : [],
    ];
}

/**
 * Generate the invoice
 *
 * @param array $booking_instant_data
 *
 * @return array
 */

function generate_the_invoice_step(array $booking_instant_data)
{
    $generated_invoice = generate_the_invoice(
        $booking_instant_data['make_the_book']['reservation_array'],
        $booking_instant_data['property_id'],
        $booking_instant_data['make_the_book']['booking_array'],
        $booking_instant_data['extra_options_array'],
        $booking_instant_data['rental_type'],
        $booking_instant_data['booking_type'],
        $booking_instant_data['booking_guest_no'],
        $booking_instant_data['make_the_book']['booking_id'],
        $booking_instant_data['make_the_book']['price'],
        $booking_instant_data['owner_id'],
        $booking_instant_data['make_the_book']['property_author'],
        $booking_instant_data['early_bird_percent'],
        $booking_instant_data['early_bird_days'],
        $booking_instant_data['taxes_value'],
        $booking_instant_data['extra_pay_options']
    );

    return $generated_invoice;
}

/**
 * Display booking confirmation popup
 *
 * @param $booking_instant_data
 * @param $generated_invoice
 *
 * @return void
 */
function display_booking_confirm_popup($booking_instant_data, $generated_invoice)
{
    echo render_booking_confirm_popup(
        $generated_invoice['invoice_id'],
        $booking_instant_data['make_the_book']['booking_id'],
        $booking_instant_data['property_id'],
        $booking_instant_data['make_the_book']['booking_array'],
        $booking_instant_data['rental_type'],
        $booking_instant_data['booking_type'],
        $booking_instant_data['extra_options_array'],
        $generated_invoice['options_array_explanations'],
        $booking_instant_data['extra_pay_options']
    );
}

/**
 * @param $booking_full_data
 * @param $generated_invoice
 * @param $is_group_booking //To separate group booking from a standard single booking
 *
 * @return void
 */
function update_necessary_metas($booking_full_data, $generated_invoice, $is_group_booking)
{
    $booking_instant_data = $booking_full_data['booking_instant_data'];

    $invoice_id = $generated_invoice['invoice_id'];

    if ($booking_instant_data['make_the_book']['booking_array']['balance'] > 0) {
        update_post_meta($invoice_id, 'invoice_status_full', 'waiting');
    }

    if ($booking_instant_data['make_the_book']['booking_array']['balance'] == 0) {
        update_post_meta($invoice_id, 'is_full_instant', 1);
        update_post_meta($booking_instant_data['make_the_book']['booking_id'], 'is_full_instant', 1);
    }

    // Save in invoice
    update_post_meta($invoice_id, 'is_group_booking', $is_group_booking);
    update_post_meta($invoice_id, 'booking_full_data', json_encode($booking_full_data));

    // Save in booking request
    update_post_meta($booking_instant_data['make_the_book']['booking_id'], 'is_group_booking', $is_group_booking);
    update_post_meta(
        $booking_instant_data['make_the_book']['booking_id'],
        'booking_full_data',
        json_encode($booking_full_data)
    );

    if ($is_group_booking) {
        if ( ! empty($booking_full_data['booking_instant_rooms_group_data'])) {
            $rooms_group_booking_id_list = [];

            foreach ($booking_full_data['booking_instant_rooms_group_data'] as $booking_instant_current_room_data) {
                // Get all other room booking ids
                $rooms_group_booking_id_list[] = $booking_instant_current_room_data['make_the_book']['booking_id'];
            }

            if ( ! empty($rooms_group_booking_id_list)) {
                foreach ($rooms_group_booking_id_list as $current_room_booking_id) {
                    // To avoid issues after booking in My Bookings page
                    update_post_meta($current_room_booking_id, 'booking_invoice_no', $invoice_id);
                }
            }
        }
    }
}