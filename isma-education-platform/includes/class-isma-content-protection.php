<?php
/**
 * Content Protection - Disable right-click, protect course content
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Content_Protection {
    
    public function __construct() {
        add_action('wp_footer', array($this, 'add_protection_script'));
        add_filter('wp_get_attachment_url', array($this, 'protect_media_urls'), 10, 2);
    }
    
    public function add_protection_script() {
        if (!is_singular('isma_course')) {
            return;
        }
        
        $protected = get_post_meta(get_the_ID(), '_isma_course_protected', true);
        if (!$protected) {
            return;
        }
        ?>
        <script>
        (function() {
            // Disable right-click context menu
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                return false;
            });
            
            // Disable keyboard shortcuts for developer tools
            document.addEventListener('keydown', function(e) {
                // F12
                if (e.key === 'F12') {
                    e.preventDefault();
                    return false;
                }
                
                // Ctrl+Shift+I (DevTools)
                if (e.ctrlKey && e.shiftKey && e.key === 'I') {
                    e.preventDefault();
                    return false;
                }
                
                // Ctrl+Shift+J (Console)
                if (e.ctrlKey && e.shiftKey && e.key === 'J') {
                    e.preventDefault();
                    return false;
                }
                
                // Ctrl+U (View Source)
                if (e.ctrlKey && e.key === 'U') {
                    e.preventDefault();
                    return false;
                }
                
                // Ctrl+S (Save)
                if (e.ctrlKey && e.key === 'S') {
                    e.preventDefault();
                    return false;
                }
                
                // PrintScreen
                if (e.key === 'PrintScreen') {
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
            
            // Add CSS to prevent text selection and image interaction
            var style = document.createElement('style');
            style.textContent = `
                .entry-content, .course-content, .isma-course-viewer {
                    -webkit-user-select: none !important;
                    -moz-user-select: none !important;
                    -ms-user-select: none !important;
                    user-select: none !important;
                    -webkit-touch-callout: none !important;
                }
                .entry-content img, .course-content img {
                    pointer-events: none !important;
                    -webkit-user-drag: none !important;
                }
            `;
            document.head.appendChild(style);
            
            // Clear clipboard on copy attempt
            document.addEventListener('copy', function(e) {
                e.preventDefault();
                return false;
            });
        })();
        </script>
        <?php
    }
    
    public function protect_media_urls($url, $attachment_id) {
        if (is_singular('isma_course')) {
            $protected = get_post_meta(get_the_ID(), '_isma_course_protected', true);
            if ($protected) {
                // Add watermark or protection parameter to media URLs
                $url = add_query_arg('protected', '1', $url);
            }
        }
        return $url;
    }
}
