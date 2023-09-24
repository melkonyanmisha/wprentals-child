<?php

/**
 * Handle ajax request add_action('wp_ajax_wpestate_show_confirmed_booking', 'wpestate_show_confirmed_booking' );
 *
 * @return void
 */
function wpestate_show_confirmed_booking(): void
{
    check_ajax_referer('wprentals_booking_confirmed_actions_nonce', 'security');
    $current_user = wp_get_current_user();
    $userID       = $current_user->ID;

    if ( ! is_user_logged_in()) {
        exit('ko');
    }
    if ($userID === 0) {
        exit('out pls');
    }

    $invoice_id = intval($_POST['invoice_id']);
    $bookid     = intval($_POST['booking_id']);

    $the_post    = get_post($bookid);
    $book_author = $the_post->post_author;

    $the_post   = get_post($invoice_id);
    $inv_author = $the_post->post_author;

    if ($userID != $inv_author && $book_author != $userID) {
        exit('out pls');
    }

    wpestate_child_super_invoice_details($invoice_id);

    die();
}

/**
 * @param int $invoice_id
 * @param string $width_logo
 *
 * @return void
 */
function wpestate_child_super_invoice_details(int $invoice_id, string $width_logo = ''): void
{
    try {
        $bookid            = esc_html(get_post_meta($invoice_id, 'item_id', true));
        $booking_from_date = esc_html(get_post_meta($bookid, 'booking_from_date', true));
        $booking_prop      = intval(get_post_meta($bookid, 'booking_id', true)); // property_id
        $booking_to_date   = esc_html(get_post_meta($bookid, 'booking_to_date', true));
        $booking_guests    = intval(get_post_meta($bookid, 'booking_guests', true));
        $booking_type      = wprentals_return_booking_type($booking_prop);

        $booking_array     = [];
        $booking_full_data = json_decode(get_post_meta($invoice_id, 'booking_full_data', true), true);

        if ( ! empty($booking_full_data['booking_instant_data']['make_the_book']['booking_array'])) {
            $booking_array = $booking_full_data['booking_instant_data']['make_the_book']['booking_array'];
        }

        if (empty($booking_array)) {
            throw new Exception('Empty $booking_array');
        }

        $price_per_weekeend      = floatval(get_post_meta($booking_prop, 'price_per_weekeend', true));
        $total_price             = floatval(get_post_meta($invoice_id, 'item_price', true));
        $wpestate_currency       = esc_html(get_post_meta($invoice_id, 'invoice_currency', true));
        $wpestate_where_currency = esc_html(wprentals_get_option('wp_estate_where_currency_symbol', ''));
        $details                 = get_post_meta($invoice_id, 'renting_details', true);

        $default_price = $booking_array['default_price'];
        $depozit       = floatval(get_post_meta($invoice_id, 'depozit_paid', true));
        $balance       = $total_price - $depozit;

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
            $total_price,
            $wpestate_currency,
            $wpestate_where_currency,
            0,
            1
        );
        $depozit_show            = wpestate_show_price_booking_for_invoice( //todo@@ keep as exist
            $depozit,
            $wpestate_currency,
            $wpestate_where_currency,
            0,
            1
        );
        $balance_show            = wpestate_show_price_booking_for_invoice( //todo@@ keep as exist
            $balance,
            $wpestate_currency,
            $wpestate_where_currency,
            0,
            1
        );
        $guest_price             = wpestate_show_price_booking_for_invoice(
            $booking_array['extra_price_per_guest'],
            $wpestate_currency,
            $wpestate_where_currency,
            1,
            1
        );

        $invoice_saved = esc_html(get_post_meta($invoice_id, 'invoice_type', true));

        wpestate_chid_print_create_form_invoice(
            $guest_price,
            $booking_guests,
            $invoice_id,
            $invoice_saved,
            $booking_from_date,
            $booking_to_date,
            $booking_array,
            $price_show,
            $details,
            $wpestate_currency,
            $wpestate_where_currency,
            $total_price,
            $total_price_show,
            $depozit_show,
            $balance_show,
            $booking_prop,
            $price_per_weekeend_show,
            $booking_type,
            $width_logo
        );
    } catch (Exception|Error $e) {
        wp_die('Error: ' . $e->getMessage());
    }
}

/**
 * Original function location is wp-content/plugins/wprentals-core/post-types/invoices.php => wpestate_print_create_form_invoice()
 *
 * @param string $guest_price
 * @param int $booking_guests
 * @param int $invoice_id
 * @param string $invoice_saved
 * @param string $booking_from_date
 * @param string $booking_to_date
 * @param array $booking_array
 * @param string $price_show
 * @param array $details
 * @param string $wpestate_currency
 * @param string $wpestate_where_currency
 * @param float $total_price
 * @param string $total_price_show
 * @param string $depozit_show
 * @param string $balance_show
 * @param int $booking_prop
 * @param string $price_per_weekeend_show
 * @param string $booking_type
 * @param string $width_logo
 *
 * @return void
 */
function wpestate_chid_print_create_form_invoice(
    string $guest_price,
    int $booking_guests,
    int $invoice_id,
    string $invoice_saved,
    string $booking_from_date,
    string $booking_to_date,
    array $booking_array,
    string $price_show,
    array $details,
    string $wpestate_currency,
    string $wpestate_where_currency,
    float $total_price,
    string $total_price_show,
    string $depozit_show,
    string $balance_show,
    int $booking_prop,
    string $price_per_weekeend_show,
    string $booking_type,
    string $width_logo = ''
) {
    $rental_type  = esc_html(wprentals_get_option('wp_estate_item_rental_type', ''));
    $current_user = wp_get_current_user();
    $userID       = $current_user->ID;

    if (wpsestate_get_author($booking_prop) == $userID) {
        $total_label = esc_html__('User Pays', 'wprentals-core');
    } else {
        $total_label = esc_html__('You Pay', 'wprentals-core');
    }

    // Start output buffering
    ob_start();

    ?>

    <div class="create_invoice_form">
        <?php
        if ($invoice_id != 0) {
            if ($width_logo == 'yes') {
                $logo = wprentals_get_option('wp_estate_logo_image', 'url');
                if ($logo != '') { ?>
                    <img src="<?= esc_attr($logo); ?>" class="img-responsive printlogo" alt="logo"/>
                    <?php
                } else { ?>
                    <img class="img-responsive printlogo" src="<?= esc_attr(get_theme_file_uri('/img/logo.png')); ?>"
                         alt="logo"/>
                    <?php
                }
            }
            ?>
            <h3><?= esc_html__('Invoice INV', 'wprentals-core') . $invoice_id; ?></h3>
            <?php
        } ?>

        <div class="invoice_table">
            <div class="invoice_data">
                <?php
                $bookid                = get_post_meta($invoice_id, 'item_id', true);
                $post_author_id        = intval(get_post_meta($invoice_id, 'rented_by', true));
                $extra_price_per_guest = wpestate_show_price_booking(
                    $booking_array['extra_price_per_guest'],
                    $wpestate_currency,
                    $wpestate_where_currency,
                    1
                );

                if (
                    $invoice_saved == 'Reservation fee'
                    || $invoice_saved == esc_html__('Reservation fee', 'wprentals-core')
                ) { ?>

                    <span class="date_interval show_invoice_period">
                        <span class="invoice_data_legend">
                            <?= esc_html__('Period', 'wprentals-core') . ' : '; ?>
                        </span>
                        <?=
                        wpestate_convert_dateformat_reverse($booking_from_date)
                        . ' ' . esc_html__('to', 'wprentals-core')
                        . ' ' . wpestate_convert_dateformat_reverse($booking_to_date);
                        ?>
                    </span>
                    <span class="date_duration show_invoice_no_nights">
                        <span class="invoice_data_legend">
                            <?= wpestate_show_labels('no_of_nights', $rental_type, $booking_type) . ':'; ?>
                        </span>
                        <?= esc_html($booking_array['numberDays']); ?>
                    </span>
                    <?php
                    if ($booking_guests > 0) { ?>
                        <span class="date_duration show_invoice_guests">
                            <span class="invoice_data_legend">
                                <?= esc_html__('Guests', 'wprentals-core') . ':'; ?>
                            </span>
                            <?= esc_html($booking_guests . wpestate_booking_guest_explanations($bookid)); ?>
                        </span>
                        <?php
                    }
                    if ($booking_array['price_per_guest_from_one'] == 1) { ?>
                        <span class="date_duration show_invoice_price_per_quest ">
                            <span class="invoice_data_legend">
                                <?= esc_html__('Price per Guest', 'wprentals-core') . ':'; ?>
                            </span>
                           <?php
                           if ($booking_array['custom_period_quest'] == 1) {
                               _e('custom price', 'wprentals-core');
                           } else {
                               print $extra_price_per_guest;
                           }
                           ?>
                        </span>
                        <?php
                    } else { ?>
                        <span class="date_duration show_invoice_price_per_night">
                            <span class="invoice_data_legend">
                                <?= wpestate_show_labels('price_label', $rental_type, $booking_type) . ':'; ?>
                            </span>

                            <?php
                            echo $price_show;
                            if ($booking_array['has_custom']) {
                                echo ', ' . esc_html__('has custom price', 'wprentals-core');
                            }
                            if ($booking_array['cover_weekend']) {
                                echo ', '
                                     . esc_html__('has weekend price of', 'wprentals-core')
                                     . ' '
                                     . $price_per_weekeend_show;
                            }
                            ?>
                        </span>
                        <?php
                    }

                    if ($booking_array['has_custom'] || $booking_array['custom_period_quest'] == 1) {
                        if (is_array($booking_array['custom_price_array'])) { ?>
                            <span class="invoice_data_legend show_invoice_price_details">
                                <?= __('Price details:', 'wprentals-core'); ?>
                            </span>

                            <?php
                            foreach ($booking_array['custom_price_array'] as $date => $price) {
                                $day_price = wpestate_show_price_booking_for_invoice(
                                    $price,
                                    $wpestate_currency,
                                    $wpestate_where_currency,
                                    1,
                                    1
                                ); ?>
                                <span class="price_custom_explained show_invoice_price_details">
                                    <?=
                                    __('on', 'wprentals-core')
                                    . ' '
                                    . wpestate_convert_dateformat_reverse(date("Y-m-d", $date))
                                    . ' '
                                    . __('price is', 'wprentals-core')
                                    . ' ' . $day_price;
                                    ?>
                                </span>
                                <?php
                            }
                        }
                    }
                }

                if ($post_author_id == 0) {
                    if (get_post_type($bookid) == 'wpestate_booking') {
                        $post_author_id = get_post_field('post_author', $bookid);
                    } else {
                        $post_author_id = wpsestate_get_author($booking_prop);
                    }
                }

                if ($booking_prop == 0) {
                    $booking_prop = get_post_meta($invoice_id, 'for_property', true);
                }

                $first_name         = get_the_author_meta('first_name', $post_author_id);
                $last_name          = get_the_author_meta('last_name', $post_author_id);
                $user_email         = get_the_author_meta('user_email', $post_author_id);
                $user_billing_phone = get_the_author_meta('billing_phone', $post_author_id);
                $user_mobile        = get_the_author_meta('mobile', $post_author_id);
                $payment_info       = get_the_author_meta('payment_info', $post_author_id);
                $paypal_payments_to = get_the_author_meta('paypal_payments_to', $post_author_id);

                ?>
                <span class="date_duration invoice_date_renter_name_wrapper">
                    <span class="invoice_data_legend">
                        <?= esc_html__('Rented by', 'wprentals') . ':'; ?>
                    </span>
                    <?php
                    if (current_user_is_admin()) { ?>
                        <a href="<?= get_edit_user_link($post_author_id); ?>">
                            <?php
                            esc_html_e($first_name . ' ' . $last_name);
                            ?>
                        </a>
                        <?php
                    } else {
                        esc_html_e($first_name . ' ' . $last_name);
                    } ?>

                </span>
                <span class="date_duration invoice_date_renter_email_wrapper">
                    <span class="invoice_data_legend">
                        <?= esc_html__('Email', 'wprentals') . ':'; ?>
                    </span>
                    <?= $user_email; ?>
                </span>
                <span class="date_duration invoice_date_renter_phone_wrapper">
                    <span class="invoice_data_legend"><?= esc_html__('Phone', 'wprentals') . ':'; ?></span>
                    <?= $user_billing_phone ?? $user_mobile; ?>
                </span>
                <span class="date_duration invoice_date_renter_payment_info_wrapper">
                    <span class="invoice_data_legend"><?= esc_html__('Payment Info', 'wprentals') . ':'; ?> </span>
                    <?= $payment_info; ?>
                </span>
                <span class="date_duration invoice_date_renter_payments_to_wrapper">
                    <span class="invoice_data_legend"><?= esc_html__('Payments to', 'wprentals') . ':'; ?> </span>
                    <?= $paypal_payments_to; ?>
                </span>
            </div>

            <div class="invoice_details">
                <div class="invoice_row header_legend">
                    <span class="inv_legend"><?= esc_html__('Cost', 'wprentals-core'); ?></span>
                    <span class="inv_data"><?= esc_html__('Price', 'wprentals-core'); ?></span>
                    <span class="inv_exp"><?= esc_html__('Detail', 'wprentals-core'); ?></span>
                </div>

                <?php
                if (is_array($details)) {
                    foreach ($details as $detail) {
                        if ($detail[1] != 0) { ?>
                            <div class="invoice_row invoice_content">
                                <span class="inv_legend"><?= esc_html($detail[0]); ?></span>
                                <span class="inv_data">
                                    <?= esc_html(
                                        wpestate_show_price_booking_for_invoice(
                                            $detail[1],
                                            $wpestate_currency,
                                            $wpestate_where_currency,
                                            0,
                                            1
                                        )
                                    ); ?>
                                </span>
                                <span class="inv_exp">
                                    <?php
                                    if (
                                        trim($detail[0]) == esc_html__('Security Depozit', 'wprentals-core')
                                        || trim($detail[0]) == esc_html__('Security Deposit', 'wprentals-core')
                                    ) {
                                        esc_html_e('*refundable', 'wprentals-core');
                                    }

                                    if (
                                        trim($detail[0]) == esc_html__('Subtotal', 'wprentals-core')
                                        || trim($detail[0]) == esc_html__('Subtotal', 'wprentals')
                                    ) {
                                        if ($booking_array['price_per_guest_from_one'] == 1) {
                                            if ($booking_array['custom_period_quest'] == 1) {
                                                echo $booking_array['count_days']
                                                     . ' '
                                                     . wpestate_show_labels('nights', $rental_type, $booking_type)
                                                     . ' x '
                                                     . $booking_array['curent_guest_no']
                                                     . ' '
                                                     . esc_html__('guests', 'wprentals-core')
                                                     . ' - '
                                                     . esc_html__(" period with custom price per guest", "wprentals");
                                            } else {
                                                echo $extra_price_per_guest
                                                     . ' x '
                                                     . $booking_array['count_days']
                                                     . ' '
                                                     . wpestate_show_labels('nights', $rental_type, $booking_type)
                                                     . ' x '
                                                     . $booking_array['curent_guest_no']
                                                     . ' '
                                                     . esc_html__('guests', 'wprentals-core');
                                            }
                                        } else {
                                            echo $booking_array['numberDays']
                                                 . ' '
                                                 . wpestate_show_labels('nights', $rental_type, $booking_type)
                                                 . ' x ';
                                            if ($booking_array['cover_weekend']) {
                                                echo esc_html__('has weekend price of', 'wprentals-core')
                                                     . ' '
                                                     . $price_per_weekeend_show;
                                            } else {
                                                if ($booking_array['has_custom'] != 0) {
                                                    esc_html_e('custom price', 'wprentals-core');
                                                } else {
                                                    echo $price_show;
                                                }
                                            }
                                        }
                                    }

                                    if ($booking_array['custom_period_quest'] == 1) {
                                        $new_guest_price = esc_html__("custom price", "wprentals");
                                    } else {
                                        $new_guest_price = $guest_price . ' ' . wpestate_show_labels(
                                                'per_night',
                                                $rental_type,
                                                $booking_type
                                            );
                                    }

                                    if (
                                        trim($detail[0]) == esc_html__('Extra Guests', 'wprentals-core')
                                        || trim($detail[0]) == esc_html__('Extra Guests', 'wprentals')
                                    ) {
                                        echo $booking_array['numberDays']
                                             . ' '
                                             . wpestate_show_labels('nights', $rental_type, $booking_type)
                                             . ' x '
                                             . $booking_array['extra_guests']
                                             . ' '
                                             . esc_html__('extra guests', 'wprentals-core')
                                             . ' x '
                                             . $new_guest_price;
                                    }

                                    if (isset($detail[2])) {
                                        echo $detail[2];
                                    }
                                    ?>

                                </span>
                            </div>
                            <?php
                        }//end if($detail[1]>0)
                    }
                } ?>
                <!--show Total row-->
                <div class="invoice_row invoice_total invoice_create_print_invoice">
                    <span class="inv_legend inv_legend_total">
                        <strong><?= esc_html($total_label); ?></strong>
                    </span>
                    <span class="inv_data" id="total_amm" data-total="<?= esc_attr($total_price); ?>">
                        <?= $total_price_show; ?>
                    </span>
                    <div class="deposit_show_wrapper total_inv_span">
                        <?php
                        if (
                            $invoice_saved == 'Reservation fee'
                            || $invoice_saved == esc_html__('Reservation fee', 'wprentals-core')
                        ) { ?>
                            <span class="inv_legend">
                                <?= esc_html__('Reservation Fee Required', 'wprentals-core') . ':'; ?>
                            </span>
                            <span class="inv_depozit"><?= $depozit_show; ?> </span>
                            </br>
                            <span class="inv_legend">
                                <?= esc_html__('Balance', 'wprentals-core') . ':'; ?>
                            </span>
                            <span class="inv_depozit"><?= $balance_show; ?></span>
                            <?php
                        } else {
                            echo $invoice_saved;
                        } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    // End output buffering
    echo ob_get_clean();
}

