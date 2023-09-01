jQuery(document).ready(function ($) {
    if (wprentalsChildData.isHomePage) {
        if (wprentalsChildData.currentUserRole === 'timeshare_user') {
            $('.rooms-categories-section').remove();
        } else if (wprentalsChildData.currentUserRole === 'guest') {
            $('.rooms-groups-section').remove();
        }
    }
})