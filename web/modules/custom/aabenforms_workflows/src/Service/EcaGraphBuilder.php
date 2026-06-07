<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Converts an ECA flow into a renderable {nodes, edges} graph.
 *
 * One shared data source for every surface that visualises a flow - the
 * dashboard, the creation wizard, and the modeler - so they all draw the same
 * picture straight from the ECA event/condition/action/successor graph rather
 * than from three different representations (bpmn-js, React Flow, server text).
 *
 * Conditions are NOT nodes - in ECA they are predicates that gate an action's
 * successor edge - so they surface as edge labels. Actions whose plugin is the
 * deny terminal render as a distinct "deny" node type.
 */
class EcaGraphBuilder {

  /**
   * Horizontal distance between layers, in layout units.
   */
  protected const COL_WIDTH = 240;

  /**
   * Vertical distance between sibling nodes, in layout units.
   */
  protected const ROW_HEIGHT = 96;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Builds the graph for a stored ECA flow by id.
   *
   * @param string $ecaId
   *   The eca config entity id.
   *
   * @return array|null
   *   {label, nodes, edges}, or NULL when the flow does not exist.
   */
  public function build(string $ecaId): ?array {
    $eca = $this->entityTypeManager->getStorage('eca')->load($ecaId);
    if ($eca === NULL) {
      return NULL;
    }
    return $this->fromConfig([
      'label' => (string) ($eca->label() ?? $ecaId),
      'events' => $eca->get('events') ?? [],
      'conditions' => $eca->get('conditions') ?? [],
      'actions' => $eca->get('actions') ?? [],
    ]);
  }

  /**
   * Pure transform from raw ECA config arrays to a graph.
   *
   * Kept separate from entity loading so it is unit-testable without a
   * database.
   *
   * @param array $config
   *   Keys: label, events, conditions, actions (the ECA config arrays).
   *
   * @return array
   *   {label, nodes, edges}.
   */
  public function fromConfig(array $config): array {
    $events = $config['events'] ?? [];
    $conditions = $config['conditions'] ?? [];
    $actions = $config['actions'] ?? [];

    $nodes = [];
    foreach ($events as $id => $event) {
      $nodes[$id] = [
        'id' => $id,
        'type' => 'event',
        'label' => (string) ($event['label'] ?? $this->humanizeEvent($event)),
        'plugin' => (string) ($event['plugin'] ?? ''),
      ];
    }
    foreach ($actions as $id => $action) {
      $plugin = (string) ($action['plugin'] ?? '');
      $nodes[$id] = [
        'id' => $id,
        'type' => $plugin === 'aabenforms_workflow_deny' ? 'deny' : 'action',
        'label' => (string) ($action['label'] ?? $id),
        'plugin' => $plugin,
      ];
    }

    $edges = [];
    foreach ([$events, $actions] as $set) {
      foreach ($set as $sourceId => $component) {
        foreach (($component['successors'] ?? []) as $successor) {
          $targetId = $successor['id'] ?? NULL;
          if ($targetId === NULL || !isset($nodes[$targetId])) {
            continue;
          }
          $conditionId = (string) ($successor['condition'] ?? '');
          $edges[] = [
            'id' => $sourceId . '->' . $targetId,
            'source' => $sourceId,
            'target' => $targetId,
            'condition' => $conditionId,
            'label' => $this->conditionLabel($conditionId, $conditions),
          ];
        }
      }
    }

    $this->assignLayout($nodes, $edges);

    return [
      'label' => (string) ($config['label'] ?? ''),
      'nodes' => array_values($nodes),
      'edges' => $edges,
    ];
  }

  /**
   * Assigns each node an (x, y) position from a longest-path layering.
   *
   * Roots (no incoming edge - the events) sit in column 0; each node's column
   * is the longest path from any root, so a node always sits to the right of
   * everything that can reach it. Within a column, nodes stack vertically.
   *
   * @param array $nodes
   *   Node map keyed by id; mutated in place to add 'position'.
   * @param array $edges
   *   Edge list.
   */
  protected function assignLayout(array &$nodes, array $edges): void {
    $depth = [];
    foreach ($nodes as $id => $node) {
      $depth[$id] = 0;
    }

    // Relax longest-path depths. Bounded iteration count guards against cycles
    // (ECA flows are acyclic, but a malformed flow must never loop forever).
    $maxPasses = count($nodes) + 1;
    for ($pass = 0; $pass < $maxPasses; $pass++) {
      $changed = FALSE;
      foreach ($edges as $edge) {
        $s = $edge['source'];
        $t = $edge['target'];
        if (!isset($depth[$s]) || !isset($depth[$t])) {
          continue;
        }
        if ($depth[$t] < $depth[$s] + 1) {
          $depth[$t] = $depth[$s] + 1;
          $changed = TRUE;
        }
      }
      if (!$changed) {
        break;
      }
    }

    $rowInColumn = [];
    foreach ($nodes as $id => &$node) {
      $col = $depth[$id];
      $row = $rowInColumn[$col] ?? 0;
      $rowInColumn[$col] = $row + 1;
      $node['position'] = [
        'x' => $col * self::COL_WIDTH + 40,
        'y' => $row * self::ROW_HEIGHT + 40,
      ];
    }
  }

  /**
   * Produces a short human label for a gating condition id.
   *
   * @param string $conditionId
   *   The condition id referenced by a successor (empty = unconditional).
   * @param array $conditions
   *   The flow's conditions config.
   *
   * @return string
   *   A short edge label ('' for an unconditional edge).
   */
  protected function conditionLabel(string $conditionId, array $conditions): string {
    if ($conditionId === '') {
      return '';
    }
    // Prefer a meaningful comparison when the condition is an eca_scalar gate.
    $config = $conditions[$conditionId]['configuration'] ?? [];
    if (isset($config['right'])) {
      $negate = !empty($config['negate']);
      return ($negate ? '≠ ' : '= ') . (string) $config['right'];
    }
    // Otherwise humanise the id (gate_building_mitid_ok -> "building mitid ok").
    $text = preg_replace('/^gate_/', '', $conditionId);
    return trim(str_replace('_', ' ', (string) $text));
  }

  /**
   * Humanises an event whose plugin has no explicit label.
   */
  protected function humanizeEvent(array $event): string {
    $plugin = (string) ($event['plugin'] ?? '');
    $type = (string) ($event['configuration']['type'] ?? '');
    if (str_contains($plugin, 'insert')) {
      return $type !== '' ? 'Submission created' : 'Created';
    }
    if (str_contains($plugin, 'update')) {
      return 'Submission updated';
    }
    if (str_contains($plugin, 'custom')) {
      return 'Custom event';
    }
    return $plugin !== '' ? $plugin : 'Event';
  }

}
