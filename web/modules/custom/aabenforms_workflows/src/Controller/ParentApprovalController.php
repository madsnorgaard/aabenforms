<?php

namespace Drupal\aabenforms_workflows\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\aabenforms_mitid\Service\MitIdSessionManager;
use Drupal\aabenforms_workflows\Service\ApprovalTokenService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
   * The MitID session manager.
   *
   * @var \Drupal\aabenforms_mitid\Service\MitIdSessionManager
   */
  protected MitIdSessionManager $mitidSessionManager;

  /**
   * Constructs a ParentApprovalController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\aabenforms_workflows\Service\ApprovalTokenService $token_service
   *   The approval token service.
   * @param \Drupal\aabenforms_mitid\Service\MitIdSessionManager $mitid_session_manager
   *   The MitID session manager (for verifying OIDC handoff).
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ApprovalTokenService $token_service,
    MitIdSessionManager $mitid_session_manager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tokenService = $token_service;
    $this->mitidSessionManager = $mitid_session_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('aabenforms_workflows.approval_token'),
      $container->get('aabenforms_mitid.session_manager'),
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
    // Validate token, then branch on outcome so the citizen sees an
    // accurate message rather than a generic "denied". Three outcomes:
    // expired (well-formed, past) → "ask for a new link"; malformed
    // (URL corrupted) → "check the URL"; tampered (HMAC mismatch) →
    // 403 (real security signal).
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

      if ($this->tokenService->isTokenMalformed($token)) {
        return [
          '#theme' => 'status_messages',
          '#message_list' => [
            'error' => [
              $this->t('This approval link is invalid. The URL may have been truncated by your email client - please copy and paste the full link, or contact the case worker for a fresh one.'),
            ],
          ],
          '#status_headings' => [
            'error' => $this->t('Invalid Link'),
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
      '#mitid_login_url' => Url::fromRoute('aabenforms_workflows.parent_approval_mitid', [
        'parent_number' => $parent_number,
        'submission_id' => $submission->id(),
        'token' => $token,
      ])->toString(),
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
   * Initiates the parent's MitID OIDC login.
   *
   * Validates the token (same gating as approvalPage so a tampered or
   * malformed link can't bypass the MitID requirement by navigating
   * directly to this route), then redirects to the aabenforms_mitid
   * OIDC login route with a workflow_id scoped to this parent and a
   * return_url pointing at our matching `mitid/complete` endpoint.
   *
   * After the OIDC round-trip the parent lands at mitidComplete()
   * below, which verifies a real authenticated MitID session is in
   * tempstore for this workflow_id before flipping the session flag.
   *
   * The workflow_id namespace `parent_approval_<sid>_p<N>` keeps each
   * parent's MitID session isolated so two parents reviewing the same
   * submission from different browsers don't collide.
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
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to /mitid/login with the parent context attached.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function mitidLogin(int $parent_number, int $submission_id, string $token, Request $request): RedirectResponse {
    if (!$this->tokenService->validateToken($submission_id, $parent_number, $token)) {
      $this->logger->warning('Invalid token at MitID handoff for submission @sid, parent @parent', [
        '@sid' => $submission_id,
        '@parent' => $parent_number,
      ]);
      throw new AccessDeniedHttpException('Invalid approval token.');
    }

    $workflow_id = sprintf('parent_approval_%d_p%d', $submission_id, $parent_number);
    $return_url = Url::fromRoute(
      'aabenforms_workflows.parent_approval_mitid_complete',
      [
        'parent_number' => $parent_number,
        'submission_id' => $submission_id,
        'token' => $token,
      ],
      ['absolute' => TRUE],
    )->toString();

    $login_url = Url::fromRoute(
      'aabenforms_mitid.login',
      [],
      [
        'query' => [
          'workflow_id' => $workflow_id,
          'return_url' => $return_url,
        ],
      ],
    )->toString();

    return new RedirectResponse($login_url);
  }

  /**
   * Handles the post-OIDC return from MitID for the parent flow.
   *
   * Re-validates the token (defence in depth: the workflow_id query
   * arg can be inspected/altered by a malicious return target), then
   * checks the aabenforms_mitid session manager for a real OIDC
   * session under our scoped workflow_id. If both pass, flips the
   * parent's per-session flag and redirects to the approval page
   * which now renders the form.
   *
   * @todo Add CPR-vs-parent verification once the parent_request_form
   *   schema carries parent CPRs (today it only stores email, which
   *   isn't a usable identity assertion). Once that lands, compare
   *   $this->mitidSessionManager->getCprFromSession($workflow_id)
   *   against the submission's parent{N}_cpr field; on mismatch, log
   *   warning + 403.
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
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect back to the approval page.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function mitidComplete(int $parent_number, int $submission_id, string $token, Request $request): RedirectResponse {
    if (!$this->tokenService->validateToken($submission_id, $parent_number, $token)) {
      $this->logger->warning('Invalid token at MitID complete for submission @sid, parent @parent', [
        '@sid' => $submission_id,
        '@parent' => $parent_number,
      ]);
      throw new AccessDeniedHttpException('Invalid approval token.');
    }

    $workflow_id = sprintf('parent_approval_%d_p%d', $submission_id, $parent_number);
    if (!$this->mitidSessionManager->hasValidSession($workflow_id)) {
      $this->logger->warning('No valid MitID session for workflow @wid (parent @parent, submission @sid)', [
        '@wid' => $workflow_id,
        '@parent' => $parent_number,
        '@sid' => $submission_id,
      ]);
      throw new AccessDeniedHttpException('MitID authentication did not complete.');
    }

    $request->getSession()->set("mitid_authenticated_parent{$parent_number}", TRUE);
    $this->logger->info('Parent @parent MitID handoff verified for submission @sid', [
      '@parent' => $parent_number,
      '@sid' => $submission_id,
    ]);

    $url = Url::fromRoute('aabenforms_workflows.parent_approval', [
      'parent_number' => $parent_number,
      'submission_id' => $submission_id,
      'token' => $token,
    ])->toString();
    return new RedirectResponse($url);
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
