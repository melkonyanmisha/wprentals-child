<?php

global $post;
global $wpestate_social_login;

$type                    = isset($_POST['type']) ? intval($_POST['type']) : 0;
$ispop                   = 0;
$wpestate_propid         = $post->ID;
$show_login              = '';
$show_register           = '';
$login_text              = isset($_POST['login_modal_type']) ? intval($_POST['login_modal_type']) : 0;
$separate_users          = esc_html(wprentals_get_option('wp_estate_separate_users', ''));
$social_register_on      = esc_html(wprentals_get_option('wp_estate_social_register_on', ''));
$enable_user_pass_status = esc_html(wprentals_get_option('wp_estate_enable_user_pass', ''));
$wp_estate_use_captcha   = esc_html(wprentals_get_option('wp_estate_use_captcha', ''));

$modal_image = wprentals_get_option('wp_estate_modal_image', 'url');
if ($modal_image === '') {
    $modal_image = get_template_directory_uri() . '/img/modal_default.png';
}

$facebook_status   = esc_html(wprentals_get_option('wp_estate_facebook_login', ''));
$google_status     = esc_html(wprentals_get_option('wp_estate_google_login', ''));
$twitter_status    = esc_html(wprentals_get_option('wp_estate_twiter_login', ''));
$login_modal_class = '';


$login_modal_height = 450;
$control_height     = 550;

if ($social_register_on === 'yes') {
    if ($facebook_status === 'yes') {
        $login_modal_height = $login_modal_height + 50;
    }
    if ($google_status === 'yes') {
        $login_modal_height = $login_modal_height + 50;
    }
    if ($twitter_status === 'yes') {
        $login_modal_height = $login_modal_height + 50;
    }
}

if ($separate_users === 'yes') {
    $login_modal_height = $login_modal_height + 44;
}

if ($enable_user_pass_status === 'yes') {
    $login_modal_height = $login_modal_height + 140;
}

if ($wp_estate_use_captcha === 'yes') {
    $login_modal_height = $login_modal_height + 79;
}

if ($login_modal_height < $control_height) {
    $login_modal_height = $control_height;
}
//#loginmodal.with_social .modal-dialog

$wp_estate_enable_user_phone = esc_html(wprentals_get_option('wp_estate_enable_user_phone', ''));
if ($wp_estate_enable_user_phone === 'yes') {
    $login_modal_height = $login_modal_height + 70;
}

?>

<!-- Modal -->
<div class="modal fade " id="loginmodal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="height:<?= $login_modal_height; ?>px">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>

            <div class="modal-body">
                <div id="ajax_login_div" class="<?= esc_html($show_login); ?> ">
                    <div class="modal-header">
                        <h2 class="modal-title_big">
                            <?= esc_html__('Log in to your account', 'wprentals'); ?>
                        </h2>
                    </div>
                    <div class="loginalert" id="login_message_area"></div>
                    <div class="loginrow password_holder">
                        <input type="text" class="form-control dofocus" name="log" id="login_user" autofocus
                               placeholder="<?= esc_html__('Username', 'wprentals'); ?>" size="20"/>
                    </div>
                    <div class="loginrow password_holder">
                        <input type="password" class="form-control" name="pwd"
                               placeholder="<?= esc_html__('Password', 'wprentals'); ?>" id="login_pwd" size="20"/>
                        <i class=" far fa-eye-slash show_hide_password"></i>
                    </div>
                    <input type="hidden" name="loginpop" id="loginpop" value="<?= esc_attr($ispop); ?>">
                    <input type="hidden" id="security-login" name="security-login"
                           value="<?= estate_create_onetime_nonce('login_ajax_nonce'); ?>">
                    <button id="wp-login-but" data-mixval="<?= esc_attr($wpestate_propid); ?>"
                            class="wpb_button wpb_btn-info wpb_regularsize  wpestate_vc_button  vc_button">
                        <?= esc_html__('Login', 'wprentals'); ?>
                    </button>
                    <div class="navigation_links">
                        <a href="#" id="reveal_register">
                            <?= esc_html__('Don\'t have an account?', 'wprentals'); ?>
                        </a>
                        |
                        <a href="#" id="forgot_password_mod">
                            <?= esc_html__('Forgot Password', 'wprentals'); ?>
                        </a>
                    </div>

                    <?php
                    if ($facebook_status === 'yes' || $google_status === 'yes' || $twitter_status === 'yes') { ?>
                        <div class="login-links">
                            <?php
                            if (class_exists('Wpestate_Social_Login')) {
                                echo trim($wpestate_social_login->display_form('', 1));
                            }
                            ?>
                        </div> <!-- end login links-->
                        <?php
                    } ?>

                </div><!-- /.ajax_login_div -->

                <div id="ajax_register_div" class="<?= esc_attr($show_register); ?>">
                    <div class="modal-header">
                        <h2 class="modal-title_big">
                            <?= esc_html__('Create an account', 'wprentals'); ?>
                        </h2>
                    </div>

                    <?= do_shortcode('[register_form type=""][/register_form]'); ?>

                    <div class="navigation_links">
                        <a href="#" id="reveal_login">
                            <?= esc_html__('Already a member? Sign in!', 'wprentals'); ?>
                        </a>
                    </div>

                    <?php
                    if ($social_register_on === 'yes') { ?>
                        <div class="login-links">
                            <?php
                            if (class_exists('Wpestate_Social_Login')) {
                                echo trim($wpestate_social_login->display_form('register', 1));
                            }
                            ?>
                        </div> <!-- end login links-->
                        <?php
                    } ?>

                </div>

                <div id="forgot-pass-div_mod" style="display:none;">
                    <div class="modal-header">
                        <h2 class="modal-title_big">
                            <?= esc_html__('Forgot Password', 'wprentals'); ?>
                        </h2>
                    </div>
                    <div class="loginalert" id="forgot_pass_area_shortcode"></div>
                    <div class="loginrow">
                        <input type="text" class="form-control forgot_email_mod" name="forgot_email"
                               id="forgot_email_mod"
                               placeholder="<?= esc_html__('Enter Your Email Address', 'wprentals'); ?>" size="20"/>
                    </div>
                    <?php
                    wp_nonce_field('login_ajax_nonce_forgot_wd', 'security-login-forgot_wd', true); ?>
                    <input type="hidden" id="postid" value="0">
                    <button class="wpb_button  wpb_btn-info  wpb_regularsize wpestate_vc_button  vc_button"
                            id="wp-forgot-but_mod" name="forgot">
                        <?= esc_html__('Reset Password', 'wprentals'); ?>
                    </button>
                    <div class="navigation_links">
                        <a href="#" id="return_login_mod">
                            <?= esc_html__('Return to Login', 'wprentals'); ?>
                        </a>
                    </div>
                </div>
                <div class="modal_login_image_wrapper">
                    <div class="modal_login_image" style="background-image:url(<?= esc_url($modal_image); ?>)"></div>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
</div> <!-- /.login model -->
