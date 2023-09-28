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

    if ( ! is_user_logged_in() || $userID === 0) {
        wp_die('Permission denied');
    }

    $invoice_id   = intval($_POST['invoice_id']);
    $booking_id   = intval($_POST['booking_id']);
    $booking_post = get_post($booking_id);
    $book_author  = $booking_post->post_author;
    $invoice_post = get_post($invoice_id);
    $inv_author   = $invoice_post->post_author;

    if ($userID != $inv_author && $book_author != $userID) {
        wp_die('Permission denied');
    }

    wpestate_child_super_invoice_details($invoice_id);
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
        $booking_id        = esc_html(get_post_meta($invoice_id, 'item_id', true));
        $booking_from_date = esc_html(get_post_meta($booking_id, 'booking_from_date', true));
        $booking_prop      = intval(get_post_meta($booking_id, 'booking_id', true)); // property_id
        $booking_to_date   = esc_html(get_post_meta($booking_id, 'booking_to_date', true));
        $booking_guests    = intval(get_post_meta($booking_id, 'booking_guests', true));
        $booking_type      = wprentals_return_booking_type($booking_prop);
        $booking_full_data = json_decode(get_post_meta($invoice_id, 'booking_full_data', true), true);

        $booking_array = [];
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
        $default_price           = $booking_array['default_price'];
        $depozit                 = floatval(get_post_meta($invoice_id, 'depozit_paid', true));
        $balance                 = $total_price - $depozit;
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
        $invoice_saved           = esc_html(get_post_meta($invoice_id, 'invoice_type', true));

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
        wp_die($e->getMessage());
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
): void {
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
                // To display separately prices for accessible and remaining days. Depends on client user role
                echo render_additional_part_of_invoice($booking_array, $rental_type, $booking_type);

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


/**
 * Handle ajax request add_action( 'wp_ajax_wpestate_ajax_update_listing_description', 'wpestate_ajax_update_listing_description' );
 *
 * @return void
 */
function wpestate_ajax_update_listing_description(): void
{
    check_ajax_referer('wprentals_edit_prop_1_nonce', 'security');
    $current_user = wp_get_current_user();
    $userID       = $current_user->ID;

    if ( ! is_user_logged_in() || $userID === 0) {
        wp_die(json_encode(['edited' => false, 'response' => 'Permission denied']));
    }

    if (isset($_POST['listing_edit'])) {
        if ( ! is_numeric($_POST['listing_edit'])) {
            wp_die(json_encode(['edited' => false, 'response' => 'The "listing_edit" should be numeric']));
        } else {
            $edit_id  = intval($_POST['listing_edit']);
            $the_post = get_post($edit_id);

            if ($current_user->ID != $the_post->post_author) {
                wp_die(json_encode(['edited' => false, 'response' => 'Permission denied']));
            } else {
                ////////////////////////////////////////////////////////////////////
                // start the edit
                ////////////////////////////////////////////////////////////////////
                $allowed_html          = [];
                $has_errors            = false;
                $show_err              = '';
                $submit_title          = isset($_POST['title']) ? wp_kses($_POST['title'], $allowed_html) : '';
                $submit_desc           = isset($_POST['prop_desc']) ? wp_kses($_POST['prop_desc'], $allowed_html) : '';
                $wpestate_guest_no     = isset($_POST['guests']) ? intval($_POST['guests']) : '';
                $mandatory_page_fields = wprentals_get_option('wp_estate_mandatory_page_fields', '');
                $rental_type           = wprentals_get_option('wp_estate_item_rental_type');

                if ($rental_type == 1) {
                    $wpestate_guest_no = 1;
                }

                //category
                if ( ! isset($_POST['category'])) {
                    $prop_category = 0;
                } else {
                    $prop_category = intval($_POST['category']);
                }

                if ($prop_category == -1) {
                    wp_delete_object_term_relationships($edit_id, 'property_category');
                }

                //action category
                if ( ! isset($_POST['action_category'])) {
                    $prop_action_category = 0;
                } else {
                    $prop_action_category = wp_kses($_POST['action_category'], $allowed_html);
                }

                if ($prop_action_category == -1) {
                    wp_delete_object_term_relationships($edit_id, 'property_action_category');
                }

                $prop_category        = get_term($prop_category, 'property_category');
                $prop_action_category = get_term($prop_action_category, 'property_action_category');

                // city
                if ( ! isset($_POST['city'])) {
                    $property_city = 0;
                } else {
                    $property_city = wp_kses($_POST['city'], $allowed_html);
                }

                if ( ! isset($_POST['country'])) {
                    $property_country = '';
                } else {
                    $property_country = wp_kses($_POST['country'], $allowed_html);
                }

                if ( ! isset($_POST['area'])) {
                    $property_area = 0;
                } else {
                    $property_area = wp_kses($_POST['area'], $allowed_html);
                }

                if ( ! isset($_POST['property_admin_area'])) {
                    $property_admin_area = '';
                } else {
                    $property_admin_area = wp_kses($_POST['property_admin_area'], $allowed_html);
                }

                if ( ! isset($_POST['aff_link'])) {
                    $property_affiliate = '';
                } else {
                    $property_affiliate = esc_url($_POST['aff_link']);
                }

                if ( ! isset($_POST['private_notes'])) {
                    $private_notes = '';
                } else {
                    $private_notes = esc_html($_POST['private_notes']);
                }

                $overload_guest     = floatval($_POST['overload_guest']);
                $max_extra_guest_no = isset($_POST['max_extra_guest_no']) ? intval($_POST['max_extra_guest_no']) : 0;

                //////////////////////////////////////// the updated

                if ($submit_title == '') {
                    $has_errors = true;
                    $errors[]   = esc_html__('Please submit a title for your listing', 'wprentals');
                }

                if (is_array($mandatory_page_fields) && in_array('prop_category_submit', $mandatory_page_fields)) {
                    if ($prop_category == '' || $prop_category == '-1') {
                        $has_errors = true;
                        $errors[]   = esc_html__('Please submit a category for your property', 'wprentals');
                    }
                }

                if (
                    is_array($mandatory_page_fields)
                    && in_array('prop_action_category_submit', $mandatory_page_fields)
                ) {
                    if ($prop_action_category == '' || $prop_action_category == '-1') {
                        $has_errors = true;
                        $errors[]   = esc_html__('Please submit a  the second category for your property', 'wprentals');
                    }
                }

                if (
                    is_array($mandatory_page_fields)
                    && (in_array('property_city_front', $mandatory_page_fields) || in_array(
                            'property_area_front',
                            $mandatory_page_fields
                        ))
                ) {
                    if ($property_city == '') {
                        $has_errors = true;
                        $errors[]   = esc_html__('Please submit a city for your listing', 'wprentals');
                    }
                }

                if (is_array($mandatory_page_fields) && in_array('property_affiliate', $mandatory_page_fields)) {
                    if ($property_affiliate == '') {
                        $has_errors = true;
                        $errors[]   = esc_html__('Please submit an affiliate link', 'wprentals');
                    }
                }

                if (is_array($mandatory_page_fields) && in_array('max_extra_guest_no', $mandatory_page_fields)) {
                    if ($max_extra_guest_no == '') {
                        $has_errors = true;
                        $errors[]   = esc_html__(
                            'Please submit an maximum extra guests above capacity number',
                            'wprentals'
                        );
                    }
                }

                if ($has_errors) {
                    foreach ($errors as $key => $value) {
                        $show_err .= $value . '</br>';
                    }
                    wp_die(json_encode(['edited' => false, 'response' => $show_err]));
                } else {
                    $post = [
                        'ID'           => $edit_id,
                        'post_title'   => $submit_title,
                        'post_type'    => 'estate_property',
                        'post_content' => $submit_desc
                    ];

                    $post_id              = wp_update_post($post);
                    $prop_category        = get_term($prop_category, 'property_category');
                    $prop_action_category = get_term($prop_action_category, 'property_action_category');

                    if (isset($property_city) && $property_city != 'none' && $property_city != '') {
                        wp_set_object_terms($post_id, $property_city, 'property_city');
                    }

                    if (isset($property_area) && $property_area != 'none') {
                        $property_area = wpestate_double_tax_cover($property_area, $property_city, $post_id);
                        // wp_set_object_terms($post_id,$property_area,'property_area');
                    }

                    if (isset ($prop_action_category->name)) {
                        wp_set_object_terms($post_id, $prop_action_category->name, 'property_action_category');
                    }

                    if (isset($prop_category->name)) {
                        wp_set_object_terms($post_id, $prop_category->name, 'property_category');
                    }

                    if (isset($property_area) && $property_area != 'none' && $property_area != '') {
                        $property_area_obj = get_term_by('name', $property_area, 'property_area');

                        $t_id                    = $property_area_obj->term_id;
                        $term_meta               = get_option("taxonomy_$t_id");
                        $term_meta['cityparent'] = wp_kses($property_city, $allowed_html);

                        //save the option array
                        update_option("taxonomy_$t_id", $term_meta);
                    }

                    update_post_meta($post_id, 'guest_no', $wpestate_guest_no);
                    update_post_meta($post_id, 'overload_guest', $overload_guest);
                    update_post_meta($post_id, 'max_extra_guest_no', $max_extra_guest_no);
                    update_post_meta($post_id, 'instant_booking', intval($_POST['instant_booking']));
                    update_post_meta($post_id, 'children_as_guests', intval($_POST['children_as_guests']));
                    update_post_meta($post_id, 'property_affiliate', $property_affiliate);
                    update_post_meta($post_id, 'private_notes', $private_notes);

                    $property_country = wprentals_agolia_dirty_hack($property_country);
                    update_post_meta($post_id, 'property_country', strtolower($property_country));

                    $property_admin_area = str_replace(" ", "-", $property_admin_area);
                    $property_admin_area = str_replace("\'", "", $property_admin_area);
                    update_post_meta($post_id, 'property_admin_area', strtolower($property_admin_area));

                    $status         = wpestate_global_check_mandatory($post_id);
                    $message_status = '';
                    if ($status == 'pending') {
                        $message_status = esc_html__(
                            'Your listing is pending. Please complete all the mandatory fields for it to be published!',
                            'wprentals'
                        );
                    }
                    wp_die(
                        json_encode([
                            'edited'   => true,
                            'response' => esc_html__('Changes are saved!', 'wprentals') . ' ' . $message_status
                        ])
                    );
                }
            }
        }
    } else {
        wp_die(json_encode(['edited' => false, 'response' => 'Missing "listing_edit"']));
    }
}

/**
 * Handle ajax request add_action( 'wp_ajax_wpestate_ajax_update_listing_price', 'wpestate_ajax_update_listing_price' );
 *
 * @return void
 */
function wpestate_ajax_update_listing_price(): void
{
    check_ajax_referer('wprentals_edit_prop_price_nonce', 'security');

    $current_user = wp_get_current_user();
    $userID       = $current_user->ID;

    if ( ! is_user_logged_in() || $userID === 0) {
        wp_die(json_encode(['edited' => false, 'response' => 'Permission denied']));
    }

    if (isset($_POST['listing_edit'])) {
        if ( ! is_numeric($_POST['listing_edit'])) {
            wp_die(json_encode(['edited' => false, 'response' => 'The "listing_edit" should be numeric']));
        } else {
            $edit_id  = intval($_POST['listing_edit']);
            $the_post = get_post($edit_id);

            if ($current_user->ID != $the_post->post_author) {
                wp_die(json_encode(['edited' => false, 'response' => "Permission denied"]));
            } else {
                $cleaning_fee       = isset($_POST['cleaning_fee']) ? floatval($_POST['cleaning_fee']) : 0;
                $city_fee           = isset($_POST['city_fee']) ? floatval($_POST['city_fee']) : 0;
                $price              = isset($_POST['price']) ? floatval($_POST['price']) : 0;
                $book_type          = isset($_POST['book_type']) ? floatval($_POST['book_type']) : 0;
                $price_week         = isset($_POST['price_week']) ? floatval($_POST['price_week']) : 0;
                $price_month        = isset($_POST['price_month']) ? floatval($_POST['price_month']) : 0;
                $city_fee_per_day   = isset($_POST['city_fee_per_day']) ? floatval($_POST['city_fee_per_day']) : 0;
                $min_days_booking   = isset($_POST['min_days_booking']) ? floatval($_POST['min_days_booking']) : 0;
                $price_per_weekeend = isset($_POST['price_per_weekeend']) ? floatval($_POST['price_per_weekeend']) : 0;
                $city_fee_percent   = isset($_POST['city_fee_percent']) ? floatval($_POST['city_fee_percent']) : 0;
                $security_deposit   = isset($_POST['security_deposit']) ? floatval($_POST['security_deposit']) : 0;
                $extra_pay_options  = $_POST['extra_pay_options'] ?? [];
                $early_bird_percent = isset($_POST['early_bird_percent']) ? floatval($_POST['early_bird_percent']) : 0;
                $early_bird_days    = isset($_POST['early_bird_days']) ? floatval($_POST['early_bird_days']) : 0;
                $property_taxes     = isset($_POST['property_taxes']) ? floatval($_POST['property_taxes']) : 0;
                $booking_start_hour = isset($_POST['booking_start_hour']) ? esc_html($_POST['booking_start_hour']) : '';
                $booking_end_hour   = isset($_POST['booking_end_hour']) ? esc_html($_POST['booking_end_hour']) : '';

                $cleaning_fee_per_day = isset($_POST['cleaning_fee_per_day']) ? floatval(
                    $_POST['cleaning_fee_per_day']
                ) : 0;

                $price_per_guest_from_one = isset($_POST['price_per_guest_from_one']) ? floatval(
                    $_POST['price_per_guest_from_one']
                ) : 0;

                $checkin_change_over = isset($_POST['checkin_change_over']) ? floatval(
                    $_POST['checkin_change_over']
                ) : 0;

                $checkin_checkout_change_over = isset($_POST['checkin_checkout_change_over']) ? floatval(
                    $_POST['checkin_checkout_change_over']
                ) : 0;

                $extra_price_per_guest = isset($_POST['extra_price_per_guest']) ? floatval(
                    $_POST['extra_price_per_guest']
                ) : 0;

                $property_price_after_label = isset($_POST['property_price_after_label']) ? esc_html(
                    $_POST['property_price_after_label']
                ) : '';

                $property_price_before_label = isset($_POST['property_price_before_label']) ? esc_html(
                    $_POST['property_price_before_label']
                ) : '';

                $extra_pay_values = [];
                if (is_array($extra_pay_options)) {
                    foreach ($extra_pay_options as $key => $pay_option) {
                        $option             = explode('|', $pay_option);
                        $extra_pay_values[] = $option;
                    }
                }

                update_post_meta($edit_id, 'property_price', $price);
                update_post_meta($edit_id, 'local_booking_type', $book_type);
                update_post_meta($edit_id, 'cleaning_fee', $cleaning_fee);
                update_post_meta($edit_id, 'city_fee', $city_fee);
                update_post_meta($edit_id, 'property_price_per_week', $price_week);
                update_post_meta($edit_id, 'property_price_per_month', $price_month);
                update_post_meta($edit_id, 'cleaning_fee_per_day', $cleaning_fee_per_day);
                update_post_meta($edit_id, 'city_fee_per_day', $city_fee_per_day);
                update_post_meta($edit_id, 'price_per_guest_from_one', $price_per_guest_from_one);
                update_post_meta($edit_id, 'price_per_weekeend', $price_per_weekeend);
                update_post_meta($edit_id, 'checkin_change_over', $checkin_change_over);
                update_post_meta($edit_id, 'checkin_checkout_change_over', $checkin_checkout_change_over);
                update_post_meta($edit_id, 'min_days_booking', $min_days_booking);
                update_post_meta($edit_id, 'extra_price_per_guest', $extra_price_per_guest);
                update_post_meta($edit_id, 'city_fee_percent', $city_fee_percent);
                update_post_meta($edit_id, 'security_deposit', $security_deposit);
                update_post_meta($edit_id, 'property_price_after_label', $property_price_after_label);
                update_post_meta($edit_id, 'property_price_before_label', $property_price_before_label);
                update_post_meta($edit_id, 'extra_pay_options', $extra_pay_values);
                update_post_meta($edit_id, 'early_bird_days', $early_bird_days);
                update_post_meta($edit_id, 'early_bird_percent', $early_bird_percent);
                update_post_meta($edit_id, 'property_taxes', $property_taxes);
                update_post_meta($edit_id, 'booking_start_hour', $booking_start_hour);
                update_post_meta($edit_id, 'booking_end_hour', $booking_end_hour);

                if (function_exists('icl_translate')) {
                    do_action('wpml_sync_all_custom_fields', $edit_id);
                }

                $status         = wpestate_global_check_mandatory($edit_id);
                $message_status = '';
                if ($status == 'pending') {
                    $message_status = esc_html__(
                        'Your listing is pending. Please complete all the mandatory fields for it to be published!',
                        'wprentals'
                    );
                }

                wp_die(
                    json_encode([
                        'edited'   => true,
                        'response' => esc_html__('Changes are saved!', 'wprentals') . ' ' . $message_status
                    ])
                );
            }
        }
    } else {
        wp_die(json_encode(['edited' => false, 'response' => 'Missing "listing_edit"']));
    }
}

/**
 * Handle ajax request add_action( 'wp_ajax_wpestate_ajax_update_listing_images', 'wpestate_ajax_update_listing_images' );
 *
 * @return void
 */
function wpestate_ajax_update_listing_images(): void
{
    check_ajax_referer('wprentals_edit_prop_image_nonce', 'security');
    $current_user = wp_get_current_user();
    $userID       = $current_user->ID;

    if ( ! is_user_logged_in() || $userID === 0) {
        wp_die(json_encode(['edited' => false, 'response' => 'Permission denied']));
    }

    if (isset($_POST['listing_edit'])) {
        if ( ! is_numeric($_POST['listing_edit'])) {
            wp_die(json_encode(['edited' => false, 'response' => 'The "listing_edit" should be numeric']));
        } else {
            $edit_id  = intval($_POST['listing_edit']);
            $the_post = get_post($edit_id);

            if ($current_user->ID != $the_post->post_author) {
                wp_die(json_encode(['edited' => false, 'response' => "Permission denied"]));
            } else {
                $allowed_html = [];
                $iframe       = [
                    'iframe' => [
                        'src'             => [],
                        'width'           => [],
                        'height'          => [],
                        'frameborder'     => [],
                        'style'           => [],
                        'allowFullScreen' => [] // add any other attributes you wish to allow
                    ]
                ];
                $video_type   = isset($_POST['video_type']) ? wp_kses($_POST['video_type'], $allowed_html) : '';
                $video_id     = isset($_POST['video_id']) ? wp_kses($_POST['video_id'], $allowed_html) : '';
                $attachthumb  = isset($_POST['attachthumb']) ? intval($_POST['attachthumb']) : 0;
                $attachid     = isset($_POST['attachid']) ? wp_kses($_POST['attachid'], $allowed_html) : '';
                $virtual_tour = isset($_POST['virtual_tour']) ? wp_kses(trim($_POST['virtual_tour']), $iframe) : '';

                $attach_array = explode(',', $attachid);
                $last_id      = '';

                // check for deleted images
                $arguments        = [
                    'numberposts' => -1,
                    'post_type'   => 'attachment',
                    'post_parent' => $edit_id,
                    'post_status' => null,
                    'orderby'     => 'menu_order',
                    'order'       => 'ASC'
                ];
                $post_attachments = get_posts($arguments);
                $new_thumb        = 0;
                $curent_thumb     = get_post_thumbnail_id($edit_id);

                if (function_exists('icl_translate')) {
                    // code from wpml team

                    foreach ($post_attachments as $attachment) {
                        if ( ! in_array($attachment->ID, $attach_array)) {
                            $active_languages = apply_filters('wpml_active_languages');
                            foreach ($active_languages as $language) {
                                $current_language = apply_filters('wpml_current_language');
                                if ($language['code'] != $current_language) {
                                    $attach_id = apply_filters(
                                        'wpml_object_id',
                                        $attachment->ID,
                                        'attachment',
                                        false,
                                        $language['code']
                                    );
                                    if ($attach_id) {
                                        wp_delete_post($attach_id);
                                    }
                                }
                            }
                            wp_delete_post($attachment->ID);
                        }
                    }
                } else {
                    foreach ($post_attachments as $attachment) {
                        if ( ! in_array($attachment->ID, $attach_array)) {
                            wp_delete_post($attachment->ID);
                            if ($curent_thumb == $attachment->ID) {
                                $new_thumb = 1;
                            }
                        }
                    }
                }
                // check for deleted images

                $order = 0;
                foreach ($attach_array as $att_id) {
                    if (is_numeric($att_id)) {
                        if ($last_id == '') {
                            $last_id = $att_id;
                        }
                        $order++;
                        wp_update_post([
                            'ID'          => $att_id,
                            'post_parent' => $edit_id,
                            'menu_order'  => $order
                        ]);
                    }
                }

                if ($attachthumb != '') {
                    set_post_thumbnail($edit_id, $attachthumb);
                }

                if ($new_thumb == 1 || ! has_post_thumbnail($edit_id) || $attachthumb == '') {
                    set_post_thumbnail($edit_id, $last_id);
                }

                update_post_meta($edit_id, 'embed_video_type', $video_type);
                update_post_meta($edit_id, 'embed_video_id', $video_id);
                update_post_meta($edit_id, 'virtual_tour', $virtual_tour);

                $status         = wpestate_global_check_mandatory($edit_id);
                $message_status = '';
                if ($status == 'pending') {
                    $message_status = esc_html__(
                        'Your listing is pending. Please complete all the mandatory fields for it to be published!',
                        'wprentals'
                    );
                }

                wp_die(
                    json_encode([
                        'edited'   => true,
                        'response' => esc_html__('Changes are saved!', 'wprentals') . ' ' . $message_status
                    ])
                );
            }
        }
    }
}

/**
 * Handle ajax request add_action( 'wp_ajax_wpestate_ajax_update_listing_details', 'wpestate_ajax_update_listing_details' );
 * @return void
 */
function wpestate_ajax_update_listing_details(): void
{
    check_ajax_referer('wprentals_edit_prop_details_nonce', 'security');
    $current_user       = wp_get_current_user();
    $userID             = $current_user->ID;
    $api_update_details = [];

    if ( ! is_user_logged_in() || $userID === 0) {
        wp_die(json_encode(['edited' => false, 'response' => 'Permission denied']));
    }

    if (isset($_POST['listing_edit'])) {
        if ( ! is_numeric($_POST['listing_edit'])) {
            wp_die(json_encode(['edited' => false, 'response' => 'The "listing_edit" should be numeric']));
        } else {
            $edit_id  = intval($_POST['listing_edit']);
            $the_post = get_post($edit_id);

            if ($current_user->ID != $the_post->post_author) {
                wp_die(json_encode(['edited' => false, 'response' => "Permission denied"]));
            } else {
                $allowed_html       = [];
                $property_size      = isset($_POST['property_size']) ? floatval($_POST['property_size']) : 0;
                $property_rooms     = isset($_POST['property_rooms']) ? floatval($_POST['property_rooms']) : 0;
                $property_bedrooms  = isset($_POST['property_bedrooms']) ? floatval($_POST['property_bedrooms']) : 0;
                $property_bathrooms = isset($_POST['property_bathrooms']) ? floatval($_POST['property_bathrooms']) : 0;
                $beds_options       = isset($_POST['beds_options']) ? array_map(
                    'intval',
                    (array)$_POST['beds_options']
                ) : [];

                $bed_types          = esc_html(wprentals_get_option('wp_estate_bed_list', ''));
                $bed_types_array    = explode(',', $bed_types);
                $beds_options_array = [];

                $i = 0;
                while ($i < count($beds_options)) {
                    $index_bed_options = $i % (count($bed_types_array));

                    $beds_options_array[trim(
                        wpestate_convert_cyrilic($bed_types_array[$index_bed_options])
                    )][] = $beds_options[$i];
                    $i++;
                }

                update_post_meta($edit_id, 'property_size', $property_size);
                update_post_meta($edit_id, 'property_rooms', $property_rooms);
                update_post_meta($edit_id, 'property_bedrooms', $property_bedrooms);
                update_post_meta($edit_id, 'property_bedrooms_details', $beds_options_array);
                update_post_meta($edit_id, 'property_bathrooms', $property_bathrooms);

                $other_rules   = isset($_POST['other_rules']) ? sanitize_textarea_field($_POST['other_rules']) : '';
                $pets_allowed  = isset($_POST['pets_allowed']) ? sanitize_text_field($_POST['pets_allowed']) : '';
                $party_allowed = isset($_POST['party_allowed']) ? sanitize_text_field($_POST['party_allowed']) : '';

                $cancellation_policy = isset($_POST['cancellation_policy']) ? sanitize_textarea_field(
                    $_POST['cancellation_policy']
                ) : '';

                $smoking_allowed = isset($_POST['smoking_allowed']) ? sanitize_text_field(
                    $_POST['smoking_allowed']
                ) : '';

                $children_allowed = isset($_POST['children_allowed']) ? sanitize_text_field(
                    $_POST['children_allowed']
                ) : '';

                update_post_meta($edit_id, 'cancellation_policy', $cancellation_policy);
                update_post_meta($edit_id, 'other_rules', $other_rules);
                update_post_meta($edit_id, 'smoking_allowed', $smoking_allowed);
                update_post_meta($edit_id, 'pets_allowed', $pets_allowed);
                update_post_meta($edit_id, 'party_allowed', $party_allowed);
                update_post_meta($edit_id, 'children_allowed', $children_allowed);

                $custom_values = explode('~', wp_kses($_POST['custom_fields_val'], $allowed_html));

                // save custom fields
                $i             = 0;
                $custom_fields = wprentals_get_option('wpestate_custom_fields_list', '');
                if ( ! empty($custom_fields)) {
                    while ($i < count($custom_fields)) {
                        $name = $custom_fields[$i][0];
                        $type = $custom_fields[$i][1];
                        $slug = str_replace(' ', '_', $name);
                        $slug = wpestate_limit45(sanitize_title($name));
                        $slug = sanitize_key($slug);

                        if ($type == 'numeric' && $custom_values[$i + 1] != '') {
                            $value_custom = intval(wp_kses($custom_values[$i + 1], $allowed_html));
                            update_post_meta($edit_id, $slug, $value_custom);
                        } else {
                            $value_custom = sanitize_text_field($custom_values[$i + 1]);
                            update_post_meta($edit_id, $slug, $value_custom);
                        }

                        $api_update_details[$slug] = $value_custom;
                        $i++;
                    }
                }

                $extra_details_options = $_POST['extra_details_options'] ?? [];
                $extra_details_array   = [];

                if ( ! empty($extra_details_options)) {
                    foreach ($extra_details_options as $key => $detail_option) {
                        $option                          = explode('|', $detail_option);
                        $extra_details_array[$option[0]] = sanitize_text_field($option[1]);
                    }
                }
                update_post_meta($edit_id, 'property_custom_details', $extra_details_array);

                $status         = wpestate_global_check_mandatory($edit_id);
                $message_status = '';

                if ($status == 'pending') {
                    $message_status = esc_html__(
                        'Your listing is pending. Please complete all the mandatory fields for it to be published!',
                        'wprentals'
                    );
                }

                wp_die(
                    json_encode([
                        'edited'       => true,
                        'beds_options' => count($beds_options),
                        'response'     => esc_html__('Changes are saved!', 'wprentals') . ' ' . $message_status
                    ])
                );
            }
        }
    }
}

/**
 * Handle ajax request add_action( 'wp_ajax_wpestate_ajax_update_listing_location', 'wpestate_ajax_update_listing_location' );
 *
 * @return void
 */
function wpestate_ajax_update_listing_location(): void
{
    check_ajax_referer('wprentals_edit_prop_locations_nonce', 'security');
    $current_user = wp_get_current_user();
    $userID       = $current_user->ID;

    if ( ! is_user_logged_in() || $userID === 0) {
        wp_die(json_encode(['edited' => false, 'response' => 'Permission denied']));
    }

    if (isset($_POST['listing_edit'])) {
        if ( ! is_numeric($_POST['listing_edit'])) {
            wp_die(json_encode(['edited' => false, 'response' => 'The "listing_edit" should be numeric']));
        } else {
            $edit_id  = intval($_POST['listing_edit']);
            $the_post = get_post($edit_id);

            if ($current_user->ID != $the_post->post_author) {
                wp_die(json_encode(['edited' => false, 'response' => "Permission denied"]));
            } else {
                $allowed_html       = [];
                $property_latitude  = isset($_POST['property_latitude']) ? floatval($_POST['property_latitude']) : 0;
                $property_longitude = isset($_POST['property_longitude']) ? floatval($_POST['property_longitude']) : 0;

                $property_address = isset($_POST['property_address']) ? wp_kses(
                    $_POST['property_address'],
                    $allowed_html
                ) : '';

                $property_zip = isset($_POST['property_zip']) ? wp_kses(
                    $_POST['property_zip'],
                    $allowed_html
                ) : '';

                $property_county = isset($_POST['property_county']) ? wp_kses(
                    $_POST['property_county'],
                    $allowed_html
                ) : '';

                $property_state = isset($_POST['property_state']) ? wp_kses(
                    $_POST['property_state'],
                    $allowed_html
                ) : '';

                $google_camera_angle = isset($_POST['google_camera_angle']) ? floatval(
                    $_POST['google_camera_angle']
                ) : 0;

                update_post_meta($edit_id, 'property_latitude', $property_latitude);
                update_post_meta($edit_id, 'property_longitude', $property_longitude);
                update_post_meta($edit_id, 'google_camera_angle', $google_camera_angle);
                update_post_meta($edit_id, 'property_address', $property_address);
                update_post_meta($edit_id, 'property_zip', $property_zip);
                update_post_meta($edit_id, 'property_state', $property_state);
                update_post_meta($edit_id, 'property_county', $property_county);

                $status         = wpestate_global_check_mandatory($edit_id);
                $message_status = '';

                if ($status == 'pending') {
                    $message_status = esc_html__(
                        'Your listing is pending. Please complete all the mandatory fields for it to be published!',
                        'wprentals'
                    );
                }

                wp_die(
                    json_encode([
                        'edited'   => true,
                        'response' => esc_html__('Changes are saved!', 'wprentals') . ' ' . $message_status
                    ])
                );
            }
        }
    }
}
