<?php
/**
 * Template Name: User Dashboard My Reservations
 *
 * @var WP_Post $post
 */

// Wp Estate Pack
if ( ! is_user_logged_in()) {
    wp_redirect(esc_url(home_url('/')));
    exit();
}

global $user_login;
$current_user              = wp_get_current_user();
$userID                    = $current_user->ID;
$user_login                = $current_user->user_login;
$user_pack                 = get_the_author_meta('package_id', $userID);
$user_registered           = get_the_author_meta('user_registered', $userID);
$user_package_activation   = get_the_author_meta('package_activation', $userID);
$paid_submission_status    = esc_html(wprentals_get_option('wp_estate_paid_submission', ''));
$price_submission          = floatval(wprentals_get_option('wp_estate_price_submission', ''));
$submission_curency_status = wpestate_curency_submission_pick();
$edit_link                 = wpestate_get_template_link('user_dashboard_edit_listing.php');
$processor_link            = wpestate_get_template_link('processor.php');
$wpestate_where_currency   = esc_html(wprentals_get_option('wp_estate_where_currency_symbol', ''));
$wpestate_currency         = wpestate_curency_submission_pick();
$wpestate_options          = wpestate_page_details($post->ID);
get_header();

$title_search = '';
$new_mess     = 0;
if (isset($_POST['wpestate_prop_title'])) {
    if ( ! isset($_POST['wpestate_dash_rez_search_nonce']) || ! wp_verify_nonce(
            $_POST['wpestate_dash_rez_search_nonce'],
            'wpestate_dash_rez_search'
        )) {
        esc_html_e('your nonce does not validated', 'wprentals');
        exit();
    }
    $title = sanitize_text_field($_POST['wpestate_prop_title']);

    $args     = array(
        'post_type'      => 'estate_property',
        'posts_per_page' => -1,
        's'              => $title
    );
    $new_mess = 1;

    if (function_exists('wpestate_search_by_title_only_filter')) {
        $prop_selection = wpestate_search_by_title_only_filter($args);
    }

    $right_array   = array();
    $right_array[] = 0;

    while ($prop_selection->have_posts()): $prop_selection->the_post();
        $right_array[] = $post->ID;
    endwhile;

    wp_reset_postdata();
    $title_search = array(
        array(
            'key'     => 'booking_id',
            'value'   => $right_array,
            'compare' => 'IN',
        ),
    );
}

// Start output buffering
ob_start();
?>

    <div class="row is_dashboard">
        <?php
        if (wpestate_check_if_admin_page($post->ID)) {
            if (is_user_logged_in()) {
                include(locate_template('templates/user_menu.php'));
            }
        }
        ?>

        <div class="dashboard-margin">
            <?php
            wprentals_dashboard_header_display();
            ?>

            <div class="row dashboard_property_list user_dashboard_panel">
                <?php
                include(locate_template('dashboard/templates/search_rezervation_list.php'));
                ?>

                <div class="wpestate_dashboard_table_list_header my_reservation_header row">
                    <div class="col-md-6"><?= esc_html__('Property', 'wprentals'); ?></div>
                    <div class="col-md-2"><?= esc_html__('Status', 'wprentals'); ?></div>
                    <div class="col-md-2"><?= esc_html__('Period', 'wprentals'); ?></div>
                    <div class="col-md-2"><?= esc_html__('Request by', 'wprentals'); ?></div>
                </div>

                <?php
                wp_reset_query();
                $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                $args  = array(
                    'post_type'      => 'wpestate_booking',
                    'post_status'    => 'publish',
                    'paged'          => $paged,
                    'posts_per_page' => 30,
                    'order'          => 'DESC',
                    'author'         => $userID
                );

                if ($title_search != '') {
                    $args['meta_query'] = $title_search;
                }

                $current_user_is_timeshare             = current_user_is_timeshare();
                $current_user_is_customer              = current_user_is_customer();
                $featured_listing_from_last_room_group = get_featured_listing_from_last_room_group();

                $book_selection = new WP_Query($args);
                if ($book_selection->have_posts()) {
                    while ($book_selection->have_posts()): $book_selection->the_post();
                        $listing_id       = get_post_meta(get_the_ID(), 'booking_id', true);
                        $is_group_booking = intval(get_post_meta($post->ID, 'is_group_booking', true));

                        if ($current_user_is_timeshare) {
                            if ($is_group_booking) {
                                $booking_full_data_json = get_post_meta(get_the_ID(), 'booking_full_data', true);
                                if ($booking_full_data_json) {
                                    $booking_full_data = json_decode($booking_full_data_json, true);
                                    $booking_id        = $booking_full_data['booking_instant_data']['make_the_book']['booking_id'];

                                    // To skip displaying the booking which is not primary in booked rooms group
                                    if ( ! empty($booking_full_data) && get_the_ID() !== $booking_id) {
                                        continue;
                                    }
                                }
                            }
                        }

                        $listing_owner = wpsestate_get_author($listing_id);
                        if ($userID != $listing_owner) {
                            include(locate_template('dashboard/templates/book-listing-user-unit.php'));
                        }
                    endwhile;
                    wprentals_pagination($book_selection->max_num_pages, $range = 2);
                } else {
                    if ($new_mess == 1) { ?>
                        <h4 class="no_favorites"><?= esc_html__('No results!', 'wprentals'); ?></h4>
                        <?php
                    } else { ?>
                        <h4 class="no_favorites">
                            <?= esc_html__('You don\'t have any reservations made!', 'wprentals'); ?>
                        </h4>
                        <?php
                    }
                }
                wp_reset_query(); ?>
            </div>
        </div>
    </div>

<?php
$ajax_nonce      = wp_create_nonce("wprentals_reservation_actions_nonce");
$ajax_nonce_book = wp_create_nonce("wprentals_booking_confirmed_actions_nonce");
?>
    <input type="hidden" id="wprentals_reservation_actions" value="<?= esc_html($ajax_nonce); ?>"/>
    <input type="hidden" id="wprentals_booking_confirmed_actions" value="<?= esc_html($ajax_nonce_book) ?>"/>

<?php
wp_reset_query();
// End output buffering
echo ob_get_clean();

get_footer();

