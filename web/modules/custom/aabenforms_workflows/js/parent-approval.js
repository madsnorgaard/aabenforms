/**
 * @file
 * JavaScript for parent approval pages.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Parent approval page behaviors.
   */
  Drupal.behaviors.parentApproval = {
    attach: function (context, settings) {
      // Add confirmation dialog for rejection.
      $('.parent-approval-page input[value="reject"]', context).once('rejection-confirm').on('change', function () {
        if ($(this).is(':checked')) {
          var confirmReject = confirm(Drupal.t('Are you sure you want to reject this request? This action cannot be undone.'));
          if (!confirmReject) {
            $('.parent-approval-page input[value="approve"]').prop('checked', true);
          }
        }
      });

      // Add form validation before submit.
      $('.parent-approval-page form', context).once('approval-validation').on('submit', function (e) {
        var selectedAction = $('.parent-approval-page input[name="action"]:checked').val();

        if (!selectedAction) {
          e.preventDefault();
          alert(Drupal.t('Please select whether you approve or reject this request.'));
          return false;
        }

        // Confirm rejection one more time.
        if (selectedAction === 'reject') {
          var finalConfirm = confirm(Drupal.t('Final confirmation: You are about to reject this request. The case worker will be notified. Continue?'));
          if (!finalConfirm) {
            e.preventDefault();
            return false;
          }
        }

        // Disable submit button to prevent double submission.
        $(this).find('input[type="submit"]').prop('disabled', true).val(Drupal.t('Submitting...'));
      });

      // Add loading state to MitID login button.
      $('.mitid-login-button', context).once('mitid-loading').on('click', function () {
        var $button = $(this);
        $button.addClass('loading').text(Drupal.t('Redirecting to MitID...'));
      });

      // Auto-focus first form element.
      $('.parent-approval-page form input[name="action"]:first', context).once('auto-focus').focus();
    }
  };

})(jQuery, Drupal);
