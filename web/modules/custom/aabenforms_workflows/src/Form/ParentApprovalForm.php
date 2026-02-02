<?php

namespace Drupal\aabenforms_workflows\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\aabenforms_workflows\Service\ApprovalTokenService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides a form for parent approval/rejection.
 *
 * Displays child information and request details with appropriate
 * GDPR controls based on parent relationship status.
 */
class ParentApprovalForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

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
   * The webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $submission;

  /**
   * The parent number.
   *
   * @var int
   */
  protected int $parentNumber;

  /**
   * The security token.
   *
   * @var string
   */
  protected string $token;

  /**
   * Whether parents live together.
   *
   * @var bool
   */
  protected bool $parentsTogether;

  /**
   * Constructs a ParentApprovalForm object.
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
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'aabenforms_parent_approval_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $submission = NULL, $parent_number = NULL, $token = NULL, $parents_together = TRUE): array {
    $this->submission = $submission;
    $this->parentNumber = $parent_number;
    $this->token = $token;
    $this->parentsTogether = $parents_together;

    // Child information section.
    $form['child_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Child Information'),
    ];

    $form['child_info']['child_name'] = [
      '#type' => 'item',
      '#title' => $this->t('Child Name'),
      '#markup' => '<strong>' . htmlspecialchars($submission->getElementData('child_name')) . '</strong>',
    ];

    // CPR - mask if parents apart per GDPR.
    $child_cpr = $submission->getElementData('child_cpr');
    if (!$this->parentsTogether) {
      // Mask CPR: show only first 6 digits (birthdate) and last digit.
      $child_cpr = substr($child_cpr, 0, 6) . '-XXX' . substr($child_cpr, -1);
    }

    $form['child_info']['child_cpr'] = [
      '#type' => 'item',
      '#title' => $this->t('CPR Number'),
      '#markup' => '<code>' . htmlspecialchars($child_cpr) . '</code>',
    ];

    if (!$this->parentsTogether) {
      $form['child_info']['gdpr_notice'] = [
        '#markup' => '<div class="messages messages--warning">' .
        $this->t('Note: Some information is masked for privacy as parents are registered as living apart.') .
        '</div>',
      ];
    }

    // Request details section.
    $form['request_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Request Details'),
    ];

    $form['request_info']['request_details'] = [
      '#type' => 'item',
      '#title' => $this->t('Description'),
      '#markup' => '<p>' . nl2br(htmlspecialchars($submission->getElementData('request_details'))) . '</p>',
    ];

    // Request date.
    $form['request_info']['request_date'] = [
      '#type' => 'item',
      '#title' => $this->t('Submitted'),
      '#markup' => \Drupal::service('date.formatter')->format($submission->getCreatedTime(), 'long'),
    ];

    // Approval decision section.
    $form['decision'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Your Decision'),
    ];

    $form['decision']['action'] = [
      '#type' => 'radios',
      '#title' => $this->t('Do you approve this request?'),
      '#options' => [
        'approve' => $this->t('Yes, I approve this request'),
        'reject' => $this->t('No, I do not approve this request'),
      ],
      '#required' => TRUE,
    ];

    $form['decision']['comments'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comments (optional)'),
      '#description' => $this->t('Add any comments or concerns about this request.'),
      '#rows' => 4,
    ];

    // Hidden fields.
    $form['submission_id'] = [
      '#type' => 'hidden',
      '#value' => $submission->id(),
    ];

    $form['parent_number'] = [
      '#type' => 'hidden',
      '#value' => $parent_number,
    ];

    $form['token'] = [
      '#type' => 'hidden',
      '#value' => $token,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit Decision'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Re-validate token to prevent CSRF attacks.
    $submission_id = $form_state->getValue('submission_id');
    $parent_number = $form_state->getValue('parent_number');
    $token = $form_state->getValue('token');

    if (!$this->tokenService->validateToken($submission_id, $parent_number, $token)) {
      $form_state->setErrorByName('token', $this->t('Security validation failed. Please try again or request a new approval link.'));
    }

    // Verify submission still exists and is in correct state.
    $storage = $this->entityTypeManager->getStorage('webform_submission');
    $submission = $storage->load($submission_id);

    if (!$submission) {
      $form_state->setErrorByName('submission_id', $this->t('The submission could not be found.'));
      return;
    }

    $status_field = "parent{$parent_number}_status";
    $current_status = $submission->getElementData($status_field);

    if (in_array($current_status, ['complete', 'rejected'])) {
      $form_state->setErrorByName('submission_id', $this->t('This request has already been processed.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $submission_id = $form_state->getValue('submission_id');
    $parent_number = $form_state->getValue('parent_number');
    $action = $form_state->getValue('action');
    $comments = $form_state->getValue('comments');

    // Load and update submission.
    $storage = $this->entityTypeManager->getStorage('webform_submission');
    $submission = $storage->load($submission_id);

    if (!$submission) {
      $this->messenger()->addError($this->t('An error occurred. Please contact support.'));
      return;
    }

    // Update parent status field.
    $status_field = "parent{$parent_number}_status";
    $new_status = $action === 'approve' ? 'complete' : 'rejected';
    $submission->setElementData($status_field, $new_status);

    // Save comments if provided.
    if ($comments) {
      $comment_field = "parent{$parent_number}_comments";
      $submission->setElementData($comment_field, $comments);
    }

    // Save submission - this will trigger the ECA workflow.
    try {
      $submission->save();

      $this->logger->info('Parent @parent @action submission @sid', [
        '@parent' => $parent_number,
        '@action' => $new_status,
        '@sid' => $submission_id,
      ]);

      // Success message.
      $message = $action === 'approve'
        ? $this->t('Thank you. Your approval has been recorded.')
        : $this->t('Your response has been recorded. The case worker will be notified.');

      $this->messenger()->addStatus($message);

      // Redirect to confirmation page.
      $form_state->setRedirect('aabenforms_workflows.parent_approval_complete', [
        'action' => $action,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to save approval: @message', [
        '@message' => $e->getMessage(),
      ]);
      $this->messenger()->addError($this->t('An error occurred while saving your response. Please try again or contact support.'));
    }
  }

}
