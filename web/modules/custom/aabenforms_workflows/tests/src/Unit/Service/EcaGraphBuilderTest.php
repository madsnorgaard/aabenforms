<?php

declare(strict_types=1);

namespace Drupal\Tests\aabenforms_workflows\Unit\Service;

use Drupal\aabenforms_workflows\Service\EcaGraphBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ECA-to-graph converter.
 *
 * @coversDefaultClass \Drupal\aabenforms_workflows\Service\EcaGraphBuilder
 * @group aabenforms_workflows
 */
class EcaGraphBuilderTest extends UnitTestCase {

  /**
   * The builder under test.
   *
   * @var \Drupal\aabenforms_workflows\Service\EcaGraphBuilder
   */
  protected EcaGraphBuilder $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->builder = new EcaGraphBuilder($this->createMock(EntityTypeManagerInterface::class));
  }

  /**
   * A small gated flow mirroring the building_permit shape.
   */
  protected function sampleConfig(): array {
    return [
      'label' => 'sample_flow',
      'events' => [
        'on_submission' => [
          'plugin' => 'content_entity:insert',
          'configuration' => ['type' => 'webform_submission sample'],
          'successors' => [['id' => 'mitid', 'condition' => '']],
        ],
      ],
      'conditions' => [
        'gate_ok' => [
          'plugin' => 'eca_scalar',
          'configuration' => ['left' => '[x_status]', 'right' => 'verified', 'negate' => FALSE],
        ],
        'gate_deny' => [
          'plugin' => 'eca_scalar',
          'configuration' => ['left' => '[x_status]', 'right' => 'verified', 'negate' => TRUE],
        ],
      ],
      'actions' => [
        'mitid' => [
          'plugin' => 'aabenforms_mitid_validate',
          'label' => 'Validate MitID',
          'successors' => [
            ['id' => 'lookup', 'condition' => 'gate_ok'],
            ['id' => 'deny', 'condition' => 'gate_deny'],
          ],
        ],
        'lookup' => ['plugin' => 'aabenforms_cpr_lookup', 'label' => 'CPR lookup', 'successors' => []],
        'deny' => ['plugin' => 'aabenforms_workflow_deny', 'label' => 'Deny', 'successors' => []],
      ],
    ];
  }

  /**
   * Tests node typing: events, actions, and the deny terminal.
   *
   * @covers ::fromConfig
   */
  public function testNodeTypes(): void {
    $graph = $this->builder->fromConfig($this->sampleConfig());
    $types = [];
    foreach ($graph['nodes'] as $node) {
      $types[$node['id']] = $node['type'];
    }
    $this->assertSame('event', $types['on_submission']);
    $this->assertSame('action', $types['mitid']);
    $this->assertSame('action', $types['lookup']);
    $this->assertSame('deny', $types['deny'], 'aabenforms_workflow_deny becomes a deny node.');
  }

  /**
   * Tests edges carry the gating condition as a readable label.
   *
   * @covers ::fromConfig
   */
  public function testEdgesCarryConditionLabels(): void {
    $graph = $this->builder->fromConfig($this->sampleConfig());
    $byTarget = [];
    foreach ($graph['edges'] as $edge) {
      $byTarget[$edge['target']] = $edge;
    }
    $this->assertSame('', $byTarget['mitid']['label'], 'Unconditional edge has no label.');
    $this->assertSame('= verified', $byTarget['lookup']['label']);
    $this->assertSame('≠ verified', $byTarget['deny']['label']);
  }

  /**
   * Tests longest-path layering places downstream nodes further right.
   *
   * @covers ::fromConfig
   * @covers ::assignLayout
   */
  public function testLayoutLayersLeftToRight(): void {
    $graph = $this->builder->fromConfig($this->sampleConfig());
    $x = [];
    foreach ($graph['nodes'] as $node) {
      $x[$node['id']] = $node['position']['x'];
    }
    $this->assertLessThan($x['mitid'], $x['on_submission']);
    $this->assertLessThan($x['lookup'], $x['mitid']);
    $this->assertSame($x['lookup'], $x['deny'], 'Siblings share a column.');
  }

  /**
   * Tests an edge to a non-existent target is dropped (no dangling edges).
   *
   * @covers ::fromConfig
   */
  public function testDanglingEdgesDropped(): void {
    $config = $this->sampleConfig();
    $config['actions']['mitid']['successors'][] = ['id' => 'ghost', 'condition' => ''];
    $graph = $this->builder->fromConfig($config);
    foreach ($graph['edges'] as $edge) {
      $this->assertNotSame('ghost', $edge['target']);
    }
  }

}
