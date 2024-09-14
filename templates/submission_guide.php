<?php

global $edit_link_desc;
global $edit_link_location;
global $edit_link_price;
global $edit_link_details;
global $edit_link_images;
global $edit_link_amenities;
global $edit_link_calendar;

if (isset($_GET['listing_edit']) && is_numeric($_GET['listing_edit'])) {
    $post_id = intval($_GET['listing_edit']);
    $edit_link = wpestate_get_template_link('user_dashboard_edit_listing.php');
    $edit_link = esc_url_raw(add_query_arg('listing_edit', $post_id, $edit_link));
    $edit_link_desc = esc_url_raw(add_query_arg('action', 'description', $edit_link));
    $edit_link_location = esc_url_raw(add_query_arg('action', 'location', $edit_link));
    $edit_link_price = esc_url_raw(add_query_arg('action', 'price', $edit_link));
    $edit_link_details = esc_url_raw(add_query_arg('action', 'details', $edit_link));
    $edit_link_images = esc_url_raw(add_query_arg('action', 'images', $edit_link));
    $edit_link_amenities = esc_url_raw(add_query_arg('action', 'amenities', $edit_link));
    $edit_link_calendar = esc_url_raw(add_query_arg('action', 'calendar', $edit_link));
    $allowed_html = array();

    $activeedit = '';
    $activelocation = '';
    $activeprice = '';
    $activedetails = '';
    $activeimages = '';
    $activeamm = '';
    $activecalendar = '';

    $action = sanitize_text_field(wp_kses($_GET['action'], $allowed_html));
    if ($action == 'description') {
        $activeedit = 'active ';
    } else {
        if ($action == 'location') {
            $activelocation = 'active';
            $activeedit = '';
            $activedetails = $activeimages = $activeprice = $activeedit = ' guide_past ';
        } else {
            if ($action == 'price') {
                $activeprice = 'active';
                $activeedit = '';
                $activeedit = ' guide_past ';
            } else {
                if ($action == 'details') {
                    $activedetails = 'active';
                    $activeedit = '';
                    $activeprice = $activeimages = $activeedit = ' guide_past ';
                } else {
                    if ($action == 'images') {
                        $activeimages = 'active';
                        $activeedit = '';
                        $activeprice = $activeedit = ' guide_past ';
                    } else {
                        if ($action == 'amenities') {
                            $activeamm = 'active';
                            $activeedit = '';
                            $activelocation = $activedetails = $activeimages = $activeprice = $activeedit = ' guide_past ';
                        } else {
                            if ($action == 'calendar') {
                                $activecalendar = 'active';
                                $activeedit = '';
                                $activeamm = $activelocation = $activedetails = $activeimages = $activeprice = $activeedit = ' guide_past ';
                            }
                        }
                    }
                }
            }
        }
    }
} else {
    $activeedit = 'active ';
    $edit_link_desc = '';
    $edit_link_location = '#';
    $edit_link_price = '#';
    $edit_link_details = '#';
    $edit_link_images = '#';
    $edit_link_amenities = '#';
    $edit_link_calendar = '#';
    $activeprice = $activeimages = $activedetails = $activelocation = $activeamm = $activecalendar = 'disabled';
}

?>

<div class=" user_dashboard_panel_guide">
    <a href="<?= esc_url($edit_link_desc); ?>" class="<?= esc_attr($activeedit); ?>">
        <?= esc_html__('Description ', 'wprentals'); ?>
    </a>
    <a href="<?= esc_url($edit_link_price); ?>" class="<?= esc_attr($activeprice); ?>">
        <?= esc_html__('Price ', 'wprentals'); ?>
    </a>
    <a href="<?= esc_url($edit_link_images); ?>" class="<?= esc_attr($activeimages); ?>">
        <?= esc_html__('Images ', 'wprentals'); ?>
    </a>
    <a href="<?= esc_url($edit_link_details); ?>" class="<?= esc_attr($activedetails); ?>">
        <?= esc_html__('Details ', 'wprentals'); ?>
    </a>
<!--    todo@@custom commented-->
<!--    <a href="--><?php //= esc_url($edit_link_location); ?><!--" class="--><?php //= esc_attr($activelocation); ?><!--">-->
<!--        --><?php //= esc_html__('Location ', 'wprentals'); ?>
<!--    </a>-->
    <a href="<?= esc_url($edit_link_amenities); ?>" class="<?= esc_attr($activeamm); ?>">
        <?= esc_html__('Amenities ', 'wprentals'); ?>
    </a>
    <a href="<?= esc_url($edit_link_calendar); ?>" class="menucalendar <?= esc_attr($activecalendar); ?>">
        <?= esc_html__('Calendar', 'wprentals'); ?>
    </a>
</div>