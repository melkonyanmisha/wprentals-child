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