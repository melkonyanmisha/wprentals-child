<?php
/**
 * @var int $paged
 */

// Template Name: Advanced Search Results
// Wp Estate Pack

get_header();

global $prop_selection;
global $property_list_type_status;
global $wpestate_full_page;
global $term;
global $taxonmy;
global $args;
global $post;

$wpestate_options                = wpestate_page_details($post->ID);
$prop_no                         = intval(wprentals_get_option('wp_estate_prop_no'));
$compute                         = wpestate_argumets_builder($_REQUEST);
$prop_selection                  = $compute[0];
$args                            = $compute[1];
$wpestate_page_tax               = '';
$custom_categories               = [];
$custom_groups                   = [];
$posts_count                     = 0;
$cottage_category_id             = get_cottage_category_id_by_slug();
$term_grouped_posts              = [];
$room_group_id                   = get_room_group_id_by_slug();
$group_with_max_room_group_order = get_group_with_max_room_group_order();

if ($wpestate_options['content_class'] == "col-md-12") {
    $wpestate_full_page = 1;
}

while ($prop_selection->have_posts()): $prop_selection->the_post();
    $current_listing   = get_post();
    $custom_groups     = get_the_terms(get_the_ID(), 'property_action_category');
    $custom_categories = get_the_terms(get_the_ID(), 'property_category');

    if ( ! empty($custom_groups)) {
        foreach ($custom_groups as $current_group) {
            //Case when parent is Room Group. Show 1 listing from Group which has a max group order
            if ($current_group->parent === $room_group_id) {
                if ( ! empty($group_with_max_room_group_order->slug) && $group_with_max_room_group_order->slug === $current_group->slug) {
                    if (current_user_is_admin() || current_user_is_timeshare()) {
                        if (check_listing_is_featured($current_listing->ID)) {
                            // Group posts by term slug
                            $current_listing->term_type                          = 'room-group';
                            $term_grouped_posts[$current_group->slug]['name']    = $current_group->name;
                            $term_grouped_posts[$current_group->slug]['posts'][] = $current_listing;
                        }
                    }
                }
            }
        }
    }

    if ( ! empty($custom_categories)) {
        foreach ($custom_categories as $current_category) {
            // The case when listing is not a Cottage.
            if ($current_category->term_id !== $cottage_category_id) {
                // The room category shouldn't be displayed to timeshare users
                if (current_user_is_timeshare()) {
                    break;
                }

                // The case when the room is not main in the current category
                if ( ! in_array($current_listing->ID, get_listings_ids_from_last_room_group())) {
                    continue;
                }

                $current_listing->term_type = 'room-category';
            } else {
                $current_listing->term_type = 'cottage';
            }
            $term_grouped_posts[$current_category->slug]['name']    = $current_category->name;
            $term_grouped_posts[$current_category->slug]['posts'][] = $current_listing;
        }
    }
endwhile;
// Start output buffering
ob_start();

if ( ! empty($term_grouped_posts)) {
    ksort($term_grouped_posts);

    // Display grouped posts by "Groups" term. Taxonomy is property_action_category
    foreach ($term_grouped_posts as $term_slug => $term_data) {
        if ( ! empty($term_data['posts'])) { ?>
            <div class="col-xs-12 search-result-section">
                <h2 class="search-result-title"><?= $term_data['name'] ?></h2>
                <?php
                foreach ($term_data['posts'] as $post) {
                    $posts_count++;
                    setup_postdata($post);

                    // Display or process the post content
                    include(locate_template('templates/property_unit.php'));
                }
                ?>
            </div>
            <?php
        }
    }
}

$templates = ob_get_contents();
ob_end_clean();
wp_reset_query();
wp_reset_postdata();

$page_template = '';
if (isset($post->ID)) {
    $page_template = get_post_meta($post->ID, '_wp_page_template', true);
}

// Start output buffering
ob_start();
?>

    <div class="row content-fixed" itemscope itemtype="https://schema.org/ItemList">
        <?php
        include(locate_template('templates/breadcrumbs.php')); ?>
        <div class="<?= esc_attr($wpestate_options['content_class']); ?>">
            <?php
            if ( ! is_tax()) {
                while (have_posts()) : the_post();
                    if (esc_html(get_post_meta($post->ID, 'page_show_title', true)) == 'yes') { ?>
                        <?php
                        if (esc_html(get_post_meta($post->ID, 'page_show_title', true)) == 'yes') {
                            if ($page_template == 'advanced_search_results.php') {
                                ?>
                                <h1 class="entry-title title_list_prop">
                                    <?php
                                    the_title();
                                    echo ': ' . $posts_count
                                         . ' ' . esc_html__('results', 'wprentals');
                                    ?>
                                </h1>
                                <?php
                            } else { ?>
                                <h1 class="entry-title title_list_prop">
                                    <?= get_the_title(); ?>
                                </h1>
                                <?php
                            }
                        }
                    } ?>
                    <div class="single-content">
                        <?= get_the_content(); ?>
                    </div>
                <?php
                endwhile;
            } else {
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
                        echo trim($content);
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
            }

            if ($property_list_type_status == 2) {
                include(locate_template('templates/advanced_search_map_list.php'));
            }

            // Filters starts here
            // include(locate_template('templates/property_list_filters.php'));

            // Filters Ends here-->
            include(locate_template('templates/compare_list.php'));
            // Listings starts here -->
            include(locate_template('templates/spiner.php'));
            ?>

            <div id="listing_ajax_container" class="row">
                <?= trim($templates); ?>
            </div>
            <!-- Listings Ends  here -->

            <?php
            if ($prop_selection->have_posts()) {
                wprentals_pagination($prop_selection->max_num_pages, $range = 2);
            }
            ?>
        </div>
        <?php
        include(get_theme_file_path('sidebar.php'));
        ?>
    </div>

<?php
// End output buffering
echo ob_get_clean();

if (wp_script_is('wpestate_googlecode_regular', 'enqueued')) {
    $mapargs                   = $args;
    $max_pins                  = intval(wprentals_get_option('wp_estate_map_max_pins'));
    $mapargs['posts_per_page'] = $max_pins;
    $mapargs['offset']         = ($paged - 1) * $prop_no;

    $args['fields'] = 'ids';
    $selected_pins  = wpestate_listing_pins('blank', 0, $args, 1, 1);//call the new pins

    wp_localize_script(
        'wpestate_googlecode_regular', 'googlecode_regular_vars2',
        [
            'markers2' => $selected_pins,
        ]
    );
}
get_footer();