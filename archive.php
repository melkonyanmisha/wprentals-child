<?php

// To restrict access for {domain}/properties/
if (get_post_type() === 'estate_property' && ! current_user_is_admin()) {
    wp_redirect(home_url(), 301);
    exit;
}

// Archive
// Wp Estate Pack
global $wpestate_row_number_col;
get_header();
$wpestate_options        = wpestate_page_details('');
$unit_class              = "col-md-6";
$wpestate_row_number_col = 6;

if ($wpestate_options['content_class'] == "col-md-12") {
    $unit_class              = "col-md-4";
    $wpestate_row_number_col = 4;
}

if (
    'wpestate_message' == get_post_type()
    || 'wpestate_invoice' == get_post_type()
    || 'wpestate_booking' == get_post_type()
) {
    wp_redirect(home_url(), 301);
    exit;
}
?>
<?php
if ( ! function_exists('elementor_theme_do_location') || ! elementor_theme_do_location('archive')) { ?>
    <div class="row content-fixed">
        <?php
        include(locate_template('templates/breadcrumbs.php')); ?>
        <div class=" <?php
        echo esc_attr($wpestate_options['content_class']); ?> ">

            <h1 class="entry-title">
                <?php
                if (is_category()) {
                    printf(
                        esc_html__('Category Archives: %s', 'wprentals'),
                        '<span>' . single_cat_title('', false) . '</span>'
                    );
                } elseif (is_day()) {
                    printf(esc_html__('Daily Archives: %s', 'wprentals'), '<span>' . get_the_date() . '</span>');
                } elseif (is_month()) {
                    printf(
                        esc_html__('Monthly Archives: %s', 'wprentals'),
                        '<span>' . get_the_date(_x('F Y', 'monthly archives date format', 'wprentals')) . '</span>'
                    );
                } elseif (is_year()) {
                    printf(
                        esc_html__('Yearly Archives: %s', 'wprentals'),
                        '<span>' . get_the_date(_x('Y', 'yearly archives date format', 'wprentals')) . '</span>'
                    );
                } else {
                    esc_html_e('Blog Archives', 'wprentals');
                }
                ?>
            </h1>
            <div class="blog_list_wrapper row">
                <?php
                while (have_posts()) : the_post();
                    include(locate_template('templates/blog_unit.php'));
                endwhile;
                wp_reset_query();
                ?>
            </div>

            <?php
            wprentals_pagination('', $range = 2);
            ?>
        </div><!-- end 8col container-->

        <?php
        include(get_theme_file_path('sidebar.php'));
        ?>
    </div>
    <?php
} ?>
<?php
get_footer(); ?>