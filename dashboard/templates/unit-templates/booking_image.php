<?php
/**
 * @var int $listing_id
 * @var bool $current_user_is_admin
 * @var array $preview
 * @var bool $is_group_booking
 * @var array $rooms_group_data_to_book
 * @var WP_Post|stdClass $featured_listing_from_last_room_group
 */

if ($is_group_booking && $featured_listing_from_last_room_group instanceof WP_Post) {
    if (isset($current_user_is_admin) && $current_user_is_admin) {
        $listing_link = $rooms_group_data_to_book['group_link'] ?? get_permalink($listing_id);
    } else {
        $listing_link = get_permalink($featured_listing_from_last_room_group->ID);
    }
} else {
    $listing_link = get_permalink($listing_id);
}
?>

<div class="blog_listing_image book_image">
    <a href="<?= esc_url($listing_link); ?>" target="_blank">
        <?php
        if (has_post_thumbnail($listing_id)) { ?>
            <img src="<?= esc_url($preview[0]); ?>" class="img-responsive"
                 alt="<?= esc_html__('image', 'wprentals'); ?>"/>
            <?php
        } else {
            $thumb_prop_default = get_stylesheet_directory_uri() . '/img/defaultimage_prop.jpg'; ?>
            <img src="<?= esc_url($thumb_prop_default); ?>" class="img-responsive"
                 alt="<?= esc_html__('image', 'wprentals'); ?>"/>
            <?php
        } ?>
    </a>
</div>
