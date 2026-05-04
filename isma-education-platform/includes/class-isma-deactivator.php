<?php
/**
 * Deactivation hook - Clean up scheduled events
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Deactivator {
    
    public static function deactivate() {
        // Clear scheduled cron events
        wp_clear_scheduled_hook('isma_ep_payment_reminder_hook');
        wp_clear_scheduled_hook('isma_exercise_deadline_check_hook');
        
        flush_rewrite_rules();
    }
}
