/**
 * ISMA Education Platform - Frontend JavaScript
 */
jQuery(document).ready(function($) {
    // Exercise submission handling is done in the shortcode
    
    // Mark notifications as read
    $('.notification-item').click(function() {
        var notificationId = $(this).data('id');
        if (notificationId) {
            $.post(ismaEP.ajaxUrl, {
                action: 'isma_mark_notification_read',
                notification_id: notificationId,
                nonce: ismaEP.nonce
            });
            $(this).removeClass('unread').addClass('read');
        }
    });
});
