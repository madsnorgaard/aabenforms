<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh\Plugin\Action;

use Drupal\aabenforms_case\Entity\AabenformsCase;
use Drupal\aabenforms_case\Plugin\Action\CaseActionBase;
use Drupal\aabenforms_esdh\Model\EsdhResult;
use Drupal\aabenforms_esdh\Service\EsdhConnectorResolver;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: journalise a case into the kommune's ESDH (system of record).
 *
 * Complements aabenforms_case_journal (SF1470, the fælleskommunale index): this
 * hands the sag to the actual ESDH (SBSYS / WorkZone / Acadre / GetOrganized)
 * via the configured connector and stores the returned reference on the case.
 *
 * Idempotent: a case that already carries an esdh_ref is left untouched.
 * On a transient failure the case is NOT advanced - the step is marked failed
 * so a flow can gate its close on it (never close on a retry-able error).
 */
#[Action(
  id: 'aabenforms_case_esdh_journal',
  label: new TranslatableMarkup('Journalise case into ESDH'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Hands the case to the configured ESDH (SBSYS/WorkZone/Acadre/GetOrganized); demo unless a connector is configured.'),
  version_introduced: '1.0.0',
)]
class EsdhJournalAction extends CaseActionBase {

  /**
   * The connector resolver.
   */
  protected EsdhConnectorResolver $resolver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->resolver = $container->get('aabenforms_esdh.connector_resolver');
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
        $this->recordStep('ESDH-journalisering', 'Mangler sags-id.', 'failed');
        return;
      }

      $case = $this->entityTypeManager->getStorage('aabenforms_case')->load($caseId);
      if (!$case instanceof AabenformsCase) {
        $this->recordStep('ESDH-journalisering', sprintf('Sag #%s ikke fundet.', $caseId), 'failed');
        return;
      }

      // Idempotent: keep an existing ESDH reference.
      $existing = (string) ($case->get('esdh_ref')->value ?? '');
      if ($existing !== '') {
        $this->recordStep('ESDH-journalisering', sprintf('Sag #%s allerede journaliseret i ESDH (%s).', $caseId, $existing));
        return;
      }

      $connector = $this->resolver->resolve();
      $result = $connector->journaliseCase($case);

      if (!$result->isJournalised()) {
        // A failure (transient or permanent) must not advance the case; the
        // failed step lets a flow gate its close rather than closing blindly.
        $suffix = $result->transient ? ' (midlertidig - forsøges igen)' : '';
        $this->recordStep('ESDH-journalisering', sprintf('Sag #%s afvist af ESDH%s: %s', $caseId, $suffix, $result->message), 'failed');
        $this->auditLogger->log('case_esdh_journal', (string) $caseId, $result->esdhSystem, 'failure', [
          'action_id' => $this->getPluginId(),
          'transient' => $result->transient,
        ]);
        return;
      }

      $case->set('esdh_ref', $result->reference);
      $case->set('esdh_system', $result->esdhSystem);
      $case->setNewRevision(TRUE);
      $case->setRevisionLogMessage(sprintf('Journaliseret i ESDH (%s): %s.', $result->esdhSystem, $result->reference));
      $case->setRevisionCreationTime($this->time->getRequestTime());
      $case->setRevisionUserId((int) $this->currentUser->id());
      $case->save();

      $this->setTokenValue('esdh_ref', $result->reference);
      $this->recordStep('ESDH-journalisering', sprintf('Sag #%s journaliseret i ESDH (%s): %s.', $caseId, $result->esdhSystem, $result->reference));
      $this->auditLogger->log('case_esdh_journal', (string) $caseId, $result->reference, 'success', [
        'action_id' => $this->getPluginId(),
        'esdh_system' => $result->esdhSystem,
        'demo' => $connector->isDemo(),
      ]);
    }
    catch (\Throwable $e) {
      $this->handleError($e, 'ESDH journalise');
    }
  }

}
