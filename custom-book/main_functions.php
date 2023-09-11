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
    } elseif ($interval_by_months > 2 && $interval_by_months < 4) {
        $key_discount_months_diff = 'two_four_before';
    } elseif ($interval_by_months > 4 && $interval_by_months < 6) {
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
 * @return array
 */
function get_timeshare_session_info(): array
{
    return $_SESSION['timeshare'];
}

/**
 * Set necessarily data into the Session
 *
 * @param int $client_id
 * @param int $booking_id
 * @param int $percent
 * @param string $from_date
 * @param string $to_date
 *
 * @return void
 */
function set_discount_info_to_session(
    int $client_id,
    int $booking_id,
    int $percent,
    string $from_date,
    string $to_date
): void {
    $booked_days_count = get_booked_days_count($from_date, $to_date);

    $_SESSION['timeshare'][$client_id][$booking_id]['price_percent']     = $percent;
    $_SESSION['timeshare'][$client_id][$booking_id]['booked_days_count'] = $booked_days_count;
}

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
function get_discount_percent(string $from_date, string $to_date, bool $force = false)
{
    $percent = 100;

    if ( ! $force && ! current_user_is_timeshare()) {
        return $percent;
    }

    $timeshare_price_calc_data = json_decode(get_option(TIMESHARE_PRICE_CALC_DATA), true);

    if ( ! ($timeshare_price_calc_data)) {
        return $percent;
    }

    $from_date_converted = convert_date_format($from_date);
    $to_date_converted   = convert_date_format($to_date);

    $discount_months_diff                  = timeshare_get_discount_months_diff($from_date_converted);
    $necessarily_timeshare_price_calc_data = $timeshare_price_calc_data[$discount_months_diff] ?? [];


    //Case for Less than 2 months and All Season
    if (isset($necessarily_timeshare_price_calc_data['all']['yearly_percent'])) {
        $percent = $necessarily_timeshare_price_calc_data['all']['yearly_percent'];
    } else {
        //Case for Low, Normal, Hot, Very Hot, Special
//        todo@@@ continue to get the daily_percent or weekly_percent

    }

    return $percent;
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
        $remaining_days                     = $booked_days_count - $timeshare_package_duration;
        // Calculate as Standard client(Guest)
        $remaining_days_price = $price_per_day_before_discount * $remaining_days;

        // Calculated Total Price
        $price = $discounted_price_by_available_days + $remaining_days_price;
    }

    return $price;
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
        if (is_array($reservation_array) && array_key_exists($from_date_unix, $reservation_array)) {
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
            if (wprentals_check_hour_booking_overlap_reservations($from_date_unix, $to_date_unix, $reservation_array)) {
                print 'stop hour';
                die();
            }
        }
    } else {
        foreach ($reservation_grouped_array as $reservation_array) {
            if (wprentals_check_booking_overlap_reservations(
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