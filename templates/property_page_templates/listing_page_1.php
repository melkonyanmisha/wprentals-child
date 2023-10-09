<?php

global $post;
global $current_user;
global $feature_list_array;
global $wpestate_propid;
global $post_attachments;
global $wpestate_options;
global $wpestate_where_currency;
global $wpestate_property_description_text;
global $wpestate_property_details_text;
global $wpestate_property_details_text;
global $wpestate_property_adr_text;
global $wpestate_property_price_text;
global $wpestate_property_pictures_text;
global $wp_estate_sleeping_text;
global $wpestate_propid;
global $wpestate_gmap_lat;
global $wpestate_gmap_long;
global $wpestate_unit;
global $wpestate_currency;
global $wpestate_use_floor_plans;
global $favorite_text;
global $favorite_class;
global $property_action_terms_icon;
global $property_action;
global $property_category_terms_icon;
global $property_category;
global $guests;
global $bedrooms;
global $bathrooms;
global $show_sim_two;
global $guest_list;
global $post_id;

$price         = intval(get_post_meta($post->ID, 'property_price', true));
$price_label   = esc_html(get_post_meta($post->ID, 'property_label', true));
$property_city = get_the_term_list($post->ID, 'property_city', '', ', ', '');
$property_area = get_the_term_list($post->ID, 'property_area', '', ', ', '');
$post_id       = $post->ID;
$guest_no_prop = '';
if (isset($_GET['guest_no_prop'])) {
    $guest_no_prop = intval($_GET['guest_no_prop']);
}
$guest_list = wpestate_get_guest_dropdown('noany');

$main_section_class = $wpestate_options['content_class'] == 'col-md-12' || $wpestate_options['content_class'] == 'none' ? 'col-md-8' : esc_attr(
    $wpestate_options['content_class']
);

if (is_singular('estate_property') && "yes" == wprentals_get_option('wp_estate_property_sidebar_sitcky')) {
    $wpestate_options['sidebar_class'] = $wpestate_options['sidebar_class'] . ' wpestate_sidebar_sticky ';
}

if ($wpestate_options['sidebar_class'] == '' || $wpestate_options['sidebar_class'] == 'none') {
    $sidebar_area_class = 'col-md-4';
} else {
    $sidebar_area_class = esc_attr($wpestate_options['sidebar_class']);
}

?>

    <div itemscope itemtype="https://schema.org/RentAction" class="row content-fixed-listing listing_type_1">
        <div class="<?= $main_section_class; ?>">
            <?php
            include(locate_template('templates/ajax_container.php'));

            while (have_posts()) : the_post();
                $image_id = intval(get_post_thumbnail_id());

                if ($image_id != 0) {
                    $image_url = wp_get_attachment_image_src($image_id, 'wpestate_property_full_map');
                    $full_img  = wp_get_attachment_image_src($image_id, 'full');
                    $image_url = $image_url[0];
                    $full_img  = $full_img [0];
                } else {
                    $image_url = '';
                    $full_img  = '';
                }
                ?>

                <div class="single-content listing-content">
                    <div class="booking_form_mobile">
                        <?php
                        if (wp_is_mobile()) {
                            include(locate_template('templates/booking_form_template.php'));
                        }
                        ?>
                    </div>

                    <?php
                    include(locate_template(
                        'templates/property_page_templates/property_page_templates_section/title_section_type_v1.php'
                    ));
                    include(locate_template(
                        'templates/property_page_templates/property_page_templates_section/property_media_section.php'
                    ));
                    ?>

                    <div class="category_wrapper ">
                        <div class="category_details_wrapper">
                            <?php
                            if (wprentals_get_option('wp_estate_use_custom_icon_area') == 'yes') {
                                wprentals_icon_bar_design();
                            } else {
                                $rental_type = wprentals_get_option('wp_estate_item_rental_type');
                                wprentals_icon_bar_classic(
                                    $property_action,
                                    $property_category,
                                    $rental_type,
                                    $guests,
                                    $bedrooms,
                                    $bathrooms
                                );
                            }
                            ?>
                        </div>
                    </div>
                </div><!-- end listing-content container-->

            <?php
            endwhile; // end of the loop

            $show_compare = 1;
            $show_sim_two = 1;
            wprentals_property_content_layout($post->ID);
            $wprentals_hide_similar_listing = wprentals_get_option('wprentals_hide_similar_listing');
            if ($wprentals_hide_similar_listing == 'no') {
                include(locate_template('/templates/similar_listings.php'));
            }
            ?>

        </div><!-- end main section-->

        <div class="clearfix visible-xs"></div>
        <div class="<?= $sidebar_area_class; ?> widget-area-sidebar listingsidebar2 listing_type_1" id="primary">
            <?php
            if ( ! wp_is_mobile()) {
                include(locate_template('templates/booking_form_template.php'));
            }

            $wprentals_hide_default_owner = wprentals_get_option('wprentals_hide_default_owner');
            if ($wprentals_hide_default_owner == 'no') { ?>
                <div class="owner_area_wrapper_sidebar" id="listing_owner">
                    <?php
                    include(locate_template('/templates/owner_area.php'));
                    ?>
                </div>
                <?php
            }
            include(get_theme_file_path('sidebar-listing.php'));
            ?>
        </div>
    </div>

<?php
get_footer();