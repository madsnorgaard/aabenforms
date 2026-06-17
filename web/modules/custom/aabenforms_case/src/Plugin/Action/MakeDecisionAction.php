<?php

declare(strict_types=1);

namespace Drupal\aabenforms_case\Plugin\Action;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;

/**
 * ECA Action: make a lawful decision (afgørelse) on a case.
 *
 * Enforces the forvaltningslov in code so a decision cannot be issued wrongly:
 * - FVL §25: a klagevejledning is mandatory on a bebyrdende outcome (afslag /
 *   delvist medhold) and not required on full medhold.
 * - FVL §19: partshøring must be afsluttet (not still afventer) before a
 *   bebyrdende decision.
 * On success it sets the decision type, computes the appeal deadline
 * (klagefrist) for bebyrdende outcomes, transitions the case to "afgoerelse"
 * (honouring the lifecycle guard), and records an auditable revision whose log
 * carries the outcome + klagevejledning + klagefrist.
 */
#[Action(
  id: 'aabenforms_case_decide',
  label: new TranslatableMarkup('Make case decision'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Issues a lawful decision (afgørelse) with mandatory klagevejledning on adverse outcomes.'),
  version_introduced: '1.0.0',
)]
class MakeDecisionAction extends CaseActionBase {

  /**
   * Outcomes that are adverse to the citizen and require a klagevejledning.
   */
  protected const ADVERSE = ['afslag', 'delvist'];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'case_id_token' => '[case_id]',
      'afgoerelse_type' => '',
      'klagevejledning' => '',
      'klagefrist_uger' => 4,
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
    $form['afgoerelse_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Decision type'),
      '#options' => [
        'medhold' => $this->t('Medhold (granted)'),
        'delvist' => $this->t('Delvist medhold (partial)'),
        'afslag' => $this->t('Afslag (denied)'),
      ],
      '#default_value' => $this->configuration['afgoerelse_type'],
      '#required' => TRUE,
    ];
    $form['klagevejledning'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Klagevejledning'),
      '#description' => $this->t('Mandatory for adverse outcomes (afslag / delvist). Names the appeal authority (Ankestyrelsen) and the deadline.'),
      '#default_value' => $this->configuration['klagevejledning'],
      '#rows' => 3,
    ];
    $form['klagefrist_uger'] = [
      '#type' => 'number',
      '#title' => $this->t('Appeal deadline (weeks)'),
      '#min' => 1,
      '#default_value' => $this->configuration['klagefrist_uger'],
      '#required' => TRUE,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['case_id_token'] = $form_state->getValue('case_id_token');
    $this->configuration['afgoerelse_type'] = $form_state->getValue('afgoerelse_type');
    $this->configuration['klagevejledning'] = $form_state->getValue('klagevejledning');
    $this->configuration['klagefrist_uger'] = $form_state->getValue('klagefrist_uger');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      $caseId = $this->getTokenValue((string) ($this->configuration['case_id_token'] ?? '[case_id]'), '');
      $type = (string) ($this->configuration['afgoerelse_type'] ?? '');
      $klagevejledning = trim((string) ($this->configuration['klagevejledning'] ?? ''));
      $uger = max(1, (int) ($this->configuration['klagefrist_uger'] ?? 4));

      if ($caseId === '') {
        $this->recordStep('Afgørelse', 'Mangler sags-id.', 'failed');
        return;
      }
      if (!in_array($type, ['medhold', 'delvist', 'afslag'], TRUE)) {
        $this->recordStep('Afgørelse', sprintf('Ukendt afgørelsestype "%s".', $type), 'failed');
        return;
      }

      $case = $this->entityTypeManager->getStorage('aabenforms_case')->load($caseId);
      if (!$case instanceof AabenformsCase) {
        $this->recordStep('Afgørelse', sprintf('Sag #%s ikke fundet.', $caseId), 'failed');
        return;
      }

      $adverse = in_array($type, self::ADVERSE, TRUE);

      // FVL §19: partshøring must be concluded before an adverse decision.
      if ($adverse && (string) $case->get('partshoering_state')->value === 'afventer') {
        $this->recordStep('Afgørelse afvist', 'Partshøring er ikke afsluttet (FVL §19).', 'failed');
        return;
      }

      // FVL §25: a klagevejledning is mandatory on an adverse outcome.
      if ($adverse && $klagevejledning === '') {
        $this->recordStep('Afgørelse afvist', 'Bebyrdende afgørelse mangler klagevejledning (FVL §25).', 'failed');
        return;
      }

      // Lifecycle guard: the case must be in a state that may reach afgoerelse.
      $current = $case->getStatus();
      if (!in_array('afgoerelse', AabenformsCase::allowedTransitions()[$current] ?? [], TRUE)) {
        $this->recordStep('Afgørelse afvist', sprintf('Kan ikke afgøre fra status "%s".', $current), 'failed');
        return;
      }

      $now = $this->time->getRequestTime();
      $case->set('afgoerelse_type', $type);
      if ($adverse) {
        $case->set('klagefrist', $now + ($uger * 7 * 86400));
      }
      $case->setStatus('afgoerelse');
      $case->setNewRevision(TRUE);
      $logParts = [sprintf('Afgørelse: %s.', $type)];
      if ($adverse) {
        $logParts[] = 'Klagevejledning: ' . $klagevejledning;
        $logParts[] = sprintf('Klagefrist: %d uger.', $uger);
      }
      $case->setRevisionLogMessage(implode(' ', $logParts));
      $case->setRevisionCreationTime($now);
      $case->setRevisionUserId((int) $this->currentUser->id());
      $case->save();

      $this->recordStep('Afgørelse truffet', sprintf('Sag #%s: %s.', $caseId, $type));
      $this->auditLogger->log('case_decision', (string) $caseId, $type, 'success', [
        'action_id' => $this->getPluginId(),
        'adverse' => $adverse,
      ]);
    }
    catch (\Throwable $e) {
      $this->handleError($e, 'Make case decision');
    }
  }

}
