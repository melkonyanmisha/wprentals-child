<?php
/**
 * Custom Help functions. For customizing functions from the wprentals/libs/help_functions.php
 */


/**
 * Works after woocommerce checkout
 *
 * @param int $booking_id
 * @param int $invoice_id
 * @param int $userId
 * @param float $depozit
 * @param string $user_email
 * @param int $is_stripe
 *
 * @return void
 */
function wpestate_booking_mark_confirmed(
    int $booking_id,
    int $invoice_id,
    int $userId,
    float $depozit,
    string $user_email,
    int $is_stripe = 0
): void {
    // The case when booked the group of rooms by Timeshare user
    if (current_user_is_timeshare() && get_post_meta($booking_id, 'is_group_booking', true)) {
        $booking_full_data_json = get_post_meta($booking_id, 'booking_full_data', true);

        if ($booking_full_data_json) {
            $booking_full_data = json_decode($booking_full_data_json, true);

            if ( ! empty($booking_full_data['booking_instant_rooms_group_data'])) {
                $rooms_group_booking_id_list = [];

                foreach ($booking_full_data['booking_instant_rooms_group_data'] as $booking_instant_current_room_data) {
                    // Get all other room booking ids
                    $rooms_group_booking_id_list[] = $booking_instant_current_room_data['make_the_book']['booking_id'];
                }

                if ( ! empty($rooms_group_booking_id_list)) {
                    foreach ($rooms_group_booking_id_list as $current_room_booking_id) {
                        booking_confirmation(
                            $current_room_booking_id,
                            $invoice_id,
                            $userId,
                            $depozit,
                            $user_email,
                            $is_stripe
                        );
                    }
                }
            }
        }
    } else {
        // The case when booked cottages by all users or booked single rooms by Customers
        booking_confirmation($booking_id, $invoice_id, $userId, $depozit, $user_email, $is_stripe);
    }
}

/**
 * @param int $booking_id
 * @param int $invoice_id
 * @param int $userId
 * @param float $depozit
 * @param string $user_email
 * @param int $is_stripe
 *
 * @return void
 */
function booking_confirmation(
    int $booking_id,
    int $invoice_id,
    int $userId,
    float $depozit,
    string $user_email,
    int $is_stripe = 0
) {
    $booking_status          = get_post_meta($booking_id, 'booking_status', true);
    $is_full_instant_booking = get_post_meta($booking_id, 'is_full_instant', true);
    $is_full_instant_invoice = get_post_meta($invoice_id, 'is_full_instant', true);

    if ($booking_status != 'confirmed') {
        update_post_meta($booking_id, 'booking_status', 'confirmed');
    } else {
        // confirmed_paid_full
        update_post_meta($booking_id, 'booking_status_full', 'confirmed');
        update_post_meta($booking_id, 'balance', 0);
    }

    if ($is_full_instant_booking == 1) {
        update_post_meta($booking_id, 'booking_status_full', 'confirmed');
        update_post_meta($booking_id, 'balance', 0);
    }

    if ($is_stripe == 1) {
        $depozit = ($depozit / 100);
    }

    // reservation array
    $curent_listng_id  = get_post_meta($booking_id, 'booking_id', true);
    $reservation_array = wpestate_get_booking_dates($curent_listng_id);
    $invoice_status    = get_post_meta($invoice_id, 'invoice_status', true);

    update_post_meta($curent_listng_id, 'booking_dates', $reservation_array);

    if ($invoice_status != 'confirmed') {
        update_post_meta($invoice_id, 'depozit_paid', $depozit);
        update_post_meta($invoice_id, 'invoice_status', 'confirmed');
    } else {
        update_post_meta($invoice_id, 'invoice_status_full', 'confirmed');
        update_post_meta($invoice_id, 'balance', 0);
    }

    if ($is_full_instant_invoice == 1) {
        update_post_meta($invoice_id, 'invoice_status_full', 'confirmed');
        update_post_meta($invoice_id, 'balance', 0);
    }

    // 100% deposit
    $wp_estate_book_down = floatval(get_post_meta($invoice_id, 'invoice_percent', true));

    if ($wp_estate_book_down == 100) {
        update_post_meta($booking_id, 'booking_status_full', 'confirmed');
        update_post_meta($booking_id, 'balance', 0);
        update_post_meta($invoice_id, 'invoice_status_full', 'confirmed');
        update_post_meta($invoice_id, 'balance', 0);
    }
    // end 100% deposit

    $woo_double_check = intval(get_post_meta($booking_id, 'woo_double_check', true));

    if ($woo_double_check != 1) {
        wpestate_send_booking_email("bookingconfirmeduser", $user_email);
        $receiver_id    = wpsestate_get_author($invoice_id);
        $receiver_email = get_the_author_meta('user_email', $receiver_id);
        wpestate_send_booking_email("bookingconfirmed", $receiver_email);

        // add messages to inbox
        $subject     = esc_html__('Booking Confirmation', 'wprentals');
        $description = esc_html__('A booking was confirmed', 'wprentals');
        wpestate_add_to_inbox($userId, $userId, $receiver_id, $subject, $description, 1);

        //marl as email sent for woo
        update_post_meta($booking_id, 'woo_double_check', 1);
    }
}

/**
 * Render booking form in single listing page
 *
 * @param int $post_id
 * @param array $wpestate_options
 * @param string $favorite_class
 * @param string $favorite_text
 * @param int $is_shortcode
 *
 * @return string
 */
function wpestate_show_booking_form(
    int $post_id,
    array $wpestate_options = [],
    string $favorite_class = '',
    string $favorite_text = '',
    int $is_shortcode = 0
): string {
    $rental_type     = wprentals_get_option('wp_estate_item_rental_type');
    $guest_list      = wpestate_get_guest_dropdown('noany');
    $container_class = "col-md-4";

    if (isset($wpestate_options['sidebar_class'])) {
        if ($wpestate_options['sidebar_class'] != '' && $wpestate_options['sidebar_class'] != 'none') {
            $container_class = esc_attr($wpestate_options['sidebar_class']);
        }
    }

    ob_start();
    ?>

    <div class="booking_form_request is_shortcode<?= $is_shortcode; ?><?= esc_attr($container_class); ?>"
         id="booking_form_request">
        <?php
        if (wprentals_get_option('wp_estate_replace_booking_form', '') == 'yes') { ?>
            <div id="booking_form_mobile_close">&times;</div>
            <?php
            wpestate_show_contact_form($post_id);
        } else {
            $book_type        = wprentals_return_booking_type($post_id);
            $start_date_class = isset($_GET['check_in_prop']) && $book_type == 1 ? sanitize_text_field(
                $_GET['check_in_prop']
            ) : '';

            ?>
            <div id="booking_form_request_mess"></div>
            <div id="booking_form_mobile_close">&times;</div>
            <h3><?= esc_html__('Book Now', 'wprentals'); ?></h3>

            <div class="has_calendar calendar_icon">
                <input type="text" id="start_date" placeholder="<?= wpestate_show_labels('check_in', $rental_type); ?>"
                       class="form-control calendar_icon" size="40" name="start_date" value="<?= $start_date_class; ?>">
            </div>
            <?php
            if (wprentals_return_booking_type($post_id) == 2) {
                $booking_start_hour = get_post_meta($post_id, 'booking_start_hour', true);
                $booking_end_hour   = get_post_meta($post_id, 'booking_end_hour', true);

                echo wprentals_show_booking_form_per_hour_dropdown(
                    'start_hour',
                    esc_html__('Start Hour', 'wprentals'),
                    $booking_start_hour,
                    $booking_end_hour,
                    ''
                );
                echo wprentals_show_booking_form_per_hour_dropdown(
                    'end_hour',
                    esc_html__('End Hour', 'wprentals'),
                    $booking_start_hour,
                    $booking_end_hour,
                    ''
                );
            } else {
                $end_date_class = isset($_GET['check_out_prop']) ? sanitize_text_field($_GET['check_out_prop']) : '';

                ?>
                <div class=" has_calendar calendar_icon">
                    <input type="text" id="end_date"
                           placeholder="<?= wpestate_show_labels('check_out', $rental_type); ?>"
                           class="form-control calendar_icon" size="40" name="end_date" value="<?= $end_date_class; ?>">
                </div>
                <?php
            }
            if ($rental_type == 0) { ?>
                <div class=" has_calendar guest_icon ">
                    <?php
                    if (wprentals_get_option('wp_estate_custom_guest_control', '') == 'yes') {
                        echo wpestate_show_advanced_guest_form(esc_html__('Guests', 'wprentals'), '', $post_id);
                    } else {
                        echo wpestate_show_booking_form_guest_dropdown($guest_list);
                    }
                    ?>
                </div>
                <?php
            } else {
                ?>
                <input type="hidden" name="booking_guest_no" value="1">
                <?php
            }
            // show extra options
        wpestate_show_extra_options_booking($post_id)
            ?>
            <p class="full_form " id="add_costs_here"></p>
            <input type="hidden" id="listing_edit" name="listing_edit" value="<?= $post_id; ?>"/>
            <?php
            wpestate_show_booking_button($post_id);
            ?>
            <div class="third-form-wrapper">
                <div class="col-md-6 reservation_buttons">
                    <div id="add_favorites" class=" <?= esc_attr($favorite_class); ?>"
                         data-postid="<?= esc_attr($post_id); ?>">
                        <?= trim($favorite_text); ?>
                    </div>
                </div>
                <div class="col-md-6 reservation_buttons">
                    <div id="contact_host" class="col-md-6" data-postid="<?= esc_attr($post_id); ?>">
                        <?php
                        esc_html_e('Contact', 'wprentals');
                        ?>
                    </div>
                </div>
            </div>

            <?php
            // Social share. May be needed in the future
//            echo wpestate_share_unit_desing($post_id);
        } ?>
    </div>
    <?php
    // Only for shortcode
    if ($is_shortcode == 1) {
        $ajax_nonce = wp_create_nonce("wprentals_add_booking_nonce"); ?>
        <input type="hidden" id="wprentals_add_booking" value="<?= esc_html($ajax_nonce); ?>"/>
        <div class="modal fade" id="instant_booking_modal" tabindex="-1" aria-labelledby="myModalLabel"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h2 class="modal-title_big">
                            <?= esc_html__('Confirm your booking', 'wprentals'); ?>
                        </h2>
                        <h4 class="modal-title" id="myModalLabel">
                            <?= esc_html__('Review the dates and confirm your booking', 'wprentals'); ?>
                        </h4>
                    </div>
                    <div class="modal-body"></div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

        <?php
        if (isset($_GET['check_in_prop']) && isset($_GET['check_out_prop'])) { ?>
            <script type="text/javascript">
                // <![CDATA[
                jQuery(document).ready(function () {
                    setTimeout(function () {
                        jQuery("#end_date").trigger("change");
                    }, 1000);
                });
                //]]>
            </script>
            <?php
        }
    } // end for shortcode

    $return = ob_get_contents();
    ob_end_clean();

    return $return;
}
