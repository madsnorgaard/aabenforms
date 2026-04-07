<?php

namespace Drupal\aabenforms_core\Controller;

use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Simple REST controller for webform access (bypasses JSON:API permissions).
 */
class WebformApiController extends ControllerBase {

  /**
   * The workflow execution collector.
   */
  protected WorkflowExecutionCollector $executionCollector;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->executionCollector = $container->get('aabenforms_core.workflow_execution_collector');
    return $instance;
  }

  /**
   * Get webform by ID.
   *
   * Route: /api/webform/{id}
   *
   * @param string $id
   *   The webform machine name.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with webform data.
   */
  public function getWebform(string $id): JsonResponse {
    $webform = Webform::load($id);

    if (!$webform) {
      return new JsonResponse(['error' => 'Webform not found'], 404);
    }

    if (!$webform->access('view')) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    $data = [
      'data' => [
        'id' => $webform->id(),
        'type' => 'webform',
        'attributes' => [
          'id' => $webform->id(),
          'title' => $webform->label(),
          'description' => $webform->get('description'),
          'elements' => $webform->getElementsDecodedAndFlattened(),
          'settings' => $webform->getSettings(),
        ],
      ],
    ];

    return new JsonResponse($data);
  }

  /**
   * Submit webform data.
   *
   * Route: /api/webform/{id}/submit.
   *
   * @param string $id
   *   The webform machine name.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with submission result and workflow execution data.
   */
  public function submitWebform(string $id, Request $request): JsonResponse {
    $webform = Webform::load($id);

    if (!$webform) {
      return new JsonResponse(['error' => 'Webform not found'], 404);
    }

    if (!$webform->access('submission_create')) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    if (!$data || !isset($data['data'])) {
      return new JsonResponse(['error' => 'Invalid submission data'], 400);
    }

    $submission_data = $data['data']['attributes']['data'] ?? $data['data'];

    $values = [
      'webform_id' => $id,
      'entity_type' => NULL,
      'entity_id' => NULL,
      'in_draft' => FALSE,
      'uid' => $this->currentUser()->id(),
      'langcode' => $this->languageManager()->getCurrentLanguage()->getId(),
      'token' => Crypt::randomBytesBase64(),
      'uri' => $request->getRequestUri(),
      'remote_addr' => $request->getClientIp(),
      'data' => $submission_data,
    ];

    try {
      $submission = $this->entityTypeManager()
        ->getStorage('webform_submission')
        ->create($values);

      // ECA workflows fire synchronously during save().
      // The WorkflowExecutionCollector captures each step.
      $submission->save();

      $this->getLogger('aabenforms_core')->notice('Webform submission created: @sid for webform @webform', [
        '@sid' => $submission->id(),
        '@webform' => $id,
      ]);

      $response_data = [
        'data' => [
          'id' => $submission->id(),
          'type' => 'webform_submission',
          'attributes' => [
            'sid' => $submission->id(),
            'created' => $submission->getCreatedTime(),
            'completed' => $submission->getCompletedTime(),
          ],
        ],
      ];

      // Append workflow execution data if any steps were collected.
      if ($this->executionCollector->hasSteps()) {
        $response_data['workflow'] = $this->executionCollector->toArray();
      }

      return new JsonResponse($response_data, 201);
    }
    catch (\Exception $e) {
      $this->getLogger('aabenforms_core')->error('Webform submission failed: @error', [
        '@error' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);

      return new JsonResponse([
        'error' => 'Submission failed',
        'message' => 'An error occurred while processing your submission. Please try again.',
      ], 500);
    }
  }

}
