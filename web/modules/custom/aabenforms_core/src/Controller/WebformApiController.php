<?php

namespace Drupal\aabenforms_core\Controller;

use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\Webform;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Simple REST controller for webform access (bypasses JSON:API permissions).
 */
class WebformApiController extends ControllerBase {

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

    // Check if user can access this webform.
    if (!$webform->access('view')) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    // Build simplified response matching frontend expectations.
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
   *   JSON response with submission result.
   */
  public function submitWebform(string $id, Request $request): JsonResponse {
    $webform = Webform::load($id);

    if (!$webform) {
      return new JsonResponse(['error' => 'Webform not found'], 404);
    }

    // Check if user can submit to this webform.
    if (!$webform->access('submission_create')) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    // Get submission data from request.
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    if (!$data || !isset($data['data'])) {
      return new JsonResponse(['error' => 'Invalid submission data'], 400);
    }

    // Extract form values and prepare submission.
    $submission_data = $data['data']['attributes']['data'] ?? $data['data'];

    $values = [
      'webform_id' => $id,
      'entity_type' => NULL,
      'entity_id' => NULL,
      'in_draft' => FALSE,
      'uid' => \Drupal::currentUser()->id(),
      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      'token' => Crypt::randomBytesBase64(),
      'uri' => $request->getRequestUri(),
      'remote_addr' => $request->getClientIp(),
      'data' => $submission_data,
    ];

    try {
      // Create submission.
      $submission = \Drupal::entityTypeManager()
        ->getStorage('webform_submission')
        ->create($values);

      // Save the submission.
      $submission->save();

      // Log the submission.
      \Drupal::logger('aabenforms_core')->notice('Webform submission created: @sid for webform @webform', [
        '@sid' => $submission->id(),
        '@webform' => $id,
      ]);

      return new JsonResponse([
        'data' => [
          'id' => $submission->id(),
          'type' => 'webform_submission',
          'attributes' => [
            'sid' => $submission->id(),
            'created' => $submission->getCreatedTime(),
            'completed' => $submission->getCompletedTime(),
          ],
        ],
      ], 201);
    }
    catch (\Exception $e) {
      // Log detailed error server-side.
      \Drupal::logger('aabenforms_core')->error('Webform submission failed: @error', [
        '@error' => $e->getMessage(),
        '@trace' => $e->getTraceAsString(),
      ]);

      // Return generic error to client (don't expose internal details).
      return new JsonResponse([
        'error' => 'Submission failed',
        'message' => 'An error occurred while processing your submission. Please try again.',
      ], 500);
    }
  }

}
