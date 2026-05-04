<?php
/**
 * Register Custom Post Types: Course, Exercise, Theme, News, Job Offer
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Post_Types {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));
    }
    
    public function register_post_types() {
        // Course post type
        register_post_type('isma_course', array(
            'labels' => array(
                'name' => __('Cours', 'isma-education-platform'),
                'singular_name' => __('Cours', 'isma-education-platform'),
                'add_new' => __('Ajouter un cours', 'isma-education-platform'),
                'add_new_item' => __('Ajouter un nouveau cours', 'isma-education-platform'),
                'edit_item' => __('Modifier le cours', 'isma-education-platform'),
                'new_item' => __('Nouveau cours', 'isma-education-platform'),
                'view_item' => __('Voir le cours', 'isma-education-platform'),
                'search_items' => __('Rechercher des cours', 'isma-education-platform'),
                'not_found' => __('Aucun cours trouvé', 'isma-education-platform'),
                'all_items' => __('Tous les cours', 'isma-education-platform'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-welcome-learn-more',
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => array('slug' => 'cours'),
        ));
        
        // Exercise post type
        register_post_type('isma_exercise', array(
            'labels' => array(
                'name' => __('Exercices', 'isma-education-platform'),
                'singular_name' => __('Exercice', 'isma-education-platform'),
                'add_new' => __('Ajouter un exercice', 'isma-education-platform'),
                'add_new_item' => __('Ajouter un nouvel exercice', 'isma-education-platform'),
                'edit_item' => __('Modifier l\'exercice', 'isma-education-platform'),
                'new_item' => __('Nouvel exercice', 'isma-education-platform'),
                'view_item' => __('Voir l\'exercice', 'isma-education-platform'),
                'search_items' => __('Rechercher des exercices', 'isma-education-platform'),
                'not_found' => __('Aucun exercice trouvé', 'isma-education-platform'),
                'all_items' => __('Tous les exercices', 'isma-education-platform'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-editor-ol',
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'author', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => array('slug' => 'exercices'),
        ));
        
        // News post type (Actualités)
        register_post_type('isma_news', array(
            'labels' => array(
                'name' => __('Actualités', 'isma-education-platform'),
                'singular_name' => __('Actualité', 'isma-education-platform'),
                'add_new' => __('Ajouter une actualité', 'isma-education-platform'),
                'add_new_item' => __('Ajouter une nouvelle actualité', 'isma-education-platform'),
                'edit_item' => __('Modifier l\'actualité', 'isma-education-platform'),
                'new_item' => __('Nouvelle actualité', 'isma-education-platform'),
                'view_item' => __('Voir l\'actualité', 'isma-education-platform'),
                'search_items' => __('Rechercher des actualités', 'isma-education-platform'),
                'not_found' => __('Aucune actualité trouvée', 'isma-education-platform'),
                'all_items' => __('Toutes les actualités', 'isma-education-platform'),
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-megaphone',
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'actualites'),
        ));
        
        // Job Offer post type (Alumni)
        register_post_type('isma_job_offer', array(
            'labels' => array(
                'name' => __('Offres d\'emploi', 'isma-education-platform'),
                'singular_name' => __('Offre d\'emploi', 'isma-education-platform'),
                'add_new' => __('Ajouter une offre', 'isma-education-platform'),
                'add_new_item' => __('Ajouter une nouvelle offre', 'isma-education-platform'),
                'edit_item' => __('Modifier l\'offre', 'isma-education-platform'),
                'new_item' => __('Nouvelle offre', 'isma-education-platform'),
                'view_item' => __('Voir l\'offre', 'isma-education-platform'),
                'search_items' => __('Rechercher des offres', 'isma-education-platform'),
                'not_found' => __('Aucune offre trouvée', 'isma-education-platform'),
                'all_items' => __('Toutes les offres', 'isma-education-platform'),
            ),
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-businessman',
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor', 'author', 'custom-fields'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'offres-emploi'),
        ));
    }
    
    public function register_taxonomies() {
        // Theme taxonomy for courses
        register_taxonomy('isma_theme', array('isma_course', 'isma_exercise'), array(
            'labels' => array(
                'name' => __('Thèmes', 'isma-education-platform'),
                'singular_name' => __('Thème', 'isma-education-platform'),
                'search_items' => __('Rechercher des thèmes', 'isma-education-platform'),
                'all_items' => __('Tous les thèmes', 'isma-education-platform'),
                'edit_item' => __('Modifier le thème', 'isma-education-platform'),
                'update_item' => __('Mettre à jour le thème', 'isma-education-platform'),
                'add_new_item' => __('Ajouter un nouveau thème', 'isma-education-platform'),
                'new_item_name' => __('Nom du nouveau thème', 'isma-education-platform'),
                'parent_item' => __('Thème parent', 'isma-education-platform'),
                'parent_item_colon' => __('Thème parent:', 'isma-education-platform'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'rewrite' => array('slug' => 'theme'),
        ));
        
        // Formation taxonomy for news
        register_taxonomy('isma_formation', array('isma_news'), array(
            'labels' => array(
                'name' => __('Formations', 'isma-education-platform'),
                'singular_name' => __('Formation', 'isma-education-platform'),
                'search_items' => __('Rechercher des formations', 'isma-education-platform'),
                'all_items' => __('Toutes les formations', 'isma-education-platform'),
                'edit_item' => __('Modifier la formation', 'isma-education-platform'),
                'update_item' => __('Mettre à jour la formation', 'isma-education-platform'),
                'add_new_item' => __('Ajouter une nouvelle formation', 'isma-education-platform'),
                'new_item_name' => __('Nom de la nouvelle formation', 'isma-education-platform'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'rewrite' => array('slug' => 'formation'),
        ));
    }
}
