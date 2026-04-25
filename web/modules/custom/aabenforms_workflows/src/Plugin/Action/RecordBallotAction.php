<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_workflows\Service\ElectionService;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: Record a ballot for a MED election.
 *
 * Wired to the med_election_ballot webform's submission_insert event.
 * Reads election_id, voter CPR (from the MitID session, not the form),
 * and choice_index. Refuses duplicate ballots via the
 * UNIQUE(election_id, voter_hash) index in the database. Writes the
 * outcome to a result token so the flow can branch on success/duplicate.
 */
#[Action(
  id: 'aabenforms_record_ballot',
  label: new TranslatableMarkup('Record election ballot'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Records a single MED-election ballot, rejecting duplicates by CPR hash.'),
  version_introduced: '2.2.0',
)]
class RecordBallotAction extends AabenFormsActionBase {

  /**
   * The election service.
   *
   * @var \Drupal\aabenforms_workflows\Service\ElectionService
   */
  protected ElectionService $election;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->election = $container->get('aabenforms_workflows.election');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'election_id_token' => '[webform_submission:values:election_id:raw]',
      'choice_index_token' => '[webform_submission:values:choice_index:raw]',
      'cpr_token' => '[citizen_session:cpr]',
      'result_token' => 'ballot_result',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['election_id_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Election ID token'),
      '#default_value' => $this->configuration['election_id_token'],
      '#required' => TRUE,
    ];
    $form['choice_index_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Choice index token'),
      '#default_value' => $this->configuration['choice_index_token'],
      '#required' => TRUE,
    ];
    $form['cpr_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Voter CPR token'),
      '#description' => $this->t('Read from the MitID session - never accepted from the form input.'),
      '#default_value' => $this->configuration['cpr_token'],
      '#required' => TRUE,
    ];
    $form['result_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result token name'),
      '#default_value' => $this->configuration['result_token'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    foreach (['election_id_token', 'choice_index_token', 'cpr_token', 'result_token'] as $key) {
      $this->configuration[$key] = (string) $form_state->getValue($key);
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(mixed $entity = NULL): void {
    try {
      $election_id = $this->getTokenValue((string) $this->configuration['election_id_token'], '');
      $choice_raw = $this->getTokenValue((string) $this->configuration['choice_index_token'], '');
      $cpr = $this->getTokenValue((string) $this->configuration['cpr_token'], '');

      if ($election_id === '' || $cpr === '' || $choice_raw === '') {
        $this->writeResult('skipped', 'Missing election_id, cpr or choice_index.');
        $this->recordStep('Ballot skipped', 'Missing election_id, cpr or choice_index.', 'skipped');
        return;
      }

      $row = $this->election->load($election_id);
      if (!$row || $row['status'] !== ElectionService::STATUS_OPEN) {
        $this->writeResult('rejected', 'Election is not currently open for voting.');
        $this->recordStep('Ballot rejected', 'Election @id not open.', 'failed');
        return;
      }

      $voter_hash = $this->election->voterHashFor($election_id, $cpr);
      $choice_index = (int) $choice_raw;
      $ok = $this->election->recordBallot($election_id, $voter_hash, $choice_index);
      if (!$ok) {
        $this->writeResult('duplicate', 'Voter has already cast a ballot for this election.');
        $this->recordStep('Ballot rejected', 'Duplicate ballot for election ' . $election_id, 'failed');
        return;
      }

      $this->writeResult('recorded', 'Ballot recorded.');
      $this->recordStep('Ballot recorded', 'Election ' . $election_id . ' choice #' . $choice_index, 'completed');
    }
    catch (\Throwable $e) {
      $this->handleError($e, 'RecordBallotAction');
    }
  }

  /**
   * Helper to write the typed result.
   */
  protected function writeResult(string $status, string $message): void {
    $name = (string) $this->configuration['result_token'];
    if ($name !== '') {
      $this->setTokenValue($name, ['status' => $status, 'message' => $message]);
    }
  }

}
