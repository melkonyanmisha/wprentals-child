<?php

function show_the_money(
    $invoice_id,
    $booking_id,
    $booking_array,
    $rental_type,
    $booking_type,
    $extra_options_array,
    $options_array_explanations
) {
    $current_user = wp_get_current_user();
    $userID       = $current_user->ID;

    $wpestate_currency       = esc_html(get_post_meta($invoice_id, 'invoice_currency', true));
    $wpestate_where_currency = esc_html(wprentals_get_option('wp_estate_where_currency_symbol', ''));
    $default_price           = get_post_meta($invoice_id, 'default_price', true);
    $booking_from_date       = esc_html(get_post_meta($booking_id, 'booking_from_date', true));
    $property_id             = esc_html(get_post_meta($booking_id, 'booking_id', true));
    $booking_to_date         = esc_html(get_post_meta($booking_id, 'booking_to_date', true));
    $booking_guests          = floatval(get_post_meta($booking_id, 'booking_guests', true));
    $price_per_weekeend      = floatval(get_post_meta($property_id, 'price_per_weekeend', true));
    $classic_period_days     = wprentals_return_standart_days_period();

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

//    var_dump(222222);
//    var_dump($invoice_id);
//    var_dump($total_price_show);
//    var_dump($invoice_price);
//    var_dump($wpestate_currency);
//    var_dump($wpestate_where_currency);
//
//    exit;


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
            // strip details generation
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
}