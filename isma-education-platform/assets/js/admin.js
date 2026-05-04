/**
 * ISMA Education Platform - Admin JavaScript
 */
jQuery(document).ready(function($) {
    // Charter approval
    $('.charter-approve-btn').click(function(e) {
        e.preventDefault();
        var type = $(this).data('type');
        var charterId = $(this).data('id');
        
        if (confirm('Êtes-vous sûr de vouloir approuver et publier ce contenu?')) {
            $.post(ajaxurl, {
                action: 'isma_charter_approve',
                type: type,
                charter_id: charterId,
                nonce: ismaEP.nonce
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        }
    });
    
    // Grade saving in exercise meta box
    $('.save-grade-btn').click(function(e) {
        e.preventDefault();
        var submissionId = $(this).data('submission-id');
        var grade = $(this).siblings('.grade-input').val();
        var feedback = $(this).siblings('.feedback-textarea').val();
        
        $.post(ajaxurl, {
            action: 'isma_save_exercise_grade',
            submission_id: submissionId,
            grade: grade,
            feedback: feedback,
            nonce: ismaEP.nonce
        }, function(response) {
            if (response.success) {
                alert('Note enregistrée avec succès!');
                location.reload();
            } else {
                alert('Erreur lors de l\'enregistrement.');
            }
        });
    });
});
