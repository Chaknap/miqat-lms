<?php
/**
 * Activation hook - Create database tables and set default options
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Activator {
    
    public static function activate() {
        self::create_database_tables();
        self::set_default_options();
        self::schedule_cron_events();
        
        flush_rewrite_rules();
    }
    
    private static function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Payment history table
        $sql_payments = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}isma_payments (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_date datetime NOT NULL,
            next_payment_date datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            payment_method varchar(50) DEFAULT NULL,
            transaction_id varchar(100) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Exercise submissions table
        $sql_submissions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}isma_exercise_submissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            exercise_id bigint(20) NOT NULL,
            student_id bigint(20) NOT NULL,
            submission_file varchar(255) DEFAULT NULL,
            submission_text text DEFAULT NULL,
            submission_date datetime DEFAULT CURRENT_TIMESTAMP,
            deadline datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'submitted',
            grade decimal(5,2) DEFAULT NULL,
            feedback text DEFAULT NULL,
            graded_by bigint(20) DEFAULT NULL,
            graded_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY exercise_id (exercise_id),
            KEY student_id (student_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Course charters table (admin workflow)
        $sql_charters = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}isma_course_charters (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            course_id bigint(20) NOT NULL,
            submitted_by bigint(20) NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            reviewed_by bigint(20) DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            charter_notes text DEFAULT NULL,
            published_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY course_id (course_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Exercise charters table
        $sql_exercise_charters = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}isma_exercise_charters (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            exercise_id bigint(20) NOT NULL,
            submitted_by bigint(20) NOT NULL,
            submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
            reviewed_by bigint(20) DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            charter_notes text DEFAULT NULL,
            published_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY exercise_id (exercise_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Proposals table
        $sql_proposals = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}isma_proposals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            trainer_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            category varchar(50) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            admin_feedback text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trainer_id (trainer_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Alumni forum posts table
        $sql_forum_posts = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}isma_forum_posts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            parent_id bigint(20) DEFAULT 0,
            user_id bigint(20) NOT NULL,
            post_type varchar(20) NOT NULL DEFAULT 'discussion',
            title varchar(255) DEFAULT NULL,
            content text NOT NULL,
            job_details text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status varchar(20) NOT NULL DEFAULT 'active',
            views_count int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY parent_id (parent_id),
            KEY user_id (user_id),
            KEY post_type (post_type)
        ) $charset_collate;";
        
        // Notification history table
        $sql_notifications = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}isma_notifications (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            notification_type varchar(50) NOT NULL,
            subject varchar(255) NOT NULL,
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY is_read (is_read)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_payments);
        dbDelta($sql_submissions);
        dbDelta($sql_charters);
        dbDelta($sql_exercise_charters);
        dbDelta($sql_proposals);
        dbDelta($sql_forum_posts);
        dbDelta($sql_notifications);
    }
    
    private static function set_default_options() {
        add_option('isma_ep_version', ISMA_EP_VERSION);
        add_option('isma_ep_payment_reminder_days', 7);
        add_option('isma_ep_exercise_deadline_notification', 3);
        add_option('isma_ep_admin_email', get_option('admin_email'));
    }
    
    private static function schedule_cron_events() {
        if (!wp_next_scheduled('isma_ep_payment_reminder_hook')) {
            wp_schedule_event(time(), 'daily', 'isma_ep_payment_reminder_hook');
        }
        
        if (!wp_next_scheduled('isma_exercise_deadline_check_hook')) {
            wp_schedule_event(time(), 'daily', 'isma_exercise_deadline_check_hook');
        }
    }
}
