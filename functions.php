<?php

if ( ! defined('WPRENTALS_THEME_URL')) {
    define('WPRENTALS_THEME_URL', trailingslashit(get_template_directory_uri()));
}

if ( ! defined('WPRENTALS_CHILD_THEME_PATH')) {
    define('WPRENTALS_CHILD_THEME_PATH', trailingslashit(get_stylesheet_directory()));
}
if ( ! defined('WPRENTALS_CHILD_THEME_URL')) {
    define('WPRENTALS_CHILD_THEME_URL', trailingslashit(get_stylesheet_directory_uri()));
}

require_once WPRENTALS_CHILD_THEME_PATH . 'plugins/wprentals-core/shortcodes/recent_items_list.php';
require_once WPRENTALS_CHILD_THEME_PATH . 'plugins/wprentals-core/post-types/property.php';
require_once WPRENTALS_CHILD_THEME_PATH . 'includes/libs/custom_help_functions.php';
require_once WPRENTALS_CHILD_THEME_PATH . 'custom_book_functions.php';


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}
/**
 * @return void
 */
function wprentals_child_enques(): void
{
    ####### CSS #######
    $parent_style = 'wpestate_style';
    wp_enqueue_style(
        'bootstrap',
        WPRENTALS_THEME_URL . '/css/bootstrap.css',
        [],
        wp_get_theme()->get('Version'),
        'all'
    );
    wp_enqueue_style(
        'bootstrap-theme',
        WPRENTALS_THEME_URL . '/css/bootstrap-theme.css',
        [],
        wp_get_theme()->get('Version'),
        'all'
    );
    wp_enqueue_style(
        $parent_style,
        WPRENTALS_THEME_URL . '/style.css',
        ['bootstrap', 'bootstrap-theme'],
        wp_get_theme()->get('Version')
    );
    wp_enqueue_style(
        'wpestate-child-main-style',
        WPRENTALS_CHILD_THEME_URL . '/style.css',
        [$parent_style],
        wp_get_theme()->get('Version')
    );
    wp_enqueue_style('wpestate-child-style', WPRENTALS_CHILD_THEME_URL . 'css/style.css');

    ####### JS #######
    wp_enqueue_script(
        'wprentals-child-main',
        WPRENTALS_CHILD_THEME_URL . 'js/index.js',
        ['jquery'],
        wp_get_theme()->get('Version')
    );

    wp_localize_script(
        'wprentals-child-main',
        'wprentalsChildData',
        [
            'currentUserRole' => ! empty(wp_get_current_user()->roles) ? wp_get_current_user()->roles[0] : 'guest',
            'isHomePage'      => is_front_page() || is_home() ? true : false
        ]
    );
}

add_action('wp_enqueue_scripts', 'wprentals_child_enques');
load_child_theme_textdomain('wprentals', WPRENTALS_CHILD_THEME_PATH . 'languages');

/**
 * Overwrite parent theme scripts
 *
 * @return void
 */
function wprentals_parent_enqueues_overwrite(): void
{
    $listing_id                = 0;
    $reservation_grouped_array = [];
    if (check_is_listing_page(get_the_ID())) {
        $listing_id                = get_the_ID();
        $all_listings_ids_in_group = current_user_is_timeshare() && check_has_room_category(
            $listing_id
        ) ? get_all_listings_ids_in_group($listing_id) : [];

        $reservation_grouped_array = get_reservation_grouped_array($all_listings_ids_in_group);
    }

    ####### JS #######
    if( get_post_type() === 'estate_property' && !is_tax() ){
        wp_dequeue_script('wpestate_property');

        wp_enqueue_script(
            'wprentals-child-property',
            WPRENTALS_CHILD_THEME_URL . 'js/property.js',
            ['jquery', 'moment'],
            wp_get_theme()->get('Version'),
            true
        );

        wp_localize_script(
            'wprentals-child-property',
            'wprentalsChildData',
            [
                'listingId'               => $listing_id,
                'currentUserIsTimeshare'  => current_user_is_timeshare(),
                'reservationGroupedArray' => $reservation_grouped_array
            ]
        );
    }

    ####### CSS #######
    wp_enqueue_style('daterangepicker-child', WPRENTALS_CHILD_THEME_URL . 'css/daterangepicker.css');
}

add_action('wp_enqueue_scripts', 'wprentals_parent_enqueues_overwrite', 20);
// END ENQUEUE PARENT ACTION

#######CUSTOMIZATION########

/**
 * Custom User Role
 *
 * @return void
 */
function add_custom_user_role(): void
{
    //Add Timeshare User like as Subscriber
    add_role('timeshare_user', 'Timeshare User', [
        'read'    => true,
        'level_0' => true,
    ]);
}

add_action('init', 'add_custom_user_role');

/**
 * @return bool
 */
function current_user_is_admin(): bool
{
    return current_user_can('administrator');
}

function current_user_is_timeshare(): bool
{
    return current_user_can('timeshare_user');
}

/**
 * @return int
 */
function get_room_category_id_by_slug(): int
{
    $taxonomy  = 'property_category';
    $term_slug = 'room';

    $term = get_term_by('slug', $term_slug, $taxonomy);

    return ! empty($term->term_id) ? $term->term_id : 0;
}

/**
 * @return int
 */
function get_room_group_id_by_slug(): int
{
    $taxonomy  = 'property_action_category';
    $term_slug = 'room';

    $term = get_term_by('slug', $term_slug, $taxonomy);

    return ! empty($term->term_id) ? $term->term_id : 0;
}

/**
 * @return int
 */
function get_cottage_category_id_by_slug(): int
{
    $taxonomy  = 'property_category';
    $term_slug = 'cottage';

    $term = get_term_by('slug', $term_slug, $taxonomy);

    return ! empty($term->term_id) ? $term->term_id : 0;
}

/**
 * Overwrite "Properties List - Properties number per page" option for Advanced Search result
 *
 * @return void
 */
function overwrite_wp_estate_prop_no(): void
{
    if (get_page_template_slug(get_the_ID()) == 'advanced_search_results.php') {
        $wprentals_admin                      = get_option('wprentals_admin');
        $wprentals_admin['wp_estate_prop_no'] = 100;

        update_option('wprentals_admin', $wprentals_admin);
    }
}

add_action('wp', 'overwrite_wp_estate_prop_no');

/**
 * @param $links
 *
 * @return array
 */
function extract_text_from_link($links): array
{
    $without_links = [];
    foreach ($links as $link) {
        preg_match('/<a.*>(.*)<\/a>/', $link, $matches);
        $without_links[] = $matches[1];
    }

    return $without_links;
}

//Filter for remove icon link(icon_bar_classic) from single Listing page
add_filter('term_links-property_category', 'extract_text_from_link');
add_filter('term_links-property_action_category', 'extract_text_from_link');

/**
 * @param int $post_id
 *
 * @return bool
 */
function check_is_listing_page(int $post_id): bool
{
    return get_post_type($post_id) === 'estate_property';
}