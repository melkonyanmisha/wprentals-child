<?php

/**
 * @param array $reservation_array
 * @param int $property_id
 * @param array $booking_array
 * @param array $extra_options_array
 * @param string $rental_type
 * @param string $booking_type
 * @param int $booking_guest_no
 * @param int $booking_id
 * @param float $price
 * @param int $owner_id
 * @param int $property_author
 * @param float $early_bird_percent
 * @param float $early_bird_days
 * @param float $taxes_value
 * @param array $extra_pay_options
 *
 * @return array
 */
function generate_the_invoice(
    array $reservation_array,
    int $property_id,
    array $booking_array,
    array $extra_options_array,
    string $rental_type,
    string $booking_type,
    int $booking_guest_no,
    int $booking_id,
    float $price,
    int $owner_id,
    int $property_author,
    float $early_bird_percent,
    float $early_bird_days,
    float $taxes_value,
    array $extra_pay_options
): array {
    $options_array_explanations = [];
    $current_user               = wp_get_current_user();
    $userID                     = $current_user->ID;
    $allowded_html              = [];
    $fromdate                   = wpestate_convert_dateformat_twodig(wp_kses($_POST['fromdate'], $allowded_html));
    $to_date                    = wpestate_convert_dateformat_twodig(wp_kses($_POST['todate'], $allowded_html));

    wpestate_check_for_booked_time($fromdate, $to_date, $reservation_array, $property_id);
    //end check

    // fill up the details array to display
    $wpestate_currency       = esc_html(wprentals_get_option('wp_estate_currency_label_main', ''));
    $wpestate_where_currency = esc_html(wprentals_get_option('wp_estate_where_currency_symbol', ''));
    $details[]               = [
        esc_html__('Subtotal', 'wprentals'),
        $booking_array['inter_price']
    ];

    if ( ! empty ($extra_options_array)) {
        $options_array_explanations = [
            0 => esc_html__('Single Fee', 'wprentals'),
            1 => ucfirst(wpestate_show_labels('per_night', $rental_type, $booking_type)),
            2 => esc_html__('Per Guest', 'wprentals'),
            3 => ucfirst(wpestate_show_labels('per_night', $rental_type, $booking_type)) . ' ' . esc_html__(
                    'Per Guest',
                    'wprentals'
                )
        ];

        foreach ($extra_options_array as $value) {
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

                $temp_array = [
                    $extra_pay_options[$value][0],
                    $value_computed,
                    $extra_option_value_show_single . ' ' . $options_array_explanations [$extra_pay_options[$value][2]]
                ];
                $details[]  = $temp_array;
            }
        }
    }

    $details[] = [
        esc_html__('Cleaning fee', 'wprentals'),
        $booking_array['cleaning_fee']
    ];
    $details[] = [
        esc_html__('City fee', 'wprentals'),
        $booking_array['city_fee']
    ];

    //security details
    if (intval($booking_array['security_deposit']) != 0) {
        $sec_array = [
            __('Security Deposit', 'wprentals'),
            $booking_array['security_deposit']
        ];
        $details[] = $sec_array;
    }
    //earky bird
    if (intval($booking_array['early_bird_discount']) != 0) {
        $sec_array = [
            __('Early Bird Discount', 'wprentals'),
            $booking_array['early_bird_discount']
        ];
        $details[] = $sec_array;
    }

    if ($booking_array['has_guest_overload'] != 0 && $booking_array['total_extra_price_per_guest'] != 0) {
        $details[] = [
            esc_html__('Extra Guests', 'wprentals'),
            $booking_array['total_extra_price_per_guest']
        ];
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

    $to          = $user_id;
    $subject     = esc_html__('New Invoice', 'wprentals');
    $description = esc_html__('A new invoice was generated for your booking request', 'wprentals');

    if (is_user_logged_in()) {
        wpestate_add_to_inbox($userID, $userID, $to, $subject, $description, 1);
        wpestate_send_booking_email('newinvoice', $receiver_email);
    }

    return [
        'invoice_id'                 => $invoice_id,
        'options_array_explanations' => $options_array_explanations
    ];
}