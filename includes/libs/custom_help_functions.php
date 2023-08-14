<?php
/**
 * Custom Help functions for overriding the parent theme functions
 */


function wpestate_show_booking_form($post_id, $wpestate_options = '', $favorite_class = '', $favorite_text = '', $is_shortcode = '') {
    $rental_type = wprentals_get_option('wp_estate_item_rental_type');
    $guest_list = wpestate_get_guest_dropdown('noany');
    $container_class = " col-md-4 ";

    if (isset($wpestate_options['sidebar_class'])) {
        if ($wpestate_options['sidebar_class'] == '' || $wpestate_options['sidebar_class'] == 'none') {
            $container_class = ' col-md-4 ';
        } else {
            $container_class = esc_attr($wpestate_options['sidebar_class']);
        }
    }

    ob_start();
    ?>

    <div class="booking_form_request is_shortcode<?php echo intval($is_shortcode); ?> <?php echo esc_attr($container_class); ?>" id="booking_form_request">

        <?php
        if (wprentals_get_option('wp_estate_replace_booking_form', '') == 'yes') {
            print '<div id="booking_form_mobile_close">&times;</div>';
            wpestate_show_contact_form($post_id);

        }else{
            ?>
            <div id="booking_form_request_mess"></div>
            <div id="booking_form_mobile_close">&times;</div>
            <h3 ><?php esc_html_e('Book Now', 'wprentals'); ?></h3>

            <?php
            $book_type = wprentals_return_booking_type($post_id);
            ?>

            <div class="has_calendar calendar_icon">
                <input type="text" id="start_date" placeholder="<?php echo wpestate_show_labels('check_in', $rental_type); ?>"  class="form-control calendar_icon" size="40" name="start_date"
                       value="<?php
                       if (isset($_GET['check_in_prop']) && $book_type == 1) {
                           echo sanitize_text_field($_GET['check_in_prop']);
                       }
                       ?>">
            </div>

            <?php
            if (wprentals_return_booking_type($post_id) == 2) {
                $booking_start_hour = get_post_meta($post_id, 'booking_start_hour', true);
                $booking_end_hour = get_post_meta($post_id, 'booking_end_hour', true);

                print wprentals_show_booking_form_per_hour_dropdown('start_hour', esc_html__('Start Hour', 'wprentals'), $booking_start_hour, $booking_end_hour, '');
                print wprentals_show_booking_form_per_hour_dropdown('end_hour', esc_html__('End Hour', 'wprentals'), $booking_start_hour, $booking_end_hour, '');
            } else {
                ?>

                <div class=" has_calendar calendar_icon">
                    <input type="text" id="end_date"  placeholder="<?php echo wpestate_show_labels('check_out', $rental_type); ?>" class="form-control calendar_icon" size="40" name="end_date"
                           value="<?php
                           if (isset($_GET['check_out_prop'])) {
                               echo sanitize_text_field($_GET['check_out_prop']);
                           }
                           ?>">
                </div>
            <?php } ?>

            <?php
            if ($rental_type == 0) {
                ?>

                <div class=" has_calendar guest_icon ">
                    <?php
                    if(wprentals_get_option('wp_estate_custom_guest_control','') =='yes'){
                        echo wpestate_show_advanced_guest_form(esc_html__('Guests','wprentals'), '',$post_id);
                    }else{
                        echo wpestate_show_booking_form_guest_dropdown($guest_list);
                    }
                    ?>
                </div>
                <?php
            } else {
                ?>
                <input type="hidden" name="booking_guest_no"  value="1">
                <?php
            }
            // show extra options
        wpestate_show_extra_options_booking($post_id)
            ?>

            <p class="full_form " id="add_costs_here"></p>
            <input type="hidden" id="listing_edit" name="listing_edit" value="<?php print intval($post_id); ?>" />


            <?php wpestate_show_booking_button($post_id); ?>


            <div class="third-form-wrapper">
                <div class="col-md-6 reservation_buttons">
                    <div id="add_favorites" class=" <?php print esc_attr($favorite_class); ?>"  data-postid="<?php echo esc_attr($post_id); ?>">
                        <?php print trim($favorite_text); ?>
                    </div>
                </div>

                <div class="col-md-6 reservation_buttons">
                    <div id="contact_host" class="col-md-6"  data-postid="<?php esc_attr($post_id); ?>">
                        <?php esc_html_e('Contact Owner', 'wprentals'); ?>
                    </div>
                </div>
            </div>

            <?php
            echo wpestate_share_unit_desing($post_id);
            ?>

        <?php } // end else?>

    </div>


    <?php
    /*
     * Only for shortcode
     *
     *
     * */
    if ($is_shortcode == 1) {

        $ajax_nonce = wp_create_nonce("wprentals_add_booking_nonce");
        print'<input type="hidden" id="wprentals_add_booking" value="' . esc_html($ajax_nonce) . '" />';
        ?>

        <div class="modal fade" id="instant_booking_modal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h2 class="modal-title_big" ><?php esc_html_e('Confirm your booking', 'wprentals'); ?></h2>
                        <h4 class="modal-title" id="myModalLabel"><?php esc_html_e('Review the dates and confirm your booking', 'wprentals'); ?></h4>
                    </div>

                    <div class="modal-body"></div>

                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

        <?php
        if (isset($_GET['check_in_prop']) && isset($_GET['check_out_prop'])) {

            print '<script type="text/javascript">
                      //<![CDATA[
                      jQuery(document).ready(function(){
                        setTimeout(function(){

                            jQuery("#end_date").trigger("change");
                        },1000);
                      });
                      //]]>
              </script>';
        }
        ?>

        <?php
    } // end for shortcode
    ?>


    <?php
    $return = ob_get_contents();
    ob_end_clean();
    return $return;
}
