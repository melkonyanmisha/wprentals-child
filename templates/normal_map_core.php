<?php

global $wpestate_options;
global $prop_selection;
global $property_list_type_status;
global $wpestate_full_page;
global $term;
global $taxonmy;
global $wpestate_book_from;
global $wpestate_book_to;
global $wpestate_listing_type;
global $wpestate_property_unit_slider;
global $schema_flag;
global $current_user;

$wpestate_listing_type = wprentals_get_option('wp_estate_listing_unit_type', '');
$wpestate_page_tax     = '';
if ($wpestate_options['content_class'] == "col-md-12") {
    $wpestate_full_page = 1;
}

$wpestate_property_unit_slider = esc_html(wprentals_get_option('wp_estate_prop_list_slider', ''));

$custom_categories = [];
$custom_groups     = [];

ob_start();

if (current_user_is_admin()) {
    while ($prop_selection->have_posts()): $prop_selection->the_post();
        $schema_flag = 1;
        include(locate_template('templates/property_unit.php'));
    endwhile;
} elseif (current_user_is_timeshare()) {
    $room_category_id                = get_room_category_id_by_slug();
    $cottage_category_id             = get_cottage_category_id_by_slug();
    $room_group_id                   = get_room_group_id_by_slug();
    $group_with_max_room_group_order = get_group_with_max_room_group_order();

    while ($prop_selection->have_posts()): $prop_selection->the_post();
        $custom_categories = get_the_terms(get_the_ID(), 'property_category');
        $custom_groups     = get_the_terms(get_the_ID(), 'property_action_category');

        if ( ! empty($custom_groups)) {
            foreach ($custom_groups as $current_group) {
                //Case when parent is Room Group. Show 1 listing from Group which has a max group order
                if ($current_group->parent === $room_group_id) {
                    if ( ! empty($group_with_max_room_group_order->slug) && $group_with_max_room_group_order->slug === $current_group->slug) {
                        $term_grouped_posts[$current_group->slug]['posts'][0] = get_post(); // Group posts by term slug
                    }
                }
            }
        } else {
            if ( ! empty($custom_categories)) {
                foreach ($custom_categories as $current_category) {
                    //Case for Cottages. Show ALL
                    if ($current_category->term_id === $cottage_category_id) {
                        $term_grouped_posts[$current_category->slug]['posts'][] = get_post(
                        ); // Group posts by term slug
                    }
                }
            }
        }
    endwhile;

    // Display grouped posts by "Groups" term. Taxonomy is property_action_category
    foreach ($term_grouped_posts as $term_id => $term_data) {
        if ( ! empty($term_data['posts'])) {
            foreach ($term_data['posts'] as $post) {
                setup_postdata($post);

                // Display or process the post content
                $schema_flag = 1;
                include(locate_template('templates/property_unit.php'));
            }
        }
    }
} else {
    //Get by categories
    $room_category_id    = get_room_category_id_by_slug();
    $cottage_category_id = get_cottage_category_id_by_slug();

    //During the booking process Guest users can only see images for 1 unit per category
    while ($prop_selection->have_posts()): $prop_selection->the_post();

        // Get the custom categories (terms) for the post
        $custom_categories = get_the_terms(get_the_ID(), 'property_category');

        if ( ! empty($custom_categories)) {
            foreach ($custom_categories as $current_category) {
                //Case when parent is Room Category. Show 1 listing per Group
                if ($current_category->parent === $room_category_id) {
                    $term_grouped_posts[$current_category->slug]['posts'][0] = get_post(); // Group posts by term slug
                } elseif ($current_category->term_id === $cottage_category_id) {
                    //Case for Cottages. Show ALL
                    $term_grouped_posts[$current_category->slug]['posts'][] = get_post(); // Group posts by term slug
                }
            }
        }
    endwhile;

    // Display grouped posts by "Categories" term. Taxonomy is property_category
    foreach ($term_grouped_posts as $term_id => $term_data) {
        if ( ! empty($term_data['posts'])) {
            foreach ($term_data['posts'] as $post) {
                setup_postdata($post);

                // Display or process the post content
                $schema_flag = 1;
                include(locate_template('templates/property_unit.php'));
            }
        }
    }
}


$templates = ob_get_contents();
ob_end_clean();
wp_reset_query();
wp_reset_postdata();
$schema_flag = 0;
global $post;
$page_template = '';
if (isset($post->ID)) {
    $page_template = get_post_meta($post->ID, '_wp_page_template', true);
}

?>

<div class="row content-fixed" itemscope itemtype="http://schema.org/ItemList">

    <?php
    include(locate_template('templates/breadcrumbs.php')); ?>
    <div class=" <?php
    print esc_attr($wpestate_options['content_class']); ?>  ">


        <?php
        if ( ! is_tax()) { ?>
            <?php
            while (have_posts()) : the_post(); ?>
                <?php
                if (esc_html(get_post_meta($post->ID, 'page_show_title', true)) == 'yes') { ?>
                    <?php
                    if (esc_html(get_post_meta($post->ID, 'page_show_title', true)) == 'yes') {
                        if ($page_template == 'advanced_search_results.php') {
                            ?>
                            <h1 class="entry-title title_list_prop"><?php
                                the_title();
                                print ': ' . esc_html($prop_selection->found_posts) . ' ' . esc_html__(
                                        'results',
                                        'wprentals'
                                    ); ?></h1>
                            <?php
                        } else { ?>
                            <h1 class="entry-title title_list_prop"><?php
                                the_title(); ?></h1>
                            <?php
                        }
                    }
                    ?>
                    <?php
                } ?>
                <div class="single-content"><?php
                    the_content(); ?></div>
            <?php
            endwhile; ?>
            <?php
        } else { ?>

            <?php
            $term_data = get_term_by('slug', $term, $taxonmy);
            $place_id  = $term_data->term_id;
            $term_meta = get_option("taxonomy_$place_id");


            if (isset($term_meta['pagetax'])) {
                $wpestate_page_tax = $term_meta['pagetax'];
            }

            if ($wpestate_page_tax != '') {
                $content_post = get_post($wpestate_page_tax);
                if (isset($content_post->post_content)) {
                    $content = $content_post->post_content;
                    $content = apply_filters('the_content', $content);
                    print trim($content);
                }
            }
            ?>

            <h1 class="entry-title title_prop">
                <?php
                esc_html_e('Listings in ', 'wprentals');
                single_cat_title();
                ?>
            </h1>
            <?php
        } ?>

        <?php
        if ($property_list_type_status == 2) {
            include(locate_template('templates/advanced_search_map_list.php'));
        }
        ?>

        <!--Filters starts here-->
        <?php
        include(locate_template('templates/property_list_filters.php')); ?>
        <!--Filters Ends here-->

        <?php
        include(locate_template('templates/compare_list.php'));
        ?>

        <!-- Listings starts here -->
        <?php
        include(locate_template('templates/spiner.php')); ?>
        <div id="listing_ajax_container" class="row">
            <?php
            print trim($templates);
            ?>
        </div>
        <!-- Listings Ends  here -->

        <?php
        if ($prop_selection->have_posts()):
            wprentals_pagination($prop_selection->max_num_pages, $range = 2);
        endif;
        ?>

    </div><!-- end 8col container-->

    <?php
    include(get_theme_file_path('sidebar.php')); ?>
</div>
