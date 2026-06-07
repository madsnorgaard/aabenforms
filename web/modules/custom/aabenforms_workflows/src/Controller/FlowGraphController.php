<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Controller;

use Drupal\aabenforms_workflows\Service\EcaGraphBuilder;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Renders an ECA flow as a node-graph.
 *
 * Read-only inline-SVG view (no build step) plus a JSON endpoint serving the
 * same {nodes, edges} model the editable React Flow surfaces will consume - one
 * shared graph for the dashboard, the wizard and the modeler.
 */
class FlowGraphController extends ControllerBase {

  /**
   * Node-box width, in SVG user units.
   */
  protected const NODE_W = 190;

  /**
   * Node-box height, in SVG user units.
   */
  protected const NODE_H = 54;

  public function __construct(
    protected EcaGraphBuilder $graphBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('aabenforms_workflows.eca_graph_builder'));
  }

  /**
   * Page: the flow rendered as a read-only SVG node-graph.
   *
   * @param string $eca
   *   The eca config entity id.
   *
   * @return array
   *   A render array.
   */
  public function view(string $eca): array {
    $graph = $this->graphBuilder->build($eca);
    if ($graph === NULL) {
      throw new NotFoundHttpException();
    }

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['aabenforms-flow-graph']],
      'legend' => [
        '#markup' => '<p>' . $this->t('Read-only diagram generated from the live ECA flow. Branch labels show the gating condition.') . '</p>',
      ],
      'svg' => [
        '#markup' => $this->renderSvg($graph),
        '#allowed_tags' => ['svg', 'g', 'rect', 'path', 'text', 'title', 'defs', 'marker', 'polygon', 'tspan'],
      ],
    ];
  }

  /**
   * JSON endpoint: the {label, nodes, edges} graph for programmatic renderers.
   *
   * @param string $eca
   *   The eca config entity id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The graph, or 404 when the flow does not exist.
   */
  public function json(string $eca): JsonResponse {
    $graph = $this->graphBuilder->build($eca);
    if ($graph === NULL) {
      return new JsonResponse(['error' => 'Flow not found'], 404);
    }
    return new JsonResponse($graph);
  }

  /**
   * Renders a {nodes, edges} graph to a standalone inline SVG string.
   *
   * @param array $graph
   *   The graph from EcaGraphBuilder.
   *
   * @return string
   *   SVG markup.
   */
  protected function renderSvg(array $graph): string {
    $styles = [
      'event' => ['fill' => '#e6f4ea', 'stroke' => '#137333', 'text' => '#0d652d'],
      'action' => ['fill' => '#e8f0fe', 'stroke' => '#1a73e8', 'text' => '#174ea6'],
      'deny' => ['fill' => '#fce8e6', 'stroke' => '#c5221f', 'text' => '#a50e0e'],
    ];

    $maxX = 0;
    $maxY = 0;
    $pos = [];
    foreach ($graph['nodes'] as $node) {
      $x = $node['position']['x'];
      $y = $node['position']['y'];
      $pos[$node['id']] = ['x' => $x, 'y' => $y];
      $maxX = max($maxX, $x + self::NODE_W);
      $maxY = max($maxY, $y + self::NODE_H);
    }
    $width = $maxX + 40;
    $height = $maxY + 40;

    $edgesSvg = '';
    foreach ($graph['edges'] as $edge) {
      if (!isset($pos[$edge['source']], $pos[$edge['target']])) {
        continue;
      }
      $s = $pos[$edge['source']];
      $t = $pos[$edge['target']];
      $x1 = $s['x'] + self::NODE_W;
      $y1 = $s['y'] + self::NODE_H / 2;
      $x2 = $t['x'];
      $y2 = $t['y'] + self::NODE_H / 2;
      $mx = ($x1 + $x2) / 2;
      $path = new FormattableMarkup(
        '<path d="M@x1,@y1 C@cx1,@y1 @cx2,@y2 @x2e,@y2" fill="none" stroke="#9aa0a6" stroke-width="1.5" marker-end="url(#af-arrow)" />',
        [
          '@x1' => $x1, '@y1' => $y1, '@cx1' => $mx, '@cx2' => $mx,
          '@y2' => $y2, '@x2e' => $x2 - 6,
        ],
      );
      $edgesSvg .= $path;
      if (($edge['label'] ?? '') !== '') {
        $edgesSvg .= new FormattableMarkup(
          '<text x="@x" y="@y" text-anchor="middle" font-size="11" fill="#5f6368" font-family="sans-serif"><tspan dy="-3">@label</tspan></text>',
          ['@x' => $mx, '@y' => ($y1 + $y2) / 2, '@label' => $edge['label']],
        );
      }
    }

    $nodesSvg = '';
    foreach ($graph['nodes'] as $node) {
      $style = $styles[$node['type']] ?? $styles['action'];
      $x = $pos[$node['id']]['x'];
      $y = $pos[$node['id']]['y'];
      $label = $this->truncate($node['label'], 26);
      $nodesSvg .= new FormattableMarkup(
        '<g><title>@full</title>'
        . '<rect x="@x" y="@y" rx="8" ry="8" width="@w" height="@h" fill="@fill" stroke="@stroke" stroke-width="1.5" />'
        . '<text x="@tx" y="@ty" text-anchor="middle" font-size="12" font-family="sans-serif" fill="@text">@label</text>'
        . '</g>',
        [
          '@full' => $node['label'] . ' (' . $node['type'] . ')',
          '@x' => $x, '@y' => $y, '@w' => self::NODE_W, '@h' => self::NODE_H,
          '@fill' => $style['fill'], '@stroke' => $style['stroke'], '@text' => $style['text'],
          '@tx' => $x + self::NODE_W / 2, '@ty' => $y + self::NODE_H / 2 + 4,
          '@label' => $label,
        ],
      );
    }

    $title = Html::escape($graph['label'] ?? 'flow');
    return '<svg viewBox="0 0 ' . $width . ' ' . $height . '" width="100%" '
      . 'style="max-width:' . $width . 'px" role="img" aria-label="' . $title . ' flow diagram" '
      . 'xmlns="http://www.w3.org/2000/svg">'
      . '<defs><marker id="af-arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">'
      . '<path d="M0,0 L10,5 L0,10 z" fill="#9aa0a6" /></marker></defs>'
      . $edgesSvg . $nodesSvg
      . '</svg>';
  }

  /**
   * Truncates a label for display inside a fixed-width node box.
   */
  protected function truncate(string $text, int $max): string {
    $text = Html::escape($text);
    if (mb_strlen($text) <= $max) {
      return $text;
    }
    return mb_substr($text, 0, $max - 1) . '…';
  }

}
