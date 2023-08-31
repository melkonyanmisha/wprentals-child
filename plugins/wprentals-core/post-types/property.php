<?php

function custom_wpestate_create_property_type()
{
    $rewrites = wpestate_safe_rewite();
    if (isset($rewrites[0])) {
        $slug = $rewrites[0];
    } else {
        $slug = 'properties';
    }
    register_post_type('estate_property', array(
            'labels'               => array(
                'name'               => esc_html__('Listings', 'wprentals-core'),
                'singular_name'      => esc_html__('Listing', 'wprentals-core'),
                'add_new'            => esc_html__('Add New Listing', 'wprentals-core'),
                'add_new_item'       => esc_html__('Add Listing', 'wprentals-core'),
                'edit'               => esc_html__('Edit', 'wprentals-core'),
                'edit_item'          => esc_html__('Edit Listings', 'wprentals-core'),
                'new_item'           => esc_html__('New Listing', 'wprentals-core'),
                'view'               => esc_html__('View', 'wprentals-core'),
                'view_item'          => esc_html__('View Listings', 'wprentals-core'),
                'search_items'       => esc_html__('Search Listings', 'wprentals-core'),
                'not_found'          => esc_html__('No Listings found', 'wprentals-core'),
                'not_found_in_trash' => esc_html__('No Listings found in Trash', 'wprentals-core'),
                'parent'             => esc_html__('Parent Listings', 'wprentals-core')
            ),
            'public'               => true,
            'has_archive'          => true,
            'rewrite'              => array('slug' => $slug),
            'supports'             => array('title', 'editor', 'thumbnail', 'comments', 'excerpt'),
            'can_export'           => true,
            'register_meta_box_cb' => 'wpestate_add_property_metaboxes',
            'menu_icon'            => WPESTATE_PLUGIN_DIR_URL . '/img/properties.png'
        )
    );

////////////////////////////////////////////////////////////////////////////////////////////////
// Add custom taxonomies
////////////////////////////////////////////////////////////////////////////////////////////////
    $category_main_label   = stripslashes(esc_html(wprentals_get_option('wp_estate_category_main', '')));
    $category_second_label = stripslashes(esc_html(wprentals_get_option('wp_estate_category_second', '')));

    $name_label          = esc_html__('Categories', 'wprentals-core');
    $add_new_item_label  = esc_html__('Add New Listing Category', 'wprentals-core');
    $new_item_name_label = esc_html__('New Listing Category', 'wprentals-core');

    if ($category_main_label != '') {
        $name_label          = $category_main_label;
        $add_new_item_label  = esc_html__('Add New', 'wprentals-core') . ' ' . $category_main_label;
        $new_item_name_label = esc_html__('New', 'wprentals-core') . ' ' . $category_main_label;
    }

    $rewrites = wpestate_safe_rewite();
    if (isset($rewrites[1])) {
        $slug = $rewrites[1];
    } else {
        $slug = 'listings';
    }

    register_taxonomy('property_category', 'estate_property', array(
            'labels'       => array(
                'name'          => $name_label,
                'add_new_item'  => $add_new_item_label,
                'new_item_name' => $new_item_name_label
            ),
            'hierarchical' => true,
            'query_var'    => true,
            'rewrite'      => array('slug' => $slug)
        )
    );

    //todo@@@ start customized
    $action_name          = esc_html__('Groups', 'wprentals-core');
    $action_add_new_item  = esc_html__('Add New Listing Group ', 'wprentals-core');
    $action_new_item_name = esc_html__('Add New Listing Group', 'wprentals-core');
    //todo@@@ end

    if ($category_second_label != '') {
        $action_name          = $category_second_label;
        $action_add_new_item  = esc_html__('Add New', 'wprentals-core') . ' ' . $category_second_label;
        $action_new_item_name = esc_html__('New', 'wprentals-core') . ' ' . $category_second_label;
    }

    $rewrites = wpestate_safe_rewite();
    if (isset($rewrites[2])) {
        $slug = $rewrites[2];
    } else {
        $slug = 'action';
    }

    // add custom taxonomy
    register_taxonomy('property_action_category', 'estate_property', array(
            'labels'       => array(
                'name'          => $action_name,
                'add_new_item'  => $action_add_new_item,
                'new_item_name' => $action_new_item_name
            ),
            'hierarchical' => true,
            'query_var'    => true,
            'rewrite'      => array('slug' => $slug)
        )
    );

    $rewrites = wpestate_safe_rewite();
    if (isset($rewrites[3])) {
        $slug = $rewrites[3];
    } else {
        $slug = 'city';
    }

    // add custom taxonomy
    register_taxonomy('property_city', 'estate_property', array(
            'labels'       => array(
                'name'          => esc_html__('City', 'wprentals-core'),
                'add_new_item'  => esc_html__('Add New City', 'wprentals-core'),
                'new_item_name' => esc_html__('New City', 'wprentals-core')
            ),
            'hierarchical' => true,
            'query_var'    => true,
            'rewrite'      => array('slug' => $slug)
        )
    );

    $rewrites = wpestate_safe_rewite();
    if (isset($rewrites[4])) {
        $slug = $rewrites[4];
    } else {
        $slug = 'area';
    }

    // add custom taxonomy
    register_taxonomy('property_area', 'estate_property', array(
            'labels'       => array(
                'name'          => esc_html__('Neighborhood / Area', 'wprentals-core'),
                'add_new_item'  => esc_html__('Add New Neighborhood / Area', 'wprentals-core'),
                'new_item_name' => esc_html__('New Neighborhood / Area', 'wprentals-core')
            ),
            'hierarchical' => true,
            'query_var'    => true,
            'rewrite'      => array('slug' => $slug)

        )
    );

    $rewrites = wpestate_safe_rewite();
    if (isset($rewrites[5])) {
        $slug = $rewrites[5];
    } else {
        $slug = 'features';
    }

    // add custom taxonomy
    register_taxonomy('property_features', 'estate_property', array(
            'labels'       => array(
                'name'          => esc_html__('Features & Amenities', 'wprentals-core'),
                'add_new_item'  => esc_html__('Add New Feature', 'wprentals-core'),
                'new_item_name' => esc_html__('New Feature', 'wprentals-core')
            ),
            'hierarchical' => true,
            'query_var'    => true,
            'rewrite'      => array('slug' => $slug)

        )
    );

    $rewrites = wpestate_safe_rewite();
    if (isset($rewrites[6])) {
        $slug = $rewrites[6];
    } else {
        $slug = 'status';
    }

    // add custom taxonomy
    register_taxonomy('property_status', 'estate_property', array(
            'labels'       => array(
                'name'          => esc_html__('Property Status', 'wprentals-core'),
                'add_new_item'  => esc_html__('Add New Status', 'wprentals-core'),
                'new_item_name' => esc_html__('New Status', 'wprentals-core')
            ),
            'hierarchical' => true,
            'query_var'    => true,
            'rewrite'      => array('slug' => $slug)

        )
    );

    wprentals_convert_features_status_to_tax();
}

// todo@@
/**
 * Remove the original estate_property custom post type and add a new one
 * @return void
 */
function replace_wpestate_create_property_type(): void
{
    unregister_post_type('estate_property');
    custom_wpestate_create_property_type();
}

add_action('after_setup_theme', 'replace_wpestate_create_property_type');