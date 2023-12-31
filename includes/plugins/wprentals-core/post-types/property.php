<?php

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

/**
 * @return bool
 */
function custom_wpestate_create_property_type(): bool
{
    if ( ! is_plugin_active('wprentals-core/wprentals-core.php')) {
        return false;
    }

    $rewrites        = wpestate_safe_rewite();
    $slug_properties = $rewrites[0] ?? 'properties';
    $slug_listings   = $rewrites[1] ?? 'listings';
    $slug_action     = $rewrites[2] ?? 'action';
    $slug_features   = $rewrites[5] ?? 'features';

    register_post_type('estate_property', [
            'labels'               => [
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
            ],
            'public'               => true,
            'has_archive'          => true,
            'rewrite'              => ['slug' => $slug_properties],
            'supports'             => ['title', 'editor', 'thumbnail', 'comments', 'excerpt'],
            'can_export'           => true,
            'register_meta_box_cb' => 'wpestate_add_property_metaboxes',
            'menu_icon'            => WPESTATE_PLUGIN_DIR_URL . '/img/properties.png'
        ]
    );

////////////////////////////////////////////////////////////////////////////////////////////////
// Add custom taxonomies
////////////////////////////////////////////////////////////////////////////////////////////////
    $category_main_label   = stripslashes(esc_html(wprentals_get_option('wp_estate_category_main', '')));
    $category_second_label = stripslashes(esc_html(wprentals_get_option('wp_estate_category_second', '')));
    $name_label            = esc_html__('Categories', 'wprentals-core');
    $add_new_item_label    = esc_html__('Add New Listing Category', 'wprentals-core');
    $new_item_name_label   = esc_html__('New Listing Category', 'wprentals-core');

    if ($category_main_label != '') {
        $name_label          = $category_main_label;
        $add_new_item_label  = esc_html__('Add New', 'wprentals-core') . ' ' . $category_main_label;
        $new_item_name_label = esc_html__('New', 'wprentals-core') . ' ' . $category_main_label;
    }

    register_taxonomy('property_category', 'estate_property', [
            'labels'       => [
                'name'          => $name_label,
                'add_new_item'  => $add_new_item_label,
                'new_item_name' => $new_item_name_label
            ],
            'hierarchical' => true,
            'query_var'    => true,
            'rewrite'      => ['slug' => $slug_listings]
        ]
    );

    $action_name          = esc_html__('Groups', 'wprentals-core');
    $action_add_new_item  = esc_html__('Add New Listing Group ', 'wprentals-core');
    $action_new_item_name = esc_html__('Add New Listing Group', 'wprentals-core');

    if ($category_second_label != '') {
        $action_name          = $category_second_label;
        $action_add_new_item  = esc_html__('Add New', 'wprentals-core') . ' ' . $category_second_label;
        $action_new_item_name = esc_html__('New', 'wprentals-core') . ' ' . $category_second_label;
    }

    $taxonomy = 'property_action_category';
    // add custom taxonomy
    register_taxonomy($taxonomy, 'estate_property', [
            'labels'       => [
                'name'          => $action_name,
                'add_new_item'  => $action_add_new_item,
                'new_item_name' => $action_new_item_name
            ],
            'hierarchical' => true,
            'query_var'    => true,
            'rewrite'      => ['slug' => $slug_action]
        ]
    );

    // Add a custom meta input field for the taxonomy term
    add_action($taxonomy . '_add_form_fields', 'add_taxonomy_term_meta_field', 10, 2);
    add_action($taxonomy . '_edit_form_fields', 'edit_taxonomy_term_meta_field', 10, 2);

    // Save the custom meta field value when the term is saved
    add_action('edited_' . $taxonomy, 'save_taxonomy_term_meta');
    add_action('create_' . $taxonomy, 'save_taxonomy_term_meta');

    // Add a custom taxonomy
    register_taxonomy('property_features', 'estate_property', [
            'labels'       => [
                'name'          => esc_html__('Features & Amenities', 'wprentals-core'),
                'add_new_item'  => esc_html__('Add New Feature', 'wprentals-core'),
                'new_item_name' => esc_html__('New Feature', 'wprentals-core')
            ],
            'hierarchical' => true,
            'query_var'    => true,
            'rewrite'      => ['slug' => $slug_features]
        ]
    );

    wprentals_convert_features_status_to_tax();

    return true;
}

/**
 * @param string $taxonomy
 *
 * @return void
 */
function add_taxonomy_term_meta_field(string $taxonomy)
{
    ?>
    <div class="form-field">
        <label for="current-group-order">Group order</label>
        <input style="width: 20%;" type="number" name="<?= ROOM_GROUP_ORDER; ?>" id="current-group-order">
        <p class="description">It will be used when booking from a timeshare user.</p>
    </div>
    <?php
}

/**
 * @param WP_Term $term
 * @param string $taxonomy
 *
 * @return void
 */
function edit_taxonomy_term_meta_field(WP_Term $term, string $taxonomy)
{
    $term_meta = get_term_meta($term->term_id, ROOM_GROUP_ORDER, true);
    ?>
    <table class="form-table">
        <tr class="form-field">
            <th scope="row">
                <label for="current-group-order">Group order</label>
            </th>
            <td>
                <input style="width: 20%;" class="postform" type="number" name=<?= ROOM_GROUP_ORDER; ?>
                id="current-group-order" value="<?= esc_attr($term_meta); ?>">
                <p class="description">It will be used when booking from a timeshare user.</p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * @param int $term_id
 *
 * @return void
 */
function save_taxonomy_term_meta(int $term_id)
{
    if (isset($_POST[ROOM_GROUP_ORDER])) {
        update_term_meta($term_id, ROOM_GROUP_ORDER, sanitize_text_field($_POST[ROOM_GROUP_ORDER]));
    }
}