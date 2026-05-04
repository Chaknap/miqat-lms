<?php
/**
 * Email Notifications System
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Email_Notifications {
    
    public static function send_email($to, $subject, $message) {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $message, $headers);
    }
    
    public static function send_course_charter_notification($admin_id, $course_id) {
        $admin = get_userdata($admin_id);
        $course = get_post($course_id);
        $trainer = get_userdata($course->post_author);
        
        $subject = sprintf(__('Nouveau cours en attente de charte: %s', 'isma-education-platform'), $course->post_title);
        $message = sprintf(
            __('Bonjour,<br><br>Le formateur %s a soumis un nouveau cours pour charte.<br><br>Titre: %s<br><br>Veuillez le réviser dans l\'espace admin.', 'isma-education-platform'),
            $trainer->display_name,
            $course->post_title
        );
        
        self::send_email($admin->user_email, $subject, $message);
    }
    
    public static function send_exercise_charter_notification($admin_id, $exercise_id) {
        $admin = get_userdata($admin_id);
        $exercise = get_post($exercise_id);
        $trainer = get_userdata($exercise->post_author);
        
        $subject = sprintf(__('Nouvel exercice en attente de charte: %s', 'isma-education-platform'), $exercise->post_title);
        $message = sprintf(
            __('Bonjour,<br><br>Le formateur %s a soumis un nouvel exercice pour charte.<br><br>Titre: %s<br><br>Veuillez le réviser dans l\'espace admin.', 'isma-education-platform'),
            $trainer->display_name,
            $exercise->post_title
        );
        
        self::send_email($admin->user_email, $subject, $message);
    }
    
    public static function send_charter_approved_notification($trainer_id, $post_id, $type) {
        $trainer = get_userdata($trainer_id);
        $post = get_post($post_id);
        
        $subject = sprintf(__('Votre %s a été approuvé et publié!', 'isma-education-platform'), $type === 'course' ? 'cours' : 'exercice');
        $message = sprintf(
            __('Bonjour,<br><br>Votre %s "%s" a été approuvé par l\'administration et est maintenant publié.', 'isma-education-platform'),
            $type === 'course' ? 'cours' : 'exercice',
            $post->post_title
        );
        
        self::send_email($trainer->user_email, $subject, $message);
    }
    
    public static function send_new_course_notification($student_id, $course_id) {
        $student = get_userdata($student_id);
        $course = get_post($course_id);
        
        $subject = sprintf(__('Nouveau cours disponible: %s', 'isma-education-platform'), $course->post_title);
        $message = sprintf(
            __('Bonjour,<br><br>Un nouveau cours est disponible sur la plateforme.<br><br>Titre: %s<br><br>Connectez-vous pour le consulter.', 'isma-education-platform'),
            $course->post_title
        );
        
        self::send_email($student->user_email, $subject, $message);
    }
    
    public static function send_exercise_submission_notification($trainer_id, $exercise_id, $student_id) {
        $trainer = get_userdata($trainer_id);
        $exercise = get_post($exercise_id);
        $student = get_userdata($student_id);
        
        $subject = sprintf(__('Nouvelle soumission pour l\'exercice: %s', 'isma-education-platform'), $exercise->post_title);
        $message = sprintf(
            __('Bonjour,<br><br>L\'étudiant %s a soumis une réponse pour l\'exercice "%s".<br><br>Veuillez la noter dans l\'espace formateur.', 'isma-education-platform'),
            $student->display_name,
            $exercise->post_title
        );
        
        self::send_email($trainer->user_email, $subject, $message);
    }
    
    public static function send_payment_confirmation($student_id, $payment_id) {
        $student = get_userdata($student_id);
        
        $subject = __('Confirmation de Paiement', 'isma-education-platform');
        $message = sprintf(
            __('Bonjour,<br><br>Votre paiement a été enregistré avec succès.<br><br>Merci pour votre confiance.', 'isma-education-platform')
        );
        
        self::send_email($student->user_email, $subject, $message);
    }
    
    public static function send_payment_reminder($student_id, $payment) {
        $student = get_userdata($student_id);
        
        $subject = __('Rappel de Paiement', 'isma-education-platform');
        $message = sprintf(
            __('Bonjour,<br><br>Nous vous rappelons que votre prochain paiement est prévu pour le %s.<br><br>Montant: %s €<br><br>Merci de régulariser votre situation.', 'isma-education-platform'),
            date_i18n(get_option('date_format'), strtotime($payment->next_payment_date)),
            number_format($payment->amount, 2)
        );
        
        self::send_email($student->user_email, $subject, $message);
    }
    
    public static function send_proposal_review_notification($trainer_id, $proposal_id, $status) {
        $trainer = get_userdata($trainer_id);
        
        $subject = __('Révision de votre proposition', 'isma-education-platform');
        $status_text = $status === 'approved' ? 'approuvée' : ($status === 'rejected' ? 'rejetée' : 'en attente');
        $message = sprintf(
            __('Bonjour,<br><br>Votre proposition a été %s par l\'administration.<br><br>Connectez-vous pour voir le feedback.', 'isma-education-platform'),
            $status_text
        );
        
        self::send_email($trainer->user_email, $subject, $message);
    }
}
