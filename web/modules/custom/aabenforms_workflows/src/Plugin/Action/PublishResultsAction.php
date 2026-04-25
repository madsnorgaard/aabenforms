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
 * ECA Action: Publish a closed election's results.
 *
 * Flips status from "closed" to "published" so the results page
 * exposes them outside admin-only view. Optional email notification
 * is left to a downstream SendApprovalEmailAction step.
 */
#[Action(
  id: 'aabenforms_publish_results',
  label: new TranslatableMarkup('Publish election results'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Marks a closed election as publicly published.'),
  version_introduced: '2.2.0',
)]
class PublishResultsAction extends AabenFormsActionBase {

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
      'election_id_token' => '[election_id]',
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
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['election_id_token'] = (string) $form_state->getValue('election_id_token');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(mixed $entity = NULL): void {
    try {
      $id = $this->getTokenValue((string) $this->configuration['election_id_token'], '');
      if ($id === '') {
        $this->recordStep('Publish skipped', 'No election_id resolved.', 'skipped');
        return;
      }
      $this->election->publish($id);
      $this->recordStep('Results published', 'Election ' . $id . ' marked as published.', 'completed');
    }
    catch (\Throwable $e) {
      $this->handleError($e, 'PublishResultsAction');
    }
  }

}
