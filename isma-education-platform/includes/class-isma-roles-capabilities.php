<?php
/**
 * Register custom roles and capabilities
 * Roles: Super Admin ISMA, Modérateur, Formateur, Étudiant, Alumni
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Roles_Capabilities {
    
    public function __construct() {
        add_action('init', array($this, 'add_custom_roles'));
        add_action('admin_init', array($this, 'add_custom_capabilities'));
    }
    
    public function add_custom_roles() {
        // Remove default roles first if they exist (to avoid conflicts on re-activation)
        remove_role('isma_super_admin');
        remove_role('isma_moderator');
        remove_role('isma_trainer');
        remove_role('isma_student');
        remove_role('isma_alumni');
        
        // Super Admin ISMA (full access)
        add_role('isma_super_admin', __('Super Admin ISMA', 'isma-education-platform'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'upload_files' => true,
            'manage_options' => true,
            'edit_theme_options' => true,
            'manage_categories' => true,
            'manage_links' => true,
            'moderate_comments' => true,
            'activate_plugins' => true,
            'edit_plugins' => true,
            'edit_users' => true,
            'edit_themes' => true,
            'delete_users' => true,
            'create_users' => true,
            'delete_themes' => true,
            'export' => true,
            'import' => true,
            'list_users' => true,
            'promote_users' => true,
            'remove_users' => true,
            // Custom capabilities
            'manage_isma_courses' => true,
            'manage_isma_exercises' => true,
            'manage_isma_payments' => true,
            'manage_isma_proposals' => true,
            'charter_courses' => true,
            'charter_exercises' => true,
            'publish_isma_courses' => true,
            'publish_isma_exercises' => true,
            'view_all_submissions' => true,
            'grade_submissions' => true,
            'manage_isma_forum' => true,
            'manage_isma_news' => true,
            'send_isma_notifications' => true,
            'view_isma_reports' => true,
        ));
        
        // Modérateur (3 comptes - suivi de l'activité)
        add_role('isma_moderator', __('Modérateur', 'isma-education-platform'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => false,
            'publish_posts' => true,
            'upload_files' => true,
            'moderate_comments' => true,
            'manage_categories' => true,
            'list_users' => true,
            // Custom capabilities
            'manage_isma_courses' => true,
            'manage_isma_exercises' => true,
            'charter_courses' => true,
            'charter_exercises' => true,
            'publish_isma_courses' => true,
            'publish_isma_exercises' => true,
            'view_all_submissions' => true,
            'grade_submissions' => true,
            'manage_isma_forum' => true,
            'manage_isma_news' => true,
            'send_isma_notifications' => true,
            'view_isma_reports' => true,
            'view_payment_history' => true,
        ));
        
        // Formateur
        add_role('isma_trainer', __('Formateur', 'isma-education-platform'), array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => false,
            'upload_files' => true,
            'moderate_comments' => true,
            // Custom capabilities
            'create_isma_courses' => true,
            'edit_isma_courses' => true,
            'delete_isma_courses' => true,
            'create_isma_exercises' => true,
            'edit_isma_exercises' => true,
            'delete_isma_exercises' => true,
            'submit_for_charter' => true,
            'view_own_submissions' => true,
            'grade_own_submissions' => true,
            'create_proposals' => true,
            'view_own_payments' => true,
            'view_own_reports' => true,
        ));
        
        // Étudiant
        add_role('isma_student', __('Étudiant', 'isma-education-platform'), array(
            'read' => true,
            'upload_files' => true,
            // Custom capabilities
            'view_isma_courses' => true,
            'view_isma_exercises' => true,
            'submit_exercise' => true,
            'view_own_grades' => true,
            'view_own_payments' => true,
            'view_isma_news' => true,
            'participate_forum' => true,
        ));
        
        // Alumni
        add_role('isma_alumni', __('Alumni', 'isma-education-platform'), array(
            'read' => true,
            'upload_files' => true,
            // Custom capabilities
            'participate_forum' => true,
            'create_forum_discussion' => true,
            'post_job_offer' => true,
            'view_job_offers' => true,
        ));
    }
    
    public function add_custom_capabilities() {
        // Add capabilities to administrator as well
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_isma_courses');
            $admin->add_cap('manage_isma_exercises');
            $admin->add_cap('manage_isma_payments');
            $admin->add_cap('manage_isma_proposals');
            $admin->add_cap('charter_courses');
            $admin->add_cap('charter_exercises');
            $admin->add_cap('publish_isma_courses');
            $admin->add_cap('publish_isma_exercises');
            $admin->add_cap('view_all_submissions');
            $admin->add_cap('grade_submissions');
            $admin->add_cap('manage_isma_forum');
            $admin->add_cap('manage_isma_news');
            $admin->add_cap('send_isma_notifications');
            $admin->add_cap('view_isma_reports');
        }
    }
}
