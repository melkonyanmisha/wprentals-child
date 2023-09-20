<?php

/**
 * @param int $listing_id
 *
 * @return bool
 */
function check_has_room_category(int $listing_id)
{
    $category_terms            = wp_get_post_terms($listing_id, 'property_category');
    $category_parent_terms_ids = wp_list_pluck($category_terms, 'parent');

    if ( ! empty($category_parent_terms_ids) && $category_parent_terms_ids[0] !== 0) {
        foreach ($category_parent_terms_ids as $current_category_parent_term_id) {
            $current_category_parent_term  = get_term($current_category_parent_term_id, 'property_category');
            $category_parent_terms_slugs[] = $current_category_parent_term->slug;
        }

        if (in_array('room', $category_parent_terms_slugs)) {
            return true;
        }
    }

    return false;
}


/**
 * @param int $listing_id
 *
 * @return bool
 */
function check_has_room_group(int $listing_id)
{
    $category_terms            = wp_get_post_terms($listing_id, 'property_action_category');
    $category_parent_terms_ids = wp_list_pluck($category_terms, 'parent');

    if ( ! empty($category_parent_terms_ids) && $category_parent_terms_ids[0] !== 0) {
        foreach ($category_parent_terms_ids as $current_category_parent_term_id) {
            $current_category_parent_term  = get_term($current_category_parent_term_id, 'property_action_category');
            $category_parent_terms_slugs[] = $current_category_parent_term->slug;
        }

        if (in_array('room-group', $category_parent_terms_slugs)) {
            return true;
        }
    }

    return false;
}


/**
 * Get listing ID's in single group
 *
 * @param int $listing_id
 *
 * @return array
 */
function get_all_listings_ids_in_group(int $listing_id): array
{
    $all_listings_ids_in_group = [];
    $all_listings_in_group     = get_all_listings_in_group($listing_id);

    if ( ! empty($all_listings_in_group)) {
        foreach ($all_listings_in_group as $current_listing) {
            $all_listings_ids_in_group[] = $current_listing->ID;
        }
    }

    return $all_listings_ids_in_group;
}

/**
 * @param int $listing_id
 *
 * @return array
 */
function get_all_listings_in_group(int $listing_id): array
{
    // Get the taxonomy Group terms for the current post
    $group_terms = wp_get_post_terms($listing_id, 'property_action_category');

    if ( ! empty($group_terms)) {
        $group_terms_ids = wp_list_pluck($group_terms, 'term_id');
        $args            = array(
            'post_type'      => 'estate_property',
            'posts_per_page' => -1, // Retrieve all posts
            'tax_query'      => array(
                array(
                    'taxonomy' => 'property_action_category',
                    'field'    => 'id',
                    'terms'    => $group_terms_ids,
                    'operator' => 'IN',
                ),
            ),
        );

        return get_posts($args);
    }

    return [];
}

/**
 * Retrieve reservation data for all listings in current group
 *
 * @param array $all_listings_ids_in_group
 *
 * @return array
 */
function get_reservation_grouped_array(array $all_listings_ids_in_group): array
{
    $reservation_grouped_array = [];
    if (current_user_is_timeshare() && ! empty($all_listings_ids_in_group)) {
        foreach ($all_listings_ids_in_group as $current_listings_id) {
            $reservation_grouped_array[$current_listings_id] = get_post_meta(
                $current_listings_id,
                'booking_dates',
                true
            );

            if ($reservation_grouped_array[$current_listings_id] == '') {
                $reservation_grouped_array[$current_listings_id] = wpestate_get_booking_dates($current_listings_id);
            }
        }
    }

    return $reservation_grouped_array;
}

/**
 * Convert date to necessarily format. Example 1970-01-01
 *
 * @param string $dateString
 *
 * @return string
 */
function convert_date_format(string $dateString): string
{
    return date('Y-m-d', strtotime($dateString));
}

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
    $current_date       = date('Y-m-d');
    $current_date_obj   = new DateTime($current_date);
    $from_date_obj      = new DateTime($from_date);
    $interval_obj       = $from_date_obj->diff($current_date_obj);
    $interval_by_months = $interval_obj->m + 12 * $interval_obj->y;

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
 * @param int $booking_id
 * @param int $percent
 * @param string $from_date
 * @param string $to_date
 *
 * @return void
 */

function set_session_timeshare_booking_data(
    int $buyer_id,
    int $percent,
    $booking_array
): void {
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

//get_discount_percent('24-02-04', '24-02-25');
//todo@@@@ need remove..... year mont day

//get_discount_percent('24-10-01', '24-10-20');

//get_discount_percent('24-10-01', '24-10-05');

//for special
//get_discount_percent('24-12-31', '25-01-16');

/**
 * Retrieve calculated price
 *
 * @param string $from_date
 * @param string $to_date
 * @param bool $force
 *
 * @return int|mixed
 * @throws Exception
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
            $from_date_obj                  = new DateTime($from_date_converted);
            $to_date_obj                    = new DateTime($to_date_converted);
            $from_to_interval               = $from_date_obj->diff($to_date_obj);
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
                        if ($from_date_obj >= $current_date_range_from && $from_date_obj <= $current_date_range_to) {
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

//    var_dump(22222222);
//    var_dump($discount_months_diff);
//    var_dump('$percent is..... ' . $percent);
//    var_dump($booked_days_info);
//    var_dump($from_date_converted);
//    var_dump($to_date_converted);
//    var_dump($necessarily_timeshare_price_calc_data);
//    exit;
    } catch (Exception|Error $e) {
        wp_die('Error: ' . $e->getMessage());
    }

    return floatval($percent);
}

/**
 * @param float $price
 * @param string $from_date
 * @param string $to_date
 * @param bool $calc_by_force
 *
 * @return float
 * @throws Exception
 */
function timeshare_discount_price_calc(
    $discount_percent,
    float $price,
    string $from_date,
    string $to_date,
    bool $calc_by_force = false
): float {
    try {
        if ($price == 0) {
            return $price;
        }

        // Calculation available during booking by timeshare users and in dashboards of administrator and timeshare user.
        // $calc_by_force used by administrators to show customized price in "My Bookings" page
        if ( ! $calc_by_force && ! current_user_is_timeshare()) {
            return $price;
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
            $price = $price * $discount_percent / 100;
        } else {
            // Divide price calculation by part
            $price_per_day_before_discount = $price / $booked_days_count;

            // Price for Timeshare user depends on available days of package duration
            $discounted_price_by_available_days = $timeshare_package_duration * $price_per_day_before_discount * $discount_percent / 100;

            $remaining_days_count = $booked_days_count - $timeshare_package_duration;
            // Calculate as for a standard user(Customer)
            $remaining_days_price = $price_per_day_before_discount * $remaining_days_count;
            // Calculated Total Price
            $price = $discounted_price_by_available_days + $remaining_days_price;
        }
    } catch (Exception|Error $e) {
        wp_die('Error: ' . $e->getMessage());
    }

    return ceil($price);
}

/**
 * Check booking availability
 * Run after ajax call
 * add_action('wp_ajax_wpestate_ajax_check_booking_valability', 'wpestate_ajax_check_booking_valability' );
 * add_action('wp_ajax_nopriv_wpestate_ajax_check_booking_valability', 'wpestate_ajax_check_booking_valability' );
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

    $all_listings_ids_in_group = current_user_is_timeshare() && check_has_room_category(
        $listing_id
    ) ? get_all_listings_ids_in_group($listing_id) : [];

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

    if (current_user_is_timeshare() && ! empty($all_listings_ids_in_group)) {
        $reservation_grouped_array = get_reservation_grouped_array($all_listings_ids_in_group);
    } else {
        $reservation_grouped_array[] = get_post_meta($listing_id, 'booking_dates', true);
        if ($reservation_grouped_array[0] == '') {
            $reservation_grouped_array[] = wpestate_get_booking_dates($listing_id);
        }
    }

    foreach ($reservation_grouped_array as $reservation_array) {
        if (is_array($reservation_array) && ! empty($reservation_array) && array_key_exists(
                $from_date_unix,
                $reservation_array
            )) {
            print 'stop array_key_exists';
            die();
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
                $group_ids_by_order[] = $term_id;
            }
        }
    }

    return $group_ids_by_order;
}

/**
 * Retrieve group data to book for timeshare user
 *
 * @param array $group_ids_by_room_group_order
 * @param int $from_date_unix
 *
 * @return array
 */
function get_group_data_to_book(array $group_ids_by_room_group_order, int $from_date_unix): array
{
    $group_data_to_book = [];

    if ( ! empty($group_ids_by_room_group_order)) {
        foreach ($group_ids_by_room_group_order as $current_group_id) {
            if ( ! empty($group_data_to_book)) {
                break;
            }

            foreach (get_reservation_grouped_array_by_group_id($current_group_id) as $room_id => $reservation_array) {
                if (is_array($reservation_array) && array_key_exists($from_date_unix, $reservation_array)) {
                    break;
                }

                $group_data_to_book['group_id']    = $current_group_id;
                $group_data_to_book['rooms_ids'][] = $room_id;
            }
        }
    }

    return $group_data_to_book;
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

        return get_reservation_grouped_array($all_listings_ids_in_group);;
    }

    return [];
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