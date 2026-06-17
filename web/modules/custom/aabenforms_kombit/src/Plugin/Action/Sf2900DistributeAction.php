<?php

declare(strict_types=1);

namespace Drupal\aabenforms_kombit\Plugin\Action;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\aabenforms_case\Plugin\Action\CaseActionBase;
use Drupal\aabenforms_kombit\Service\Sf2900DistributionService;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: distribute a finished case to a fagsystem via SF2900.
 *
 * The wedge of the casework engine: a decided case is handed to the receiving
 * fagsystem through the Fordelingskomponent, and the **business receipt drives
 * the case state** - on ACCEPTERET the case is closed (afgoerelse → lukket)
 * with the distribution transaction id in an audited revision; on AFVIST the
 * case stays open for manual follow-up.
 */
#[Action(
  id: 'aabenforms_case_sf2900_distribute',
  label: new TranslatableMarkup('Distribute case via SF2900'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Hands a decided case to a fagsystem via SF2900; the business receipt closes the case.'),
  version_introduced: '1.0.0',
)]
class Sf2900DistributeAction extends CaseActionBase {

  /**
   * The SF2900 distribution service.
   */
  protected Sf2900DistributionService $distribution;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->distribution = $container->get('aabenforms_kombit.sf2900_distribution');
    return $instance;
  }

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
        $this->recordStep('SF2900-distribution', 'Mangler sags-id.', 'failed');
        return;
      }

      $case = $this->entityTypeManager->getStorage('aabenforms_case')->load($caseId);
      if (!$case instanceof AabenformsCase) {
        $this->recordStep('SF2900-distribution', sprintf('Sag #%s ikke fundet.', $caseId), 'failed');
        return;
      }

      // Only distribute a decided case (and the close must be a lawful move).
      if ($case->getStatus() !== 'afgoerelse'
        || !in_array('lukket', AabenformsCase::allowedTransitions()['afgoerelse'] ?? [], TRUE)) {
        $this->recordStep('SF2900-distribution', sprintf('Sag #%s er ikke afgjort - kan ikke distribueres.', $caseId), 'failed');
        return;
      }

      $result = $this->distribution->distribute($case);

      if (!$result->isAccepted()) {
        $this->recordStep('SF2900-distribution', sprintf('Sag #%s afvist af modtagersystem (%s).', $caseId, $result->receipt), 'failed');
        $this->auditLogger->log('case_sf2900_distribute', (string) $caseId, $result->receipt, 'failure', [
          'action_id' => $this->getPluginId(),
          'transaction_id' => $result->transactionId,
        ]);
        return;
      }

      $case->setStatus('lukket');
      $case->setNewRevision(TRUE);
      $case->setRevisionLogMessage(sprintf(
        'Distribueret til fagsystem via SF2900 (%s). Forretningskvittering: ACCEPTERET. Sag lukket.',
        $result->transactionId
      ));
      $case->setRevisionCreationTime($this->time->getRequestTime());
      $case->setRevisionUserId((int) $this->currentUser->id());
      $case->save();

      $this->recordStep('SF2900-distribution', sprintf('Sag #%s distribueret og lukket (%s).', $caseId, $result->transactionId));
      $this->auditLogger->log('case_sf2900_distribute', (string) $caseId, 'ACCEPTERET', 'success', [
        'action_id' => $this->getPluginId(),
        'transaction_id' => $result->transactionId,
      ]);
    }
    catch (\Throwable $e) {
      $this->handleError($e, 'SF2900 distribute');
    }
  }

}
