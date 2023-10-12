<?php

$current_user        = wp_get_current_user();
$userID              = $current_user->ID;
$user_login          = $current_user->user_login;
$first_name          = get_the_author_meta('first_name', $userID);
$last_name           = get_the_author_meta('last_name', $userID);
$user_email          = get_the_author_meta('user_email', $userID);
$user_mobile         = get_the_author_meta('mobile', $userID);
$user_phone          = get_the_author_meta('phone', $userID);
$description         = get_the_author_meta('description', $userID);
$facebook            = get_the_author_meta('facebook', $userID);
$twitter             = get_the_author_meta('twitter', $userID);
$linkedin            = get_the_author_meta('linkedin', $userID);
$pinterest           = get_the_author_meta('pinterest', $userID);
$user_skype          = get_the_author_meta('skype', $userID);
$instagram           = get_the_author_meta('instagram', $userID);
$youtube             = get_the_author_meta('youtube', $userID);
$user_website        = get_the_author_meta('website', $userID);
$user_title          = get_the_author_meta('title', $userID);
$user_custom_picture = get_the_author_meta('custom_picture', $userID);
$user_small_picture  = get_the_author_meta('small_custom_picture', $userID);
$image_id            = get_the_author_meta('small_custom_picture', $userID);
$user_id_picture     = get_the_author_meta('user_id_image', $userID);
$id_image_id         = get_the_author_meta('user_id_image_id', $userID);
$about_me            = get_the_author_meta('description', $userID);
$live_in             = get_the_author_meta('live_in', $userID);
$i_speak             = get_the_author_meta('i_speak', $userID);
$paypal_payments_to  = get_the_author_meta('paypal_payments_to', $userID);
$payment_info        = get_the_author_meta('payment_info', $userID);

if ($user_custom_picture == '') {
    $user_custom_picture = get_stylesheet_directory_uri() . '/img/default_user.png';
}

if ($user_id_picture == '') {
    $user_id_picture = get_stylesheet_directory_uri() . '/img/default_user.png';
}
?>

<div class="user_profile_div wprentals_dashboard_container">
    <div class=" row">
        <div class="col-md-12">
            <?php
            include(locate_template('dashboard/templates/sms_validation.php'));
            ?>
        </div>
        <div class="col-md-9">
            <div class="user_dashboard_panel ">
                <h4 class="user_dashboard_panel_title">
                    <?= esc_html__('Your details', 'wprentals'); ?>
                </h4>
                <div class="col-md-12" id="profile_message"></div>
                <div class="col-md-6">
                    <p>
                        <label for="firstname">
                            <?= esc_html__('First Name', 'wprentals'); ?>
                        </label>
                        <input type="text" id="firstname" class="form-control" value="<?= esc_html($first_name); ?>"
                               name="firstname">
                    </p>
                    <p>
                        <label for="secondname">
                            <?= esc_html__('Last Name', 'wprentals'); ?>
                        </label>
                        <input type="text" id="secondname" class="form-control" value="<?= esc_html($last_name); ?>"
                               name="firstname">
                    </p>
                    <p>
                        <label for="useremail">
                            <?= esc_html__('Email', 'wprentals'); ?>
                        </label>
                        <input type="text" id="useremail" class="form-control" value="<?= esc_html($user_email); ?>"
                               name="useremail">
                    </p>
                    <p>
                        <label for="about_me">
                            <?= esc_html__('About Me', 'wprentals'); ?>
                        </label>
                        <textarea id="about_me" class="form-control about_me_profile" name="about_me"><?= esc_textarea(
                                $about_me
                            ); ?></textarea>
                    </p>
                    <p>
                        <label for="live_in">
                            <?= esc_html__('I live in', 'wprentals'); ?>
                        </label>
                        <input type="text" id="live_in" class="form-control" value="<?= esc_html($live_in); ?>"
                               name="live_in">
                    </p>
                    <p>
                        <label for="i_speak">
                            <?= esc_html__('I speak', 'wprentals'); ?>
                        </label>
                        <input type="text" id="i_speak" class="form-control" value="<?= esc_html($i_speak); ?>"
                               name="i_speak">
                    </p>
                </div>
                <div class="col-md-6">
                    <p>
                        <label for="userphone"><?= esc_html__('Phone', 'wprentals'); ?></label>
                        <input type="text" id="userphone" class="form-control" value="<?= esc_html($user_phone); ?>"
                               name="userphone">
                    </p>
                    <p>
                        <label for="usermobile">
                            <?= esc_html__('Mobile(*Add the country code format Ex :+1 232 3232)', 'wprentals'); ?>
                        </label>
                        <input type="text" id="usermobile" class="form-control" value="<?= esc_html($user_mobile); ?>"
                               name="usermobile">
                    </p>

                </div>
                <?php
                wp_nonce_field('profile_ajax_nonce', 'security-profile');
                $ajax_nonce = wp_create_nonce("wprentals_update_profile_nonce");
                ?>
                <p class="fullp-button">
                    <button class="wpb_btn-info wpb_btn-small wpestate_vc_button  vc_button" id="update_profile">
                        <?= esc_html__('Update profile', 'wprentals'); ?>
                    </button>
                    <input type="hidden" id="wprentals_update_profile_nonce" value="<?= esc_html($ajax_nonce); ?>"/>
                </p>
            </div>
        </div>
        <div class="col-md-3 profile_upload_image_wrapper">
            <div class="profile_upload_image user_dashboard_panel">
                <div class="">
                    <div id="profile-div" class="feature-media-upload">
                        <img id="profile-image" src="<?= esc_url($user_custom_picture); ?>"
                             alt="<?= esc_html__('thumb', 'wprentals'); ?>"
                             data-profileurl="<?= esc_attr($user_custom_picture); ?>"
                             data-smallprofileurl="<?= esc_attr($image_id); ?>">

                        <div id="upload-container">
                            <div id="aaiu-upload-container">
                                <button id="aaiu-uploader"
                                        class="wpb_btn-info wpb_btn-small wpestate_vc_button  vc_button">
                                    <?= esc_html__('Upload Image', 'wprentals'); ?>
                                </button>
                                <div id="profile-div-upload-imagelist">
                                    <ul id="aaiu-ul-list" class="aaiu-upload-list"></ul>
                                </div>
                            </div>
                        </div>
                        <span class="upload_explain">
                            <?= esc_html__('* recommended size: minimum 550px', 'wprentals'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <?php
            include(locate_template('dashboard/templates/change_password.php'));
            ?>
        </div>
    </div>