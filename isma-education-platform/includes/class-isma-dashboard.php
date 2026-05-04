<?php
/**
 * Dashboard System - Student and Trainer dashboards
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Dashboard {
    
    public function __construct() {
        add_shortcode('isma_student_dashboard', array($this, 'student_dashboard_shortcode'));
        add_shortcode('isma_trainer_dashboard', array($this, 'trainer_dashboard_shortcode'));
        add_action('wp_ajax_isma_generate_report', array($this, 'generate_training_report'));
    }
    
    public function student_dashboard_shortcode($atts) {
        if (!is_user_logged_in() || !current_user_can('view_isma_courses')) {
            return '<p>' . __('Accès non autorisé.', 'isma-education-platform') . '</p>';
        }
        
        global $wpdb;
        $student_id = get_current_user_id();
        
        // Get enrolled courses
        $courses_count = wp_count_posts('isma_course')->publish;
        
        // Get pending exercises
        $exercises_pending = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}isma_exercise_submissions 
             WHERE student_id = %d AND grade IS NULL",
            $student_id
        ));
        
        // Get grades
        $grades = $wpdb->get_results($wpdb->prepare(
            "SELECT s.grade, e.post_title as exercise_name, s.graded_at
             FROM {$wpdb->prefix}isma_exercise_submissions s
             LEFT JOIN {$wpdb->posts} e ON s.exercise_id = e.ID
             WHERE s.student_id = %d AND s.grade IS NOT NULL
             ORDER BY s.graded_at DESC
             LIMIT 10",
            $student_id
        ));
        
        // Get next payment
        $next_payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}isma_payments 
             WHERE user_id = %d AND next_payment_date >= CURDATE()
             ORDER BY next_payment_date ASC LIMIT 1",
            $student_id
        ));
        
        ob_start();
        ?>
        <div class="isma-student-dashboard">
            <h2><?php _e('Tableau de Bord Étudiant', 'isma-education-platform'); ?></h2>
            
            <div class="dashboard-widgets">
                <div class="widget">
                    <h3><?php _e('Cours Disponibles', 'isma-education-platform'); ?></h3>
                    <p class="stat-number"><?php echo intval($courses_count); ?></p>
                </div>
                
                <div class="widget">
                    <h3><?php _e('Exercices en Attente', 'isma-education-platform'); ?></h3>
                    <p class="stat-number"><?php echo intval($exercises_pending); ?></p>
                </div>
                
                <?php if ($next_payment): ?>
                <div class="widget">
                    <h3><?php _e('Prochain Paiement', 'isma-education-platform'); ?></h3>
                    <p class="stat-number"><?php echo date_i18n(get_option('date_format'), strtotime($next_payment->next_payment_date)); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-sections">
                <section class="my-grades">
                    <h3><?php _e('Mes Notes', 'isma-education-platform'); ?></h3>
                    <?php if (empty($grades)): ?>
                        <p><?php _e('Aucune note disponible.', 'isma-education-platform'); ?></p>
                    <?php else: ?>
                        <table class="grades-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Exercice', 'isma-education-platform'); ?></th>
                                    <th><?php _e('Note', 'isma-education-platform'); ?></th>
                                    <th><?php _e('Date', 'isma-education-platform'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $grade): ?>
                                    <tr>
                                        <td><?php echo esc_html($grade->exercise_name); ?></td>
                                        <td><strong><?php echo number_format($grade->grade, 2); ?>/20</strong></td>
                                        <td><?php echo date_i18n(get_option('date_format'), strtotime($grade->graded_at)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>
                
                <section class="school-life">
                    <h3><?php _e('Ma Vie à l\'École', 'isma-education-platform'); ?></h3>
                    <p><?php _e('Actualités et événements de l\'école...', 'isma-education-platform'); ?></p>
                    <?php echo do_shortcode('[isma_course_list limit="5"]'); ?>
                </section>
                
                <section class="news">
                    <h3><?php _e('Les Nouveautés', 'isma-education-platform'); ?></h3>
                    <?php 
                    $news = new WP_Query(array(
                        'post_type' => 'isma_news',
                        'posts_per_page' => 5,
                        'post_status' => 'publish'
                    ));
                    
                    if ($news->have_posts()):
                        while ($news->have_posts()): $news->the_post();
                    ?>
                        <article class="news-item">
                            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                            <div class="news-date"><?php echo get_the_date(); ?></div>
                        </article>
                    <?php 
                        endwhile;
                        wp_reset_postdata();
                    else:
                    ?>
                        <p><?php _e('Aucune actualité.', 'isma-education-platform'); ?></p>
                    <?php endif; ?>
                </section>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function trainer_dashboard_shortcode($atts) {
        if (!current_user_can('create_isma_courses')) {
            return '<p>' . __('Accès non autorisé.', 'isma-education-platform') . '</p>';
        }
        
        global $wpdb;
        $trainer_id = get_current_user_id();
        
        // Get trainer's courses
        $courses = get_posts(array(
            'post_type' => 'isma_course',
            'author' => $trainer_id,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        // Get submissions to grade
        $submissions_to_grade = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, e.post_title as exercise_name, u.display_name as student_name
             FROM {$wpdb->prefix}isma_exercise_submissions s
             LEFT JOIN {$wpdb->posts} e ON s.exercise_id = e.ID
             LEFT JOIN {$wpdb->users} u ON s.student_id = u.ID
             WHERE s.grade IS NULL
             ORDER BY s.submission_date DESC",
            $trainer_id
        ));
        
        // Count late submissions
        $late_submissions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}isma_exercise_submissions s
             WHERE s.grade IS NULL AND s.deadline IS NOT NULL 
             AND s.submission_date > s.deadline",
            $trainer_id
        ));
        
        ob_start();
        ?>
        <div class="isma-trainer-dashboard">
            <h2><?php _e('Tableau de Bord Formateur', 'isma-education-platform'); ?></h2>
            
            <div class="dashboard-widgets">
                <div class="widget">
                    <h3><?php _e('Mes Cours', 'isma-education-platform'); ?></h3>
                    <p class="stat-number"><?php echo count($courses); ?></p>
                </div>
                
                <div class="widget">
                    <h3><?php _e('À Noter', 'isma-education-platform'); ?></h3>
                    <p class="stat-number"><?php echo count($submissions_to_grade); ?></p>
                </div>
                
                <?php if ($late_submissions): ?>
                <div class="widget warning">
                    <h3><?php _e('Retards', 'isma-education-platform'); ?></h3>
                    <p class="stat-number" style="color: red;"><?php echo intval($late_submissions); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-sections">
                <section class="space-courses">
                    <h3><?php _e('Espace Cours', 'isma-education-platform'); ?></h3>
                    <a href="<?php echo admin_url('post-new.php?post_type=isma_course'); ?>" class="button button-primary">
                        <?php _e('Créer un cours', 'isma-education-platform'); ?>
                    </a>
                    <ul class="course-list">
                        <?php foreach ($courses as $course): ?>
                            <li>
                                <a href="<?php echo get_edit_post_link($course->ID); ?>">
                                    <?php echo esc_html($course->post_title); ?>
                                </a>
                                <span class="status status-<?php echo $course->post_status; ?>">
                                    <?php echo esc_html($course->post_status); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                
                <section class="space-exercises">
                    <h3><?php _e('Espace Exercices', 'isma-education-platform'); ?></h3>
                    <a href="<?php echo admin_url('post-new.php?post_type=isma_exercise'); ?>" class="button button-primary">
                        <?php _e('Créer un exercice', 'isma-education-platform'); ?>
                    </a>
                </section>
                
                <section class="submissions-to-grade">
                    <h3><?php _e('Soumissions à Noter', 'isma-education-platform'); ?></h3>
                    <?php if (empty($submissions_to_grade)): ?>
                        <p><?php _e('Aucune soumission en attente.', 'isma-education-platform'); ?></p>
                    <?php else: ?>
                        <table class="submissions-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Exercice', 'isma-education-platform'); ?></th>
                                    <th><?php _e('Étudiant', 'isma-education-platform'); ?></th>
                                    <th><?php _e('Date', 'isma-education-platform'); ?></th>
                                    <th><?php _e('Statut', 'isma-education-platform'); ?></th>
                                    <th><?php _e('Action', 'isma-education-platform'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions_to_grade as $submission): 
                                    $is_late = $submission->deadline && strtotime($submission->submission_date) > strtotime($submission->deadline);
                                ?>
                                    <tr class="<?php echo $is_late ? 'late-submission-row' : ''; ?>">
                                        <td><?php echo esc_html($submission->exercise_name); ?></td>
                                        <td><?php echo esc_html($submission->student_name); ?></td>
                                        <td><?php echo date_i18n(get_option('date_format'), strtotime($submission->submission_date)); ?></td>
                                        <td>
                                            <?php if ($is_late): ?>
                                                <span style="color: red; font-weight: bold;"><?php _e('EN RETARD', 'isma-education-platform'); ?></span>
                                            <?php else: ?>
                                                <span><?php _e('À temps', 'isma-education-platform'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo get_edit_post_link(get_page_by_path($submission->exercise_name, OBJECT, 'isma_exercise')->ID); ?>" class="button">
                                                <?php _e('Noter', 'isma-education-platform'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>
                
                <section class="reports">
                    <h3><?php _e('Rapports de Formation', 'isma-education-platform'); ?></h3>
                    <?php foreach ($courses as $course): ?>
                        <div class="report-item">
                            <span><?php echo esc_html($course->post_title); ?></span>
                            <button class="button generate-report-btn" data-course-id="<?php echo $course->ID; ?>" data-course-name="<?php echo esc_attr($course->post_title); ?>">
                                <?php _e('Générer le rapport', 'isma-education-platform'); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </section>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.generate-report-btn').click(function(e) {
                e.preventDefault();
                var courseId = $(this).data('course-id');
                var courseName = $(this).data('course-name');
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'isma_generate_report',
                    course_id: courseId,
                    nonce: '<?php echo wp_create_nonce('isma_generate_report'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('<?php _e('Rapport généré avec succès!', 'isma-education-platform'); ?>\n' + response.data.report_name);
                    } else {
                        alert('<?php _e('Erreur lors de la génération du rapport.', 'isma-education-platform'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function generate_training_report() {
        check_ajax_referer('isma_generate_report', 'nonce');
        
        if (!current_user_can('view_own_reports')) {
            wp_send_json_error(array('message' => __('Permission refusée.', 'isma-education-platform')));
        }
        
        $course_id = intval($_POST['course_id']);
        $course = get_post($course_id);
        
        if (!$course) {
            wp_send_json_error(array('message' => __('Cours non trouvé.', 'isma-education-platform')));
        }
        
        $report_name = sprintf(
            __('Rapport de formation "%s" du %s', 'isma-education-platform'),
            $course->post_title,
            date('d-m-Y')
        );
        
        // Generate report logic here (could export to PDF, etc.)
        
        wp_send_json_success(array(
            'report_name' => $report_name,
            'course_id' => $course_id,
        ));
    }
}
