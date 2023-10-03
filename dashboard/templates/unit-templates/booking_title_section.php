<?php
/**
 *  The template part to display booking description in My Bookings page
 *
 * @var WP_Post $post
 * @var int $listing_id
 * @var string $booking_status
 * @var string $wpestate_currency
 * @var string $wpestate_where_currency
 * @var string $author
 * @var string $user_login
 * @var int $invoice_no
 * @var int $booking_guests
 * @var string $event_description
 * @var string $booking_from_date
 * @var string $booking_to_date
 * @var bool $current_user_is_admin
 * @var bool $is_group_booking
 * @var array $rooms_group_data_to_book
 * @var WP_Post|stdClass $featured_listing_from_last_room_group
 *
 */

$listing_title = get_the_title($listing_id);
$listing_link  = get_permalink($listing_id);

if ($booking_status === 'confirmed') {
    $total_price     = floatval(get_post_meta($post->ID, 'total_price', true));
    $to_be_paid      = floatval(get_post_meta($post->ID, 'to_be_paid', true));
    $to_be_paid      = $total_price - $to_be_paid;
    $to_be_paid_show = wpestate_show_price_booking($to_be_paid, $wpestate_currency, $wpestate_where_currency, 1);
} else {
    $to_be_paid      = floatval(get_post_meta($post->ID, 'total_price', true));
    $to_be_paid_show = wpestate_show_price_booking($to_be_paid, $wpestate_currency, $wpestate_where_currency, 1);
}

$prices_to_customize = [
    'item_price' => floatval(get_post_meta($invoice_no, 'item_price', true)),
];

if ($current_user_is_admin && $is_group_booking) {
    $listing_link = $rooms_group_data_to_book['group_link'] ?? $listing_link;

    if ($featured_listing_from_last_room_group instanceof WP_Post) {
        $listing_title = $featured_listing_from_last_room_group->post_title;
    }
}

// Start output buffering
ob_start();
?>

    <div class="prop-info">
        <h4 class="listing_title_book book_listing_user_title">
            <?= esc_html__('Booking request', 'wprentals') . ' ' . $post->ID; ?>
            <strong>
                <?= esc_html__('for', 'wprentals'); ?>
            </strong>
            <a href="<?= esc_url($listing_link); ?>" target="_blank">
                <?= esc_html($listing_title); ?>
            </a>
        </h4>

        <?php
        if ($author != $user_login) {
            if ($is_group_booking) { ?>
                <div class="user_dashboard_listed">
                    <span class="booking_details_title">
                        <?= esc_html__('Group Booking', 'wprentals'); ?>
                    </span>
                </div>
                <?php
            } ?>

            <div class="user_dashboard_listed book_listing_user_invoice">
                <span class="booking_details_title">
                    <?= esc_html__('Invoice No: ', 'wprentals'); ?>
                </span>
                <span class="invoice_list_id">
                    <?= esc_html($invoice_no); ?>
                </span>
            </div>
            <div class="user_dashboard_listed book_listing_user_pay">
                <span class="booking_details_title">
                    <?= esc_html__('Pay Amount: ', 'wprentals'); ?>
                </span>
                <?php
                echo wpestate_show_price_booking(
                    floatval(get_post_meta($invoice_no, 'item_price', true)),
                    $wpestate_currency,
                    $wpestate_where_currency,
                    1
                );
                ?>
                <span class="booking_details_title guest_details book_listing_user_guest_details">
                <?= esc_html__('Guests: ', 'wprentals'); ?>
            </span>
                <?php
                if ($booking_guests != 0) { ?>
                    <span class="book_listing_user_guest_details">
                        <?= esc_html($booking_guests) . wpestate_booking_guest_explanations($post->ID); ?>
                    </span>
                    <?php
                } ?>
            </div>
            <?php
        }

        include(locate_template('dashboard/templates/unit-templates/balance_display.php'));

        if ($event_description != '') { ?>
            <div class="user_dashboard_listed event_desc">
                <span class="booking_details_title">
                   <?= esc_html__('Reservation made by owner', 'wprentals'); ?>
                </span>
            </div>
            <div class="user_dashboard_listed event_desc">
                <span class="booking_details_title">
                        <?= esc_html__('Comments: ', 'wprentals'); ?>
                </span>
                <?= esc_html($event_description); ?>
            </div>
            <?php
        } ?>

    </div>

<?php
// End output buffering
echo ob_get_clean();