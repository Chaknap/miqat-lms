<?php
/**
 * Notification System - In-app notifications
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Notification_System {
    
    public function __construct() {
        add_action('wp_ajax_isma_mark_notification_read', array($this, 'mark_as_read'));
        add_shortcode('isma_notifications', array($this, 'notifications_shortcode'));
    }
    
    public static function create_notification($user_id, $type, $subject, $message) {
        global $wpdb;
        
        return $wpdb->insert(
            $wpdb->prefix . 'isma_notifications',
            array(
                'user_id' => intval($user_id),
                'notification_type' => sanitize_text_field($type),
                'subject' => sanitize_text_field($subject),
                'message' => sanitize_textarea_field($message),
                'is_read' => 0,
                'sent_at' => current_time('mysql'),
            )
        );
    }
    
    public function notifications_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        global $wpdb;
        $user_id = get_current_user_id();
        
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}isma_notifications 
             WHERE user_id = %d 
             ORDER BY sent_at DESC 
             LIMIT 20",
            $user_id
        ));
        
        $unread_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}isma_notifications 
             WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
        
        ob_start();
        ?>
        <div class="isma-notifications">
            <h3><?php _e('Notifications', 'isma-education-platform'); ?> 
                <?php if ($unread_count): ?>
                    <span class="unread-count"><?php echo intval($unread_count); ?></span>
                <?php endif; ?>
            </h3>
            
            <?php if (empty($notifications)): ?>
                <p><?php _e('Aucune notification.', 'isma-education-platform'); ?></p>
            <?php else: ?>
                <ul class="notification-list">
                    <?php foreach ($notifications as $notification): ?>
                        <li class="notification-item <?php echo $notification->is_read ? 'read' : 'unread'; ?>" 
                            data-id="<?php echo $notification->id; ?>">
                            <div class="notification-subject"><?php echo esc_html($notification->subject); ?></div>
                            <div class="notification-message"><?php echo esc_html($notification->message); ?></div>
                            <div class="notification-date"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($notification->sent_at)); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function mark_as_read() {
        check_ajax_referer('isma_ep_nonce', 'nonce');
        
        global $wpdb;
        $notification_id = intval($_POST['notification_id']);
        
        $wpdb->update(
            $wpdb->prefix . 'isma_notifications',
            array('is_read' => 1),
            array('id' => $notification_id)
        );
        
        wp_send_json_success();
    }
}
