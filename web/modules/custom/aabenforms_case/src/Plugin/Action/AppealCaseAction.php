<?php

declare(strict_types=1);

namespace Drupal\aabenforms_case\Plugin\Action;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;

/**
 * ECA Action: lodge an appeal (klage) against a decided case.
 *
 * Moves a case from "afgoerelse" to "paaklaget" (the lifecycle guard rejects
 * an appeal of a case that has not been decided), stores the appeal grounds in
 * an auditable revision, and audits the event. The genvurdering outcome is then
 * a normal caseworker decision (paaklaget → afgoerelse) or a close
 * (paaklaget → lukket), both already supported.
 */
#[Action(
  id: 'aabenforms_case_appeal',
  label: new TranslatableMarkup('Appeal case (klage)'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Lodges an appeal against a decided case, moving it to paaklaget for genvurdering.'),
  version_introduced: '1.0.0',
)]
class AppealCaseAction extends CaseActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'case_id_token' => '[webform_submission:values:case_id]',
      'grounds_token' => '[webform_submission:values:klage_begrundelse]',
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
    $form['grounds_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Appeal grounds token'),
      '#default_value' => $this->configuration['grounds_token'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['case_id_token'] = $form_state->getValue('case_id_token');
    $this->configuration['grounds_token'] = $form_state->getValue('grounds_token');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      $caseId = $this->getTokenValue((string) ($this->configuration['case_id_token'] ?? ''), '');
      $grounds = trim($this->getTokenValue((string) ($this->configuration['grounds_token'] ?? ''), ''));

      if ($caseId === '') {
        $this->recordStep('Klage', 'Mangler sags-id.', 'failed');
        return;
      }

      $case = $this->entityTypeManager->getStorage('aabenforms_case')->load($caseId);
      if (!$case instanceof AabenformsCase) {
        $this->recordStep('Klage', sprintf('Sag #%s ikke fundet.', $caseId), 'failed');
        return;
      }

      $current = $case->getStatus();
      if (!in_array('paaklaget', AabenformsCase::allowedTransitions()[$current] ?? [], TRUE)) {
        $this->recordStep('Klage afvist', sprintf('Kan ikke påklage en sag med status "%s".', $current), 'failed');
        return;
      }

      $case->setStatus('paaklaget');
      $case->setNewRevision(TRUE);
      $log = 'Klage modtaget.';
      if ($grounds !== '') {
        $log .= ' Begrundelse: ' . $grounds;
      }
      $case->setRevisionLogMessage($log);
      $case->setRevisionCreationTime($this->time->getRequestTime());
      $case->setRevisionUserId((int) $this->currentUser->id());
      $case->save();

      $this->recordStep('Klage modtaget', sprintf('Sag #%s sendt til genvurdering.', $caseId));
      $this->auditLogger->log('case_appeal', (string) $caseId, 'paaklaget', 'success', [
        'action_id' => $this->getPluginId(),
      ]);
    }
    catch (\Throwable $e) {
      $this->handleError($e, 'Appeal case');
    }
  }

}
