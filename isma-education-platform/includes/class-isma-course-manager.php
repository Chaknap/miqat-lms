<?php
/**
 * Course Manager - Handle course creation, display, and protection
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Course_Manager {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_course_meta_boxes'));
        add_action('save_post_isma_course', array($this, 'save_course_meta'), 10, 2);
        add_filter('the_content', array($this, 'protect_course_content'));
        add_shortcode('isma_course_list', array($this, 'course_list_shortcode'));
        add_shortcode('isma_course_viewer', array($this, 'course_viewer_shortcode'));
    }
    
    public function add_course_meta_boxes() {
        add_meta_box(
            'isma_course_pdf',
            __('Fichier PDF du cours', 'isma-education-platform'),
            array($this, 'render_pdf_meta_box'),
            'isma_course',
            'side',
            'default'
        );
        
        add_meta_box(
            'isma_course_settings',
            __('Paramètres du cours', 'isma-education-platform'),
            array($this, 'render_settings_meta_box'),
            'isma_course',
            'normal',
            'default'
        );
    }
    
    public function render_pdf_meta_box($post) {
        wp_nonce_field('isma_course_meta', 'isma_course_meta_nonce');
        $pdf_url = get_post_meta($post->ID, '_isma_course_pdf_url', true);
        ?>
        <p>
            <label for="isma_course_pdf_url"><?php _e('URL du fichier PDF:', 'isma-education-platform'); ?></label>
            <input type="text" id="isma_course_pdf_url" name="isma_course_pdf_url" 
                   value="<?php echo esc_url($pdf_url); ?>" class="regular-text" />
            <button type="button" class="button" id="upload_pdf_btn">
                <?php _e('Télécharger le PDF', 'isma-education-platform'); ?>
            </button>
        </p>
        <p class="description">
            <?php _e('Les étudiants pourront consulter ce cours mais ne pourront pas le télécharger.', 'isma-education-platform'); ?>
        </p>
        <script>
        jQuery(document).ready(function($) {
            $('#upload_pdf_btn').click(function(e) {
                e.preventDefault();
                var mediaUploader = wp.media({
                    title: '<?php _e('Choisir un fichier PDF', 'isma-education-platform'); ?>',
                    button: { text: '<?php _e('Utiliser ce fichier', 'isma-education-platform'); ?>' },
                    library: { type: 'application/pdf' },
                    multiple: false
                });
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#isma_course_pdf_url').val(attachment.url);
                });
                mediaUploader.open();
            });
        });
        </script>
        <?php
    }
    
    public function render_settings_meta_box($post) {
        $is_protected = get_post_meta($post->ID, '_isma_course_protected', true);
        $allow_download = get_post_meta($post->ID, '_isma_course_allow_download', true);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="isma_course_protected"><?php _e('Protection contre capture', 'isma-education-platform'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="isma_course_protected" name="isma_course_protected" 
                           value="1" <?php checked($is_protected, '1'); ?> />
                    <label for="isma_course_protected">
                        <?php _e('Désactiver le clic droit et les captures d\'écran', 'isma-education-platform'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="isma_course_allow_download"><?php _e('Autoriser le téléchargement', 'isma-education-platform'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="isma_course_allow_download" name="isma_course_allow_download" 
                           value="1" <?php checked($allow_download, '1'); ?> />
                    <label for="isma_course_allow_download">
                        <?php _e('Permettre aux étudiants de télécharger le PDF', 'isma-education-platform'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function save_course_meta($post_id, $post) {
        if (!isset($_POST['isma_course_meta_nonce']) || 
            !wp_verify_nonce($_POST['isma_course_meta_nonce'], 'isma_course_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (isset($_POST['isma_course_pdf_url'])) {
            update_post_meta($post_id, '_isma_course_pdf_url', esc_url_raw($_POST['isma_course_pdf_url']));
        }
        
        if (isset($_POST['isma_course_protected'])) {
            update_post_meta($post_id, '_isma_course_protected', '1');
        } else {
            delete_post_meta($post_id, '_isma_course_protected');
        }
        
        if (isset($_POST['isma_course_allow_download'])) {
            update_post_meta($post_id, '_isma_course_allow_download', '1');
        } else {
            delete_post_meta($post_id, '_isma_course_allow_download');
        }
    }
    
    public function protect_course_content($content) {
        if (is_singular('isma_course')) {
            $protected = get_post_meta(get_the_ID(), '_isma_course_protected', true);
            if ($protected) {
                $content .= $this->get_protection_script();
            }
        }
        return $content;
    }
    
    private function get_protection_script() {
        ob_start();
        ?>
        <script>
        (function() {
            // Disable right-click
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
            
            // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F12' || 
                    (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J')) ||
                    (e.ctrlKey && e.key === 'U')) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Prevent drag and drop of images
            document.addEventListener('dragstart', function(e) {
                if (e.target.tagName === 'IMG') {
                    e.preventDefault();
                    return false;
                }
            });
            
            // Add CSS to prevent selection
            var style = document.createElement('style');
            style.textContent = `
                .isma-course-content {
                    -webkit-user-select: none;
                    -moz-user-select: none;
                    -ms-user-select: none;
                    user-select: none;
                    -webkit-touch-callout: none;
                }
                .isma-course-content img {
                    pointer-events: none;
                }
            `;
            document.head.appendChild(style);
        })();
        </script>
        <?php
        return '<div class="isma-course-content">' . ob_get_clean() . '</div>';
    }
    
    public function course_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'theme' => '',
            'limit' => '-1',
        ), $atts);
        
        $args = array(
            'post_type' => 'isma_course',
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
        
        $courses = new WP_Query($args);
        
        if (!$courses->have_posts()) {
            return '<p>' . __('Aucun cours disponible.', 'isma-education-platform') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="isma-course-list">
            <?php while ($courses->have_posts()): $courses->the_post(); ?>
                <div class="isma-course-item">
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <div class="course-meta">
                        <?php 
                        $themes = get_the_terms(get_the_ID(), 'isma_theme');
                        if ($themes && !is_wp_error($themes)):
                            echo '<span class="course-theme">' . implode(', ', wp_list_pluck($themes, 'name')) . '</span>';
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
    
    public function course_viewer_shortcode($atts) {
        if (!is_user_logged_in() || !current_user_can('view_isma_courses')) {
            return '<p>' . __('Vous devez être connecté pour consulter les cours.', 'isma-education-platform') . '</p>';
        }
        
        global $post;
        $pdf_url = get_post_meta($post->ID, '_isma_course_pdf_url', true);
        $allow_download = get_post_meta($post->ID, '_isma_course_allow_download', true);
        
        if (empty($pdf_url)) {
            return '<p>' . __('Aucun PDF disponible pour ce cours.', 'isma-education-platform') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="isma-course-viewer">
            <div class="course-header">
                <h2><?php the_title(); ?></h2>
                <?php if ($allow_download): ?>
                    <a href="<?php echo esc_url($pdf_url); ?>" class="download-btn" download>
                        <?php _e('Télécharger le PDF', 'isma-education-platform'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="course-pdf-container">
                <iframe src="<?php echo esc_url($pdf_url); ?>#toolbar=0" width="100%" height="600px"></iframe>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
