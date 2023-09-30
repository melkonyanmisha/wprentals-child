<?php

/**
 * Returns the key of month difference
 * Depends on the difference between the booking start day and the current day.
 *
 * @param string $from_date
 *
 * @return string
 * @throws Exception
 */
function timeshare_get_discount_months_diff(string $from_date): string
{
    $current_date              = date('Y-m-d');
    $current_date_obj          = new DateTime($current_date);
    $from_date_obj_increasable = new DateTime($from_date);
    $interval_obj              = $from_date_obj_increasable->diff($current_date_obj);
    $interval_by_months        = $interval_obj->m + 12 * $interval_obj->y;

    if ($interval_by_months < 2) {
        $key_discount_months_diff = 'less_two';
    } elseif ($interval_by_months <= 4) {
        $key_discount_months_diff = 'two_four_before';
    } elseif ($interval_by_months <= 6) {
        $key_discount_months_diff = 'four_six_before';
    } else {
        $key_discount_months_diff = 'more_six';
    }

    return $key_discount_months_diff;
}

/**
 * Returns the booked total days
 *
 * @param string $from_date
 * @param string $to_date
 *
 * @return false|int
 */
function get_booked_days_count(string $from_date, string $to_date)
{
    // Convert date strings to DateTime objects
    $date1Obj = DateTime::createFromFormat('y-m-d', $from_date);
    $date2Obj = DateTime::createFromFormat('y-m-d', $to_date);

    // Calculate the difference between the two dates
    $interval = $date2Obj->diff($date1Obj);

    return $interval->days;
}

/**
 * Retrieve Timeshare user booking data from the Session
 *
 * @return array
 */
function get_session_timeshare_booking_data(): array
{
    return current_user_is_timeshare() && ! empty($_SESSION['timeshare']) ? $_SESSION['timeshare'] : [];
}

/**
 * Set Timeshare user booking data into the SESSION
 *
 * @param int $buyer_id
 * @param int $percent
 * @param array $booking_array
 *
 * @return void
 */
function set_session_timeshare_booking_data(int $buyer_id, int $percent, array $booking_array): void
{
    if (current_user_is_timeshare()) {
        $booking_id                                                                        = $booking_array['booking_id'];
        $_SESSION['timeshare'][$buyer_id][$booking_id]['booking_instant']                  = $booking_array;
        $_SESSION['timeshare'][$buyer_id][$booking_id]['booking_instant']['price_percent'] = $percent;
    }
}

/**
 * Return array of week names and days
 *
 * @param string $from_date_converted
 * @param string $to_date_converted
 *
 * @return array
 * @throws Exception
 */
function generateBookedDaysInfo(string $from_date_converted, string $to_date_converted): array
{
    $date1 = new DateTime($from_date_converted);
    $date2 = new DateTime($to_date_converted);

    $interval  = new DateInterval('P1D'); // 1 day interval
    $dateRange = new DatePeriod($date1, $interval, $date2);

    $week_days_list = [];
    foreach ($dateRange as $date) {
        // https://www.php.net/manual/en/datetime.format.php
        $current_week_name = strtolower($date->format('l')); // 'l' Returns day of the week
        $current_day       = strtolower($date->format('Y-m-d'));

        $week_days_list[] = [
            'day'       => $current_day,
            'week_name' => $current_week_name
        ];
    }

    return $week_days_list;
}

/**
 * Retrieve calculated price
 *
 * @param string $from_date
 * @param string $to_date
 * @param bool $force
 *
 * @return float
 */
function get_discount_percent(string $from_date, string $to_date, bool $force = false): float
{
    try {
        $percent = 100;

        if ( ! $force && ! current_user_is_timeshare()) {
            return $percent;
        }

        $timeshare_price_calc_data = json_decode(get_option(TIMESHARE_PRICE_CALC_DATA), true);

        if ( ! ($timeshare_price_calc_data)) {
            return $percent;
        }

        $from_date_converted                   = convert_date_format($from_date);
        $to_date_converted                     = convert_date_format($to_date);
        $discount_months_diff                  = timeshare_get_discount_months_diff($from_date_converted);
        $necessarily_timeshare_price_calc_data = $timeshare_price_calc_data[$discount_months_diff] ?? [];

        //Case for All Season(now exist in "Less than 2 months")
        if (isset($necessarily_timeshare_price_calc_data['all']['discount_mode']['yearly_percent'])) {
            $percent = $necessarily_timeshare_price_calc_data['all']['discount_mode']['yearly_percent'];
        } else {
            $from_date_obj_increasable      = new DateTime($from_date_converted);
            $to_date_obj                    = new DateTime($to_date_converted);
            $from_to_interval               = $from_date_obj_increasable->diff($to_date_obj);
            $interval_days                  = $from_to_interval->days;
            $booked_days_info               = generateBookedDaysInfo($from_date_converted, $to_date_converted);
            $booked_days_with_percents_info = [];

            foreach ($necessarily_timeshare_price_calc_data as $season => $season_info) {
                // The case when successfully calculated the percent
                if ($percent !== 100) {
                    break;
                }

                if ( ! empty($season_info['date_range'])) {
                    foreach ($season_info['date_range'] as $current_date_range_info) {
                        $current_date_range_from = new DateTime($current_date_range_info['from']);
                        $current_date_range_to   = new DateTime($current_date_range_info['to']);

                        // The case, when booked start date exists between dates of current Season.
                        if ($from_date_obj_increasable >= $current_date_range_from && $from_date_obj_increasable <= $current_date_range_to) {
                            if ($season_info['discount_mode']['mode'] === 'always') {
                                $percent = $season_info['discount_mode']['always_percent'] ?? $percent;
                            } else {
                                $discount_percent = 0;

                                if ( ! empty($booked_days_info) && ! empty($season_info['discount_mode']['weeks'])) {
                                    foreach ($booked_days_info as $key => $current_day_info) {
                                        foreach ($season_info['discount_mode']['weeks'] as $current_week) {
                                            if (array_key_exists(
                                                    $current_day_info['week_name'],
                                                    $current_week
                                                ) && $current_week[$current_day_info['week_name']]) {
                                                $booked_days_with_percents_info[$key]['day']       = $current_day_info['day'];
                                                $booked_days_with_percents_info[$key]['week_name'] = $current_day_info['week_name'];
                                                $booked_days_with_percents_info[$key]['percent']   = floatval(
                                                    $current_week['daily_percent']
                                                );
                                            }
                                        }
                                    }
                                }

                                // The case when the required data was not generated
                                if (empty($booked_days_with_percents_info)) {
                                    break;
                                }

                                // Case when booked less than a week
                                if ($interval_days <= 7) {
                                    if ( ! empty($season_info['discount_mode']['weeks'])) {
                                        // Calculate the sum of percents by per day
                                        foreach ($booked_days_with_percents_info as $current_day_info) {
                                            $discount_percent += $current_day_info['percent'];
                                        }

                                        $percent = $discount_percent;
                                    }
                                } else {  // The case when booked more than 7days.
                                    $remaining_days_discount_percent = 0;

                                    // Calculate how many weeks are in $interval_days
                                    $weeks_count_in_interval = floor($interval_days / 7);
                                    // Calculate how many days are left
                                    $remaining_days_count = $interval_days % 7;

                                    // The first part of percent. Depends on how many weeks in $interval_days.
                                    $weekly_percent = $weeks_count_in_interval * floatval(
                                            $season_info['discount_mode']['weekly_percent']
                                        );

                                    //The second part of percent. Get percent per remaining days, e.g. for remaining tuesday and wednesday
                                    $booked_remaining_days_with_percents_info = array_slice(
                                        $booked_days_with_percents_info,
                                        -$remaining_days_count
                                    );

                                    // Calculate the sum of percents
                                    foreach ($booked_remaining_days_with_percents_info as $current_day_info) {
                                        $remaining_days_discount_percent += $current_day_info['percent'];
                                    }

                                    $percent = $weekly_percent + $remaining_days_discount_percent;
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }
    } catch (Exception|Error $e) {
        wp_die('Error: ' . $e->getMessage());
    }

    return floatval($percent);
}

/**
 * Calculate new price. Depends on discount percent
 *
 * @param float $discount_percent
 * @param float $price
 * @param string $from_date
 * @param string $to_date
 *
 * @return array
 */
function timeshare_discount_price_calc(
    float $discount_percent,
    float $price,
    string $from_date,
    string $to_date
): array {
    try {
        $current_user_is_timeshare = current_user_is_timeshare();
        $discount_price_calc       = [
            'booked_by_timeshare_user' => $current_user_is_timeshare,
            'calculated_price'         => $price,
            'timeshare_user_calc'      => [],
        ];

        // New calculation accessible for booking by timeshare users.
        if ($price == 0 || ! $current_user_is_timeshare) {
            return $discount_price_calc;
        }

        $timeshare_user_data_encoded = get_user_meta(get_current_user_id(), TIMESHARE_USER_DATA);
        $timeshare_user_data_decoded = ! empty($timeshare_user_data_encoded) ? json_decode(
            $timeshare_user_data_encoded[0],
            true
        ) : [];
        $timeshare_package_duration  = ! empty($timeshare_user_data_decoded) ? $timeshare_user_data_decoded[TIMESHARE_PACKAGE_DURATION] : TIMESHARE_PACKAGE_DEFAULT_DURATION_VALUE;
        $booked_days_count           = get_booked_days_count($from_date, $to_date);

        // Case when trying to book less than a timeshare package duration
        if ($timeshare_package_duration >= $booked_days_count) {
            $accessible_days_count = $booked_days_count;
            $calculated_price      = $price * $discount_percent / 100;

            $discount_price_calc['calculated_price']    = $calculated_price;
            $discount_price_calc['timeshare_user_calc'] = [
                'discount_percent'                     => $discount_percent,
                'timeshare_package_duration'           => $timeshare_package_duration,
                'accessible_days_count'                => $accessible_days_count,
                'discounted_price_for_accessible_days' => $calculated_price,
                'remaining_days_count'                 => 0,
                'remaining_days_price'                 => 0,
            ];
        } else {
            $accessible_days_count = $timeshare_package_duration;
            // Divide price calculation by part
            $price_per_day_before_discount = $price / $booked_days_count;
            // Price for Timeshare user depends on accessible days of package duration
            $discounted_price_for_accessible_days = $timeshare_package_duration * $price_per_day_before_discount * $discount_percent / 100;

            $remaining_days_count = $booked_days_count - $timeshare_package_duration;
            // Calculate as for a standard user(Customer)
            $remaining_days_price = $price_per_day_before_discount * $remaining_days_count;
            // Calculated Total Price
            $calculated_price = $discounted_price_for_accessible_days + $remaining_days_price;

            $discount_price_calc['calculated_price']    = $calculated_price;
            $discount_price_calc['timeshare_user_calc'] = [
                'discount_percent'                     => $discount_percent,
                'timeshare_package_duration'           => $timeshare_package_duration,
                'accessible_days_count'                => $accessible_days_count,
                'discounted_price_for_accessible_days' => $discounted_price_for_accessible_days,
                'remaining_days_count'                 => $remaining_days_count,
                'remaining_days_price'                 => $remaining_days_price,
            ];
        }
    } catch (Exception|Error $e) {
        wp_die('Error: ' . $e->getMessage());
    }

    return $discount_price_calc;
}

/**
 * Check booking availability
 * Run after ajax call
 * add_action('wp_ajax_wpestate_ajax_check_booking_valability', 'wpestate_ajax_check_booking_valability' );
 * add_action('wp_ajax_nopriv_wpestate_ajax_check_booking_valability', 'wpestate_ajax_check_booking_valability' );
 *
 * @return void
 * @throws Exception
 */
function wpestate_ajax_check_booking_valability(): void
{
    $wpestate_book_from    = esc_html($_POST['book_from']);
    $wpestate_book_to      = esc_html($_POST['book_to']);
    $listing_id            = intval($_POST['listing_id']);
    $internal              = intval($_POST['internal']);
    $mega                  = wpml_mega_details_adjust($listing_id);
    $wprentals_is_per_hour = wprentals_return_booking_type($listing_id);
    $wpestate_book_from    = wpestate_convert_dateformat($wpestate_book_from);
    $wpestate_book_to      = wpestate_convert_dateformat($wpestate_book_to);
    $from_date             = new DateTime($wpestate_book_from);
    $from_date_unix        = $from_date->getTimestamp();
    $to_date               = new DateTime($wpestate_book_to);
    $to_date_unix_check    = $to_date->getTimestamp();
    $date_checker          = strtotime(date("Y-m-d 00:00", $from_date_unix));

    // All listing ID's in single group
    $listings_ids_list = current_user_is_timeshare() && check_has_room_group(
        $listing_id
    ) ? get_all_listings_ids_in_group($listing_id) : [$listing_id];

    $to_date_unix = $to_date->getTimestamp();
    if ($wprentals_is_per_hour == 2) {
        $diff = 3600;
    } else {
        $diff = 86400;
    }

    //check min days situation
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    if ($internal == 0) {
        $min_days_booking = intval(get_post_meta($listing_id, 'min_days_booking', true));

        if (is_array($mega) && array_key_exists($date_checker, $mega)) {
            if (isset($mega[$date_checker]['period_min_days_booking'])) {
                $min_days_value = $mega[$date_checker]['period_min_days_booking'];

                if (abs($from_date_unix - $to_date_unix) / $diff < $min_days_value) {
                    print 'stopdays';
                    die();
                }
            }
        } elseif ($min_days_booking > 0) {
            if (abs($from_date_unix - $to_date_unix) / $diff < $min_days_booking) {
                print 'stopdays';
                die();
            }
        }
    }

    // check in check out days
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $checkin_checkout_change_over = floatval(get_post_meta($listing_id, 'checkin_checkout_change_over', true));
    $weekday                      = date('N', $from_date_unix);
    $end_bookday                  = date('N', $to_date_unix_check);
    if (is_array($mega) && array_key_exists($from_date_unix, $mega)) {
        if (isset($mega[$from_date_unix]['period_checkin_checkout_change_over']) && $mega[$from_date_unix]['period_checkin_checkout_change_over'] != 0) {
            $period_checkin_checkout_change_over = $mega[$from_date_unix]['period_checkin_checkout_change_over'];

            if ($weekday != $period_checkin_checkout_change_over || $end_bookday != $period_checkin_checkout_change_over) {
                print 'stopcheckinout';
                die();
            }
        }
    } elseif ($checkin_checkout_change_over > 0) {
        if ($weekday != $checkin_checkout_change_over || $end_bookday != $checkin_checkout_change_over) {
            print 'stopcheckinout';
            die();
        }
    }

    // check in  days
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    $checkin_change_over = floatval(get_post_meta($listing_id, 'checkin_change_over', true));

    if (is_array($mega) && array_key_exists($from_date_unix, $mega)) {
        if (isset($mega[$from_date_unix]['period_checkin_change_over']) && $mega[$from_date_unix]['period_checkin_change_over'] != 0) {
            $period_checkin_change_over = $mega[$from_date_unix]['period_checkin_change_over'];

            if ($weekday != $period_checkin_change_over) {
                print 'stopcheckin';
                die();
            }
        }
    } elseif ($checkin_change_over > 0) {
        if ($weekday != $checkin_change_over) {
            print 'stopcheckin';
            die();
        }
    }

    //todo@@@ check to remove the comment

    // The case when booked Room group by timeshare user. Will add reservation data from all listings in array
//    if (current_user_is_timeshare() && ! empty($listings_ids_list)) {
//        $reservation_grouped_array = get_reservation_grouped_array($listings_ids_list);
//    } else {
//        $reservation_grouped_array[] = get_post_meta($listing_id, 'booking_dates', true);
//        if ($reservation_grouped_array[0] == '') {
//            $reservation_grouped_array[] = wpestate_get_booking_dates($listing_id);
//        }
//    }

    $reservation_grouped_array = get_reservation_grouped_array($listings_ids_list);

    if ( ! empty($reservation_grouped_array)) {
        foreach ($reservation_grouped_array as $reservation_array) {
            if ( ! empty($reservation_array) && array_key_exists($from_date_unix, $reservation_array)) {
                print 'stop array_key_exists';
                die();
            }
        }
    }

    if ( ! $wprentals_is_per_hour == 2) {
        $to_date->modify('yesterday');
    }
    $to_date_unix = $to_date->getTimestamp();

    // checking booking avalability
    if ($wprentals_is_per_hour == 2) {
        foreach ($reservation_grouped_array as $reservation_array) {
            if (is_array(
                    $reservation_array
                ) && ! empty($reservation_array) && wprentals_check_hour_booking_overlap_reservations(
                    $from_date_unix,
                    $to_date_unix,
                    $reservation_array
                )) {
                print 'stop hour';
                die();
            }
        }
    } else {
        foreach ($reservation_grouped_array as $reservation_array) {
            if (is_array(
                    $reservation_array
                ) && ! empty($reservation_array) && wprentals_check_booking_overlap_reservations(
                    $from_date,
                    $from_date_unix,
                    $to_date_unix,
                    $reservation_array
                )) {
                print 'stop';
                die();
            }
        }
    }

    print 'run';
    die();
}

/**
 * Get array of group IDs with ascending by Group Order
 *
 * @return array
 */
function get_group_ids_by_room_group_order(): array
{
    global $wpdb;
    $group_with_max_room_group_order = get_group_with_max_room_group_order();

    // Initialize an array to store the Group IDs
    $group_ids_by_order = [];

    if ( ! empty($group_with_max_room_group_order->order)) {
        // Loop through the max Group Order
        for ($order = 1; $order <= $group_with_max_room_group_order->order; $order++) {
            // Custom database query to retrieve term IDs with the current Group Order'
            $term_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = %s AND meta_value = %d",
                    ROOM_GROUP_ORDER,
                    $order
                )
            );

            if ($term_id) {
                $group_ids_by_order[] = intval($term_id);
            }
        }
    }

    return $group_ids_by_order;
}

/**
 * Retrieve rooms group data to book for timeshare user
 *
 * @param array $group_ids_by_room_group_order
 * @param int $from_date_unix
 *
 * @return array
 */
function get_rooms_group_data_to_book(array $group_ids_by_room_group_order, int $from_date_unix): array
{
    $rooms_group_data_to_book = [];

    if ( ! empty($group_ids_by_room_group_order)) {
        foreach ($group_ids_by_room_group_order as $current_group_id) {
            if ( ! empty($rooms_group_data_to_book)) {
                break;
            }

            foreach (get_reservation_grouped_array_by_group_id($current_group_id) as $room_id => $reservation_array) {
                if (is_array($reservation_array) && array_key_exists($from_date_unix, $reservation_array)) {
                    break;
                }

                $rooms_group_data_to_book['group_link']  = get_term_link($current_group_id);
                $rooms_group_data_to_book['group_id']    = $current_group_id;
                $rooms_group_data_to_book['rooms_ids'][] = $room_id;
            }
        }
    }

    return $rooms_group_data_to_book;
}

/**
 * Retrieve reservation data for all listings by Group ID
 *
 * @param int $group_id
 *
 * @return array
 */
function get_reservation_grouped_array_by_group_id(int $group_id): array
{
    $taxonomy  = 'property_action_category';
    $post_type = 'estate_property';

    $args  = array(
        'post_type' => $post_type,
        'tax_query' => array(
            array(
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $group_id,
            ),
        )
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $all_listings_ids_in_group = [];
        foreach ($query->posts as $post) {
            $all_listings_ids_in_group[] = $post->ID;
        }

        return get_reservation_grouped_array($all_listings_ids_in_group);
    }

    return [];
}

/**
 * @param string $billing_for
 * @param string $type
 * @param int $pack_id
 * @param string $date
 * @param int $user_id
 * @param string $is_featured
 * @param string $is_upgrade
 * @param string $paypal_tax_id
 * @param array $details
 * @param float $price
 * @param int $author_id
 *
 * @return int|WP_Error
 */
function wpestate_booking_insert_invoice(
    string $billing_for,
    string $type,
    int $pack_id,
    string $date,
    int $user_id,
    string $is_featured,
    string $is_upgrade,
    string $paypal_tax_id,
    array $details,
    float $price,
    int $author_id = 0
) {
    $post = array(
        'post_title'  => 'Invoice ',
        'post_status' => 'publish',
        'post_type'   => 'wpestate_invoice',
    );

    if ($author_id) {
        $post['post_author'] = $author_id;
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

/**
 * Original function location is wp-content/themes/wprentals/libs/help_functions.php => wpestate_booking_price()
 *
 * @param int $current_guest_no
 * @param int $property_id
 * @param string $from_date
 * @param string $to_date
 * @param int $bookid
 * @param $extra_options_array
 * @param $manual_expenses
 *
 * @return array
 * @throws Exception
 */
function wpestate_booking_price(
    int $current_guest_no,
    $invoice_id, // Bug in parent theme maybe coming a string, but correct is int
    int $property_id,
    string $from_date,
    string $to_date,
    int $bookid = 0,
    // Not declared to avoid issues. Bug in parent theme maybe coming a string, but correct is array
    $extra_options_array = [],
    // Not declared to avoid issues. Bug in parent theme maybe coming a string, but correct is array
    $manual_expenses = []
): array {
    // Try to get booking_full_data from DB.
    $booking_full_data = json_decode(get_post_meta($invoice_id, 'booking_full_data', true), true);

    // The case when booking process successfully finished and created an invoice
    if ( ! empty($booking_full_data['booking_instant_data']['make_the_book']['booking_array'])) {
        // Retrieve and return the booking_array
        return $booking_full_data['booking_instant_data']['make_the_book']['booking_array'];
    }

    $wprentals_is_per_hour = wprentals_return_booking_type($property_id);
    $price_array           = wpml_custom_price_adjust($property_id);
    $mega                  = wpml_mega_details_adjust($property_id);
    $price_per_weekeend    = floatval(get_post_meta($property_id, 'price_per_weekeend', true));
    $setup_weekend_status  = esc_html(wprentals_get_option('wp_estate_setup_weekend', ''));
    $include_expeses       = esc_html(wprentals_get_option('wp_estate_include_expenses', ''));
    $booking_from_date     = $from_date;
    $booking_to_date       = $to_date;
    $total_guests          = floatval(get_post_meta($bookid, 'booking_guests', true));
    $classic_period_days   = wprentals_return_standart_days_period();

    $numberDays = 1;
    if ($invoice_id == 0) {
        $price_per_day        = floatval(get_post_meta($property_id, 'property_price', true));
        $week_price           = floatval(get_post_meta($property_id, 'property_price_per_week', true));
        $month_price          = floatval(get_post_meta($property_id, 'property_price_per_month', true));
        $cleaning_fee         = floatval(get_post_meta($property_id, 'cleaning_fee', true));
        $city_fee             = floatval(get_post_meta($property_id, 'city_fee', true));
        $cleaning_fee_per_day = floatval(get_post_meta($property_id, 'cleaning_fee_per_day', true));
        $city_fee_per_day     = floatval(get_post_meta($property_id, 'city_fee_per_day', true));
        $city_fee_percent     = floatval(get_post_meta($property_id, 'city_fee_percent', true));
        $security_deposit     = floatval(get_post_meta($property_id, 'security_deposit', true));
        $early_bird_percent   = floatval(get_post_meta($property_id, 'early_bird_percent', true));
        $early_bird_days      = floatval(get_post_meta($property_id, 'early_bird_days', true));
    } else {
        $price_per_day        = floatval(get_post_meta($invoice_id, 'default_price', true));
        $week_price           = floatval(get_post_meta($invoice_id, 'week_price', true));
        $month_price          = floatval(get_post_meta($invoice_id, 'month_price', true));
        $cleaning_fee         = floatval(get_post_meta($invoice_id, 'cleaning_fee', true));
        $city_fee             = floatval(get_post_meta($invoice_id, 'city_fee', true));
        $cleaning_fee_per_day = floatval(get_post_meta($invoice_id, 'cleaning_fee_per_day', true));
        $city_fee_per_day     = floatval(get_post_meta($invoice_id, 'city_fee_per_day', true));
        $city_fee_percent     = floatval(get_post_meta($invoice_id, 'city_fee_percent', true));
        $security_deposit     = floatval(get_post_meta($invoice_id, 'security_deposit', true));
        $early_bird_percent   = floatval(get_post_meta($invoice_id, 'early_bird_percent', true));
        $early_bird_days      = floatval(get_post_meta($invoice_id, 'early_bird_days', true));
    }

    $from_date_obj = new DateTime($booking_from_date);
    // Duplicated the new DateTime($booking_from_date) to avoid issues after using wprentals_increase_time_unit()
    $from_date_obj_increasable = new DateTime($booking_from_date);
    $from_date_unix            = $from_date_obj_increasable->getTimestamp();
    $date_checker              = strtotime(date("Y-m-d 00:00", $from_date_unix));
    $from_date_discount        = $from_date_obj_increasable->getTimestamp();
    $to_date_obj               = new DateTime($booking_to_date);
    $to_date_unix              = $to_date_obj->getTimestamp();
    $total_price               = 0;
    $inter_price               = 0;
    $has_custom                = 0;
    $usable_price              = 0;
    $has_wkend_price           = 0;
    $cover_weekend             = 0;
    $custom_period_quest       = 0;
    $custom_price_array        = array();
    $timeDiff                  = abs(strtotime($booking_to_date) - strtotime($booking_from_date));

    if ($wprentals_is_per_hour == 2) {
        //per h
        $count_days = wprentals_compute_no_of_hours($booking_from_date, $booking_to_date, $property_id);
    } else {
        //per day
        $count_days = $timeDiff / 86400;  // 86400 seconds in one day
    }

    $count_days = intval($count_days);

    //check extra price per guest
    ///////////////////////////////////////////////////////////////////////////
    $extra_price_per_guest       = floatval(get_post_meta($property_id, 'extra_price_per_guest', true));
    $price_per_guest_from_one    = floatval(get_post_meta($property_id, 'price_per_guest_from_one', true));
    $overload_guest              = floatval(get_post_meta($property_id, 'overload_guest', true));
    $guestnumber                 = floatval(get_post_meta($property_id, 'guest_no', true));
    $booking_start_hour_string   = get_post_meta($property_id, 'booking_start_hour', true);
    $booking_end_hour_string     = get_post_meta($property_id, 'booking_end_hour', true);
    $booking_start_hour          = intval($booking_start_hour_string);
    $booking_end_hour            = intval($booking_end_hour_string);
    $has_guest_overload          = 0;
    $total_extra_price_per_guest = 0;
    $extra_guests                = 0;

    if ($price_per_guest_from_one == 0) {
        ///////////////////////////////////////////////////////////////
        //  per day math
        ////////////////////////////////////////////////////////////////
        //period_price_per_month,period_price_per_week
        //discoutn prices for month and week
        ///////////////////////////////////////////////////////////////////////////
        if ($count_days >= $classic_period_days['week_days'] && $week_price != 0) { // if more than 7 days booked
            $price_per_day = $week_price;
        }

        if ($count_days >= $classic_period_days['month_days'] && $month_price != 0) {
            $price_per_day = $month_price;
        }

        //custom prices - check the first day
        ///////////////////////////////////////////////////////////////////////////
        if (isset($price_array[$date_checker])) {
            $has_custom                         = 1;
            $custom_price_array [$date_checker] = $price_array[$date_checker];
        }

        if (isset($mega[$date_checker]) && isset($mega[$date_checker]['period_price_per_weekeend']) && $mega[$date_checker]['period_price_per_weekeend'] != 0) {
            $has_wkend_price = 1;
        }

        if ($overload_guest == 1) {  // if we allow overload
            if ($current_guest_no > $guestnumber) {
                $has_guest_overload = 1;
                $extra_guests       = $current_guest_no - $guestnumber;
                if (isset($mega[$date_checker]) && isset($mega[$date_checker]['period_price_per_weekeend'])) {
                    $total_extra_price_per_guest = $total_extra_price_per_guest + $extra_guests * $mega[$date_checker]['period_extra_price_per_guest'];
                    $custom_period_quest         = 1;
                } else {
                    $total_extra_price_per_guest = $total_extra_price_per_guest + $extra_guests * $extra_price_per_guest;
                }
            }
        }

        if ($price_per_weekeend != 0) {
            $has_wkend_price = 1;
        }

        $usable_price = wpestate_return_custom_price(
            $date_checker,
            $mega,
            $price_per_weekeend,
            $price_array,
            $price_per_day,
            $count_days
        );
        $total_price  = $total_price + $usable_price;

        $inter_price                        = $inter_price + $usable_price;
        $custom_price_array [$date_checker] = $usable_price;
        $from_date_unix_first_day           = $from_date_obj_increasable->getTimestamp();
        $from_date_obj_increasable          = wprentals_increase_time_unit(
            $wprentals_is_per_hour,
            $from_date_obj_increasable
        );
        $from_date_unix                     = $from_date_obj_increasable->getTimestamp();
        $date_checker                       = strtotime(date("Y-m-d 00:00", $from_date_unix));
        $weekday                            = date('N', $from_date_unix_first_day); // 1-7
        if (wpestate_is_cover_weekend($weekday, $has_wkend_price, $setup_weekend_status)) {
            $cover_weekend = 1;
        }

        // loop trough the dates
        //////////////////////////////////////////////////////////////////////////
        while ($from_date_unix < $to_date_unix) {
            $skip_a_beat = 1;
            if ($wprentals_is_per_hour == 2) { //is per h
                $current_hour = $from_date_obj_increasable->format('H');

                if ($booking_start_hour_string == '' && $booking_end_hour_string == '') {
                    $skip_a_beat = 1;
                } else {
                    if ($booking_end_hour > $current_hour && $booking_start_hour <= $current_hour) {
                        $skip_a_beat = 1;
                    } else {
                        $skip_a_beat = 0;
                    }
                }
            }

            if ($skip_a_beat == 1) {
                $numberDays++;
                if (isset($price_array[$date_checker])) {
                    $has_custom = 1;
                }

                if (isset($mega[$date_checker]) && isset($mega[$date_checker]['period_price_per_weekeend']) && $mega[$date_checker]['period_price_per_weekeend'] != 0) {
                    $has_wkend_price = 1;
                }

                if ($overload_guest == 1) {  // if we allow overload
                    if ($current_guest_no > $guestnumber) {
                        $has_guest_overload = 1;
                        $extra_guests       = $current_guest_no - $guestnumber;
                        if (isset($mega[$date_checker]) && isset($mega[$date_checker]['period_price_per_weekeend'])) {
                            $total_extra_price_per_guest = $total_extra_price_per_guest + $extra_guests * $mega[$date_checker]['period_extra_price_per_guest'];
                            $custom_period_quest         = 1;
                        } else {
                            $total_extra_price_per_guest = $total_extra_price_per_guest + $extra_guests * $extra_price_per_guest;
                        }
                    }
                }

                if ($price_per_weekeend != 0) {
                    $has_wkend_price = 1;
                }


                $weekday = date('N', $from_date_unix); // 1-7
                if (wpestate_is_cover_weekend($weekday, $has_wkend_price, $setup_weekend_status)) {
                    $cover_weekend = 1;
                }

                $usable_price = wpestate_return_custom_price(
                    $date_checker,
                    $mega,
                    $price_per_weekeend,
                    $price_array,
                    $price_per_day,
                    $count_days
                );
                $total_price  = $total_price + $usable_price;

                $inter_price                        = $inter_price + $usable_price;
                $custom_price_array [$date_checker] = $usable_price;
            }//end skip a beat


            $from_date_obj_increasable = wprentals_increase_time_unit(
                $wprentals_is_per_hour,
                $from_date_obj_increasable
            );
            $from_date_unix            = $from_date_obj_increasable->getTimestamp();
            $date_checker              = strtotime(date("Y-m-d 00:00", $from_date_unix));
        }
    } else {
        $custom_period_quest = 0;

        ///////////////////////////////////////////////////////////////
        //  per guest math
        ////////////////////////////////////////////////////////////////

        if (isset($mega[$date_checker]['period_extra_price_per_guest'])) {
            $total_price                        = $current_guest_no * $mega[$date_checker]['period_extra_price_per_guest'];
            $inter_price                        = $current_guest_no * $mega[$date_checker]['period_extra_price_per_guest'];
            $custom_price_array [$date_checker] = $current_guest_no * $mega[$date_checker]['period_extra_price_per_guest'];
            $custom_period_quest                = 1;
        } else {
            $total_price = $current_guest_no * $extra_price_per_guest;
            $inter_price = $current_guest_no * $extra_price_per_guest;
        }

        $from_date_obj_increasable = wprentals_increase_time_unit($wprentals_is_per_hour, $from_date_obj_increasable);
        $from_date_unix            = $from_date_obj_increasable->getTimestamp();
        $date_checker              = strtotime(date("Y-m-d 00:00", $from_date_unix));

        while ($from_date_unix < $to_date_unix) {
            $skip_a_beat = 1;
            if ($wprentals_is_per_hour == 2) { //is per h
                $current_hour = $from_date_obj_increasable->format('H');

                if ($booking_start_hour_string == '' && $booking_end_hour_string == '') {
                    $skip_a_beat = 1;
                } else {
                    if ($booking_end_hour > $current_hour && $booking_start_hour <= $current_hour) {
                        $skip_a_beat = 1;
                    } else {
                        $skip_a_beat = 0;
                    }
                }
            }

            if ($skip_a_beat == 1) {
                $numberDays++;

                if (isset($mega[$date_checker]['period_extra_price_per_guest'])) {
                    $total_price                        = $total_price + $current_guest_no * $mega[$date_checker]['period_extra_price_per_guest'];
                    $inter_price                        = $inter_price + $current_guest_no * $mega[$date_checker]['period_extra_price_per_guest'];
                    $custom_price_array [$date_checker] = $current_guest_no * $mega[$date_checker]['period_extra_price_per_guest'];


                    $custom_period_quest = 1;
                } else {
                    $total_price = $total_price + $current_guest_no * $extra_price_per_guest;
                    $inter_price = $inter_price + $current_guest_no * $extra_price_per_guest;
                }
            }

            $from_date_obj_increasable = wprentals_increase_time_unit(
                $wprentals_is_per_hour,
                $from_date_obj_increasable
            );
            $from_date_unix            = $from_date_obj_increasable->getTimestamp();

            if ($wprentals_is_per_hour != 2) {
                $date_checker = $from_date_obj_increasable->getTimestamp();
            }
        }
    }// end per guest math

    $wp_estate_book_down           = floatval(wprentals_get_option('wp_estate_book_down', ''));
    $wp_estate_book_down_fixed_fee = floatval(wprentals_get_option('wp_estate_book_down_fixed_fee', ''));

    if (is_array($extra_options_array) && ! empty($extra_options_array)) {
        $extra_pay_options = get_post_meta($property_id, 'extra_pay_options', true);

        foreach ($extra_options_array as $key => $value) {
            if (isset($extra_pay_options[$value][0])) {
                $extra_option_value = wpestate_calculate_extra_options_value(
                    $count_days,
                    $total_guests,
                    $extra_pay_options[$value][2],
                    $extra_pay_options[$value][1]
                );
                $total_price        = $total_price + $extra_option_value;
            }
        }
    }

    if (is_array($manual_expenses) && ! empty($manual_expenses)) {
        foreach ($manual_expenses as $key => $value) {
            if (floatval($value[1]) != 0) {
                $total_price = $total_price + floatval($value[1]);
            }
        }
    }

    // extra price per guest
    if ($has_guest_overload == 1 && $total_extra_price_per_guest > 0) {
        $total_price = $total_price + $total_extra_price_per_guest;
    }

    //early bird discount
    ///////////////////////////////////////////////////////////////////////////
    $early_bird_discount = wpestate_early_bird(
        $property_id,
        $early_bird_percent,
        $early_bird_days,
        $from_date_discount,
        $total_price
    );

    if ($early_bird_discount > 0) {
        $total_price = $total_price - $early_bird_discount;
    }

    //security depozit - refundable
    ///////////////////////////////////////////////////////////////////////////
    if (intval($security_deposit) != 0) {
        $total_price = $total_price + $security_deposit;
    }

    $total_price_before_extra = $total_price;

    //cleaning or city fee per day
    ///////////////////////////////////////////////////////////////////////////

    $cleaning_fee = wpestate_calculate_cleaning_fee(
        $property_id,
        $count_days,
        $current_guest_no,
        $cleaning_fee,
        $cleaning_fee_per_day
    );
    $city_fee     = wpestate_calculate_city_fee(
        $property_id,
        $count_days,
        $current_guest_no,
        $city_fee,
        $city_fee_per_day,
        $city_fee_percent,
        $inter_price
    );

    if ($cleaning_fee != 0 && $cleaning_fee != '') {
        $total_price = $total_price + $cleaning_fee;
    }

    if ($city_fee != 0 && $city_fee != '') {
        $total_price = $total_price + $city_fee;
    }

    if ($invoice_id == 0) {
        $price_for_service_fee = $total_price - $security_deposit - floatval($city_fee) - floatval($cleaning_fee);
        $service_fee           = wpestate_calculate_service_fee($price_for_service_fee, $invoice_id);
    } else {
        $service_fee = get_post_meta($invoice_id, 'service_fee', true);
    }

    if ($include_expeses == 'yes') {
        $deposit = wpestate_calculate_deposit($wp_estate_book_down, $wp_estate_book_down_fixed_fee, $total_price);
    } else {
        $deposit = wpestate_calculate_deposit(
            $wp_estate_book_down,
            $wp_estate_book_down_fixed_fee,
            $total_price_before_extra
        );
    }

    if (intval($invoice_id) == 0) {
        $you_earn = $total_price - $security_deposit - floatval($city_fee) - floatval($cleaning_fee) - $service_fee;
        update_post_meta($bookid, 'you_earn', $you_earn);
    } else {
        $you_earn = get_post_meta($bookid, 'you_earn', true);
    }

    $taxes = 0;

    if (intval($invoice_id) == 0) {
        $taxes_value = floatval(get_post_meta($property_id, 'property_taxes', true));
    } else {
        $taxes_value = floatval(get_post_meta($invoice_id, 'prop_taxed', true));
    }
    if ($taxes_value > 0) {
        $taxes = round($you_earn * $taxes_value / 100, 2);
    }

    if (intval($invoice_id) == 0) {
        update_post_meta($bookid, 'custom_price_array', $custom_price_array);
    } else {
        $custom_price_array = get_post_meta($bookid, 'custom_price_array', true);
    }

    $balance                                     = $total_price - $deposit;
    $return_array                                = array();
    $return_array['book_type']                   = $wprentals_is_per_hour;
    $return_array['default_price']               = $price_per_day;
    $return_array['week_price']                  = $week_price;
    $return_array['month_price']                 = $month_price;
    $return_array['total_price']                 = $total_price;
    $return_array['inter_price']                 = $inter_price;
    $return_array['balance']                     = $balance;
    $return_array['deposit']                     = $deposit;
    $return_array['from_date']                   = $from_date_obj;
    $return_array['to_date']                     = $to_date_obj;
    $return_array['cleaning_fee']                = $cleaning_fee;
    $return_array['city_fee']                    = $city_fee;
    $return_array['has_custom']                  = $has_custom;
    $return_array['custom_price_array']          = $custom_price_array;
    $return_array['numberDays']                  = $numberDays;
    $return_array['count_days']                  = $count_days;
    $return_array['has_wkend_price']             = $has_wkend_price;
    $return_array['has_guest_overload']          = $has_guest_overload;
    $return_array['total_extra_price_per_guest'] = $total_extra_price_per_guest;
    $return_array['extra_guests']                = $extra_guests;
    $return_array['extra_price_per_guest']       = $extra_price_per_guest;
    $return_array['price_per_guest_from_one']    = $price_per_guest_from_one;
    $return_array['curent_guest_no']             = $current_guest_no;
    $return_array['cover_weekend']               = $cover_weekend;
    $return_array['custom_period_quest']         = $custom_period_quest;
    $return_array['security_deposit']            = $security_deposit;
    $return_array['early_bird_discount']         = $early_bird_discount;
    $return_array['taxes']                       = $taxes;
    $return_array['service_fee']                 = $service_fee;
    $return_array['youearned']                   = $you_earn;

    return $return_array;
}