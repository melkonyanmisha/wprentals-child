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

function timeshare_discount_price_calc(float $price, string $fromdate, string $to_date): float
{
    //todo@@@@ also need to keep in mind calculation by Timeshare user package duration
    return $price;
    if ( ! current_user_is_timeshare()) {
        return $price;
    }

    $timeshare_price_calc_data = json_decode(get_option(TIMESHARE_PRICE_CALC_DATA), true);

    if ( ! ($timeshare_price_calc_data)) {
        return $price;
    }
    $from_date_converted                   = convert_date_format($fromdate);
    $to_date_converted                     = convert_date_format($to_date);
    $discount_months_diff                  = timeshare_get_discount_months_diff($from_date_converted);
    $necessarily_timeshare_price_calc_data = $timeshare_price_calc_data[$discount_months_diff] ?? [];


//        todo@@@ continue to get the daily_percent or weekly_percent or yearly_percent
//    var_dump(99999);
//    var_dump($discount_months_diff);
//    var_dump($from_date_converted);
//    var_dump($to_date_converted);
//    var_dump($price);
//    var_dump($necessarily_timeshare_price_calc_data);
//    exit;


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
function wpestate_ajax_check_booking_valability()
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


