<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;

/**
 * Renders an EcaGraphBuilder {nodes, edges} graph to a standalone inline SVG.
 *
 * Shared by every read-only surface (the per-flow page and the template-browser
 * cards) so they draw flows identically, with no JS build step.
 */
class FlowGraphRenderer {

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
   * Builds and renders the graph for a stored ECA flow.
   *
   * @param string $ecaId
   *   The eca config entity id.
   *
   * @return string|null
   *   SVG markup, or NULL when the flow does not exist.
   */
  public function renderForFlow(string $ecaId): ?string {
    $graph = $this->graphBuilder->build($ecaId);
    return $graph === NULL ? NULL : $this->render($graph);
  }

  /**
   * Renders a {nodes, edges} graph to an inline SVG string.
   *
   * @param array $graph
   *   The graph from EcaGraphBuilder.
   *
   * @return string
   *   SVG markup.
   */
  public function render(array $graph): string {
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
      $edgesSvg .= new FormattableMarkup(
        '<path d="M@x1,@y1 C@cx,@y1 @cx,@y2 @x2e,@y2" fill="none" stroke="#9aa0a6" stroke-width="1.5" marker-end="url(#af-arrow)" />',
        ['@x1' => $x1, '@y1' => $y1, '@cx' => $mx, '@y2' => $y2, '@x2e' => $x2 - 6],
      );
      if (($edge['label'] ?? '') !== '') {
        $edgesSvg .= new FormattableMarkup(
          '<text x="@x" y="@y" text-anchor="middle" font-size="11" fill="#5f6368" font-family="sans-serif">@label</text>',
          ['@x' => $mx, '@y' => ($y1 + $y2) / 2 - 4, '@label' => $edge['label']],
        );
      }
    }

    $nodesSvg = '';
    foreach ($graph['nodes'] as $node) {
      $style = $styles[$node['type']] ?? $styles['action'];
      $x = $pos[$node['id']]['x'];
      $y = $pos[$node['id']]['y'];
      $nodesSvg .= new FormattableMarkup(
        '<g><title>@full</title>'
        . '<rect x="@x" y="@y" rx="8" ry="8" width="@w" height="@h" fill="@fill" stroke="@stroke" stroke-width="1.5" />'
        . '<text x="@tx" y="@ty" text-anchor="middle" font-size="12" font-family="sans-serif" fill="@text">@label</text>'
        . '</g>',
        [
          '@full' => $node['label'] . ' (' . $node['type'] . ')',
          '@x' => $x,
          '@y' => $y,
          '@w' => self::NODE_W,
          '@h' => self::NODE_H,
          '@fill' => $style['fill'],
          '@stroke' => $style['stroke'],
          '@text' => $style['text'],
          '@tx' => $x + self::NODE_W / 2,
          '@ty' => $y + self::NODE_H / 2 + 4,
          '@label' => $this->truncate($node['label'], 26),
        ],
      );
    }

    $title = Html::escape($graph['label'] ?? 'flow');
    return '<svg viewBox="0 0 ' . $width . ' ' . $height . '" width="100%" '
      . 'role="img" aria-label="' . $title . ' flow diagram" '
      . 'xmlns="http://www.w3.org/2000/svg">'
      . '<defs><marker id="af-arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">'
      . '<path d="M0,0 L10,5 L0,10 z" fill="#9aa0a6" /></marker></defs>'
      . $edgesSvg . $nodesSvg
      . '</svg>';
  }

  /**
   * The SVG tags a renderer surface must allow through Xss/markup filtering.
   *
   * @return string[]
   *   Allowed tag names.
   */
  public static function allowedTags(): array {
    return ['svg', 'g', 'rect', 'path', 'text', 'title', 'defs', 'marker', 'polygon', 'tspan'];
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
