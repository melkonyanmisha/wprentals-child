<?php
/**
 * The template part to display booking description in My Reservations page
 *
 * @var string $booking_status
 * @var WP_Post $post
 * @var string $wpestate_currency
 * @var string $wpestate_where_currency
 * @var int $booking_id
 * @var int $invoice_no
 * @var int $booking_guests
 */

if ($booking_status == 'confirmed') {
    $total_price     = floatval(get_post_meta($post->ID, 'total_price', true));
    $to_be_paid      = floatval(get_post_meta($post->ID, 'to_be_paid', true));
    $to_be_paid      = $total_price - $to_be_paid;
    $to_be_paid_show = wpestate_show_price_booking($to_be_paid, $wpestate_currency, $wpestate_where_currency, 1);
} else {
    $to_be_paid      = floatval(get_post_meta($post->ID, 'total_price', true));
    $to_be_paid_show = wpestate_show_price_booking($to_be_paid, $wpestate_currency, $wpestate_where_currency, 1);
}

$featured_post_title_from_last_room_group = get_the_title($booking_id);
$featured_post_link_from_last_room_group  = get_permalink($booking_id);

// Get the room data from where start to book the timeshare user
if (current_user_is_timeshare()) {
    $is_group_booking = intval(get_post_meta($post->ID, 'is_group_booking', true));

    if ($is_group_booking) {
        $featured_listing_from_last_room_group = get_featured_listing_from_last_room_group();

        if ($featured_listing_from_last_room_group instanceof WP_Post) {
            $featured_post_title_from_last_room_group = $featured_listing_from_last_room_group->post_title;
            $featured_post_link_from_last_room_group  = get_post_permalink($featured_listing_from_last_room_group->ID);
        }
    }
}
?>

<div class="prop-info">
    <h4 class="listing_title_book book_listing_user_unit_title">
        <?= esc_html__('Booking request', 'wprentals') . ' ' . $post->ID; ?>
        <strong><?= esc_html__('for', 'wprentals'); ?></strong>
        <a href="<?= esc_url($featured_post_link_from_last_room_group); ?>">
            <?= esc_html($featured_post_title_from_last_room_group); ?>
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
