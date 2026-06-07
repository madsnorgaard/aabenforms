<?php

declare(strict_types=1);

namespace Drupal\aabenforms_workflows\Controller;

use Drupal\aabenforms_workflows\Service\EcaGraphBuilder;
use Drupal\aabenforms_workflows\Service\FlowGraphRenderer;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Renders an ECA flow as a node-graph.
 *
 * Read-only inline-SVG view (no build step) plus a JSON endpoint serving the
 * same {nodes, edges} model the editable React Flow surfaces will consume - one
 * shared graph for the dashboard, the template browser, and the modeler.
 */
class FlowGraphController extends ControllerBase {

  public function __construct(
    protected EcaGraphBuilder $graphBuilder,
    protected FlowGraphRenderer $renderer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('aabenforms_workflows.eca_graph_builder'),
      $container->get('aabenforms_workflows.flow_graph_renderer'),
    );
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
    $svg = $this->renderer->renderForFlow($eca);
    if ($svg === NULL) {
      throw new NotFoundHttpException();
    }

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['aabenforms-flow-graph']],
      'legend' => [
        '#markup' => '<p>' . $this->t('Read-only diagram generated from the live ECA flow. Branch labels show the gating condition.') . '</p>',
      ],
      'svg' => [
        '#markup' => $svg,
        '#allowed_tags' => FlowGraphRenderer::allowedTags(),
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

}
