<?php

/**
 * @return bool
 */
function current_user_is_admin(): bool
{
    return current_user_can('administrator');
}

/**
 * @return bool
 */
function current_user_is_timeshare(): bool
{
    return current_user_can('timeshare_user');
}

/**
 * @return bool
 */
function current_user_is_customer(): bool
{
    return current_user_can('customer');
}

/**
 * Convert date to necessarily format. Example 1970-01-01
 *
 * @param string $dateString
 *
 * @return string
 */
function convert_date_format(string $dateString): string
{
    return date('Y-m-d', strtotime($dateString));
}

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
 * To display separately prices for accessible and remaining days. Depends on client user role
 *
 * @param int $invoice_id
 * @param array $booking_array
 * @param string $rental_type //wprentals_get_option('wp_estate_item_rental_type', '')
 * @param string $booking_type
 *
 * @return string
 */
function render_additional_part_of_invoice(
    int $invoice_id,
    array $booking_array,
    string $rental_type,
    string $booking_type
): string {
    $discount_price_calc = $booking_array['discount_price_calc'] ?? [];

    // Start output buffering
    ob_start();

    // To display separately prices for accessible and remaining days
    if (
        ! empty($discount_price_calc)
        && $discount_price_calc['booked_by_timeshare_user']
        && ! empty($discount_price_calc['timeshare_user_calc'])
        && $discount_price_calc['timeshare_user_calc']['accessible_days_count'] > 0
    ) {
        $discounted_price_for_accessible_days = $discount_price_calc['timeshare_user_calc']['discounted_price_for_accessible_days'];
        $accessible_days_count                = $discount_price_calc['timeshare_user_calc']['accessible_days_count'];
        $remaining_days_price                 = $discount_price_calc['timeshare_user_calc']['remaining_days_price'];
        $remaining_days_count                 = $discount_price_calc['timeshare_user_calc']['remaining_days_count'];

        $price_per_night_accessible_days = $discounted_price_for_accessible_days / $accessible_days_count;
        $price_per_night_remaining_days  = $remaining_days_count !== 0 ? $remaining_days_price / $remaining_days_count : 0;

        $wpestate_currency       = esc_html(get_post_meta($invoice_id, 'invoice_currency', true));
        $wpestate_where_currency = esc_html(wprentals_get_option('wp_estate_where_currency_symbol', ''));

        $discounted_price_for_accessible_days_show = wpestate_show_price_booking_for_invoice(
            $discounted_price_for_accessible_days,
            $wpestate_currency,
            $wpestate_where_currency,
            0,
            1
        );

        $price_per_night_accessible_days_show = wpestate_show_price_booking_for_invoice(
            $price_per_night_accessible_days,
            $wpestate_currency,
            $wpestate_where_currency,
            0,
            1
        );

        $remaining_days_price_show = wpestate_show_price_booking_for_invoice(
            $remaining_days_price,
            $wpestate_currency,
            $wpestate_where_currency,
            0,
            1
        );

        $price_per_night_remaining_days_show = wpestate_show_price_booking_for_invoice(
            $price_per_night_remaining_days,
            $wpestate_currency,
            $wpestate_where_currency,
            0,
            1
        );


        $subtotal_show = wpestate_show_price_booking_for_invoice(
            $discounted_price_for_accessible_days + $remaining_days_price,
            $wpestate_currency,
            $wpestate_where_currency,
            0,
            1
        );

        ?>
        <div class="invoice_row invoice_content">
            <span class="inv_legend">
                <?= esc_html__('Accessible days', 'wprentals'); ?>
            </span>
            <span class="inv_data">
                <?= $discounted_price_for_accessible_days_show; ?>
            </span>
            <span class="inv_exp">
                <?php
                echo $accessible_days_count
                     . ' '
                     . wpestate_show_labels('nights', $rental_type, $booking_type)
                     . ' x '
                     . $price_per_night_accessible_days_show;
                ?>
            </span>
        </div>

        <?php
        if ($remaining_days_count) { ?>
            <div class="invoice_row invoice_content">
                <span class="inv_legend">
                    <?= esc_html__('Remaining days', 'wprentals'); ?>
                </span>
                <span class="inv_data">
                    <?= $remaining_days_price_show; ?>
                </span>
                <span class="inv_exp">
                    <?php
                    echo $remaining_days_count
                         . ' '
                         . wpestate_show_labels('nights', $rental_type, $booking_type)
                         . ' x '
                         . $price_per_night_remaining_days_show;
                    ?>
                </span>
            </div>

            <div class="invoice_row invoice_content">
                <span class="inv_legend">
                    <?= esc_html__('Subtotal', 'wprentals'); ?>
                </span>
                <span class="inv_data">
                    <?= $subtotal_show; ?>
                </span>
                <span class="inv_exp">
                    <?= $discounted_price_for_accessible_days . ' + ' . $remaining_days_price; ?>
                </span>
            </div>
            <?php
        } ?>


        <?php
    }

    // End output buffering
    return ob_get_clean();
}

/**
 * Check if the selected "Make it Featured" checkbox
 *
 * @param int $listing_id
 *
 * @return bool
 */
function check_listing_is_featured(int $listing_id): bool
{
    return boolval(get_post_meta($listing_id, 'prop_featured', true));
}

################## START OF ROOM CATEGORY ##################
/**
 * @param int $listing_id
 *
 * @return bool
 */
function check_has_parent_room_category(int $listing_id): bool
{
    $category_parent_terms_slugs = [];
    $category_terms              = wp_get_post_terms($listing_id, 'property_category');
    $category_parent_terms_ids   = wp_list_pluck($category_terms, 'parent');

    if ( ! empty($category_parent_terms_ids) && $category_parent_terms_ids[0] !== 0) {
        foreach ($category_parent_terms_ids as $current_category_parent_term_id) {
            $current_category_parent_term  = get_term($current_category_parent_term_id, 'property_category');
            $category_parent_terms_slugs[] = $current_category_parent_term->slug;
        }

        if (in_array('room', $category_parent_terms_slugs)) {
            return true;
        }
    }

    return false;
}

/**
 * @return int
 */
function get_parent_room_category_id_by_slug(): int
{
    $taxonomy  = 'property_category';
    $term_slug = 'room';
    $term      = get_term_by('slug', $term_slug, $taxonomy);

    return ! empty($term->term_id) ? $term->term_id : 0;
}

/**
 * Retrieve ordered listing ids from current category
 *
 * @param int $category_id
 *
 * @return array
 */
function get_ordered_listing_ids_from_category(int $category_id): array
{
    $taxonomy  = 'property_category';
    $post_type = 'estate_property';

    // Query for posts in the same taxonomy term(s) and post type
    $args = [
        'post_type'      => $post_type,
        'posts_per_page' => -1, // To get all posts
        'fields'         => 'ids', // Retrieve only post IDs
        'tax_query'      => [
            [
                'taxonomy' => $taxonomy,
                'field'    => 'id',
                'terms'    => $category_id,
            ],
        ],
        'order'          => 'ASC'
    ];

    return get_posts($args);
}

/**
 * Retrieve the ID of the first room category found
 *
 * @param int $listing_id
 *
 * @return int
 */
function get_room_category_id(int $listing_id): int
{
    $taxonomy = 'property_category';

    // Get the current post's taxonomy terms
    $post_terms       = wp_get_post_terms($listing_id, $taxonomy);
    $room_category_id = 0;

    if (is_array($post_terms)) {
        foreach ($post_terms as $current_term) {
            if ($current_term instanceof WP_Term && $current_term->parent === get_parent_room_category_id_by_slug()) {
                $room_category_id = $current_term->term_id;

                break;
            }
        }
    }

    return $room_category_id;
}

//get_main_room_id_from_room_category(98);
function get_main_room_id_from_room_category(int $room_category_id): int
{
    $main_room_id                      = 0;
    $taxonomy                          = 'property_category';
    $room_category_term                = get_term($room_category_id, $taxonomy);
    $ordered_listing_ids_from_category = get_ordered_listing_ids_from_category($room_category_id);

    if (
        $room_category_term instanceof WP_Term
        && $room_category_term->parent === get_parent_room_category_id_by_slug()
    ) {
        $listings_ids_from_last_room_group = get_listings_ids_from_last_room_group();

        foreach ($ordered_listing_ids_from_category as $current_listing_id) {
            if (in_array($current_listing_id, $listings_ids_from_last_room_group)) {
                $main_room_id = $current_listing_id;
                break;
            }
        }
    }

    return $main_room_id;
}

################## END OF ROOM CATEGORY ##################

################## START OF ROOM GROUP ##################
/**
 * @param int $listing_id
 *
 * @return bool
 */
function check_has_room_group(int $listing_id): bool
{
    $category_parent_terms_slugs = [];
    $category_terms              = wp_get_post_terms($listing_id, 'property_action_category');
    $category_parent_terms_ids   = wp_list_pluck($category_terms, 'parent');

    if ( ! empty($category_parent_terms_ids) && $category_parent_terms_ids[0] !== 0) {
        foreach ($category_parent_terms_ids as $current_category_parent_term_id) {
            $current_category_parent_term  = get_term($current_category_parent_term_id, 'property_action_category');
            $category_parent_terms_slugs[] = $current_category_parent_term->slug;
        }

        return in_array('room-group', $category_parent_terms_slugs);
    }

    return false;
}

/**
 * @return int
 */
function get_room_group_id_by_slug(): int
{
    $taxonomy  = 'property_action_category';
    $term_slug = 'room-group';

    $term = get_term_by('slug', $term_slug, $taxonomy);

    return ! empty($term->term_id) ? $term->term_id : 0;
}

/**
 * Get listing ID's in single group
 *
 * @param int $listing_id
 *
 * @return array
 */
function get_all_listings_ids_in_group(int $listing_id): array
{
    $all_listings_ids_in_group = [];
    $all_listings_in_group     = get_all_listings_in_group($listing_id);

    if ( ! empty($all_listings_in_group)) {
        foreach ($all_listings_in_group as $current_listing) {
            $all_listings_ids_in_group[] = $current_listing->ID;
        }
    }

    return $all_listings_ids_in_group;
}

/**
 * @param int $listing_id
 *
 * @return array
 */
function get_all_listings_in_group(int $listing_id): array
{
    // Get the taxonomy Group terms for the current post
    $group_terms = wp_get_post_terms($listing_id, 'property_action_category');

    if ( ! empty($group_terms)) {
        $group_terms_ids = wp_list_pluck($group_terms, 'term_id');
        $args            = [
            'post_type'      => 'estate_property',
            'posts_per_page' => -1, // Retrieve all posts
            'tax_query'      => [
                [
                    'taxonomy' => 'property_action_category',
                    'field'    => 'id',
                    'terms'    => $group_terms_ids,
                    'operator' => 'IN',
                ],
            ],
        ];

        return get_posts($args);
    }

    return [];
}

/**
 * Retrieve reservation data for listing/listings
 *
 * @param array $listings_ids_list
 *
 * @return array
 */
function get_reservation_grouped_array(array $listings_ids_list): array
{
    $reservation_grouped_array = [];

    if ( ! empty($listings_ids_list)) {
        foreach ($listings_ids_list as $current_listings_id) {
            $reservation_grouped_array[$current_listings_id] = get_post_meta(
                $current_listings_id,
                'booking_dates',
                true
            );

            if ($reservation_grouped_array[$current_listings_id] == '') {
                $reservation_grouped_array[$current_listings_id] = wpestate_get_booking_dates($current_listings_id);
            }
        }
    }

    return $reservation_grouped_array;
}

/**
 * Retrieve a group that has max order
 *
 * @return object
 */
function get_group_with_max_room_group_order(): object
{
    $term_with_max_order = new stdClass();

    $args = [
        'taxonomy'   => 'property_action_category',
        'hide_empty' => true, // Include terms with no posts assigned
        'fields'     => 'all', // Get all term data including custom meta
    ];

    $terms                   = get_terms($args);
    $max_current_group_order = 0;

    if ( ! is_array($terms) || empty($terms)) {
        return $term_with_max_order;
    }

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
 * Retrieve all listings from rooms group which has a max group order.
 *
 * @return WP_Post[]
 */
function get_all_listings_from_last_room_group(): array
{
    $group_with_max_room_group_order = get_group_with_max_room_group_order();

    if ($group_with_max_room_group_order instanceof stdClass) {
        return [];
    }

    $args = [
        'post_type'   => 'estate_property',
        'tax_query'   => [
            [
                'taxonomy' => 'property_action_category',
                'field'    => 'id', // Possible values 'id', 'name', or 'term_taxonomy_id'
                'terms'    => $group_with_max_room_group_order->term_id
            ],
        ],
        'numberposts' => -1,
    ];

    return get_posts($args);
}

/**
 * Retrieve all listings ID's from rooms group which has a max group order.
 *
 * @return array
 */
function get_listings_ids_from_last_room_group(): array
{
    $listings_ids_from_last_room_group = [];
    $all_listings_from_last_room_group = get_all_listings_from_last_room_group();

    if ( ! empty($all_listings_from_last_room_group)) {
        foreach ($all_listings_from_last_room_group as $current_listing) {
            $listings_ids_from_last_room_group[] = $current_listing->ID;
        }
    }

    return $listings_ids_from_last_room_group;
}


/**
 * Retrieve a listing from rooms group which has a max group order. The post should have selected "Make it Featured" checkbox
 *
 * @return object|stdClass|WP_Post
 */
function get_featured_listing_from_last_room_group(): object
{
    $featured_post_from_last_room_group = new stdClass();
    $group_with_max_room_group_order    = get_group_with_max_room_group_order();

    if ($group_with_max_room_group_order instanceof stdClass) {
        return $featured_post_from_last_room_group;
    }

    $args = [
        'post_type'   => 'estate_property',
        'tax_query'   => [
            [
                'taxonomy' => 'property_action_category',
                'field'    => 'id', // Possible values 'id', 'name', or 'term_taxonomy_id'
                'terms'    => $group_with_max_room_group_order->term_id
            ],
        ],
        'meta_query'  => [
            [
                'key'     => 'prop_featured',
                'value'   => '1', // '1' indicates a post is featured
                'compare' => '=',
            ],
        ],
        'numberposts' => 1, // To get only 1 post
    ];

    $post_in_last_room_group = get_posts($args);

    if ( ! empty($post_in_last_room_group) and $post_in_last_room_group[0] instanceof WP_Post) {
        $featured_post_from_last_room_group = $post_in_last_room_group[0];
    }

    return $featured_post_from_last_room_group;
}

################## END OF ROOM GROUP ##################

################## START OF COTTAGE ##################
/**
 * @param int $listing_id
 *
 * @return bool
 */
function check_has_cottage_category(int $listing_id): bool
{
    $category_terms      = wp_get_post_terms($listing_id, 'property_category');
    $categories_term_ids = wp_list_pluck($category_terms, 'term_id');

    return in_array(get_cottage_category_id_by_slug(), $categories_term_ids);
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

################## END OF COTTAGE ##################