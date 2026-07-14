<?php

declare(strict_types=1);

namespace Drupal\aabenforms_case\Plugin\Action;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;

/**
 * ECA Action: set the partshøring (FVL §19) state on a case.
 *
 * "afventer" opens a hearing (in a real flow paired with a høringsbrev);
 * "afsluttet" concludes it. MakeDecisionAction blocks an adverse decision
 * while the state is "afventer". Each change is an auditable revision.
 */
#[Action(
  id: 'aabenforms_case_partshoering',
  label: new TranslatableMarkup('Set partshøring state'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Opens or concludes a party hearing (partshøring, FVL §19) on a case.'),
  version_introduced: '1.0.0',
)]
class SetPartshoeringAction extends CaseActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'case_id_token' => '[case_id]',
      'state' => 'afventer',
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
    $form['state'] = [
      '#type' => 'select',
      '#title' => $this->t('Partshøring state'),
      '#options' => [
        'afventer' => $this->t('Afventer svar (open)'),
        'afsluttet' => $this->t('Afsluttet (concluded)'),
      ],
      '#default_value' => $this->configuration['state'],
      '#required' => TRUE,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['case_id_token'] = $form_state->getValue('case_id_token');
    $this->configuration['state'] = $form_state->getValue('state');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      $caseId = $this->getTokenValue((string) ($this->configuration['case_id_token'] ?? '[case_id]'), '');
      $state = (string) ($this->configuration['state'] ?? '');

      if ($caseId === '' || !in_array($state, ['afventer', 'afsluttet'], TRUE)) {
        $this->recordStep('Partshøring', 'Mangler sags-id eller ugyldig tilstand.', 'failed');
        return;
      }

      $case = $this->entityTypeManager->getStorage('aabenforms_case')->load($caseId);
      if (!$case instanceof AabenformsCase) {
        $this->recordStep('Partshøring', sprintf('Sag #%s ikke fundet.', $caseId), 'failed');
        return;
      }

      // A hearing only makes sense while the case is being informed. Refuse to
      // touch partshøring on a decided, appealed, or closed case (that would
      // rewrite the record after the fact and, unguarded, was the path that
      // normalised skipping FVL §19).
      $status = $case->getStatus();
      if (!in_array($status, ['oplyst', 'partshoering'], TRUE)) {
        $this->recordStep('Partshøring', sprintf('Sag #%s kan ikke partshøres i status "%s".', $caseId, $status), 'failed');
        return;
      }

      $now = $this->time->getRequestTime();
      $case->set('partshoering_state', $state);
      $case->setNewRevision(TRUE);
      $case->setRevisionLogMessage($state === 'afventer' ? 'Partshøring åbnet.' : 'Partshøring afsluttet.');
      $case->setRevisionCreationTime($now);
      $case->setRevisionUserId((int) $this->currentUser->id());
      $case->save();

      $this->recordStep('Partshøring', sprintf('Sag #%s: %s.', $caseId, $state));
      $this->auditLogger->log('case_partshoering', (string) $caseId, $state, 'success', [
        'action_id' => $this->getPluginId(),
      ]);
    }
    catch (\Throwable $e) {
      $this->handleError($e, 'Set partshøring');
    }
  }

}
