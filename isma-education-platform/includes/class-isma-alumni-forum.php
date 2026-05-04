<?php
/**
 * Alumni Forum - Discussion forum and job offers
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Alumni_Forum {
    
    public function __construct() {
        add_shortcode('isma_alumni_forum', array($this, 'forum_shortcode'));
        add_shortcode('isma_job_offers', array($this, 'job_offers_shortcode'));
        add_action('wp_ajax_isma_create_forum_post', array($this, 'handle_create_forum_post'));
        add_action('wp_ajax_isma_create_job_offer', array($this, 'handle_create_job_offer'));
    }
    
    public function forum_shortcode($atts) {
        if (!is_user_logged_in() || !current_user_can('participate_forum')) {
            return '<p>' . __('Vous devez être connecté pour accéder au forum.', 'isma-education-platform') . '</p>';
        }
        
        global $wpdb;
        
        // Get forum posts
        $posts = $wpdb->get_results("
            SELECT p.*, u.display_name as author_name
            FROM {$wpdb->prefix}isma_forum_posts p
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            WHERE p.parent_id = 0 AND p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        
        ob_start();
        ?>
        <div class="isma-alumni-forum">
            <h2><?php _e('Forum Alumni', 'isma-education-platform'); ?></h2>
            
            <?php if (current_user_can('create_forum_discussion')): ?>
                <button class="button button-primary" id="new-discussion-btn">
                    <?php _e('Nouvelle Discussion', 'isma-education-platform'); ?>
                </button>
            <?php endif; ?>
            
            <div class="forum-discussions">
                <?php if (empty($posts)): ?>
                    <p><?php _e('Aucune discussion pour le moment.', 'isma-education-platform'); ?></p>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="forum-post">
                            <h3><?php echo esc_html($post->title); ?></h3>
                            <div class="post-meta">
                                <span class="author"><?php echo esc_html($post->author_name); ?></span>
                                <span class="date"><?php echo date_i18n(get_option('date_format'), strtotime($post->created_at)); ?></span>
                                <span class="views"><?php printf(__('%d vues', 'isma-education-platform'), $post->views_count); ?></span>
                            </div>
                            <div class="post-excerpt">
                                <?php echo wp_trim_words(esc_html($post->content), 30); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function job_offers_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '10',
        ), $atts);
        
        $args = array(
            'post_type' => 'isma_job_offer',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
        );
        
        $job_offers = new WP_Query($args);
        
        ob_start();
        ?>
        <div class="isma-job-offers">
            <h2><?php _e('Offres d\'Emploi', 'isma-education-platform'); ?></h2>
            
            <?php if (current_user_can('post_job_offer')): ?>
                <button class="button button-primary" id="new-job-offer-btn">
                    <?php _e('Publier une Offre', 'isma-education-platform'); ?>
                </button>
            <?php endif; ?>
            
            <?php if (!$job_offers->have_posts()): ?>
                <p><?php _e('Aucune offre d\'emploi disponible.', 'isma-education-platform'); ?></p>
            <?php else: ?>
                <div class="job-list">
                    <?php while ($job_offers->have_posts()): $job_offers->the_post(); ?>
                        <div class="job-item">
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <div class="job-meta">
                                <?php 
                                $company = get_post_meta(get_the_ID(), '_isma_job_company', true);
                                $location = get_post_meta(get_the_ID(), '_isma_job_location', true);
                                $type = get_post_meta(get_the_ID(), '_isma_job_type', true);
                                
                                if ($company) echo '<span class="company">' . esc_html($company) . '</span>';
                                if ($location) echo '<span class="location">' . esc_html($location) . '</span>';
                                if ($type) echo '<span class="type">' . esc_html($type) . '</span>';
                                ?>
                            </div>
                            <div class="job-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function handle_create_forum_post() {
        check_ajax_referer('isma_forum_nonce', 'nonce');
        
        if (!current_user_can('create_forum_discussion')) {
            wp_send_json_error(array('message' => __('Permission refusée.', 'isma-education-platform')));
        }
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'isma_forum_posts',
            array(
                'user_id' => get_current_user_id(),
                'post_type' => 'discussion',
                'title' => sanitize_text_field($_POST['title']),
                'content' => sanitize_textarea_field($_POST['content']),
                'status' => 'active',
                'created_at' => current_time('mysql'),
            )
        );
        
        if ($result) {
            wp_send_json_success(array('message' => __('Discussion créée avec succès!', 'isma-education-platform')));
        } else {
            wp_send_json_error(array('message' => __('Erreur lors de la création.', 'isma-education-platform')));
        }
    }
    
    public function handle_create_job_offer() {
        check_ajax_referer('isma_job_nonce', 'nonce');
        
        if (!current_user_can('post_job_offer')) {
            wp_send_json_error(array('message' => __('Permission refusée.', 'isma-education-platform')));
        }
        
        $post_data = array(
            'post_title' => sanitize_text_field($_POST['title']),
            'post_content' => sanitize_textarea_field($_POST['content']),
            'post_status' => 'publish',
            'post_type' => 'isma_job_offer',
            'post_author' => get_current_user_id(),
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_isma_job_company', sanitize_text_field($_POST['company']));
            update_post_meta($post_id, '_isma_job_location', sanitize_text_field($_POST['location']));
            update_post_meta($post_id, '_isma_job_type', sanitize_text_field($_POST['job_type']));
            
            wp_send_json_success(array('message' => __('Offre publiée avec succès!', 'isma-education-platform')));
        } else {
            wp_send_json_error(array('message' => __('Erreur lors de la publication.', 'isma-education-platform')));
        }
    }
}
