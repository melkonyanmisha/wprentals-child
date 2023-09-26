<?php
/**
 * @param $attributes
 * @param $content
 *
 * @return string
 */

function custom_wpestate_recent_posts_pictures($attributes, $content = null)
{
    global $wpestate_options;
    global $align;
    global $align_class;
    global $post;
    global $wpestate_currency;
    global $wpestate_where_currency;
    global $is_shortcode;
    global $wpestate_show_compare_only;
    global $wpestate_row_number_col;
    global $row_number;
    global $wpestate_curent_fav;
    global $current_user;
    global $wpestate_listing_type;
    global $wpestate_property_unit_slider;


    $wpestate_property_unit_slider = esc_html(wprentals_get_option('wp_estate_prop_list_slider', ''));
    $wpestate_listing_type         = wprentals_select_unit_cards($attributes);
    $current_user                  = wp_get_current_user();
    $userID                        = $current_user->ID;
    $user_option                   = 'favorites' . $userID;
    $wpestate_curent_fav           = get_option($user_option);
    $wpestate_options              = wpestate_page_details($post->ID);
    $return_string                 = '';
    $pictures                      = '';
    $button                        = '';
    $class                         = '';
    $category                      = $action = $city = $area = '';
    $title                         = '';
    $wpestate_currency             = esc_html(wprentals_get_option('wp_estate_currency_label_main', ''));
    $wpestate_where_currency       = esc_html(wprentals_get_option('wp_estate_where_currency_symbol', ''));
    $is_shortcode                  = 1;
    $wpestate_show_compare_only    = 'no';
    $wpestate_row_number_col       = '';
    $row_number                    = '';
    $show_featured_only            = '';
    $wpestate_full_row             = '';
    $extra_class_name              = '';
    $random_pick                   = '';
    $orderby                       = 'meta_value';

    if (isset($attributes['title'])) {
        $title = $attributes['title'];
    }

    $attributes = shortcode_atts(
        array(
            'full_row'           => 'yes',
            'blogtype'           => 2,
            'title'              => '',
            'type'               => 'properties',
            'category_ids'       => '',
            'action_ids'         => '',
            'city_ids'           => '',
            'area_ids'           => '',
            'number'             => 4,
            'rownumber'          => 4,
            'align'              => 'vertical',
            'link'               => '',
            'show_featured_only' => 'no',
            'random_pick'        => 'no',
            'extra_class_name'   => '',
            'display_grid'       => 'no'
        ),
        $attributes
    );

    if (isset($attributes['category_ids'])) {
        $category = $attributes['category_ids'];
    }

    if (isset($attributes['action_ids'])) {
        $action = $attributes['action_ids'];
    }

    if (isset($attributes['city_ids'])) {
        $city = $attributes['city_ids'];
    }

    if (isset($attributes['area_ids'])) {
        $area = $attributes['area_ids'];
    }

    if (isset($attributes['show_featured_only'])) {
        $show_featured_only = $attributes['show_featured_only'];
    }

    if (isset($attributes['full_row'])) {
        $full_row = $attributes['full_row'];
    }
    $blogtype = 2;
    if (isset($attributes['blogtype'])) {
        $blogtype = $attributes['blogtype'];
    }
    if (isset($attributes['display_grid'])) {
        $display_grid = $attributes['display_grid'];
    }

    if (isset($attributes['random_pick'])) {
        $random_pick = $attributes['random_pick'];
        if ($random_pick === 'yes') {
            $orderby = 'rand';
        }
    }

    if (isset($attributes['extra_class_name'])) {
        $extra_class_name = $attributes['extra_class_name'];
    }


    $post_number_total = $attributes['number'];
    if (isset($attributes['rownumber'])) {
        $row_number = $attributes['rownumber'];
    }

    // max 4 per row
    if ($row_number > 6) {
        $row_number = 6;
    }

    if ($row_number == 4 || $row_number == 5) {
        $wpestate_row_number_col = 3; // col value is 3
    } elseif ($row_number == 3) {
        $wpestate_row_number_col = 4; // col value is 4
    } elseif ($row_number == 2) {
        $wpestate_row_number_col = 6;// col value is 6
    } elseif ($row_number == 6) {
        $wpestate_row_number_col = 2;// col value is 12
    } elseif ($row_number == 1) {
        $wpestate_row_number_col = 12;// col value is 12
    }

    $align       = '';
    $align_class = '';
    if (isset($attributes['align']) && $attributes['align'] == 'horizontal') {
        $align                   = "col-md-12";
        $align_class             = 'the_list_view';
        $wpestate_row_number_col = '12';
    }

    if ($attributes['type'] == 'properties') {
        $type = 'estate_property';

        $category_array = '';
        $action_array   = '';
        $city_array     = '';
        $area_array     = '';

        // build category array
        if ($category != '') {
            $category_of_tax = array();
            $category_of_tax = explode(',', $category);
            $category_array  = array(
                'taxonomy' => 'property_category',
                'field'    => 'term_id',
                'terms'    => $category_of_tax
            );
        }

        // build action array
        if ($action != '') {
            $action_of_tax = array();
            $action_of_tax = explode(',', $action);
            $action_array  = array(
                'taxonomy' => 'property_action_category',
                'field'    => 'term_id',
                'terms'    => $action_of_tax
            );
        }

        // build city array
        if ($city != '') {
            $city_of_tax = array();
            $city_of_tax = explode(',', $city);
            $city_array  = array(
                'taxonomy' => 'property_city',
                'field'    => 'term_id',
                'terms'    => $city_of_tax
            );
        }

        // build city array
        if ($area != '') {
            $area_of_tax = array();
            $area_of_tax = explode(',', $area);
            $area_array  = array(
                'taxonomy' => 'property_area',
                'field'    => 'term_id',
                'terms'    => $area_of_tax
            );
        }

        $meta_query = array();
        if ($show_featured_only == 'yes') {
            $compare_array            = array();
            $compare_array['key']     = 'prop_featured';
            $compare_array['value']   = 1;
            $compare_array['type']    = 'numeric';
            $compare_array['compare'] = '=';
            $meta_query[]             = $compare_array;
        }

        $args = array(
            'post_type'      => $type,
            'post_status'    => 'publish',
            'paged'          => 0,
            'posts_per_page' => $post_number_total,
            'meta_key'       => 'prop_featured',
            'orderby'        => $orderby,
            'order'          => 'DESC',
            'meta_query'     => $meta_query,
            'tax_query'      => array(
                $category_array,
                $action_array,
                $city_array,
                $area_array
            )

        );
    } else {
        $type = 'post';
        $args = array(
            'post_type'      => 'post',
            'status'         => 'published',
            'paged'          => 0,
            'posts_per_page' => $post_number_total,
            'cat'            => $category
        );
    }

    if (isset($attributes['link']) && $attributes['link'] != '') {
        if ($attributes['type'] == 'properties') {
            $button .= '<div class="listinglink-wrapper">
               <a href="' . $attributes['link'] . '"> <span class="wpb_btn-info wpb_btn-small wpestate_vc_button  vc_button more_list">' . esc_html__(
                    'More Listings',
                    'wprentals-core'
                ) . ' </span></a>
               </div>';
        } else {
            $button .= '<div class="listinglink-wrapper">
               <a href="' . $attributes['link'] . '"> <span class="wpb_btn-info wpb_btn-small wpestate_vc_button  vc_button more_list">  ' . esc_html__(
                    'More Articles',
                    'wprentals-core'
                ) . ' </span></a>
               </div>';
        }
    } else {
        $class = "nobutton";
    }

    $transient_name = 'wpestate_recent_posts_pictures_query_' . $type . '_' . $category . '_' . $action . '_' . $city . '_' . $area . '_' . $post_number_total . '_' . $show_featured_only . '_' . $random_pick;
    $transient_name = wpestate_add_language_currency_cache($transient_name);

    $recent_posts = false;
    if (function_exists('wpestate_request_transient_cache')) {
        $recent_posts = wpestate_request_transient_cache($transient_name);
    }

    if ($recent_posts === false) {
        if ($attributes['type'] == 'properties') {
            if ($random_pick !== 'yes') {
                add_filter('posts_orderby', 'wpestate_my_order');
                $recent_posts = new WP_Query($args);
                $count        = 1;
                remove_filter('posts_orderby', 'wpestate_my_order');
            } else {
                $recent_posts = new WP_Query($args);
                $count        = 1;
            }
        } else {
            $recent_posts = new WP_Query($args);
            $count        = 1;
        }
        if (function_exists('wpestate_set_transient_cache')) {
            wpestate_set_transient_cache($transient_name, $recent_posts, 60 * 60 * 4);
        }
    }

    if ($full_row === 'yes') {
        $return_string .= '<div class="  ' . $extra_class_name . ' " >';
    } else {
        $return_string .= '<div class=" bottom-' . $type . ' ' . $class . ' ' . $extra_class_name . '" >';
        if ($title != '') {
            $return_string .= '<h2 class="shortcode_title">' . $title . '</h2>';
        }
    }

    ob_start();

    if ($display_grid == 'yes') {
        print '<div class="items_shortcode_wrapper_grid"> ';
        $row_number              = 'x';
        $rownumber               = 'x';
        $wpestate_row_number_col = 'x';
    } else {
        print '<div class="items_shortcode_wrapper';
        if ($full_row === 'yes') {
            print ' items_shortcode_wrapper_full ';
        }
        print'  ">';
    }

    $unit_counter = 1;
    $path         = wprentals_blog_card_picker($blogtype);

    // todo@@@@ custom case to show sections
    if (need_to_show_recent_items($recent_posts)) {
        while ($recent_posts->have_posts()): $recent_posts->the_post();

            if ($display_grid == 'yes') {
                print '<div class="shortcode_wrapper_grid_item shortcode_wrapper_grid_item-' . intval(
                        $unit_counter
                    ) . '">';
                $unit_counter++;
            }

            if ($type == 'estate_property') {
                if ($full_row === 'yes') {
                    get_template_part('templates/property_unit_full_row');
                } else {
                    get_template_part('templates/property_unit');
                }
            } else {
                if ($full_row === 'yes') {
                    get_template_part('templates/blog-unit/blog_unit_full_row');
                } else {
                    include(locate_template($path));
                }
            }

            if ($display_grid == 'yes') {
                print '  </div>';
            }

        endwhile;
    }

    print '</div>';
    $templates = ob_get_contents();
    ob_end_clean();
    $return_string .= $templates;
    if ($full_row != 'yes') {
        $return_string .= $button;
    }

    $return_string .= '</div>';
    wp_reset_query();
    $is_shortcode = 0;

    return $return_string;
}

//Remove shortcode from parent theme
remove_shortcode('recent_items');
//Add custom shortcode
add_shortcode('recent_items', 'custom_wpestate_recent_posts_pictures');


/**
 * The decision to display a section depends on user role and type of section.
 *
 * @param object $recent_posts
 *
 * @return bool
 */
function need_to_show_recent_items(object $recent_posts):bool
{
    foreach ($recent_posts->query['tax_query'] as $current_tex_query) {
        //Show all sections if user is Administrator
        if (current_user_is_admin()) {
            return true;
        }

        //Case for categories
        if ( ! empty($current_tex_query['taxonomy']) && $current_tex_query['taxonomy'] === 'property_category') {
            //Case for Cottage category
            if (in_array(get_cottage_category_id_by_slug(), $current_tex_query['terms'])) {
                return true;
            } else {
                if (current_user_is_timeshare()) {
                    //Case for all another Categories.
                    return false;
                }

                return true;
            }
        } elseif ( //Case for Groups(will work with any group)
            ! empty($current_tex_query['taxonomy']) && $current_tex_query['taxonomy'] === 'property_action_category'
        ) {
            if (current_user_is_timeshare()) {
                return true;
            }

            return false;
        }
    }

    return false;
}