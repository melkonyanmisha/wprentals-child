<?php

/**
 * Handle ajax requests
 * add_action('wp_ajax_nopriv_wpestate_ajax_register_form', 'wpestate_ajax_register_form');
 * add_action('wp_ajax_wpestate_ajax_register_form', 'wpestate_ajax_register_form');
 *
 * @return void
 */
function wpestate_ajax_register_form(): void
{
    check_ajax_referer('wpestate_ajax_log_reg_nonce', 'security');

    $captcha = sanitize_text_field($_POST['capthca']);

    if (wprentals_get_option('wp_estate_use_captcha', '') == 'yes') {
        if ( ! isset($_POST['capthca']) || $_POST['capthca'] == '') {
            wp_die(
                json_encode(
                    ['register' => false, 'message' => esc_html__('Please confirm you are not a robot!', 'wprentals')]
                )
            );
        }

        $secret = wprentals_get_option('wp_estate_recaptha_secretkey', '');

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }

        $cappval  = esc_html($_POST['capthca']);
        $response = wpestate_return_recapthca($secret, $cappval);

        if ( ! $response['success']) {
            wp_die(
                json_encode([
                    'register' => false,
                    'message'  => esc_html__('Please confirm you are not a robot!', 'wprentals')
                ])
            );
        }
    }

    $allowed_html = [];
    $user_email   = isset($_POST['user_email_register']) ? trim(
        sanitize_text_field($_POST['user_email_register'])
    ) : '';
    $user_name    = isset($_POST['user_login_register']) ? trim(
        sanitize_text_field($_POST['user_login_register'])
    ) : '';
    $user_phone   = isset($_POST['user_phone']) ? trim(sanitize_text_field($_POST['user_phone'])) : '';

    if (preg_match("/^[0-9A-Za-z_]+$/", $user_name) == 0) {
        wp_die(
            json_encode([
                'register' => false,
                'message'  => esc_html__('Invalid username (do not use special characters or spaces)!', 'wprentals')
            ])
        );
    }

    if ($user_email == '' || $user_name == '') {
        wp_die(
            json_encode([
                'register' => false,
                'message'  => esc_html__('Username and/or Email field is empty!', 'wprentals')
            ])
        );
    }

    if (filter_var($user_email, FILTER_VALIDATE_EMAIL) === false) {
        wp_die(
            json_encode([
                'register' => false,
                'message'  => esc_html__('The email doesn\'t look right!', 'wprentals')
            ])
        );
    }

    $domain = substr(strrchr($user_email, "@"), 1);
    if ( ! checkdnsrr($domain)) {
        wp_die(
            json_encode([
                'register' => false,
                'message'  => esc_html__('The email\'s domain doesn\'t look right!', 'wprentals')
            ])
        );
    }

    $wp_estate_enable_user_phone = esc_html(wprentals_get_option('wp_estate_enable_user_phone', ''));
    if ($wp_estate_enable_user_phone == 'yes' && $user_phone == '') {
        wp_die(
            json_encode([
                'register' => false,
                'message'  => esc_html__('The phone number field is mandatory', 'wprentals')
            ])
        );
    }

    $user_id = username_exists($user_name);
    if ($user_id) {
        wp_die(
            json_encode([
                'register' => false,
                'message'  => esc_html__('Username already exists.  Please choose a new one.!', 'wprentals')
            ])
        );
    }

    $enable_user_pass_status = esc_html(wprentals_get_option('wp_estate_enable_user_pass', ''));
    if ($enable_user_pass_status == 'yes') {
        $user_pass        = trim(sanitize_text_field(wp_kses($_POST['user_pass'], $allowed_html)));
        $user_pass_retype = trim(sanitize_text_field(wp_kses($_POST['user_pass_retype'], $allowed_html)));

        if ($user_pass == '' || $user_pass_retype == '') {
            wp_die(
                json_encode([
                    'register' => false,
                    'message'  => esc_html__('One of the password field is empty!', 'wprentals')
                ])
            );
        }

        if ($user_pass !== $user_pass_retype) {
            wp_die(
                json_encode([
                    'register' => false,
                    'message'  => esc_html__('Passwords do not match!', 'wprentals')
                ])
            );
        }
    }

    if ( ! $user_id and email_exists($user_email) == false) {
        if ($enable_user_pass_status == 'yes') {
            $random_password = $user_pass; // no so random now!
        } else {
            $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
        }

        $user_id = wp_create_user($user_name, $random_password, $user_email);

        if ( ! is_wp_error($user_id)) {
            if (isset($_POST['user_type'])) {
                update_user_meta($user_id, 'user_type', intval($_POST['user_type']));
            }

            if (isset($_POST['user_phone'])) {
                update_user_meta($user_id, 'mobile', sanitize_text_field($_POST['user_phone']));
                $agent_id = intval(get_user_meta($user_id, 'user_agent_id', true));
                if ($agent_id != 0) {
                    update_post_meta($agent_id, 'agent_mobile', sanitize_text_field($_POST['user_phone']));
                }
            }

            if ($enable_user_pass_status == 'yes') {
                wp_die(
                    json_encode([
                        'register' => true,
                        'message'  => esc_html__('The account was created. You can login now.', 'wprentals')
                    ])
                );
            } else {
                wp_die(
                    json_encode([
                        'register' => true,
                        'message'  => esc_html__('An email with the generated password was sent', 'wprentals')
                    ])
                );
            }

            wpestate_update_profile($user_id);
            wpestate_wp_new_user_notification($user_id, $random_password);

            if (intval($_POST['user_type']) == 0) {
                wpestate_register_as_user($user_name, $user_id);
            }
        }
    } else {
        wp_die(
            json_encode([
                'register' => false,
                'message'  => esc_html__('Email already exists.  Please choose a new one!', 'wprentals')
            ])
        );
    }
}