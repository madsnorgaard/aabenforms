<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_workflows\Service\ParentCprVerifier;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: verify the MitID-asserted CPR matches the consenting parent.
 *
 * Wraps the authoritative parent-approval consent gate (ParentCprVerifier,
 * issue #54) so the MATCH / MISMATCH decision runs inside the workflow and
 * shows up as a token and a replay step. This does NOT replace the hard 403
 * enforced in ParentApprovalController::mitidComplete() - it is the same
 * decision surfaced into the flow for visibility and audit (defence in depth).
 */
#[Action(
  id: 'aabenforms_parent_cpr_verify',
  label: new TranslatableMarkup('Verify Parent CPR Consent'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Verifies the MitID-asserted CPR equals the expected parent CPR (issue #54 consent gate) and records the decision as a token and replay step.'),
  version_introduced: '2.1.0',
)]
class ParentCprVerifyAction extends AabenFormsActionBase {

  /**
   * The parent-approval CPR verifier (issue #54 consent gate).
   *
   * @var \Drupal\aabenforms_workflows\Service\ParentCprVerifier
   */
  protected ParentCprVerifier $cprVerifier;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->cprVerifier = $container->get('aabenforms_workflows.parent_cpr_verifier');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      // Which parent this step verifies (1 or 2).
      'parent_number' => '1',
      // Optional token holding the MitID workflow id. Leave empty to derive
      // the deterministic id the approval controller uses (recommended).
      'workflow_id_token' => '',
      // Token to store the verification result (one of ParentCprVerifier's
      // RESULT_* constants: match / mismatch / missing_mitid_cpr /
      // missing_expected_cpr).
      'result_token' => 'cpr_consent_result',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['parent_number'] = [
      '#type' => 'select',
      '#title' => $this->t('Parent number'),
      '#description' => $this->t('Which parent this consent check verifies.'),
      '#options' => ['1' => $this->t('Parent 1'), '2' => $this->t('Parent 2')],
      '#default_value' => $this->configuration['parent_number'],
      '#required' => TRUE,
    ];

    $form['workflow_id_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Workflow ID token (optional)'),
      '#description' => $this->t('Token holding the MitID workflow id. Leave empty to derive parent_approval_&lt;sid&gt;_p&lt;N&gt; - the id the approval controller stores the session under.'),
      '#default_value' => $this->configuration['workflow_id_token'],
    ];

    $form['result_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result token name'),
      '#description' => $this->t('Token to store the consent decision (match / mismatch / missing_mitid_cpr / missing_expected_cpr).'),
      '#default_value' => $this->configuration['result_token'],
      '#required' => TRUE,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['parent_number'] = $form_state->getValue('parent_number');
    $this->configuration['workflow_id_token'] = $form_state->getValue('workflow_id_token');
    $this->configuration['result_token'] = $form_state->getValue('result_token');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $resultToken = $this->configuration['result_token'] ?? 'cpr_consent_result';
    $parentNumber = (int) ($this->configuration['parent_number'] ?? 1);
    $submission = $this->getSubmission();

    if ($submission === NULL) {
      // No submission in context (e.g. a cross-fired event). Do not fail open:
      // record the gate as unverified rather than silently passing.
      $this->log('Parent CPR consent: no submission in context - not verified', [], 'info');
      $this->setTokenValue($resultToken, ParentCprVerifier::RESULT_MISSING_EXPECTED_CPR);
      $this->recordStep('Parent CPR Consent Check', 'No submission in context - consent not verified', 'failed');
      return;
    }

    // Resolve the MitID workflow id. Prefer an explicit token override; else
    // derive the deterministic id the approval controller uses, so we read the
    // same session it stored. MUST stay in sync with
    // ParentApprovalController::mitidLogin()/mitidComplete().
    $workflowId = $this->getTokenValue($this->configuration['workflow_id_token'] ?? '', '');
    if ($workflowId === '') {
      $workflowId = sprintf('parent_approval_%d_p%d', (int) $submission->id(), $parentNumber);
    }

    try {
      // ParentCprVerifier performs the comparison and writes the audit trail
      // (hashed CPRs, never raw) for every outcome.
      $result = $this->cprVerifier->verify($submission, $parentNumber, $workflowId);
      $this->setTokenValue($resultToken, $result);

      [$description, $status] = match ($result) {
        ParentCprVerifier::RESULT_MATCH => [
          sprintf('MitID-asserted CPR matches parent %d - consent verified', $parentNumber),
          'completed',
        ],
        ParentCprVerifier::RESULT_MISMATCH => [
          sprintf('MitID-asserted CPR does NOT match parent %d - consent denied', $parentNumber),
          'failed',
        ],
        ParentCprVerifier::RESULT_MISSING_MITID_CPR => [
          'MitID returned no CPR claim - consent cannot be verified',
          'failed',
        ],
        ParentCprVerifier::RESULT_MISSING_EXPECTED_CPR => [
          sprintf('Submission carries no parent %d CPR - consent not enforced (legacy form)', $parentNumber),
          'completed',
        ],
        default => ['Unknown verification result', 'failed'],
      };
      $this->recordStep('Parent CPR Consent Check', $description, $status);
    }
    catch (\Throwable $e) {
      // verify() does not throw today, but never fail open: on any error treat
      // the gate as a mismatch (deny) and surface it.
      $this->handleError($e, 'Parent CPR Consent Check');
      $this->setTokenValue($resultToken, ParentCprVerifier::RESULT_MISMATCH);
    }
  }

}
