<?php
/**
 * Admin Workflow - Course and exercise chartering process
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Admin_Workflow {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_workflow_meta_boxes'));
        add_action('save_post_isma_course', array($this, 'handle_course_submission'), 20, 2);
        add_action('save_post_isma_exercise', array($this, 'handle_exercise_submission'), 20, 2);
        add_action('admin_menu', array($this, 'add_charter_admin_menu'));
        add_action('wp_ajax_isma_charter_approve', array($this, 'handle_charter_approval'));
    }
    
    public function add_workflow_meta_boxes() {
        if (current_user_can('charter_courses') || current_user_can('charter_exercises')) {
            add_meta_box(
                'isma_workflow_status',
                __('Statut de Charte', 'isma-education-platform'),
                array($this, 'render_workflow_status_box'),
                array('isma_course', 'isma_exercise'),
                'side',
                'high'
            );
        }
    }
    
    public function render_workflow_status_box($post) {
        global $wpdb;
        $table = $post->post_type === 'isma_course' ? 'isma_course_charters' : 'isma_exercise_charters';
        
        $charter = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}{$table} WHERE course_id = %d OR exercise_id = %d ORDER BY submitted_at DESC LIMIT 1",
            $post->ID, $post->ID
        ));
        
        if ($charter):
        ?>
        <div class="workflow-status">
            <p><strong><?php _e('Statut:', 'isma-education-platform'); ?></strong> 
                <span class="status-<?php echo esc_attr($charter->status); ?>">
                    <?php echo esc_html(ucfirst($charter->status)); ?>
                </span>
            </p>
            <p><strong><?php _e('Soumis par:', 'isma-education-platform'); ?></strong> 
                <?php echo get_userdata($charter->submitted_by)->display_name; ?>
            </p>
            <p><strong><?php _e('Date de soumission:', 'isma-education-platform'); ?></strong> 
                <?php echo date_i18n(get_option('date_format'), strtotime($charter->submitted_at)); ?>
            </p>
            <?php if ($charter->reviewed_by): ?>
            <p><strong><?php _e('Révisé par:', 'isma-education-platform'); ?></strong> 
                <?php echo get_userdata($charter->reviewed_by)->display_name; ?>
            </p>
            <p><strong><?php _e('Date de révision:', 'isma-education-platform'); ?></strong> 
                <?php echo date_i18n(get_option('date_format'), strtotime($charter->reviewed_at)); ?>
            </p>
            <?php endif; ?>
            <?php if ($charter->charter_notes): ?>
            <p><strong><?php _e('Notes de charte:', 'isma-education-platform'); ?></strong></p>
            <p><?php echo esc_html($charter->charter_notes); ?></p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p><?php _e('En attente de soumission pour charte.', 'isma-education-platform'); ?></p>
        <?php endif; ?>
    }
    
    public function handle_course_submission($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('submit_for_charter')) return;
        
        // Only process on initial publish or status change to pending
        if ($post->post_status !== 'pending') return;
        
        global $wpdb;
        
        // Check if already submitted
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}isma_course_charters WHERE course_id = %d AND status = 'pending'",
            $post_id
        ));
        
        if (!$existing) {
            $wpdb->insert(
                $wpdb->prefix . 'isma_course_charters',
                array(
                    'course_id' => $post_id,
                    'submitted_by' => get_current_user_id(),
                    'submitted_at' => current_time('mysql'),
                    'status' => 'pending',
                )
            );
            
            // Notify admin
            $admins = get_users(array('role' => 'isma_super_admin'));
            foreach ($admins as $admin) {
                ISMA_Email_Notifications::send_course_charter_notification($admin->ID, $post_id);
            }
        }
    }
    
    public function handle_exercise_submission($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('submit_for_charter')) return;
        
        if ($post->post_status !== 'pending') return;
        
        global $wpdb;
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}isma_exercise_charters WHERE exercise_id = %d AND status = 'pending'",
            $post_id
        ));
        
        if (!$existing) {
            $wpdb->insert(
                $wpdb->prefix . 'isma_exercise_charters',
                array(
                    'exercise_id' => $post_id,
                    'submitted_by' => get_current_user_id(),
                    'submitted_at' => current_time('mysql'),
                    'status' => 'pending',
                )
            );
            
            // Notify admin
            $admins = get_users(array('role' => 'isma_super_admin'));
            foreach ($admins as $admin) {
                ISMA_Email_Notifications::send_exercise_charter_notification($admin->ID, $post_id);
            }
        }
    }
    
    public function add_charter_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=isma_course',
            __('Charte en Attente', 'isma-education-platform'),
            __('Charte en Attente', 'isma-education-platform'),
            'charter_courses',
            'isma-charter-pending',
            array($this, 'render_charter_pending_page')
        );
    }
    
    public function render_charter_pending_page() {
        global $wpdb;
        
        $courses_pending = $wpdb->get_results("
            SELECT c.*, u.display_name as trainer_name
            FROM {$wpdb->prefix}isma_course_charters c
            LEFT JOIN {$wpdb->users} u ON c.submitted_by = u.ID
            WHERE c.status = 'pending'
            ORDER BY c.submitted_at DESC
        ");
        
        $exercises_pending = $wpdb->get_results("
            SELECT c.*, u.display_name as trainer_name
            FROM {$wpdb->prefix}isma_exercise_charters c
            LEFT JOIN {$wpdb->users} u ON c.submitted_by = u.ID
            WHERE c.status = 'pending'
            ORDER BY c.submitted_at DESC
        ");
        ?>
        <div class="wrap">
            <h1><?php _e('Contenu en Attente de Charte', 'isma-education-platform'); ?></h1>
            
            <h2><?php _e('Cours en Attente', 'isma-education-platform'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Cours', 'isma-education-platform'); ?></th>
                        <th><?php _e('Formateur', 'isma-education-platform'); ?></th>
                        <th><?php _e('Date de Soumission', 'isma-education-platform'); ?></th>
                        <th><?php _e('Action', 'isma-education-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses_pending)): ?>
                        <tr><td colspan="4"><?php _e('Aucun cours en attente.', 'isma-education-platform'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($courses_pending as $item): 
                            $course = get_post($item->course_id);
                        ?>
                            <tr>
                                <td><?php echo esc_html($course->post_title); ?></td>
                                <td><?php echo esc_html($item->trainer_name); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($item->submitted_at)); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($course->ID); ?>" class="button">
                                        <?php _e('Voir', 'isma-education-platform'); ?>
                                    </a>
                                    <button class="button button-primary charter-approve-btn" data-type="course" data-id="<?php echo $item->id; ?>">
                                        <?php _e('Approuver et Publier', 'isma-education-platform'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <h2><?php _e('Exercices en Attente', 'isma-education-platform'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Exercice', 'isma-education-platform'); ?></th>
                        <th><?php _e('Formateur', 'isma-education-platform'); ?></th>
                        <th><?php _e('Date de Soumission', 'isma-education-platform'); ?></th>
                        <th><?php _e('Action', 'isma-education-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exercises_pending)): ?>
                        <tr><td colspan="4"><?php _e('Aucun exercice en attente.', 'isma-education-platform'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($exercises_pending as $item): 
                            $exercise = get_post($item->exercise_id);
                        ?>
                            <tr>
                                <td><?php echo esc_html($exercise->post_title); ?></td>
                                <td><?php echo esc_html($item->trainer_name); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($item->submitted_at)); ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($exercise->ID); ?>" class="button">
                                        <?php _e('Voir', 'isma-education-platform'); ?>
                                    </a>
                                    <button class="button button-primary charter-approve-btn" data-type="exercise" data-id="<?php echo $item->id; ?>">
                                        <?php _e('Approuver et Publier', 'isma-education-platform'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function handle_charter_approval() {
        check_ajax_referer('isma_ep_nonce', 'nonce');
        
        if (!current_user_can('charter_courses') && !current_user_can('charter_exercises')) {
            wp_send_json_error(array('message' => __('Permission refusée.', 'isma-education-platform')));
        }
        
        global $wpdb;
        $type = sanitize_text_field($_POST['type']);
        $charter_id = intval($_POST['charter_id']);
        
        $table = $type === 'course' ? 'isma_course_charters' : 'isma_exercise_charters';
        $post_type = $type === 'course' ? 'isma_course' : 'isma_exercise';
        $id_field = $type === 'course' ? 'course_id' : 'exercise_id';
        
        // Update charter status
        $wpdb->update(
            $wpdb->prefix . $table,
            array(
                'status' => 'approved',
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time('mysql'),
                'published_at' => current_time('mysql'),
            ),
            array('id' => $charter_id)
        );
        
        // Get the post ID
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT {$id_field} FROM {$wpdb->prefix}{$table} WHERE id = %d",
            $charter_id
        ));
        
        // Publish the post
        wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'publish',
        ));
        
        // Notify trainer
        $post = get_post($post_id);
        ISMA_Email_Notifications::send_charter_approved_notification($post->post_author, $post_id, $type);
        
        // Notify students about new content
        if ($type === 'course') {
            $students = get_users(array('role' => 'isma_student'));
            foreach ($students as $student) {
                ISMA_Email_Notifications::send_new_course_notification($student->ID, $post_id);
            }
        }
        
        wp_send_json_success(array('message' => __('Contenu approuvé et publié!', 'isma-education-platform')));
    }
}
