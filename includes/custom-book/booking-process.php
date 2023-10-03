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
    try {
        if (empty($_POST['listing_edit']) || empty($_POST['fromdate']) || empty($_POST['todate'])) {
            throw new Exception('Invalid data to book');
        }

        $listing_id          = intval($_POST['listing_edit']);
        $allowded_html       = [];
        $from_date           = $_POST['fromdate'];
        $from_date_converted = wpestate_convert_dateformat_twodig(wp_kses($from_date, $allowded_html));
        $to_date             = $_POST['todate'];
        $to_date_converted   = wpestate_convert_dateformat_twodig(wp_kses($to_date, $allowded_html));
        $discount_percent    = get_discount_percent($from_date_converted, $to_date_converted);

        if (current_user_is_timeshare()) {
            // The case for Rooms. Timeshare users can book only grouped rooms
            if (check_has_room_group($listing_id)) {
                room_group_booking($from_date, $discount_percent, $from_date_converted, $to_date_converted);
            } elseif (check_has_cottage_category($listing_id)) { //The case for Cottages
                single_booking($listing_id, $discount_percent, $from_date_converted, $to_date_converted);
            }
        } elseif (current_user_is_customer() || ! is_user_logged_in()) { // The case for Customer or Guest users
            // The case for listing which have parent Room category
            if (check_has_parent_room_category($listing_id)) {
                room_category_booking(
                    $listing_id,
                    $from_date,
                    $discount_percent,
                    $from_date_converted,
                    $to_date_converted
                );
            } elseif (check_has_cottage_category($listing_id)) { ////The case for Cottages
                single_booking($listing_id, $discount_percent, $from_date_converted, $to_date_converted);
            }
        }
    } catch (Exception|Error $e) {
        wp_die($e->getMessage());
    }
}

/**
 * @param string $from_date
 * @param float $discount_percent
 * @param string $from_date_converted
 * @param string $to_date_converted
 *
 * @return void
 * @throws Exception
 */
function room_group_booking(
    string $from_date,
    float $discount_percent,
    string $from_date_converted,
    string $to_date_converted
): void {
    $from_date      = new DateTime($from_date);
    $from_date_unix = $from_date->getTimestamp();

    //Array of group IDs with ascending by Group Order
    $group_ids_by_room_group_order = get_group_ids_by_room_group_order();
    // Single available group by ASC order
    $rooms_group_data_to_book = get_rooms_group_data_to_book($group_ids_by_room_group_order, $from_date_unix);

    if ( ! empty($rooms_group_data_to_book['rooms_ids'])) {
        $booking_instant_rooms_group_data = [];

        // STEP 1 - Start booking
        foreach ($rooms_group_data_to_book['rooms_ids'] as $room_id) {
            $booking_instant_data = wpestate_child_ajax_add_booking_instant(
                $room_id,
                $from_date_converted,
                $to_date_converted
            );
            if ( ! empty($booking_instant_data)) {
                $booking_instant_rooms_group_data[] = $booking_instant_data;
            }
        }

        //  Calculate summarized prices of rooms in a group
        $booking_array_summed_prices = summarize_prices_group_booking(
            $booking_instant_rooms_group_data
        );

        $booking_instant_data_first_room_summarized                                   = $booking_instant_rooms_group_data[0];
        $booking_instant_data_first_room_summarized['make_the_book']['booking_array'] = array_merge(
            $booking_instant_rooms_group_data[0]['make_the_book']['booking_array'],
            $booking_array_summed_prices
        );

        // Calculate summarized discounted prices of rooms in a group
        $booking_instant_data_first_room_summarized['make_the_book']['booking_array']['discount_price_calc'] = summarize_discount_price_calc_group_booking(
            $booking_instant_rooms_group_data
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
            true,
            $rooms_group_data_to_book
        );

        // STEP 3 - Display confirmation popup
        display_booking_confirm_popup($booking_instant_data_first_room_summarized, $generated_invoice_first_room);
    }
}

/**
 * Calculate summarized prices of rooms in a group
 *
 * @param array $booking_instant_rooms_group_data
 *
 * @return array
 */
function summarize_prices_group_booking(array $booking_instant_rooms_group_data): array
{
    if (empty($booking_instant_rooms_group_data)) {
        wp_die('Error: Empty $booking_instant_rooms_group_data');
    }

    // Array to summarize prices
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
                if ($price_type === 'custom_price_array') {
                    // Summarize prices by per day.
                    if ( ! empty($booking_instant_current_room_data['make_the_book']['booking_array'][$price_type])) {
                        foreach ($booking_instant_current_room_data['make_the_book']['booking_array'][$price_type] as $current_day => $current_day_price) {
                            if (isset($booking_array_summed_prices[$price_type][$current_day])) {
                                $booking_array_summed_prices[$price_type][$current_day] += $current_day_price;
                            } else {
                                $booking_array_summed_prices[$price_type][$current_day] = $current_day_price;
                            }
                        }
                    }
                } else {
                    // Summarize all another prices
                    $booking_array_summed_prices[$price_type] += $booking_instant_current_room_data['make_the_book']['booking_array'][$price_type];
                }
            }
        }
    }

    return $booking_array_summed_prices;
}

/**
 * Calculate summarized discounted prices of rooms in a group
 *
 * @param array $booking_instant_rooms_group_data
 *
 * @return array
 */
function summarize_discount_price_calc_group_booking(array $booking_instant_rooms_group_data): array
{
    if (empty($booking_instant_rooms_group_data)) {
        wp_die('Error: Empty $booking_instant_rooms_group_data');
    }

    $discount_price_calc = [];

    // Get initial data from first room
    $first_room_key = 0;
    if ( ! empty($booking_instant_rooms_group_data[$first_room_key]['make_the_book']['booking_array']['discount_price_calc'])) {
        $discount_price_calc = $booking_instant_rooms_group_data[$first_room_key]['make_the_book']['booking_array']['discount_price_calc'];
    }

    foreach ($booking_instant_rooms_group_data as $key => $booking_instant_current_room_data) {
        if ($first_room_key !== $key && ! empty($booking_instant_current_room_data['make_the_book']['booking_array']['discount_price_calc'])) {
            $current_room_discount_price_calc = $booking_instant_current_room_data['make_the_book']['booking_array']['discount_price_calc'];

            $discount_price_calc['calculated_price'] += $current_room_discount_price_calc['calculated_price'];

            if ( ! empty($current_room_discount_price_calc['timeshare_user_calc'])) {
                $discount_price_calc['timeshare_user_calc']['discounted_price_for_accessible_days'] += $current_room_discount_price_calc['timeshare_user_calc']['discounted_price_for_accessible_days'];
                $discount_price_calc['timeshare_user_calc']['remaining_days_price']                 += $current_room_discount_price_calc['timeshare_user_calc']['remaining_days_price'];
            }
        }
    }

    return $discount_price_calc;
}

/**
 * Booking single room for Customer and single cottage for all users
 *
 * @param int $listing_id
 * @param float $discount_percent
 * @param string $from_date_converted
 * @param string $to_date_converted
 *
 * @return void
 * @throws Exception
 */
function single_booking(
    int $listing_id,
    float $discount_percent,
    string $from_date_converted,
    string $to_date_converted
): void {
    // STEP 1 - Start booking
    $booking_instant_data = wpestate_child_ajax_add_booking_instant(
        $listing_id,
        $from_date_converted,
        $to_date_converted
    );

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
 *  Will give preference to the room that has a group with already booked any other room for the same time
 *  Otherwise will book a room from a category by sequence
 *
 * @param int $listing_id
 * @param string $from_date
 * @param float $discount_percent
 * @param string $from_date_converted
 * @param string $to_date_converted
 *
 * @return void
 * @throws Exception
 */
function room_category_booking(
    int $listing_id,
    string $from_date,
    float $discount_percent,
    string $from_date_converted,
    string $to_date_converted
): void {
    $from_date        = new DateTime($from_date);
    $from_date_unix   = $from_date->getTimestamp();
    $room_category_id = get_room_category_id($listing_id);
    $ordered_rooms_ids_from_category            = get_ordered_listing_ids_from_category($room_category_id);
    $reservation_grouped_array_current_category = get_reservation_grouped_array($ordered_rooms_ids_from_category);
    $listing_ids_ready_to_book_from_current_cat = [];

    try {
        if (empty($reservation_grouped_array_current_category)) {
            throw new Exception('Failed to retrieve reservation data for listing/listings in the current category');
        }

        foreach ($reservation_grouped_array_current_category as $listing_id_current_category => $reservation_array) {
            // Check is already booked or not for the same time
            if ( ! array_key_exists($from_date_unix, $reservation_array)) {
                // All listing IDs in the current category which didn't book for the same time
                $listing_ids_ready_to_book_from_current_cat[] = $listing_id_current_category;
            }
        }

        // The case when can't find a room which is ready for booking.
        if (empty($listing_ids_ready_to_book_from_current_cat)) {
            throw new Exception('Failed to book a room from current category');
        }

        // Add the retrieved listing ID as the initial listing for booking
        $listing_id_ready_to_book          = $listing_id;
        $listings_ids_from_last_room_group = get_listings_ids_from_last_room_group();

        if (check_has_room_group($listing_id)) {
            foreach ($listing_ids_ready_to_book_from_current_cat as $current_listing_id_current_category) {
                // Add the ID which is not from the rooms group which has a maximum order
                if ( ! in_array($current_listing_id_current_category, $listings_ids_from_last_room_group)) {
                    $listing_id_ready_to_book = $current_listing_id_current_category;
                    break;
                }
            }
        }

        $grouped_listings_by_room_group = [];

        foreach ($listing_ids_ready_to_book_from_current_cat as $current_listing_id_from_current_cat) {
            // Try to get rooms groups
            if (check_has_room_group($current_listing_id_from_current_cat)) {
                $grouped_listings_by_room_group[$current_listing_id_from_current_cat] = get_all_listings_ids_in_group(
                    $current_listing_id_from_current_cat
                );
            }
        }

        // The case when listings has a room groups.
        // Try to book the room from any group which has another booked room for the same time
        if ( ! empty($grouped_listings_by_room_group)) {
            $reservation_grouped_array_by_room_group = [];
            foreach ($grouped_listings_by_room_group as $current_listing_id_from_current_cat => $current_room_group) {
                // All reservation data by room groups. Rooms from the current category
                $reservation_grouped_array_by_room_group[$current_listing_id_from_current_cat] = get_reservation_grouped_array(
                    array_values($current_room_group)
                );
            }

            $is_found_preferred_listing = false;
            // Preferred to try to find a group where any room is already booked for the same time.
            foreach ($reservation_grouped_array_by_room_group as $current_listing_id_from_current_cat => $reservation_grouped_array_current_room_group) {
                if ($is_found_preferred_listing) {
                    break;
                }

                // Need to keep available the listing which is from the rooms group which has a maximum order
                if (in_array($current_listing_id_from_current_cat, $listings_ids_from_last_room_group)) {
                    continue;
                }

                foreach ($reservation_grouped_array_current_room_group as $reservation_grouped_array_current_listing) {
                    if (array_key_exists($from_date_unix, $reservation_grouped_array_current_listing)) {
                        $listing_id_ready_to_book   = $current_listing_id_from_current_cat;
                        $is_found_preferred_listing = true;
                        break;
                    }
                }
            }
        }

        if ($listing_id_ready_to_book) {
            single_booking(
                $listing_id_ready_to_book,
                $discount_percent,
                $from_date_converted,
                $to_date_converted
            );
        }
    } catch (Exception|Error $e) {
        wp_die($e->getMessage());
    }
}

/**
 * Start booking
 *
 * @param int $listing_id
 * @param string $from_date_converted
 * @param string $to_date_converted
 *
 * @return array
 * @throws Exception
 */
function wpestate_child_ajax_add_booking_instant(
    int $listing_id,
    string $from_date_converted,
    string $to_date_converted
): array {
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
    $extra_options      = wp_kses($_POST['extra_options'], $allowded_html);
    $extra_options      = rtrim($extra_options, ",");

    $extra_options_array = array();
    if ($extra_options != '') {
        $extra_options_array = explode(',', $extra_options);
    }

    $booking_type     = wprentals_return_booking_type($listing_id);
    $rental_type      = wprentals_get_option('wp_estate_item_rental_type');
    $discount_percent = get_discount_percent($from_date_converted, $to_date_converted);

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

function generate_the_invoice_step(array $booking_instant_data): array
{
    return generate_the_invoice(
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
}

/**
 * Display booking confirmation popup
 *
 * @param array $booking_instant_data
 * @param array $generated_invoice
 *
 * @return void
 */
function display_booking_confirm_popup(array $booking_instant_data, array $generated_invoice)
{
    // To display the initial room data(from post request) on the checkout page. Because during booking listing ID can be changed depending on booking type
    $booking_instant_data['property_id'] = intval($_POST['listing_edit']);

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
 * @param array $booking_full_data
 * @param array $generated_invoice
 * @param bool $is_group_booking //To separate group booking from a standard single booking
 * @param array $rooms_group_data_to_book
 *
 * @return void
 */
function update_necessary_metas(
    array $booking_full_data,
    array $generated_invoice,
    bool $is_group_booking,
    array $rooms_group_data_to_book = []
) {
    $booking_instant_data = $booking_full_data['booking_instant_data'];
    $invoice_id           = $generated_invoice['invoice_id'];

    // ######## Start of saving data to Invoice during single room(or cottage) booking ########

    if ($booking_instant_data['make_the_book']['booking_array']['balance'] > 0) {
        update_post_meta($invoice_id, 'invoice_status_full', 'waiting');
    }

    if ($booking_instant_data['make_the_book']['booking_array']['balance'] == 0) {
        update_post_meta($invoice_id, 'is_full_instant', 1);
        update_post_meta($booking_instant_data['make_the_book']['booking_id'], 'is_full_instant', 1);
    }

    update_post_meta($invoice_id, 'is_group_booking', $is_group_booking);
    update_post_meta($invoice_id, 'booking_full_data', json_encode($booking_full_data));

    // ######## End of saving data to Invoice during single room(or cottage) booking ########

    // The case when booked rooms group by Timeshare user
    if ($is_group_booking) {
        // Save data of current booked rooms group in invoice
        update_post_meta($invoice_id, 'rooms_group_data_to_book', json_encode($rooms_group_data_to_book));

        if ( ! empty($booking_full_data['booking_instant_rooms_group_data'])) {
            $rooms_group_booking_id_list = [];

            foreach ($booking_full_data['booking_instant_rooms_group_data'] as $booking_instant_current_room_data) {
                // Get all other room booking ids
                $rooms_group_booking_id_list[] = $booking_instant_current_room_data['make_the_book']['booking_id'];
            }

            if ( ! empty($rooms_group_booking_id_list)) {
                foreach ($rooms_group_booking_id_list as $current_room_booking_id) {
                    update_post_meta($current_room_booking_id, 'is_group_booking', true);

                    update_post_meta(
                        $current_room_booking_id,
                        'booking_full_data',
                        json_encode($booking_full_data)
                    );

                    // Save data of current booked rooms group in booking request
                    update_post_meta(
                        $current_room_booking_id,
                        'rooms_group_data_to_book',
                        json_encode($rooms_group_data_to_book)
                    );
                    // To avoid issues after booking in My Bookings page
                    update_post_meta($current_room_booking_id, 'booking_invoice_no', $invoice_id);
                }
            }
        }
    } else {
        update_post_meta($booking_instant_data['make_the_book']['booking_id'], 'is_group_booking', false);

        update_post_meta(
            $booking_instant_data['make_the_book']['booking_id'],
            'booking_full_data',
            json_encode($booking_full_data)
        );
    }
}