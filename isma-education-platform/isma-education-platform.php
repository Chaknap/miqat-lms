<?php
/**
 * Plugin Name: ISMA Education Platform
 * Plugin URI: https://isma-edu.com
 * Description: Plateforme complète de gestion éducative avec cours, exercices, paiements, forum alumni et dashboards personnalisés.
 * Version: 1.0.0
 * Author: ISMA Development Team
 * Author URI: https://isma-edu.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: isma-education-platform
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ISMA_EP_VERSION', '1.0.0');
define('ISMA_EP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ISMA_EP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-activator.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-deactivator.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-post-types.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-roles-capabilities.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-course-manager.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-exercise-manager.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-payment-manager.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-alumni-forum.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-notification-system.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-dashboard.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-content-protection.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-admin-workflow.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-proposal-system.php';
require_once ISMA_EP_PLUGIN_DIR . 'includes/class-isma-email-notifications.php';

class ISMA_Education_Platform {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array('ISMA_Activator', 'activate'));
        register_deactivation_hook(__FILE__, array('ISMA_Deactivator', 'deactivate'));
        
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Initialize managers
        new ISMA_Post_Types();
        new ISMA_Roles_Capabilities();
        new ISMA_Course_Manager();
        new ISMA_Exercise_Manager();
        new ISMA_Payment_Manager();
        new ISMA_Alumni_Forum();
        new ISMA_Notification_System();
        new ISMA_Dashboard();
        new ISMA_Content_Protection();
        new ISMA_Admin_Workflow();
        new ISMA_Proposal_System();
        new ISMA_Email_Notifications();
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('isma-education-platform', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style('isma-ep-frontend', ISMA_EP_PLUGIN_URL . 'assets/css/frontend.css', array(), ISMA_EP_VERSION);
        wp_enqueue_script('isma-ep-frontend', ISMA_EP_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), ISMA_EP_VERSION, true);
        
        wp_localize_script('isma-ep-frontend', 'ismaEP', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('isma_ep_nonce')
        ));
    }
    
    public function enqueue_admin_assets() {
        wp_enqueue_style('isma-ep-admin', ISMA_EP_PLUGIN_URL . 'assets/css/admin.css', array(), ISMA_EP_VERSION);
        wp_enqueue_script('isma-ep-admin', ISMA_EP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ISMA_EP_VERSION, true);
    }
}

new ISMA_Education_Platform();
