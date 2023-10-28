<?php

global $post;
global $wpestate_options;
global $prop_selection;
global $property_list_type_status;
global $term;
global $taxonmy;


ob_start();

while ($prop_selection->have_posts()): $prop_selection->the_post();
    include(locate_template('templates/property_unit.php'));
endwhile;

$templates = ob_get_contents();
ob_end_clean();
wp_reset_query();
wp_reset_postdata();

$page_template = '';
if (isset($post->ID)) {
    $page_template = get_post_meta($post->ID, '_wp_page_template', true);
}

?>

<div class="row content-fixed" itemscope itemtype="https://schema.org/ItemList">
    <?php
    include(locate_template('templates/breadcrumbs.php'));
    ?>
    <div class="<?= esc_attr($wpestate_options['content_class']); ?>">
        <?php
        if ( ! is_tax()) {
            while (have_posts()) : the_post();
                if (esc_html(get_post_meta($post->ID, 'page_show_title', true)) == 'yes') {
                    if (esc_html(get_post_meta($post->ID, 'page_show_title', true)) == 'yes') {
                        if ($page_template == 'advanced_search_results.php') {
                            ?>
                            <h1 class="entry-title title_list_prop">
                                <?php
                                the_title();
                                echo ': ' . esc_html($prop_selection->found_posts) . ' ' . esc_html__(
                                        'results',
                                        'wprentals'
                                    );
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
            $term_data         = get_term_by('slug', $term, $taxonmy);
            $place_id          = $term_data->term_id;
            $term_meta         = get_option("taxonomy_$place_id");
            $wpestate_page_tax = '';

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

        include(locate_template('templates/compare_list.php'));

        //        <!-- Listings starts here -->
        include(locate_template('templates/spiner.php')); ?>
        <div id="listing_ajax_container" class="row">
            <?= trim($templates); ?>
        </div>
        <!-- Listings Ends  here -->

        <?php
        if ($prop_selection->have_posts()):
            wprentals_pagination($prop_selection->max_num_pages, $range = 2);
        endif;
        ?>

    </div>

    <?php
    include(get_theme_file_path('sidebar.php'));
    ?>
</div>
