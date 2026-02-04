<?php

namespace Drupal\aabenforms_workflows\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\aabenforms_workflows\Service\ApprovalTokenService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Log\LoggerInterface;

/**
 * Controller for parent approval pages.
 *
 * Handles the approval workflow:
 * 1. Validates token and submission
 * 2. Checks if already approved
 * 3. Shows MitID login button
 * 4. After MitID auth: displays form with data
 * 5. Processes approval/rejection.
 */
class ParentApprovalController extends ControllerBase {

  /**
   * The approval token service.
   *
   * @var \Drupal\aabenforms_workflows\Service\ApprovalTokenService
   */
  protected ApprovalTokenService $tokenService;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a ParentApprovalController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\aabenforms_workflows\Service\ApprovalTokenService $token_service
   *   The approval token service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ApprovalTokenService $token_service,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tokenService = $token_service;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('aabenforms_workflows.approval_token'),
      $container->get('logger.factory')->get('aabenforms_workflows')
    );
  }

  /**
   * Displays the parent approval page.
   *
   * @param int $parent_number
   *   The parent number (1 or 2).
   * @param int $submission_id
   *   The webform submission ID.
   * @param string $token
   *   The security token.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function approvalPage(int $parent_number, int $submission_id, string $token, Request $request): array {
    // Validate token.
    if (!$this->tokenService->validateToken($submission_id, $parent_number, $token)) {
      $this->logger->warning('Invalid or expired token for submission @sid, parent @parent', [
        '@sid' => $submission_id,
        '@parent' => $parent_number,
      ]);

      if ($this->tokenService->isTokenExpired($token)) {
        return [
          '#theme' => 'status_messages',
          '#message_list' => [
            'error' => [$this->t('This approval link has expired. Please contact the case worker for a new link.')],
          ],
          '#status_headings' => [
            'error' => $this->t('Expired Link'),
          ],
        ];
      }

      throw new AccessDeniedHttpException('Invalid approval token.');
    }

    // Load submission.
    $storage = $this->entityTypeManager->getStorage('webform_submission');
    $submission = $storage->load($submission_id);

    if (!$submission) {
      $this->logger->error('Submission @sid not found', ['@sid' => $submission_id]);
      throw new NotFoundHttpException('Submission not found.');
    }

    // Check if already approved/rejected.
    $status_field = "parent{$parent_number}_status";
    $current_status = $submission->getElementData($status_field);

    if (in_array($current_status, ['complete', 'rejected'])) {
      $this->logger->info('Parent @parent already processed submission @sid with status: @status', [
        '@parent' => $parent_number,
        '@sid' => $submission_id,
        '@status' => $current_status,
      ]);

      return [
        '#theme' => 'status_messages',
        '#message_list' => [
          'status' => [
            $this->t('You have already @action this request.', [
              '@action' => $current_status === 'complete' ? 'approved' : 'rejected',
            ]),
          ],
        ],
        '#status_headings' => [
          'status' => $this->t('Already Processed'),
        ],
      ];
    }

    // Check if MitID authenticated.
    $session = $request->getSession();
    $mitid_authenticated = $session->get("mitid_authenticated_parent{$parent_number}");

    if (!$mitid_authenticated) {
      // Show MitID login page.
      return $this->buildMitIdLoginPage($submission, $parent_number, $token);
    }

    // Show approval form.
    return $this->buildApprovalForm($submission, $parent_number, $token);
  }

  /**
   * Builds the MitID login page.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The webform submission.
   * @param int $parent_number
   *   The parent number.
   * @param string $token
   *   The security token.
   *
   * @return array
   *   A render array.
   */
  protected function buildMitIdLoginPage($submission, int $parent_number, string $token): array {
    $child_name = $submission->getElementData('child_name');
    $request_details = $submission->getElementData('request_details');

    return [
      '#theme' => 'parent_approval_login',
      '#child_name' => $child_name,
      '#parent_number' => $parent_number,
      '#request_summary' => $this->truncateText($request_details, 200),
      '#mitid_login_url' => \Drupal::url('aabenforms_workflows.parent_approval_mitid', [
        'parent_number' => $parent_number,
        'submission_id' => $submission->id(),
        'token' => $token,
      ]),
      '#attached' => [
        'library' => [
          'aabenforms_workflows/parent-approval',
        ],
      ],
    ];
  }

  /**
   * Builds the approval form.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The webform submission.
   * @param int $parent_number
   *   The parent number.
   * @param string $token
   *   The security token.
   *
   * @return array
   *   A render array.
   */
  protected function buildApprovalForm($submission, int $parent_number, string $token): array {
    $form_builder = \Drupal::formBuilder();

    // Determine data visibility based on parents_together field.
    $parents_together = $submission->getElementData('parents_together') === 'together';

    return [
      '#theme' => 'parent_approval_page',
      '#form' => $form_builder->getForm(
        'Drupal\aabenforms_workflows\Form\ParentApprovalForm',
        $submission,
        $parent_number,
        $token,
        $parents_together
      ),
      '#attached' => [
        'library' => [
          'aabenforms_workflows/parent-approval',
        ],
      ],
    ];
  }

  /**
   * Truncates text to specified length.
   *
   * @param string $text
   *   The text to truncate.
   * @param int $length
   *   Maximum length.
   *
   * @return string
   *   Truncated text.
   */
  protected function truncateText(string $text, int $length): string {
    if (mb_strlen($text) <= $length) {
      return $text;
    }
    return mb_substr($text, 0, $length) . '...';
  }

  /**
   * Displays the completion page after approval/rejection.
   *
   * @param string $action
   *   The action taken (approve or reject).
   *
   * @return array
   *   A render array.
   */
  public function completePage(string $action): array {
    $is_approved = $action === 'approve';

    return [
      '#theme' => 'status_messages',
      '#message_list' => [
        'status' => [
          $is_approved
            ? $this->t('Thank you for approving this request. The case worker has been notified and will proceed with processing.')
            : $this->t('Your response has been recorded. The case worker has been notified of your decision.'),
        ],
      ],
      '#status_headings' => [
        'status' => $this->t('Decision Recorded'),
      ],
      'info' => [
        '#markup' => '<div class="parent-approval-complete">' .
        '<p>' . $this->t('You may now close this window.') . '</p>' .
        '<p>' . $this->t('If you have any questions, please contact the case worker handling this request.') . '</p>' .
        '</div>',
      ],
    ];
  }

}
