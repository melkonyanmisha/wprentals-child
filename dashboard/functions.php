<?php

/**
 * Original function location is wp-content/themes/wprentals/functions.php => wpestate_check_user_level()
 * Only administrators have access to all pages in the dashboard
 *
 * @return bool
 */
function wpestate_check_user_level(): bool
{
    return current_user_is_admin();
}

/**
 * Handle dropdowns for fees
 *
 * @param string $name
 * @param float $selected
 * @param int $rental_type
 * @param int $booking_type
 *
 * @return string
 */
function wpestate_dropdown_fee_select(string $name, float $selected, int $rental_type, int $booking_type): string
{
    $options_array = [
        0 => esc_html__('Single Fee', 'wprentals'),
        1 => ucfirst(wpestate_show_labels('per_night', $rental_type, $booking_type)),
        2 => esc_html__('Per Guest', 'wprentals'),
        3 => ucfirst(wpestate_show_labels('per_night', $rental_type, $booking_type)) . ' ' . esc_html__(
                'Per Guest',
                'wprentals'
            )
    ];

    // Start output buffering
    ob_start();
    ?>

    <select class="select_submit_price" name="<?= esc_attr($name); ?>" id="<?= esc_attr($name); ?>">
        <?php
        foreach ($options_array as $key => $option) { ?>
            <option value="<?= esc_attr($key); ?>" <?= $key == $selected ? 'selected' : ''; ?> >
                <?= esc_html($option); ?>
            </option>
            <?php
        } ?>
    </select>

    <?php
    // End output buffering
    return trim(ob_get_clean());
}