<?php
/**
 * Payment Manager - Handle student payments, history, reminders
 */

if (!defined('ABSPATH')) {
    exit;
}

class ISMA_Payment_Manager {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_payment_admin_menu'));
        add_action('wp_ajax_isma_record_payment', array($this, 'handle_record_payment'));
        add_shortcode('isma_student_payments', array($this, 'student_payments_shortcode'));
        add_shortcode('isma_trainer_payments', array($this, 'trainer_payments_shortcode'));
        add_action('isma_ep_payment_reminder_hook', array($this, 'send_payment_reminders'));
    }
    
    public function add_payment_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=isma_course',
            __('Paiements', 'isma-education-platform'),
            __('Paiements', 'isma-education-platform'),
            'manage_isma_payments',
            'isma-payments',
            array($this, 'render_payments_admin_page')
        );
    }
    
    public function render_payments_admin_page() {
        global $wpdb;
        
        $payments = $wpdb->get_results("
            SELECT p.*, u.display_name as student_name, u.user_email as student_email
            FROM {$wpdb->prefix}isma_payments p
            LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
            ORDER BY p.created_at DESC
        ");
        ?>
        <div class="wrap">
            <h1><?php _e('Gestion des Paiements', 'isma-education-platform'); ?></h1>
            
            <button class="button button-primary" id="add-payment-btn">
                <?php _e('Enregistrer un paiement', 'isma-education-platform'); ?>
            </button>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Étudiant', 'isma-education-platform'); ?></th>
                        <th><?php _e('Montant', 'isma-education-platform'); ?></th>
                        <th><?php _e('Date de paiement', 'isma-education-platform'); ?></th>
                        <th><?php _e('Prochain paiement', 'isma-education-platform'); ?></th>
                        <th><?php _e('Statut', 'isma-education-platform'); ?></th>
                        <th><?php _e('Méthode', 'isma-education-platform'); ?></th>
                        <th><?php _e('Notes', 'isma-education-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="7"><?php _e('Aucun paiement enregistré.', 'isma-education-platform'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($payment->student_name); ?></strong><br>
                                    <small><?php echo esc_html($payment->student_email); ?></small>
                                </td>
                                <td><?php echo number_format($payment->amount, 2); ?> €</td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($payment->payment_date)); ?></td>
                                <td><?php echo $payment->next_payment_date ? date_i18n(get_option('date_format'), strtotime($payment->next_payment_date)) : '-'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($payment->status); ?>">
                                        <?php echo esc_html(ucfirst($payment->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($payment->payment_method); ?></td>
                                <td><?php echo esc_html($payment->notes); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Modal for adding payment -->
        <div id="payment-modal" style="display:none;">
            <form id="payment-form">
                <?php wp_nonce_field('isma_record_payment', 'isma_payment_nonce'); ?>
                <p>
                    <label><?php _e('Étudiant:', 'isma-education-platform'); ?></label>
                    <select name="user_id" required>
                        <option value=""><?php _e('Sélectionner un étudiant', 'isma-education-platform'); ?></option>
                        <?php
                        $students = get_users(array('role' => 'isma_student'));
                        foreach ($students as $student):
                        ?>
                            <option value="<?php echo $student->ID; ?>"><?php echo esc_html($student->display_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p>
                    <label><?php _e('Montant:', 'isma-education-platform'); ?></label>
                    <input type="number" name="amount" step="0.01" min="0" required />
                </p>
                <p>
                    <label><?php _e('Date de paiement:', 'isma-education-platform'); ?></label>
                    <input type="date" name="payment_date" value="<?php echo current_time('Y-m-d'); ?>" required />
                </p>
                <p>
                    <label><?php _e('Prochaine date de paiement:', 'isma-education-platform'); ?></label>
                    <input type="date" name="next_payment_date" />
                </p>
                <p>
                    <label><?php _e('Statut:', 'isma-education-platform'); ?></label>
                    <select name="status">
                        <option value="paid"><?php _e('Payé', 'isma-education-platform'); ?></option>
                        <option value="pending"><?php _e('En attente', 'isma-education-platform'); ?></option>
                        <option value="late"><?php _e('En retard', 'isma-education-platform'); ?></option>
                    </select>
                </p>
                <p>
                    <label><?php _e('Méthode de paiement:', 'isma-education-platform'); ?></label>
                    <select name="payment_method">
                        <option value="bank_transfer"><?php _e('Virement bancaire', 'isma-education-platform'); ?></option>
                        <option value="cash"><?php _e('Espèces', 'isma-education-platform'); ?></option>
                        <option value="check"><?php _e('Chèque', 'isma-education-platform'); ?></option>
                        <option value="credit_card"><?php _e('Carte bancaire', 'isma-education-platform'); ?></option>
                    </select>
                </p>
                <p>
                    <label><?php _e('Notes:', 'isma-education-platform'); ?></label>
                    <textarea name="notes" rows="3"></textarea>
                </p>
            </form>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#add-payment-btn').click(function(e) {
                e.preventDefault();
                var modalContent = $('#payment-modal').html();
                
                tb_show('<?php _e('Enregistrer un paiement', 'isma-education-platform'); ?>', '#TB_inline?width=600&inlineId=payment-modal-content', false);
            });
            
            $('#payment-form').submit(function(e) {
                e.preventDefault();
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', $(this).serialize() + '&action=isma_record_payment', function(response) {
                    if (response.success) {
                        alert('<?php _e('Paiement enregistré avec succès!', 'isma-education-platform'); ?>');
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
    
    public function handle_record_payment() {
        check_ajax_referer('isma_record_payment', 'isma_payment_nonce');
        
        if (!current_user_can('manage_isma_payments')) {
            wp_send_json_error(array('message' => __('Permission refusée.', 'isma-education-platform')));
        }
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'isma_payments',
            array(
                'user_id' => intval($_POST['user_id']),
                'amount' => floatval($_POST['amount']),
                'payment_date' => sanitize_text_field($_POST['payment_date']),
                'next_payment_date' => !empty($_POST['next_payment_date']) ? sanitize_text_field($_POST['next_payment_date']) : null,
                'status' => sanitize_text_field($_POST['status']),
                'payment_method' => sanitize_text_field($_POST['payment_method']),
                'notes' => sanitize_textarea_field($_POST['notes']),
                'created_at' => current_time('mysql'),
            )
        );
        
        if ($result) {
            // Send notification to student
            ISMA_Email_Notifications::send_payment_confirmation($_POST['user_id'], $wpdb->insert_id);
            
            wp_send_json_success(array('message' => __('Paiement enregistré avec succès!', 'isma-education-platform')));
        } else {
            wp_send_json_error(array('message' => __('Erreur lors de l\'enregistrement.', 'isma-education-platform')));
        }
    }
    
    public function student_payments_shortcode($atts) {
        if (!is_user_logged_in() || !current_user_can('view_own_payments')) {
            return '<p>' . __('Vous devez être connecté pour voir vos paiements.', 'isma-education-platform') . '</p>';
        }
        
        global $wpdb;
        $student_id = get_current_user_id();
        
        $payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}isma_payments WHERE user_id = %d ORDER BY created_at DESC",
            $student_id
        ));
        
        // Get next payment due
        $next_payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}isma_payments 
             WHERE user_id = %d AND next_payment_date IS NOT NULL AND next_payment_date >= CURDATE()
             ORDER BY next_payment_date ASC LIMIT 1",
            $student_id
        ));
        
        ob_start();
        ?>
        <div class="isma-student-payments">
            <h2><?php _e('Historique de Paiement', 'isma-education-platform'); ?></h2>
            
            <?php if ($next_payment): ?>
                <div class="next-payment-alert">
                    <h3><?php _e('Prochain Paiement', 'isma-education-platform'); ?></h3>
                    <p><strong><?php _e('Date:', 'isma-education-platform'); ?></strong> <?php echo date_i18n(get_option('date_format'), strtotime($next_payment->next_payment_date)); ?></p>
                    <p><strong><?php _e('Montant estimé:', 'isma-education-platform'); ?></strong> <?php echo number_format($next_payment->amount, 2); ?> €</p>
                </div>
            <?php endif; ?>
            
            <table class="payment-history-table">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'isma-education-platform'); ?></th>
                        <th><?php _e('Montant', 'isma-education-platform'); ?></th>
                        <th><?php _e('Statut', 'isma-education-platform'); ?></th>
                        <th><?php _e('Méthode', 'isma-education-platform'); ?></th>
                        <th><?php _e('Notes', 'isma-education-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="5"><?php _e('Aucun historique de paiement.', 'isma-education-platform'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($payment->payment_date)); ?></td>
                                <td><?php echo number_format($payment->amount, 2); ?> €</td>
                                <td><span class="status-<?php echo esc_attr($payment->status); ?>"><?php echo ucfirst($payment->status); ?></span></td>
                                <td><?php echo esc_html($payment->payment_method); ?></td>
                                <td><?php echo esc_html($payment->notes); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function trainer_payments_shortcode($atts) {
        if (!current_user_can('view_own_payments')) {
            return '<p>' . __('Accès non autorisé.', 'isma-education-platform') . '</p>';
        }
        
        global $wpdb;
        $trainer_id = get_current_user_id();
        
        // Get total earnings and payment history for trainer
        $total_earnings = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}isma_payments 
             WHERE user_id = %d AND status = 'paid'",
            $trainer_id
        ));
        
        ob_start();
        ?>
        <div class="isma-trainer-payments">
            <h2><?php _e('Mes Paiements', 'isma-education-platform'); ?></h2>
            
            <div class="total-earnings">
                <h3><?php _e('Total perçu', 'isma-education-platform'); ?></h3>
                <p class="amount"><?php echo number_format($total_earnings ?: 0, 2); ?> €</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function send_payment_reminders() {
        global $wpdb;
        
        $reminder_days = get_option('isma_ep_payment_reminder_days', 7);
        $target_date = date('Y-m-d', strtotime("+{$reminder_days} days"));
        
        $upcoming_payments = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name as student_name, u.user_email as student_email
             FROM {$wpdb->prefix}isma_payments p
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
             WHERE p.next_payment_date <= %s AND p.status != 'paid'
             ORDER BY p.next_payment_date ASC",
            $target_date
        ));
        
        foreach ($upcoming_payments as $payment) {
            ISMA_Email_Notifications::send_payment_reminder($payment->user_id, $payment);
        }
    }
}
