<?php
/**
 * Proposal System - Trainer can submit ideas/proposals
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Proposal_System {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_proposal_admin_menu'));
        add_shortcode('isma_trainer_proposals', array($this, 'trainer_proposals_shortcode'));
        add_action('wp_ajax_isma_submit_proposal', array($this, 'handle_submit_proposal'));
        add_action('wp_ajax_isma_review_proposal', array($this, 'handle_review_proposal'));
    }
    
    public function add_proposal_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=isma_course',
            __('Propositions', 'isma-education-platform'),
            __('Propositions', 'isma-education-platform'),
            'manage_isma_proposals',
            'isma-proposals',
            array($this, 'render_proposals_admin_page')
        );
    }
    
    public function render_proposals_admin_page() {
        global $wpdb;
        
        $proposals = $wpdb->get_results("
            SELECT p.*, u.display_name as trainer_name
            FROM {$wpdb->prefix}isma_proposals p
            LEFT JOIN {$wpdb->users} u ON p.trainer_id = u.ID
            ORDER BY p.created_at DESC
        ");
        
        // Get history
        $history = $wpdb->get_results("
            SELECT p.*, u.display_name as trainer_name
            FROM {$wpdb->prefix}isma_proposals p
            LEFT JOIN {$wpdb->users} u ON p.trainer_id = u.ID
            WHERE p.status != 'pending'
            ORDER BY p.updated_at DESC
            LIMIT 50
        ");
        ?>
        <div class="wrap">
            <h1><?php _e('Gestion des Propositions', 'isma-education-platform'); ?></h1>
            
            <h2><?php _e('Toutes les Propositions', 'isma-education-platform'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Titre', 'isma-education-platform'); ?></th>
                        <th><?php _e('Formateur', 'isma-education-platform'); ?></th>
                        <th><?php _e('Catégorie', 'isma-education-platform'); ?></th>
                        <th><?php _e('Statut', 'isma-education-platform'); ?></th>
                        <th><?php _e('Date', 'isma-education-platform'); ?></th>
                        <th><?php _e('Action', 'isma-education-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proposals)): ?>
                        <tr><td colspan="6"><?php _e('Aucune proposition.', 'isma-education-platform'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($proposals as $proposal): ?>
                            <tr>
                                <td><?php echo esc_html($proposal->title); ?></td>
                                <td><?php echo esc_html($proposal->trainer_name); ?></td>
                                <td><?php echo esc_html($proposal->category); ?></td>
                                <td><span class="status-<?php echo esc_attr($proposal->status); ?>"><?php echo esc_html(ucfirst($proposal->status)); ?></span></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($proposal->created_at)); ?></td>
                                <td>
                                    <button class="button view-proposal-btn" data-id="<?php echo $proposal->id; ?>">
                                        <?php _e('Voir', 'isma-education-platform'); ?>
                                    </button>
                                    <?php if ($proposal->status === 'pending'): ?>
                                        <button class="button button-primary review-proposal-btn" data-id="<?php echo $proposal->id; ?>">
                                            <?php _e('Réviser', 'isma-education-platform'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <h2><?php _e('Historique de Prise en Charge', 'isma-education-platform'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Titre', 'isma-education-platform'); ?></th>
                        <th><?php _e('Formateur', 'isma-education-platform'); ?></th>
                        <th><?php _e('Statut Final', 'isma-education-platform'); ?></th>
                        <th><?php _e('Feedback Admin', 'isma-education-platform'); ?></th>
                        <th><?php _e('Date', 'isma-education-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="5"><?php _e('Aucun historique.', 'isma-education-platform'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($history as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->title); ?></td>
                                <td><?php echo esc_html($item->trainer_name); ?></td>
                                <td><?php echo esc_html(ucfirst($item->status)); ?></td>
                                <td><?php echo esc_html($item->admin_feedback); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($item->updated_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function trainer_proposals_shortcode($atts) {
        if (!current_user_can('create_proposals')) {
            return '<p>' . __('Accès non autorisé.', 'isma-education-platform') . '</p>';
        }
        
        global $wpdb;
        $trainer_id = get_current_user_id();
        
        $proposals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}isma_proposals WHERE trainer_id = %d ORDER BY created_at DESC",
            $trainer_id
        ));
        
        ob_start();
        ?>
        <div class="isma-trainer-proposals">
            <h2><?php _e('Espace Proposition', 'isma-education-platform'); ?></h2>
            
            <button class="button button-primary" id="new-proposal-btn">
                <?php _e('Nouvelle Proposition', 'isma-education-platform'); ?>
            </button>
            
            <h3><?php _e('Mes Propositions', 'isma-education-platform'); ?></h3>
            <?php if (empty($proposals)): ?>
                <p><?php _e('Aucune proposition soumise.', 'isma-education-platform'); ?></p>
            <?php else: ?>
                <table class="proposals-table">
                    <thead>
                        <tr>
                            <th><?php _e('Titre', 'isma-education-platform'); ?></th>
                            <th><?php _e('Catégorie', 'isma-education-platform'); ?></th>
                            <th><?php _e('Statut', 'isma-education-platform'); ?></th>
                            <th><?php _e('Date', 'isma-education-platform'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proposals as $proposal): ?>
                            <tr>
                                <td><?php echo esc_html($proposal->title); ?></td>
                                <td><?php echo esc_html($proposal->category); ?></td>
                                <td><span class="status-<?php echo esc_attr($proposal->status); ?>"><?php echo esc_html(ucfirst($proposal->status)); ?></span></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($proposal->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_submit_proposal() {
        check_ajax_referer('isma_ep_nonce', 'nonce');
        
        if (!current_user_can('create_proposals')) {
            wp_send_json_error(array('message' => __('Permission refusée.', 'isma-education-platform')));
        }
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'isma_proposals',
            array(
                'trainer_id' => get_current_user_id(),
                'title' => sanitize_text_field($_POST['title']),
                'description' => sanitize_textarea_field($_POST['description']),
                'category' => sanitize_text_field($_POST['category']),
                'status' => 'pending',
                'created_at' => current_time('mysql'),
            )
        );
        
        if ($result) {
            wp_send_json_success(array('message' => __('Proposition soumise avec succès!', 'isma-education-platform')));
        } else {
            wp_send_json_error(array('message' => __('Erreur lors de la soumission.', 'isma-education-platform')));
        }
    }
    
    public function handle_review_proposal() {
        check_ajax_referer('isma_ep_nonce', 'nonce');
        
        if (!current_user_can('manage_isma_proposals')) {
            wp_send_json_error(array('message' => __('Permission refusée.', 'isma-education-platform')));
        }
        
        global $wpdb;
        $proposal_id = intval($_POST['proposal_id']);
        $status = sanitize_text_field($_POST['status']);
        $feedback = sanitize_textarea_field($_POST['feedback']);
        
        $result = $wpdb->update(
            $wpdb->prefix . 'isma_proposals',
            array(
                'status' => $status,
                'admin_feedback' => $feedback,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $proposal_id)
        );
        
        if ($result !== false) {
            // Notify trainer
            $proposal = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}isma_proposals WHERE id = %d",
                $proposal_id
            ));
            
            ISMA_Email_Notifications::send_proposal_review_notification($proposal->trainer_id, $proposal_id, $status);
            
            wp_send_json_success(array('message' => __('Proposition révisée avec succès!', 'isma-education-platform')));
        } else {
            wp_send_json_error(array('message' => __('Erreur lors de la révision.', 'isma-education-platform')));
        }
    }
}
