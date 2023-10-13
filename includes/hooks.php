<?php

/**
 * Show contact owner form
 *
 * Handle ajax requests
 * add_action('wp_ajax_nopriv_wpestate_ajax_show_contact_owner_form', 'wpestate_ajax_show_contact_owner_form');
 * add_action('wp_ajax_wpestate_ajax_show_contact_owner_form', 'wpestate_ajax_show_contact_owner_form');
 *
 * @return void
 */
function wpestate_ajax_show_contact_owner_form()
{
    global $post;
    if (is_singular('estate_property')) {
        $post_id  = $post->ID;
        $agent_id = 0;
    } else {
        $agent_id = $post->ID;
        $post_id  = 0;
    }
    ?>
    <!-- Modal -->
    <div class="modal fade" id="contact_owner_modal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h2 class="modal-title_big"><?= esc_html__('Contact', 'wprentals'); ?></h2>
                    <h4 class="modal-title" id="myModalLabel">
                        <?= esc_html__('Please complete the form to contact us.', 'wprentals'); ?>
                    </h4>
                </div>
                <div class="modal-body">
                    <?php
                    wpestate_show_contact_form_simple($agent_id, $post_id);
                    ?>
                </div><!-- /.modal-body -->
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    <?php
}