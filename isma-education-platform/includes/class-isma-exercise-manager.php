<?php
/**
 * Exercise Manager - Handle exercise creation, submission, deadlines, and grading
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Exercise_Manager {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_exercise_meta_boxes'));
        add_action('save_post_isma_exercise', array($this, 'save_exercise_meta'), 10, 2);
        add_shortcode('isma_exercise_list', array($this, 'exercise_list_shortcode'));
        add_shortcode('isma_exercise_submission', array($this, 'exercise_submission_shortcode'));
        add_action('wp_ajax_isma_submit_exercise', array($this, 'handle_exercise_submission'));
        add_action('wp_ajax_nopriv_isma_submit_exercise', array($this, 'handle_exercise_submission'));
    }
    
    public function add_exercise_meta_boxes() {
        add_meta_box(
            'isma_exercise_pdf',
            __('Fichier PDF de l\'exercice', 'isma-education-platform'),
            array($this, 'render_pdf_meta_box'),
            'isma_exercise',
            'side',
            'default'
        );
        
        add_meta_box(
            'isma_exercise_deadline',
            __('Date limite de dépôt', 'isma-education-platform'),
            array($this, 'render_deadline_meta_box'),
            'isma_exercise',
            'side',
            'default'
        );
        
        add_meta_box(
            'isma_exercise_submissions',
            __('Soumissions des étudiants', 'isma-education-platform'),
            array($this, 'render_submissions_meta_box'),
            'isma_exercise',
            'normal',
            'default'
        );
    }
    
    public function render_pdf_meta_box($post) {
        wp_nonce_field('isma_exercise_meta', 'isma_exercise_meta_nonce');
        $pdf_url = get_post_meta($post->ID, '_isma_exercise_pdf_url', true);
        ?>
        <p>
            <label for="isma_exercise_pdf_url"><?php _e('URL du fichier PDF:', 'isma-education-platform'); ?></label>
            <input type="text" id="isma_exercise_pdf_url" name="isma_exercise_pdf_url" 
                   value="<?php echo esc_url($pdf_url); ?>" class="regular-text" />
            <button type="button" class="button" id="upload_exercise_pdf_btn">
                <?php _e('Télécharger le PDF', 'isma-education-platform'); ?>
            </button>
        </p>
        <script>
        jQuery(document).ready(function($) {
            $('#upload_exercise_pdf_btn').click(function(e) {
                e.preventDefault();
                var mediaUploader = wp.media({
                    title: '<?php _e('Choisir un fichier PDF', 'isma-education-platform'); ?>',
                    button: { text: '<?php _e('Utiliser ce fichier', 'isma-education-platform'); ?>' },
                    library: { type: 'application/pdf' },
                    multiple: false
                });
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#isma_exercise_pdf_url').val(attachment.url);
                });
                mediaUploader.open();
            });
        });
        </script>
        <?php
    }
    
    public function render_deadline_meta_box($post) {
        $deadline = get_post_meta($post->ID, '_isma_exercise_deadline', true);
        $deadline_date = $deadline ? date('Y-m-d\TH:i', strtotime($deadline)) : '';
        ?>
        <p>
            <label for="isma_exercise_deadline"><?php _e('Date et heure limite:', 'isma-education-platform'); ?></label>
            <input type="datetime-local" id="isma_exercise_deadline" name="isma_exercise_deadline" 
                   value="<?php echo esc_attr($deadline_date); ?>" class="regular-text" />
        </p>
        <p class="description">
            <?php _e('Les soumissions après cette date seront marquées comme en retard.', 'isma-education-platform'); ?>
        </p>
        <?php
    }
    
    public function render_submissions_meta_box($post) {
        global $wpdb;
        $exercise_id = $post->ID;
        
        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name as student_name, u.user_email as student_email
             FROM {$wpdb->prefix}isma_exercise_submissions s
             LEFT JOIN {$wpdb->users} u ON s.student_id = u.ID
             WHERE s.exercise_id = %d
             ORDER BY s.submission_date DESC",
            $exercise_id
        ));
        
        $deadline = get_post_meta($exercise_id, '_isma_exercise_deadline', true);
        ?>
        <style>
            .submission-table { width: 100%; border-collapse: collapse; }
            .submission-table th, .submission-table td { padding: 8px; border: 1px solid #ddd; text-align: left; }
            .submission-table th { background: #f5f5f5; }
            .late-submission { background-color: #ffebee !important; }
            .grade-input { width: 60px; }
            .feedback-textarea { width: 100%; height: 80px; }
        </style>
        <table class="submission-table">
            <thead>
                <tr>
                    <th><?php _e('Étudiant', 'isma-education-platform'); ?></th>
                    <th><?php _e('Date de dépôt', 'isma-education-platform'); ?></th>
                    <th><?php _e('Statut', 'isma-education-platform'); ?></th>
                    <th><?php _e('Note', 'isma-education-platform'); ?></th>
                    <th><?php _e('Feedback', 'isma-education-platform'); ?></th>
                    <th><?php _e('Action', 'isma-education-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr>
                        <td colspan="6"><?php _e('Aucune soumission pour le moment.', 'isma-education-platform'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): 
                        $is_late = $deadline && strtotime($submission->submission_date) > strtotime($deadline);
                    ?>
                        <tr class="<?php echo $is_late ? 'late-submission' : ''; ?>">
                            <td>
                                <strong><?php echo esc_html($submission->student_name); ?></strong><br>
                                <small><?php echo esc_html($submission->student_email); ?></small>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission->submission_date)); ?></td>
                            <td>
                                <?php if ($is_late): ?>
                                    <span style="color: red; font-weight: bold;"><?php _e('EN RETARD', 'isma-education-platform'); ?></span>
                                <?php else: ?>
                                    <span style="color: green;"><?php _e('À temps', 'isma-education-platform'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($submission->grade): ?>
                                    <strong><?php echo number_format($submission->grade, 2); ?>/20</strong>
                                <?php else: ?>
                                    <input type="number" class="grade-input" min="0" max="20" step="0.01" 
                                           data-submission-id="<?php echo $submission->id; ?>" placeholder="/20" />
                                <?php endif; ?>
                            </td>
                            <td>
                                <textarea class="feedback-textarea" data-submission-id="<?php echo $submission->id; ?>" 
                                          placeholder="<?php _e('Votre feedback...', 'isma-education-platform'); ?>"><?php echo esc_textarea($submission->feedback); ?></textarea>
                            </td>
                            <td>
                                <button class="button button-primary save-grade-btn" data-submission-id="<?php echo $submission->id; ?>">
                                    <?php _e('Enregistrer', 'isma-education-platform'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <script>
        jQuery(document).ready(function($) {
            $('.save-grade-btn').click(function(e) {
                e.preventDefault();
                var submissionId = $(this).data('submission-id');
                var grade = $(this).siblings('.grade-input').val();
                var feedback = $(this).siblings('.feedback-textarea').val();
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'isma_save_exercise_grade',
                    submission_id: submissionId,
                    grade: grade,
                    feedback: feedback,
                    nonce: '<?php echo wp_create_nonce('isma_save_grade'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('Note enregistrée avec succès!', 'isma-education-platform'); ?>');
                        location.reload();
                    } else {
                        alert('<?php _e('Erreur lors de l\'enregistrement.', 'isma-education-platform'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    public function save_exercise_meta($post_id, $post) {
        if (!isset($_POST['isma_exercise_meta_nonce']) || 
            !wp_verify_nonce($_POST['isma_exercise_meta_nonce'], 'isma_exercise_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['isma_exercise_pdf_url'])) {
            update_post_meta($post_id, '_isma_exercise_pdf_url', esc_url_raw($_POST['isma_exercise_pdf_url']));
        }
        
        if (isset($_POST['isma_exercise_deadline']) && !empty($_POST['isma_exercise_deadline'])) {
            update_post_meta($post_id, '_isma_exercise_deadline', sanitize_text_field($_POST['isma_exercise_deadline']));
        } else {
            delete_post_meta($post_id, '_isma_exercise_deadline');
        }
    }
    
    public function exercise_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'theme' => '',
            'limit' => '-1',
        ), $atts);
        
        $args = array(
            'post_type' => 'isma_exercise',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
        );
        
        if (!empty($atts['theme'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'isma_theme',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($atts['theme']),
                ),
            );
        }
        
        $exercises = new WP_Query($args);
        
        if (!$exercises->have_posts()) {
            return '<p>' . __('Aucun exercice disponible.', 'isma-education-platform') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="isma-exercise-list">
            <?php while ($exercises->have_posts()): $exercises->the_post(); ?>
                <div class="isma-exercise-item">
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <div class="exercise-meta">
                        <?php 
                        $themes = get_the_terms(get_the_ID(), 'isma_theme');
                        if ($themes && !is_wp_error($themes)):
                            echo '<span class="exercise-theme">' . implode(', ', wp_list_pluck($themes, 'name')) . '</span>';
                        endif;
                        
                        $deadline = get_post_meta(get_the_ID(), '_isma_exercise_deadline', true);
                        if ($deadline):
                            echo '<span class="exercise-deadline"><strong>' . __('Date limite:', 'isma-education-platform') . '</strong> ' . 
                                 date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($deadline)) . '</span>';
                        endif;
                        ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    public function exercise_submission_shortcode($atts) {
        if (!is_user_logged_in() || !current_user_can('submit_exercise')) {
            return '<p>' . __('Vous devez être connecté pour déposer une réponse.', 'isma-education-platform') . '</p>';
        }
        
        global $post;
        $exercise_id = $post->ID;
        $student_id = get_current_user_id();
        $deadline = get_post_meta($exercise_id, '_isma_exercise_deadline', true);
        
        // Check if already submitted
        global $wpdb;
        $existing_submission = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}isma_exercise_submissions 
             WHERE exercise_id = %d AND student_id = %d",
            $exercise_id, $student_id
        ));
        
        ob_start();
        ?>
        <div class="isma-exercise-submission">
            <?php if ($existing_submission): ?>
                <div class="already-submitted">
                    <p><strong><?php _e('Vous avez déjà déposé votre réponse.', 'isma-education-platform'); ?></strong></p>
                    <p><?php _e('Date de dépôt:', 'isma-education-platform'); ?> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($existing_submission->submission_date)); ?></p>
                    <?php if ($existing_submission->grade): ?>
                        <p><strong><?php _e('Votre note:', 'isma-education-platform'); ?> <?php echo number_format($existing_submission->grade, 2); ?>/20</strong></p>
                        <?php if ($existing_submission->feedback): ?>
                            <p><strong><?php _e('Feedback du formateur:', 'isma-education-platform'); ?></strong></p>
                            <p><?php echo nl2br(esc_html($existing_submission->feedback)); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <form id="exercise-submission-form" enctype="multipart/form-data">
                    <input type="hidden" name="exercise_id" value="<?php echo $exercise_id; ?>" />
                    <?php wp_nonce_field('isma_submit_exercise', 'isma_exercise_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="submission_file"><?php _e('Déposer votre réponse (PDF):', 'isma-education-platform'); ?></label>
                        <input type="file" id="submission_file" name="submission_file" accept=".pdf" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="submission_text"><?php _e('Commentaire (optionnel):', 'isma-education-platform'); ?></label>
                        <textarea id="submission_text" name="submission_text" rows="4"></textarea>
                    </div>
                    
                    <?php if ($deadline): 
                        $now = current_time('timestamp');
                        $deadline_ts = strtotime($deadline);
                        $is_expired = $now > $deadline_ts;
                    ?>
                        <div class="deadline-info <?php echo $is_expired ? 'expired' : ''; ?>">
                            <p><strong><?php _e('Date limite:', 'isma-education-platform'); ?></strong> <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $deadline_ts); ?></p>
                            <?php if ($is_expired): ?>
                                <p style="color: red;"><strong><?php _e('La date limite est dépassée!', 'isma-education-platform'); ?></strong></p>
                            <?php else: ?>
                                <p><?php printf(__('Il reste %s jours avant la date limite.', 'isma-education-platform'), 
                                    floor(($deadline_ts - $now) / DAY_IN_SECONDS)); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="button button-primary" <?php echo (isset($is_expired) && $is_expired) ? 'disabled' : ''; ?>>
                        <?php _e('Déposer ma réponse', 'isma-education-platform'); ?>
                    </button>
                </form>
                
                <div id="submission-message"></div>
                
                <script>
                jQuery(document).ready(function($) {
                    $('#exercise-submission-form').submit(function(e) {
                        e.preventDefault();
                        
                        var formData = new FormData(this);
                        formData.append('action', 'isma_submit_exercise');
                        
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.success) {
                                    $('#submission-message').html('<p style="color: green;">' + response.data.message + '</p>');
                                    location.reload();
                                } else {
                                    $('#submission-message').html('<p style="color: red;">' + response.data.message + '</p>');
                                }
                            },
                            error: function() {
                                $('#submission-message').html('<p style="color: red;"><?php _e('Une erreur est survenue.', 'isma-education-platform'); ?></p>');
                            }
                        });
                    });
                });
                </script>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_exercise_submission() {
        check_ajax_referer('isma_submit_exercise', 'isma_exercise_nonce');
        
        if (!current_user_can('submit_exercise')) {
            wp_send_json_error(array('message' => __('Vous n\'avez pas la permission de soumettre un exercice.', 'isma-education-platform')));
        }
        
        $exercise_id = intval($_POST['exercise_id']);
        $student_id = get_current_user_id();
        
        // Check deadline
        $deadline = get_post_meta($exercise_id, '_isma_exercise_deadline', true);
        if ($deadline && current_time('timestamp') > strtotime($deadline)) {
            wp_send_json_error(array('message' => __('La date limite de dépôt est dépassée.', 'isma-education-platform')));
        }
        
        // Handle file upload
        $upload_file = $_FILES['submission_file'];
        $submission_text = isset($_POST['submission_text']) ? sanitize_textarea_field($_POST['submission_text']) : '';
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $allowed_types = array('application/pdf');
        $file_type = wp_check_filetype($upload_file['name']);
        
        if (!in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error(array('message' => __('Seuls les fichiers PDF sont autorisés.', 'isma-education-platform')));
        }
        
        $upload_overrides = array('test_form' => false);
        $move_file = wp_handle_upload($upload_file, $upload_overrides);
        
        if (isset($move_file['error'])) {
            wp_send_json_error(array('message' => $move_file['error']));
        }
        
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'isma_exercise_submissions',
            array(
                'exercise_id' => $exercise_id,
                'student_id' => $student_id,
                'submission_file' => $move_file['url'],
                'submission_text' => $submission_text,
                'deadline' => $deadline,
                'status' => 'submitted',
                'submission_date' => current_time('mysql'),
            )
        );
        
        if ($result) {
            // Notify trainer
            $trainer_id = get_post_field('post_author', $exercise_id);
            ISMA_Email_Notifications::send_exercise_submission_notification($trainer_id, $exercise_id, $student_id);
            
            wp_send_json_success(array('message' => __('Votre réponse a été déposée avec succès!', 'isma-education-platform')));
        } else {
            wp_send_json_error(array('message' => __('Une erreur est survenue lors du dépôt.', 'isma-education-platform')));
        }
    }
}
