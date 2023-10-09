jQuery(document).ready(function ($) {
    if (wprentalsChildData.isHomePage) {
        if (wprentalsChildData.currentUserRole === 'timeshare_user') {
            $('.rooms-categories-section').remove();
        } else if (wprentalsChildData.currentUserRole === 'customer' || wprentalsChildData.currentUserRole === 'guest') {
            $('.rooms-groups-section').remove();
        }
    }

    // The case when user logged in
    $('.user_loged #user_menu_trigger').on('click', function (event) {
        open_user_menu(event);
    });

    // The case when user logged in
    $('.user_loged .menu_user_picture').on('click', function (event) {
        open_user_menu(event);
    });

    /**
     * Open the user menu for logged-in users
     *
     * @param event
     */
    function open_user_menu(event) {
        jQuery('#wpestate_header_shoping_cart').fadeOut(400);
        if ($('#user_menu_open').is(":visible")) {
            $('#user_menu_open').removeClass('iosfixed').fadeOut(400);
        } else {
            $('#user_menu_open').fadeIn(400);
        }
        event.stopPropagation();
    }

})