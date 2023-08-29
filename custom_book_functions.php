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
            $current_category_parent_term = get_term($current_category_parent_term_id, 'property_category');

            $category_parent_terms_slugs[] = $current_category_parent_term->slug;
        }

        if (in_array('room', $category_parent_terms_slugs)) {
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
    } else {
        $reservation_grouped_array[] = get_post_meta($listing_id, 'booking_dates', true);
        if ($reservation_grouped_array[0] == '') {
            $reservation_grouped_array[] = wpestate_get_booking_dates($listing_id);
        }
    }

    foreach ($reservation_grouped_array as $reservation_array) {
        if (array_key_exists($from_date_unix, $reservation_array)) {
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


function wpestate_ajax_add_booking_instant()
{
//    todo@@@ booking continue
//    var_dump(99999);
//    exit;
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
    $price_per_weekeend  = floatval(get_post_meta($property_id, 'price_per_weekeend', true));
    $extra_options_array = array();
    if ($extra_options != '') {
        $extra_options_array = explode(',', $extra_options);
    }

    $booking_type = wprentals_return_booking_type($property_id);
    $rental_type  = wprentals_get_option('wp_estate_item_rental_type');


    // STEP1 -make the book

    $post       = array(
        'post_title'   => $event_name,
        'post_content' => $comment,
        'post_status'  => 'publish',
        'post_type'    => 'wpestate_booking',
        'post_author'  => $userID
    );
    $booking_id = wp_insert_post($post);

    $post = array(
        'ID'         => $booking_id,
        'post_title' => $event_name . ' ' . $booking_id
    );
    wp_update_post($post);


    update_post_meta($booking_id, 'booking_status', $status);
    update_post_meta($booking_id, 'booking_id', $property_id);
    update_post_meta($booking_id, 'owner_id', $owner_id);
    update_post_meta($booking_id, 'booking_from_date', $fromdate);
    update_post_meta($booking_id, 'booking_to_date', $to_date);
    update_post_meta($booking_id, 'booking_from_date_unix', strtotime($fromdate));
    update_post_meta($booking_id, 'booking_to_date_unix', strtotime($to_date));
    update_post_meta($booking_id, 'booking_invoice_no', 0);
    update_post_meta($booking_id, 'booking_pay_ammount', 0);
    update_post_meta($booking_id, 'booking_guests', $booking_guest_no);

    update_post_meta($booking_id, 'booking_adults', $booking_adults);
    update_post_meta($booking_id, 'booking_childs', $booking_childs);
    update_post_meta($booking_id, 'booking_infants', $booking_infants);

    update_post_meta($booking_id, 'extra_options', $extra_options);
    update_post_meta($booking_id, 'security_deposit', $security_deposit);
    update_post_meta($booking_id, 'full_pay_invoice_id', $full_pay_invoice_id);
    update_post_meta($booking_id, 'to_be_paid', $to_be_paid);
    update_post_meta($booking_id, 'early_bird_percent', $early_bird_percent);
    update_post_meta($booking_id, 'early_bird_days', $early_bird_days);
    update_post_meta($booking_id, 'booking_taxes', $taxes_value);


    // Re build the reservation array
    $reservation_array = get_post_meta($property_id, 'booking_dates', true);
    if ($reservation_array == '') {
        $reservation_array = wpestate_get_booking_dates($property_id);
    }
    update_post_meta($property_id, 'booking_dates', $reservation_array);

    //get booking array
    $booking_array = wpestate_booking_price(
        $booking_guest_no,
        $invoice_id,
        $property_id,
        $fromdate,
        $to_date,
        $booking_id,
        $extra_options_array
    );
    $price         = $booking_array['total_price'];


    // updating the booking detisl
    update_post_meta($booking_id, 'to_be_paid', $booking_array['deposit']);
    update_post_meta($booking_id, 'booking_taxes', $booking_array['taxes']);
    update_post_meta($booking_id, 'service_fee', $booking_array['service_fee']);
    update_post_meta($booking_id, 'taxes', $booking_array['taxes']);
    update_post_meta($booking_id, 'service_fee', $booking_array['service_fee']);
    update_post_meta($booking_id, 'youearned', $booking_array['youearned']);
    update_post_meta($booking_id, 'custom_price_array', $booking_array['custom_price_array']);
    update_post_meta($booking_id, 'balance', $booking_array['balance']);
    update_post_meta($booking_id, 'total_price', $booking_array['total_price']);


    $property_author = wpsestate_get_author($property_id);

    if ($userID != $property_author) {
        $add_booking_details = array(

            "booking_status"       => $status,
            "original_property_id" => $property_id,

            "book_author"               => $userID,
            "owner_id"                  => $owner_id,
            "booking_from_date"         => $fromdate,
            "booking_to_date"           => $to_date,
            "booking_invoice_no"        => 0,
            "booking_pay_ammount"       => $booking_array['deposit'],
            "booking_guests"            => $booking_guest_no,
            "extra_options"             => $extra_options,
            "security_deposit"          => $booking_array['security_deposit'],
            "full_pay_invoice_id"       => 0,
            "to_be_paid"                => $booking_array['deposit'],
            "youearned"                 => $booking_array['youearned'],
            "service_fee"               => $booking_array['service_fee'],
            "booking_taxes"             => $booking_array['taxes'],
            "total_price"               => $booking_array['total_price'],
            "custom_price_array"        => $booking_array['custom_price_array'],
            "submission_curency_status" => esc_html(wprentals_get_option('wp_estate_submission_curency', '')),
            "balance"                   => $booking_array['balance']
        );
        // update on API if is the case

        if ($booking_array['balance'] > 0) {
            update_post_meta($booking_id, 'booking_status_full', 'waiting');
            $add_booking_details['booking_status_full'] = 'waiting';
        }
    }

    //STEP 2 generate the invoice


    wpestate_check_for_booked_time($fromdate, $to_date, $reservation_array, $property_id);
    //end check


    // fill up the details array to display
    $wpestate_currency       = esc_html(wprentals_get_option('wp_estate_currency_label_main', ''));
    $wpestate_where_currency = esc_html(wprentals_get_option('wp_estate_where_currency_symbol', ''));


    $details[] = array(esc_html__('Subtotal', 'wprentals'), $booking_array['inter_price']);
    if (is_array($extra_options_array) && ! empty ($extra_options_array)) {
        $extra_pay_options          = (get_post_meta($property_id, 'extra_pay_options', true));
        $options_array_explanations = array(
            0 => esc_html__('Single Fee', 'wprentals'),
            1 => ucfirst(wpestate_show_labels('per_night', $rental_type, $booking_type)),
            2 => esc_html__('Per Guest', 'wprentals'),
            3 => ucfirst(wpestate_show_labels('per_night', $rental_type, $booking_type)) . ' ' . esc_html__(
                    'per Guest',
                    'wprentals'
                )
        );
        foreach ($extra_options_array as $key => $value) {
            if (isset($extra_pay_options[$value][0])) {
                $value_computed = wpestate_calculate_extra_options_value(
                    $booking_array['count_days'],
                    $booking_guest_no,
                    $extra_pay_options[$value][2],
                    $extra_pay_options[$value][1]
                );

                $extra_option_value_show_single = wpestate_show_price_booking_for_invoice(
                    $extra_pay_options[$value][1],
                    $wpestate_currency,
                    $wpestate_where_currency,
                    0,
                    1
                );

                $temp_array = array(
                    $extra_pay_options[$value][0],
                    $value_computed,
                    $extra_option_value_show_single . ' ' . $options_array_explanations [$extra_pay_options[$value][2]]
                );
                $details[]  = $temp_array;
            }
        }
    }

    $details[] = array(esc_html__('Cleaning fee', 'wprentals'), $booking_array['cleaning_fee']);
    $details[] = array(esc_html__('City fee', 'wprentals'), $booking_array['city_fee']);

    //security details
    if (intval($booking_array['security_deposit']) != 0) {
        $sec_array = array(__('Security Deposit', 'wprentals'), $booking_array['security_deposit']);
        $details[] = $sec_array;
    }
    //earky bird
    if (intval($booking_array['early_bird_discount']) != 0) {
        $sec_array = array(__('Early Bird Discount', 'wprentals'), $booking_array['early_bird_discount']);
        $details[] = $sec_array;
    }


    if ($booking_array['has_guest_overload'] != 0 && $booking_array['total_extra_price_per_guest'] != 0) {
        $details[] = array(esc_html__('Extra Guests', 'wprentals'), $booking_array['total_extra_price_per_guest']);
    }


    $billing_for = esc_html__('Reservation fee', 'wprentals');
    $type        = esc_html__('One Time', 'wprentals');
    $pack_id     = $booking_id; // booking id

    $time    = time();
    $date    = date('Y-m-d H:i:s', $time);
    $user_id = wpse119881_get_author($booking_id);

    $is_featured   = '';
    $is_upgrade    = '';
    $paypal_tax_id = '';


    $invoice_id = wpestate_booking_insert_invoice(
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
        $owner_id
    );


    // update booking status
    if ($userID != $property_author) {
        update_post_meta($booking_id, 'booking_status', 'waiting');
        update_post_meta($booking_id, 'booking_invoice_no', $invoice_id);
        $booking_details = array(
            'booking_status'     => 'waiting',
            'booking_invoice_no' => $invoice_id
        );
    }

    //update invoice data
    update_post_meta($invoice_id, 'early_bird_percent', $early_bird_percent);
    update_post_meta($invoice_id, 'early_bird_days', $early_bird_days);
    update_post_meta($invoice_id, 'booking_taxes', $taxes_value);
    update_post_meta($invoice_id, 'booking_taxes', $booking_array['taxes']);
    update_post_meta($invoice_id, 'service_fee', $booking_array['service_fee']);
    update_post_meta($invoice_id, 'youearned', $booking_array['youearned']);
    update_post_meta($invoice_id, 'depozit_to_be_paid', $booking_array['deposit']);
    update_post_meta($invoice_id, 'item_price', $booking_array['total_price']);
    update_post_meta($invoice_id, 'custom_price_array', $booking_array['custom_price_array']);
    update_post_meta($invoice_id, 'balance', $booking_array['balance']);


    // send notifications
    $receiver       = get_userdata($user_id);
    $receiver_email = '';
    if (isset($receiver->user_email)) {
        $receiver_email = $receiver->user_email;
    }
    $receiver_login = '';
    if (isset($receiver->user_login)) {
        $receiver_login = $receiver->user_login;
    }

    $from        = $owner_id;
    $to          = $user_id;
    $subject     = esc_html__('New Invoice', 'wprentals');
    $description = esc_html__('A new invoice was generated for your booking request', 'wprentals');

    if (is_user_logged_in()) {
        wpestate_add_to_inbox($userID, $userID, $to, $subject, $description, 1);
        wpestate_send_booking_email('newinvoice', $receiver_email);
    }


    //STEP3 - show me the money

    $wpestate_currency       = esc_html(get_post_meta($invoice_id, 'invoice_currency', true));
    $wpestate_where_currency = esc_html(wprentals_get_option('wp_estate_where_currency_symbol', ''));
    $default_price           = get_post_meta($invoice_id, 'default_price', true);

    $booking_from_date = esc_html(get_post_meta($booking_id, 'booking_from_date', true));
    $property_id       = esc_html(get_post_meta($booking_id, 'booking_id', true));

    $booking_to_date = esc_html(get_post_meta($booking_id, 'booking_to_date', true));
    $booking_guests  = floatval(get_post_meta($booking_id, 'booking_guests', true));
    // $booking_array      =   wpestate_booking_price($booking_guests,$invoice_id,$property_id, $booking_from_date, $booking_to_date);

    $classic_period_days = wprentals_return_standart_days_period();


    if ($booking_array['numberDays'] >= $classic_period_days['week_days'] && $booking_array['numberDays'] < $classic_period_days['month_days']) {
        if ($booking_array['week_price'] > 0) {
            $default_price = $booking_array['week_price'];
        }
    } elseif ($booking_array['numberDays'] >= $classic_period_days['month_days']) {
        if ($booking_array['month_price'] > 0) {
            $default_price = $booking_array['month_price'];
        }
    }

    $wp_estate_book_down           = get_post_meta($invoice_id, 'invoice_percent', true);
    $wp_estate_book_down_fixed_fee = get_post_meta($invoice_id, 'invoice_percent_fixed_fee', true);
    $invoice_price                 = floatval(get_post_meta($invoice_id, 'item_price', true));


    $include_expeses = esc_html(wprentals_get_option('wp_estate_include_expenses', ''));

    if ($include_expeses == 'yes') {
        $total_price_comp = $invoice_price;
    } else {
        $total_price_comp = $invoice_price - $booking_array['city_fee'] - $booking_array['cleaning_fee'];
    }


    $depozit = wpestate_calculate_deposit($wp_estate_book_down, $wp_estate_book_down_fixed_fee, $total_price_comp);

    // $depozit            =   round($wp_estate_book_down*$total_price_comp/100,2);
    $balance = $invoice_price - $depozit;


    $price_show              = wpestate_show_price_booking_for_invoice(
        $default_price,
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $price_per_weekeend_show = wpestate_show_price_booking_for_invoice(
        $price_per_weekeend,
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $total_price_show        = wpestate_show_price_booking_for_invoice(
        $invoice_price,
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $depozit_show            = wpestate_show_price_booking_for_invoice(
        $depozit,
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $balance_show            = wpestate_show_price_booking_for_invoice(
        $balance,
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $city_fee_show           = wpestate_show_price_booking_for_invoice(
        $booking_array['city_fee'],
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $cleaning_fee_show       = wpestate_show_price_booking_for_invoice(
        $booking_array['cleaning_fee'],
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $inter_price_show        = wpestate_show_price_booking_for_invoice(
        $booking_array['inter_price'],
        $wpestate_currency,
        $wpestate_where_currency,
        0,
        1
    );
    $total_guest             = wpestate_show_price_booking_for_invoice(
        $booking_array['total_extra_price_per_guest'],
        $wpestate_currency,
        $wpestate_where_currency,
        1,
        1
    );
    $guest_price             = wpestate_show_price_booking_for_invoice(
        $booking_array['extra_price_per_guest'],
        $wpestate_currency,
        $wpestate_where_currency,
        1,
        1
    );
    $extra_price_per_guest   = wpestate_show_price_booking(
        $booking_array['extra_price_per_guest'],
        $wpestate_currency,
        $wpestate_where_currency,
        1
    );


    $depozit_stripe = $depozit;
    $details        = get_post_meta($invoice_id, 'renting_details', true);

    global $wpestate_global_payments;
    if ($wpestate_global_payments->is_woo == 'yes') {
        if ( ! is_user_logged_in()) {
            $wpestate_global_payments->wpestate_woo_pay_non_logged($property_id, $invoice_id, $booking_id, $depozit);
            print  (wc_get_cart_url());
            die();
        }
    }


    // strip details generation
    $is_stripe_live = esc_html(wprentals_get_option('wp_estate_enable_stripe', ''));

    print '
            <div class="create_invoice_form">
                   <h3>' . esc_html__('Invoice INV', 'wprentals') . $invoice_id . '</h3>

                   <div class="invoice_table">
                        <div class="invoice_data">
                                <span class="date_interval invoice_date_period_wrapper"><span class="invoice_data_legend">' . esc_html__(
            'Period ',
            'wprentals'
        ) . ' : </span>' . wpestate_convert_dateformat_reverse($booking_from_date) . ' ' . esc_html__(
              'to',
              'wprentals'
          ) . ' ' . wpestate_convert_dateformat_reverse($booking_to_date) . '</span>
                                <span class="date_duration invoice_date_nights_wrapper"><span class="invoice_data_legend">' . wpestate_show_labels(
              'no_of_nights',
              $rental_type,
              $booking_type
          ) . ': </span>' . $booking_array['count_days'] . '</span>
                                <span class="date_duration invoice_date_guests_wrapper"><span class="invoice_data_legend">' . esc_html__(
              'No of guests',
              'wprentals'
          ) . ': </span>' . $booking_guests . wpestate_booking_guest_explanations($booking_id) . '</span>';
    if ($booking_array['price_per_guest_from_one'] == 1) {
        print'
                                    <span class="date_duration invoice_date_price_guest_wrapper"><span class="invoice_data_legend">' . esc_html__(
                'Price per Guest',
                'wprentals'
            ) . ': </span>';
        print trim($extra_price_per_guest);
        print'</span>';
    } else {
        print'
                                    <span class="date_duration invoice_date_label_wrapper"><span class="invoice_data_legend">' . wpestate_show_labels(
                'price_label',
                $rental_type,
                $booking_type
            ) . ': </span>';
        print trim($price_show);
        if ($booking_array['has_custom']) {
            print ', ' . esc_html__('has custom price', 'wprentals');
        }
        if ($booking_array['cover_weekend']) {
            print ', ' . esc_html__('has weekend price of', 'wprentals') . ' ' . $price_per_weekeend_show;
        }
        print'</span>';
    }
    if ($booking_array['has_custom']) {
        print '<span class="invoice_data_legend">' . __('Price details:', 'wprentals') . '</span>';
        foreach ($booking_array['custom_price_array'] as $date => $price) {
            $day_price = wpestate_show_price_booking_for_invoice(
                $price,
                $wpestate_currency,
                $wpestate_where_currency,
                1,
                1
            );
            print '<span class="price_custom_explained">' . __(
                    'on',
                    'wprentals'
                ) . ' ' . wpestate_convert_dateformat_reverse(date("Y-m-d", $date)) . ' ' . __(
                      'price is',
                      'wprentals'
                  ) . ' ' . $day_price . '</span>';
        }
    }

    print'<span class="date_duration invoice_date_property_name_wrapper"><span class="invoice_data_legend">' . esc_html__(
            'Property',
            'wprentals'
        ) . ': </span><a href="' . esc_url(get_permalink($property_id)) . '" target="_blank">' . esc_html(
             get_the_title($property_id)
         ) . '</a></span>';

    print '</div>

                        <div class="invoice_details">
                            <div class="invoice_row header_legend">
                               <span class="inv_legend">' . esc_html__('Cost', 'wprentals') . '</span>
                               <span class="inv_data">  ' . esc_html__('Price', 'wprentals') . '</span>
                               <span class="inv_exp">   ' . esc_html__('Detail', 'wprentals') . '</span>
                            </div>';

    print'
                        <div class="invoice_row invoice_content">
                            <span class="inv_legend">   ' . esc_html__('Subtotal', 'wprentals') . '</span>
                            <span class="inv_data">   ' . $inter_price_show . '</span>';

    if ($booking_array['price_per_guest_from_one'] == 1) {
        print  esc_html($extra_price_per_guest) . ' x ' . $booking_array['count_days'] . ' ' . wpestate_show_labels(
                'nights',
                $rental_type,
                $booking_type
            ) . ' x ' . $booking_array['curent_guest_no'] . ' ' . esc_html__('guests', 'wprentals');
    } else {
        if ($booking_array['cover_weekend']) {
            $new_price_to_show = esc_html__('has weekend price of', 'wprentals') . ' ' . $price_per_weekeend_show;
        } else {
            if ($booking_array['has_custom']) {
                $new_price_to_show = esc_html__("custom price", "wprentals");
            } else {
                $new_price_to_show = $price_show . ' ' . wpestate_show_labels('per_night', $rental_type, $booking_type);
            }
        }


        if ($booking_array['numberDays'] == 1) {
            print ' <span class="inv_exp">   (' . $booking_array['numberDays'] . ' ' . wpestate_show_labels(
                    'night',
                    $rental_type,
                    $booking_type
                ) . ' | ' . $new_price_to_show . ') </span>';
        } else {
            print ' <span class="inv_exp">   (' . $booking_array['numberDays'] . ' ' . wpestate_show_labels(
                    'nights',
                    $rental_type,
                    $booking_type
                ) . ' | ' . $new_price_to_show . ') </span>';
        }
    }

    if ($booking_array['custom_period_quest'] == 1) {
        esc_html_e(" period with custom price per guest", "wprentals");
    }

    print'</div>';


    if ($booking_array['has_guest_overload'] != 0 && $booking_array['total_extra_price_per_guest'] != 0) {
        print'
                                <div class="invoice_row invoice_content">
                                    <span class="inv_legend">   ' . esc_html__('Extra Guests', 'wprentals') . '</span>
                                    <span class="inv_data" id="extra-guests" data-extra-guests="' . esc_attr(
                $booking_array['total_extra_price_per_guest']
            ) . '">  ' . $total_guest . '</span>
                                    <span class="inv_exp">   (' . $booking_array['numberDays'] . ' ' . wpestate_show_labels(
                 'nights',
                 $rental_type,
                 $booking_type
             ) . ' | ' . $booking_array['extra_guests'] . ' ' . esc_html__('extra guests', 'wprentals') . ' ) </span>
                                </div>';
    }


    if ($booking_array['cleaning_fee'] != 0 && $booking_array['cleaning_fee'] != '') {
        print'
                               <div class="invoice_row invoice_content">
                                   <span class="inv_legend">   ' . esc_html__('Cleaning fee', 'wprentals') . '</span>
                                   <span class="inv_data" id="cleaning-fee" data-cleaning-fee="' . esc_attr(
                $booking_array['cleaning_fee']
            ) . '">  ' . $cleaning_fee_show . '</span>
                               </div>';
    }


    if ($booking_array['city_fee'] != 0 && $booking_array['city_fee'] != '') {
        print'
                               <div class="invoice_row invoice_content">
                                   <span class="inv_legend">   ' . esc_html__('City fee', 'wprentals') . '</span>
                                   <span class="inv_data" id="city-fee" data-city-fee="' . esc_attr(
                $booking_array['city_fee']
            ) . '">  ' . $city_fee_show . '</span>
                               </div>';
    }


    // update_post_meta($invoice_id, 'renting_details', $details);


    foreach ($extra_options_array as $key => $value) {
        if (isset($extra_pay_options[$value][0])) {
            $extra_option_value             = wpestate_calculate_extra_options_value(
                $booking_array['count_days'],
                $booking_guests,
                $extra_pay_options[$value][2],
                $extra_pay_options[$value][1]
            );
            $extra_option_value_show        = wpestate_show_price_booking_for_invoice(
                $extra_option_value,
                $wpestate_currency,
                $wpestate_where_currency,
                1,
                1
            );
            $extra_option_value_show_single = wpestate_show_price_booking_for_invoice(
                $extra_pay_options[$value][1],
                $wpestate_currency,
                $wpestate_where_currency,
                0,
                1
            );

            print'
                                    <div class="invoice_row invoice_content">
                                        <span class="inv_legend">   ' . $extra_pay_options[$value][0] . '</span>
                                        <span class="inv_data">  ' . $extra_option_value_show . '</span>
                                        <span class="inv_data">' . $extra_option_value_show_single . ' ' . $options_array_explanations[$extra_pay_options[$value][2]] . '</span>
                                    </div>';
        }
    }


    if ($booking_array['security_deposit'] != 0) {
        $security_depozit_show = wpestate_show_price_booking_for_invoice(
            $booking_array['security_deposit'],
            $wpestate_currency,
            $wpestate_where_currency,
            1,
            1
        );
        print'
                                <div class="invoice_row invoice_content">
                                    <span class="inv_legend">   ' . __('Security Deposit', 'wprentals') . '</span>
                                    <span class="inv_data">  ' . $security_depozit_show . '</span>
                                    <span class="inv_data">' . __('*refundable', 'wprentals') . '</span>
                                </div>';
    }


    if ($booking_array['early_bird_discount'] > 0) {
        $early_bird_discount_show = wpestate_show_price_booking_for_invoice(
            $booking_array['early_bird_discount'],
            $wpestate_currency,
            $wpestate_where_currency,
            1,
            1
        );
        print'
                                <div class="invoice_row invoice_content">
                                    <span class="inv_legend">   ' . __('Early Bird Discount', 'wprentals') . '</span>
                                    <span class="inv_data">  ' . $early_bird_discount_show . '</span>
                                    <span class="inv_data"></span>
                                </div>';
    }


    print '
                            <div class="invoice_row invoice_total total_inv_span total_invoice_for_payment">
                               <span class="inv_legend"><strong>' . esc_html__('Total', 'wprentals') . '</strong></span>
                               <span class="inv_data" id="total_amm" data-total="' . esc_attr(
            $invoice_price
        ) . '">' . $total_price_show . '</span></br>

                               <span class="inv_legend invoice_reseration_fee_req">' . esc_html__(
              'Reservation Fee Required',
              'wprentals'
          ) . ':</span> <span class="inv_depozit depozit_show" data-value="' . esc_attr(
              $depozit
          ) . '"> ' . $depozit_show . '</span></br>
                               <span class="inv_legend invoice_balance_owed ">' . esc_html__(
              'Balance Owed',
              'wprentals'
          ) . ':</span> <span class="inv_depozit balance_show"  data-value="' . esc_attr(
              $balance
          ) . '">' . $balance_show . '</span>
                           </div>
                       </div>';


    global $wpestate_global_payments;

    $submission_curency_status = esc_html(wprentals_get_option('wp_estate_submission_curency', ''));
    if ($wpestate_global_payments->is_woo == 'yes') {
        if ( ! is_user_logged_in()) {
            wp_safe_redirect(wc_get_cart_url());
        }
        $wpestate_global_payments->show_button_pay($property_id, $booking_id, $invoice_id, $depozit, 1);
    } else {
        if (floatval($depozit) == 0) {
            print '<span id="confirm_zero_instant_booking"   data-propid="' . esc_attr(
                    $property_id
                ) . '" data-bookid="' . esc_attr($booking_id) . '" data-invoiceid="' . esc_attr(
                      $invoice_id
                  ) . '">' . esc_html__('Confirm Booking - No Deposit Needed', 'wprentals') . '</span>';
            $ajax_nonce = wp_create_nonce("wprentals_confirm_zero_instant_booking_nonce");
            print'<input type="hidden" id="wprentals_confirm_zero_instant_booking" value="' . esc_html(
                    $ajax_nonce
                ) . '" />    ';
        } else {
            $is_paypal_live = esc_html(wprentals_get_option('wp_estate_enable_paypal', ''));
            $is_stripe_live = esc_html(wprentals_get_option('wp_estate_enable_stripe', ''));


            print '<span class="pay_notice_booking">' . esc_html__(
                    'Pay Deposit & Confirm Reservation',
                    'wprentals'
                ) . '</span>';

            if ($is_stripe_live == 'yes') {
                global $wpestate_global_payments;
                $metadata = array(
                    'booking_id' => $booking_id,
                    'invoice_id' => $invoice_id,
                    'listing_id' => $property_id,
                    'user_id'    => $userID,
                    'pay_type'   => 1,
                    'message'    => esc_html__('Pay & Confirm Reservation', 'wprentals'),

                );

                $wpestate_global_payments->stripe_payments->wpestate_show_stripe_form($depozit_stripe, $metadata);
            }

            if ($is_paypal_live == 'yes') {
                print '<span id="paypal_booking" data-deposit="' . esc_attr($depozit) . '"  data-propid="' . esc_attr(
                        $property_id
                    ) . '" data-bookid="' . esc_attr($booking_id) . '" data-invoiceid="' . esc_attr(
                          $invoice_id
                      ) . '">' . esc_html__('Pay with Paypal', 'wprentals') . '</span>';
                $ajax_nonce = wp_create_nonce("wprentals_reservation_actions_nonce");
                print'<input type="hidden" id="wprentals_reservation_actions" value="' . esc_html(
                        $ajax_nonce
                    ) . '" />    ';
            }
            $enable_direct_pay = esc_html(wprentals_get_option('wp_estate_enable_direct_pay', ''));
        }
    }
    print'
                  </div>


            </div>';


    $invoice_details = array(
        "invoice_status" => "issued",
        "purchase_date"  => $date,
        "buyer_id"       => $userID,
        "item_price"     => $booking_array['total_price'],

        "orignal_invoice_id"        => $invoice_id,
        "billing_for"               => $billing_for,
        "type"                      => $type,
        "pack_id"                   => $pack_id,
        "date"                      => $date,
        "user_id"                   => $user_id,
        "is_featured"               => $is_featured,
        "is_upgrade"                => $is_upgrade,
        "paypal_tax_id"             => $paypal_tax_id,
        "details"                   => $details,
        "price"                     => $price,
        "to_be_paid"                => $booking_array['deposit'],
        "submission_curency_status" => $submission_curency_status,
        "bookid"                    => $bookid,
        "author_id"                 => $author_id,
        "youearned"                 => $booking_array['youearned'],
        "service_fee"               => $booking_array['service_fee'],
        "booking_taxes"             => $booking_array['taxes'],
        "security_deposit"          => $booking_array['security_deposit'],
        "renting_details"           => $details,
        "custom_price_array"        => $booking_array['custom_price_array'],
        "balance"                   => $booking_array['balance']
    );

    if ($booking_array['balance'] > 0) {
        update_post_meta($invoice_id, 'invoice_status_full', 'waiting');
        $invoice_details['invoice_status_full'] = 'waiting';
    }

    if ($booking_array['balance'] == 0) {
        update_post_meta($invoice_id, 'is_full_instant', 1);
        update_post_meta($booking_id, 'is_full_instant', 1);
    }


    die();
}