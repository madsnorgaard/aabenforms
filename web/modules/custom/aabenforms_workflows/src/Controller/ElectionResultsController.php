<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Controller;

use Drupal\aabenforms_workflows\Service\ElectionService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Renders the elections list and per-election results pages.
 */
class ElectionResultsController extends ControllerBase {

  public function __construct(
    protected readonly ElectionService $election,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('aabenforms_workflows.election'));
  }

  /**
   * Lists elections (admin overview).
   */
  public function listElections(): array {
    $rows = [];
    foreach ($this->election->list(50) as $row) {
      $rows[] = [
        'data' => [
          $row['label'] ?: $row['id'],
          $row['status'],
          $this->renderTimestamp((int) $row['opens_at']),
          $this->renderTimestamp((int) $row['closes_at']),
          [
            'data' => [
              '#type' => 'link',
              '#title' => $this->t('Results'),
              '#url' => Url::fromRoute('aabenforms_workflows.election_results', ['id' => $row['id']]),
            ],
          ],
        ],
      ];
    }
    return [
      'header' => [
        '#markup' => '<h1>' . $this->t('AabenForms Elections') . '</h1>',
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Election'),
          $this->t('Status'),
          $this->t('Opens'),
          $this->t('Closes'),
          $this->t('Operations'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No elections yet. Approve a MED election nomination to create one.'),
      ],
      '#cache' => [
        'tags' => ['aabenforms_election_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Renders results for a single election.
   */
  public function viewResults(string $id): array {
    $row = $this->election->load($id);
    if (!$row) {
      throw new NotFoundHttpException();
    }
    $tally = $this->election->tabulate($id);
    $total = array_sum(array_column($tally, 'count'));

    return [
      '#theme' => 'aabenforms_election_results',
      '#election' => [
        'id' => $row['id'],
        'label' => $row['label'],
        'description' => $row['description'],
        'status' => $row['status'],
        'opens_at' => $this->renderTimestamp((int) $row['opens_at']),
        'closes_at' => $this->renderTimestamp((int) $row['closes_at']),
      ],
      '#tally' => $tally,
      '#total_ballots' => $total,
      '#cache' => [
        'tags' => ['aabenforms_election:' . $id],
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Formats a unix timestamp using Drupal's "short" date format.
   */
  protected function renderTimestamp(int $ts): string {
    return $ts ? \Drupal::service('date.formatter')->format($ts, 'short') : '—';
  }

}
