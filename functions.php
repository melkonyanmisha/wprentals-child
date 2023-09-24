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

if ( ! defined('TIMESHARE_PRICE_CALC_DATA')) {
    define('TIMESHARE_PRICE_CALC_DATA', 'timeshare_price_calc_data');
}

//meta_key for save room Group order in wp_termmeta
if ( ! defined('ROOM_GROUP_ORDER')) {
    define('ROOM_GROUP_ORDER', 'room_group_order');
}

//Meta Key
if ( ! defined('TIMESHARE_USER_DATA')) {
    define('TIMESHARE_USER_DATA', 'timeshare_user_data');
}

//The Timeshare user package duration
if ( ! defined('TIMESHARE_PACKAGE_DURATION')) {
    define('TIMESHARE_PACKAGE_DURATION', 'timeshare_package_duration');
}

//Default value for TIMESHARE_PACKAGE_DURATION if it not set in DB
if ( ! defined('TIMESHARE_PACKAGE_DEFAULT_DURATION_VALUE')) {
    define('TIMESHARE_PACKAGE_DEFAULT_DURATION_VALUE', 7);
}

require_once WPRENTALS_CHILD_THEME_PATH . 'plugins/wprentals-core/shortcodes/recent_items_list.php';
require_once WPRENTALS_CHILD_THEME_PATH . 'plugins/wprentals-core/post-types/property.php';
require_once WPRENTALS_CHILD_THEME_PATH . 'includes/libs/custom_help_functions.php';

// Booking Process
require_once WPRENTALS_CHILD_THEME_PATH . 'custom-book/utils.php';
require_once WPRENTALS_CHILD_THEME_PATH . 'custom-book/steps/generate-the-invoice.php';
require_once WPRENTALS_CHILD_THEME_PATH . 'custom-book/steps/make-the-book.php';
require_once WPRENTALS_CHILD_THEME_PATH . 'custom-book/steps/render-booking-confirm-popup.php';
require_once WPRENTALS_CHILD_THEME_PATH . 'custom-book/booking-process.php';

// User Dashboard
require_once WPRENTALS_CHILD_THEME_PATH . 'dashboard/functions.php';

// Exit if accessed directly
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Enqueues child theme js and css files
 *
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
    ####### JS #######
    global $post;
    $page_template = '';
    if (isset($post->ID)) {
        $page_template = get_post_meta($post->ID, '_wp_page_template', true);
    }

    if (
        'estate_property' == get_post_type()
        || 'estate_agent' == get_post_type()
        || $page_template == 'user_dashboard_invoices.php'
        || $page_template == 'user_dashboard_my_reservations.php'
        || $page_template == 'user_dashboard_my_bookings.php'
    ) {
        wp_deregister_script('wpestate_property');
        wp_dequeue_script('wpestate_property');

        $reservation_grouped_data = [];
        if (check_is_listing_page(get_the_ID())) {
            $listing_id                = get_the_ID();
            $all_listings_ids_in_group = current_user_is_timeshare() && check_has_room_category(
                $listing_id
            ) ? get_all_listings_ids_in_group($listing_id) : [];

            $reservation_grouped_data = get_reservation_grouped_array($all_listings_ids_in_group);
        }

        if (is_user_logged_in()) {
            $logged_in = "yes";
        } else {
            $logged_in = "no";
        }

        $early_discount             = '';
        $include_children_as_guests = '';
        $include_booking_type       = '';
        if (isset($post->ID)) {
            $early_discount             = floatval(get_post_meta($post->ID, 'early_bird_percent', true));
            $include_booking_type       = wprentals_return_booking_type($post->ID);
            $include_children_as_guests = get_post_meta($post->ID, 'children_as_guests', true);
        }

        $book_type            = intval(wprentals_get_option('wp_estate_booking_type'));
        $property_js_required = ['jquery', 'wpestate_control', 'fancybox'];
        if ($book_type == 2 || $book_type == 3) {
            $property_js_required = ['jquery', 'wpestate_control', 'fullcalendar', 'fancybox'];
        }

        wp_enqueue_script(
            'wprentals-child-property',
            WPRENTALS_CHILD_THEME_URL . 'js/property.js',
            $property_js_required,
            wp_get_theme()->get('Version'),
            true
        );

        wp_localize_script(
            'wprentals-child-property', 'property_vars',
            array(
                'plsfill'                => esc_html__('Please fill all the forms!', 'wprentals'),
                'sending'                => esc_html__('Sending Request...', 'wprentals'),
                'logged_in'              => $logged_in,
                'notlog'                 => esc_html__('You need to log in order to book a listing!', 'wprentals'),
                'viewless'               => esc_html__('View less', 'wprentals'),
                'viewmore'               => esc_html__('View more', 'wprentals'),
                'nostart'                => esc_html__(
                    'Check-in date cannot be bigger than Check-out date',
                    'wprentals'
                ),
                'noguest'                => esc_html__('Please select the number of guests', 'wprentals'),
                'guestoverload'          => esc_html__(
                    'The number of guests is greater than the property capacity - ',
                    'wprentals'
                ),
                'guests'                 => esc_html__('guests', 'wprentals'),
                'early_discount'         => $early_discount,
                'rental_type'            => wprentals_get_option('wp_estate_item_rental_type'),
                'book_type'              => $include_booking_type,
                'reserved'               => esc_html__('reserved', 'wprentals'),
                'use_gdpr'               => wprentals_get_option('wp_estate_use_gdpr'),
                'gdpr_terms'             => esc_html__('You must agree to GDPR Terms', 'wprentals'),
                'is_woo'                 => wprentals_get_option('wp_estate_enable_woo', ''),
                'allDayText'             => esc_html__('hours', 'wprentals'),
                'clickandragtext'        => esc_html__('click and drag to select the hours', 'wprentals'),
                'processing'             => esc_html__('Processing..', 'wprentals'),
                'book_now'               => esc_html__('Book Now', 'wprentals'),
                'instant_booking'        => esc_html__('Instant Booking', 'wprentals'),
                'send_mess'              => esc_html__('Send Message', 'wprentals'),
                'children_as_guests'     => $include_children_as_guests,

//                todo@@@custom data
                'reservationGroupedData' => $reservation_grouped_data
            )
        );
    }
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

/**
 * @return object|int|mixed|stdClass|string|WP_Term
 */
function get_group_with_max_room_group_order(): object
{
    $args = array(
        'taxonomy'   => 'property_action_category',
        'hide_empty' => true, // Include terms with no posts assigned
        'fields'     => 'all', // Get all term data including custom meta
    );

    $terms = get_terms($args);

    $max_current_group_order = 0;
    $term_with_max_order     = new stdClass();

    foreach ($terms as $term) {
        $term_order = get_term_meta($term->term_id, ROOM_GROUP_ORDER, true);

        if ($term_order && is_numeric($term_order)) {
            $term_order = intval($term_order);

            if ($term_order > $max_current_group_order) {
                $max_current_group_order    = $term_order;
                $term_with_max_order        = $term;
                $term_with_max_order->order = $term_order;
            }
        }
    }

    return $term_with_max_order;
}

/**
 *  To restrict page access by user role
 *
 * @param WP_Query $query
 *
 * @return void
 */
function restrict_page_access(WP_Query $query): void
{
    // Check if this is a Rooms Group taxonomy page. The page can only be accessed by the administrator user
    if ( ! current_user_is_admin() && is_tax('property_action_category')) {
        wp_redirect(home_url(), '302');
    }
}

// Fires after the query variable object is created, but before the actual query is run.
add_action('pre_get_posts', 'restrict_page_access');