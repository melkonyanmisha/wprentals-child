<?php

global $wpestate_curent_fav;
global $wpestate_currency;
global $wpestate_where_currency;
global $show_remove_fav;
global $is_shortcode;
global $is_widget;
global $wpestate_row_number_col;
global $wpestate_full_page;
global $wpestate_listing_type;
global $prop_selection;
global $post;

$booking_type = wprentals_return_booking_type($post->ID);
$rental_type  = wprentals_get_option('wp_estate_item_rental_type');

if ($wpestate_listing_type == 3) {
    include(locate_template('templates/property_unit_3.php'));

    return true;
} elseif ($wpestate_listing_type == 4) {
    include(locate_template('templates/property_unit_4.php'));

    return true;
}

$col_class = 'col-md-6';
$col_org   = 4;
$title     = get_the_title($post->ID);

if (is_front_page()) {
    if (isset($is_shortcode) && $is_shortcode == 1) {
        $col_class = 'col-md-' . esc_attr($wpestate_row_number_col) . ' shortcode-col';
    }

    if (isset($is_widget) && $is_widget == 1) {
        $col_class = 'col-md-12';
        $col_org   = 12;
    }

    if (isset($wpestate_full_page) && $wpestate_full_page == 1) {
        $col_class = 'col-md-4 ';
        $col_org   = 3;
        if (isset($is_shortcode) && $is_shortcode == 1 && $wpestate_row_number_col == '') {
            $col_class = 'col-md-' . esc_attr($wpestate_row_number_col) . ' shortcode-col';
        }
    }
} else {
    $col_class = $post->term_type === 'cottage' ? 'col-md-4' : 'col-md-12';
}

$link                  = esc_url(get_permalink());
$wprentals_is_per_hour = wprentals_return_booking_type($post->ID);
$card_link             = wprentals_card_link_autocomplete($post->ID, $link, $wprentals_is_per_hour);
$preview               = [];
$preview[0]            = '';
$favorite_class        = 'icon-fav-off';
$fav_mes               = esc_html__('add to favorites', 'wprentals');
if ($wpestate_curent_fav) {
    if (in_array($post->ID, $wpestate_curent_fav)) {
        $favorite_class = 'icon-fav-on';
        $fav_mes        = esc_html__('remove from favorites', 'wprentals');
    }
}

$listing_type_class = 'property_unit_v2';
if ($wpestate_listing_type == 1) {
    $listing_type_class = 'property_unit_v1';
}

global $schema_flag;
if ($schema_flag == 1) {
    $schema_data = 'itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" ';
} else {
    $schema_data = ' itemscope itemtype="https://schema.org/Product" ';
}
?>

<div <?= trim($schema_data); ?>
        class="listing_wrapper <?= esc_attr($col_class . ' ' . $listing_type_class); ?>  property_flex "
        data-org="<?= esc_attr($col_org); ?>" data-listid="<?= esc_attr($post->ID); ?>">

    <?php
    if ($schema_flag == 1) { ?>
        <meta itemprop="position" content="<?= esc_html($prop_selection->current_post); ?>"/>
        <?php
    }
    ?>

    <div class="property_listing ">
        <?php
        $featured        = intval(get_post_meta($post->ID, 'prop_featured', true));
        $price           = intval(get_post_meta($post->ID, 'property_price', true));
        $property_city   = get_the_term_list($post->ID, 'property_city', '', ', ', '');
        $property_area   = get_the_term_list($post->ID, 'property_area', '', ', ', '');
        $property_action = get_the_term_list($post->ID, 'property_action_category', '', ', ', '');
        $property_categ  = get_the_term_list($post->ID, 'property_category', '', ', ', '');
        $preview         = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'wpestate_property_full_map');

        if (isset($preview[0])) {
            $thumb_prop = '<img itemprop="image" src="' . esc_url($preview[0]) . '" 
                class="b-lazy img-responsive wp-post-image lazy-hidden" 
                alt="' . esc_attr(get_the_title($post->ID)) . '" />';
        } else {
            $thumb_prop_default = get_stylesheet_directory_uri() . '/img/defaultimage_prop.jpg';
            $thumb_prop         = '<img itemprop="image"  src="' . esc_url($thumb_prop_default) . '" 
                class="b-lazy img-responsive wp-post-image  lazy-hidden" 
                alt="' . esc_html__('image', 'wprentals') . '" />';
        }
        ?>

        <div class="listing-unit-img-wrapper">
            <a href="<?= esc_url($card_link); ?>"
               target="<?= esc_attr(wprentals_get_option('wp_estate_prop_page_new_tab', '')); ?>">
                <?= trim($thumb_prop); ?>
            </a>
        </div>
        <?php
        if ($featured == 1) { ?>
            <div class="featured_div"><?= esc_html__('featured', 'wprentals'); ?></div>
            <?php
        }
        echo wpestate_return_property_status($post->ID); ?>

        <div class="title-container">
            <?php
            if ($wpestate_listing_type == 1) {
                $price_per_guest_from_one = floatval(get_post_meta($post->ID, 'price_per_guest_from_one', true));

                if ($price_per_guest_from_one == 1) {
                    $price = floatval(get_post_meta($post->ID, 'extra_price_per_guest', true));
                } else {
                    $price = floatval(get_post_meta($post->ID, 'property_price', true));
                } ?>

                <div class="price_unit">
                    <?php
                    wpestate_show_price($post->ID, $wpestate_currency, $wpestate_where_currency, 0);
                    if ($price != 0) { ?>
                        <span class="pernight">
                            <?= wpestate_show_labels('per_night2', $rental_type, $booking_type) ?>
                        </span>
                        <?php
                    } ?>

                </div>
                <?php
            }

            if (wpestate_has_some_review($post->ID) !== 0) {
                echo wpestate_display_property_rating($post->ID);
            } else { ?>
                <div class=rating_placeholder></div>
                <?php
            }
            echo wprentals_card_owner_image($post->ID); ?>

            <div class="category_name">
                <?php
                include(locate_template('templates/property_card_templates/property_card_title.php'));
                ?>

                <div class="category_tagline map_icon">
                    <?php
                    if ($property_area != '') {
                        echo trim($property_area) . ', ';
                    }
                    echo trim($property_city);
                    ?>
                </div>

                <div class="category_tagline actions_icon">
                    <?php
                    if (current_user_is_admin()) {
                        $property_taxonomies = $property_categ . ' / ' . $property_action;
                    } elseif (current_user_is_timeshare() && ! check_has_cottage_category($post->ID)) {
                        $property_taxonomies = $property_action;
                    } else {
                        $property_taxonomies = $property_categ;
                    }

                    echo wp_kses_post($property_taxonomies);
                    ?>
                </div>
            </div>

            <div class="property_unit_action">
                <span class="icon-fav <?= esc_attr($favorite_class); ?>"
                      data-original-title="<?= esc_attr($fav_mes); ?>" data-postid="<?= $post->ID; ?>">
                    <i class="fas fa-heart"></i>
                </span>
            </div>
        </div>
        <?php
        if (isset($show_remove_fav) && $show_remove_fav == 1) { ?>
            <span class="icon-fav icon-fav-on-remove" data-postid="<?= $post->ID; ?>">
                <?= esc_html($fav_mes); ?>
            </span>
            <?php
        } ?>
    </div>
</div>
