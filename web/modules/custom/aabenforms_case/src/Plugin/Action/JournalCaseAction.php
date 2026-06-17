<?php

declare(strict_types=1);

namespace Drupal\aabenforms_case\Plugin\Action;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;

/**
 * ECA Action: journalise a case into the Sags- og Dokumentindeks (SF1470).
 *
 * Registers the case so it surfaces across the ecosystem (SAPA/Borgerblikket).
 * Stores the returned reference on the case's journal_ref field and records an
 * auditable revision. Idempotent: an already-journalised case keeps its
 * reference.
 *
 * DEMO: the reference is synthesised deterministically from the case UUID
 * (SDI-DEMO-XXXXXXXX). When a Serviceplatform certificate is configured this
 * is where a real SF1470 SOAP registration would run; the action contract
 * (case in, journal_ref out) stays the same.
 */
#[Action(
  id: 'aabenforms_case_journal',
  label: new TranslatableMarkup('Journalise case (SF1470)'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Registers the case in the Sags- og Dokumentindeks (SF1470); demo unless a Serviceplatform certificate is configured.'),
  version_introduced: '1.0.0',
)]
class JournalCaseAction extends CaseActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'case_id_token' => '[case_id]',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['case_id_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Case id token'),
      '#default_value' => $this->configuration['case_id_token'],
      '#required' => TRUE,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['case_id_token'] = $form_state->getValue('case_id_token');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      $caseId = $this->getTokenValue((string) ($this->configuration['case_id_token'] ?? '[case_id]'), '');
      if ($caseId === '') {
        $this->recordStep('Journalisering', 'Mangler sags-id.', 'failed');
        return;
      }

      $case = $this->entityTypeManager->getStorage('aabenforms_case')->load($caseId);
      if (!$case instanceof AabenformsCase) {
        $this->recordStep('Journalisering', sprintf('Sag #%s ikke fundet.', $caseId), 'failed');
        return;
      }

      // Idempotent: keep an existing reference.
      $existing = (string) ($case->get('journal_ref')->value ?? '');
      if ($existing !== '') {
        $this->recordStep('Journalisering', sprintf('Sag #%s allerede journaliseret (%s).', $caseId, $existing));
        return;
      }

      // DEMO reference derived from the case UUID. Real SF1470 SOAP goes here.
      $ref = 'SDI-DEMO-' . strtoupper(substr(str_replace('-', '', (string) $case->uuid()), 0, 8));
      $case->set('journal_ref', $ref);
      $case->setNewRevision(TRUE);
      $case->setRevisionLogMessage(sprintf('Journaliseret i Sags- og Dokumentindeks: %s.', $ref));
      $case->setRevisionCreationTime($this->time->getRequestTime());
      $case->setRevisionUserId((int) $this->currentUser->id());
      $case->save();

      $this->setTokenValue('journal_ref', $ref);
      $this->recordStep('Journalisering', sprintf('Sag #%s journaliseret: %s.', $caseId, $ref));
      $this->auditLogger->log('case_journaled', (string) $caseId, $ref, 'success', [
        'action_id' => $this->getPluginId(),
        'demo' => TRUE,
      ]);
    }
    catch (\Throwable $e) {
      $this->handleError($e, 'Journalise case');
    }
  }

}
