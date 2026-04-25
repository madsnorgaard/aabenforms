<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_workflows\Service\ElectionService;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: Find elections whose voting window has lapsed and tabulate them.
 *
 * Wired to ECA's cron event. Walks {aabenforms_election}, opens any
 * pending elections whose opens_at has passed, closes + tabulates any
 * open elections whose closes_at has passed. Idempotent: ignores
 * already-open / already-closed rows.
 */
#[Action(
  id: 'aabenforms_tabulate_election',
  label: new TranslatableMarkup('Tabulate due elections'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Cron-triggered: opens pending elections whose start time has passed; closes + tallies open elections whose end time has passed.'),
  version_introduced: '2.2.0',
)]
class TabulateElectionAction extends AabenFormsActionBase {

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
  public function execute(mixed $entity = NULL): void {
    try {
      $stale = $this->election->findStaleWindows();
      foreach ($stale['to_open'] as $id) {
        $this->election->openWindow($id);
      }
      foreach ($stale['to_close'] as $id) {
        $this->election->closeWindow($id);
      }
      $opened = count($stale['to_open']);
      $closed = count($stale['to_close']);
      if ($opened || $closed) {
        $this->recordStep(
          'Election windows transitioned',
          sprintf('Opened %d, closed %d.', $opened, $closed),
          'completed',
        );
      }
    }
    catch (\Throwable $e) {
      $this->handleError($e, 'TabulateElectionAction');
    }
  }

}
