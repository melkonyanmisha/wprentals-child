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

require_once WPRENTALS_CHILD_THEME_PATH . 'includes/libs/custom_help_functions.php';
require_once WPRENTALS_CHILD_THEME_PATH . 'custom_book_functions.php';


// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

function wprentals_child_enques()
{
    ####### CSS #######
    $parent_style = 'wpestate_style';
    wp_enqueue_style('bootstrap', WPRENTALS_THEME_URL . '/css/bootstrap.css', array(), '1.0', 'all');
    wp_enqueue_style(
        'bootstrap-theme',
        WPRENTALS_THEME_URL . '/css/bootstrap-theme.css',
        array(),
        '1.0',
        'all'
    );
    wp_enqueue_style(
        $parent_style,
        WPRENTALS_THEME_URL . '/style.css',
        array('bootstrap', 'bootstrap-theme'),
        'all'
    );
    wp_enqueue_style(
        'wpestate-child-main-style',
        WPRENTALS_CHILD_THEME_URL . '/style.css',
        array($parent_style),
        wp_get_theme()->get('Version')
    );


    wp_enqueue_style('wpestate-child-style', WPRENTALS_CHILD_THEME_URL . 'css/style.css');
}

add_action('wp_enqueue_scripts', 'wprentals_child_enques');
load_child_theme_textdomain('wprentals', WPRENTALS_CHILD_THEME_PATH . 'languages');


function wprentals_parent_enques_overwrite()
{
    ####### JS #######
    wp_dequeue_script('daterangepicker');
    wp_enqueue_script(
        'daterangepicker-child',
        WPRENTALS_CHILD_THEME_URL . 'js/daterangepicker.js',
        array('jquery', 'moment'),
        '1.0',
        true
    );

    wp_localize_script(
        'daterangepicker-child',
        'daterangepicker_vars',
        array(
            'pls_select' => esc_html__('Select both dates:', 'wprentals'),
            'start_date' => esc_html__('Check-in', 'wprentals'),
            'end_date'   => esc_html__('Check-out', 'wprentals'),
            'to'         => esc_html__('to', 'wprentals')
        )
    );

    ####### CSS #######
    wp_enqueue_style('daterangepicker-child', WPRENTALS_CHILD_THEME_URL . 'css/daterangepicker.css');
}

add_action('wp_enqueue_scripts', 'wprentals_parent_enques_overwrite', 20);
// END ENQUEUE PARENT ACTION


#######CUSTOMIZATION########


function wpestate_check_user_level()
{
    $current_user          = wp_get_current_user();
    $userID                = $current_user->ID;
    $user_login            = $current_user->user_login;
    $separate_users_status = esc_html(wprentals_get_option('wp_estate_separate_users'));
    $publish_only          = esc_html(wprentals_get_option('wp_estate_publish_only'));
    global $post;
    $page_template = '';
    if (isset($post->ID)) {
        $page_template = get_post_meta($post->ID, '_wp_page_template', true);
    }

    if (trim($publish_only) != '') {
        $user_array = explode(',', $publish_only);

        if (in_array($user_login, $user_array)) {
            return true;
        } else {
            return false;
        }
    }
    $dashboard_pages = array(
        'user_dashboard_main.php',
        'user_dashboard.php',
        'user_dashboard_add_step1.php',
        'user_dashboard_edit_listing.php',
        'user_dashboard_my_bookings.php',
        'user_dashboard_packs.php',
        'user_dashboard_searches.php',
        'user_dashboard_allinone.php',
        'user_dashboard_my_reviews.php',
    );

    if ($separate_users_status == 'no') {
        return true;
    } else {
        $user_level = intval(get_user_meta($userID, 'user_type', true));

        if ($user_level == 0) { // user can book and rent
            return true;
        } else {
            // user can only book
            if (in_array($page_template, $dashboard_pages)) {
                return false;
            }
        }
    }
}

/**
 * Custom User Role
 *
 * @return void
 */
function add_custom_user_role()
{
    //Add Timeshare User like as Subscriber
    add_role('timeshare_user', 'Timeshare User', array(
        'read'    => true,
        'level_0' => true,
    ));
}

add_action('init', 'add_custom_user_role');

/**
 * @return bool
 */
function current_user_is_admin()
{
    return current_user_can('administrator');
}

function current_user_is_timeshare()
{
    return current_user_can('timeshare_user');
}

/**
 * @return int
 */
function get_room_category_id_by_slug()
{
    $taxonomy  = 'property_category';
    $term_slug = 'room';

    $term = get_term_by('slug', $term_slug, $taxonomy);

    return ! empty($term->term_id) ? $term->term_id : 0;
}

/**
 * @return int
 */
function get_room_group_id_by_slug()
{
    $taxonomy  = 'property_action_category';
    $term_slug = 'room';

    $term = get_term_by('slug', $term_slug, $taxonomy);

    return ! empty($term->term_id) ? $term->term_id : 0;
}

/**
 * @return int
 */
function get_cottage_category_id_by_slug()
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
function overwrite_wp_estate_prop_no()
{
    if (get_page_template_slug(get_the_ID()) == 'advanced_search_results.php') {
        $wprentals_admin                      = get_option('wprentals_admin');
        $wprentals_admin['wp_estate_prop_no'] = 100;

        update_option('wprentals_admin', $wprentals_admin);
    }
}

add_action('wp', 'overwrite_wp_estate_prop_no');

//Filter for remove icon link(icon_bar_classic) from single Listing page
add_filter('term_links-property_category', 'extract_text_from_link');
add_filter('term_links-property_action_category', 'extract_text_from_link');

/**
 * @param $links
 *
 * @return array
 */
function extract_text_from_link($links)
{
    $without_links = [];
    foreach ($links as $link) {
        preg_match('/<a.*>(.*)<\/a>/', $link, $matches);
        $without_links[] = $matches[1];
    }

    return $without_links;
}


