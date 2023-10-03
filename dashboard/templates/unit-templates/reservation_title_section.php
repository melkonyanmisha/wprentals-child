<?php
/**
 * The template part to display booking description in My Reservations page
 *
 * @var string $booking_status
 * @var WP_Post $post
 * @var string $wpestate_currency
 * @var string $wpestate_where_currency
 * @var int $listing_id
 * @var int $invoice_no
 * @var int $booking_guests
 * @var bool $current_user_is_timeshare
 * @var bool $current_user_is_customer
 * @var bool $is_group_booking
 * @var WP_Post|stdClass $featured_listing_from_last_room_group
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

// Get the room data from where start to book the timeshare user
if ($current_user_is_timeshare) {
    if ($is_group_booking) {
        if ($featured_listing_from_last_room_group instanceof WP_Post) {
            $listing_title = $featured_listing_from_last_room_group->post_title;
            $listing_link  = get_post_permalink($featured_listing_from_last_room_group->ID);
        }
    }
} elseif ($current_user_is_customer) {
    // The case when the listing is Room
    if (check_has_parent_room_category($listing_id)) {
        $room_category_id                = get_room_category_id($listing_id);
        $main_room_id_from_room_category = get_main_room_id_from_room_category($room_category_id);

        if ($main_room_id_from_room_category) {
            $listing_title = get_the_title($main_room_id_from_room_category);
            $listing_link  = get_permalink($main_room_id_from_room_category);
        }
    }
}
// Start output buffering
ob_start();

?>

    <div class="prop-info">
        <h4 class="listing_title_book book_listing_user_unit_title">
            <?= esc_html__('Booking request', 'wprentals') . ' ' . $post->ID; ?>
            <strong><?= esc_html__('for', 'wprentals'); ?></strong>
            <a href="<?= esc_url($listing_link); ?>">
                <?= esc_html($listing_title); ?>
            </a>
        </h4>
        <div class="user_dashboard_listed book_listing_user_unit_invoice">
            <strong><?= esc_html__('Invoice No: ', 'wprentals'); ?></strong>
            <span class="invoice_list_id"><?= esc_html($invoice_no); ?></span>
        </div>
        <div class="user_dashboard_listed book_listing_user_unit_guests">
            <?php
            if ($booking_guests != 0) { ?>
                <strong><?= esc_html__('Guests: ', 'wprentals'); ?> </strong>
                <?= esc_html($booking_guests . wpestate_booking_guest_explanations($post->ID));
            } ?>
        </div>
        <?php
        include(locate_template('dashboard/templates/unit-templates/balance_display.php'));
        ?>
    </div>

<?php
// End output buffering
echo ob_get_clean();
