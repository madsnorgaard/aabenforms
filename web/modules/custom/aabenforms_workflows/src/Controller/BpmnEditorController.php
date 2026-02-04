<?php

namespace Drupal\aabenforms_workflows\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\aabenforms_workflows\Service\BpmnTemplateManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for BPMN editor AJAX operations.
 */
class BpmnEditorController extends ControllerBase {

  /**
   * The BPMN template manager service.
   *
   * @var \Drupal\aabenforms_workflows\Service\BpmnTemplateManager
   */
  protected BpmnTemplateManager $templateManager;

  /**
   * Constructs a BpmnEditorController object.
   *
   * @param \Drupal\aabenforms_workflows\Service\BpmnTemplateManager $template_manager
   *   The BPMN template manager service.
   */
  public function __construct(BpmnTemplateManager $template_manager) {
    $this->templateManager = $template_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aabenforms_workflows.bpmn_template_manager')
    );
  }

  /**
   * Auto-saves BPMN XML from the editor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with save status.
   */
  public function autosave(Request $request): JsonResponse {
    $bpmn_xml = $request->request->get('bpmn_xml');

    if (empty($bpmn_xml)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'No BPMN XML provided',
      ], 400);
    }

    // Store in session for later use.
    $tempstore = \Drupal::service('tempstore.private')->get('aabenforms_workflows');
    $tempstore->set('bpmn_xml_draft', $bpmn_xml);

    return new JsonResponse([
      'success' => TRUE,
      'message' => 'BPMN XML auto-saved',
      'timestamp' => time(),
    ]);
  }

  /**
   * Validates BPMN XML structure.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with validation results.
   */
  public function validate(Request $request): JsonResponse {
    $bpmn_xml = $request->request->get('bpmn_xml');

    if (empty($bpmn_xml)) {
      return new JsonResponse([
        'valid' => FALSE,
        'errors' => ['No BPMN XML provided'],
      ], 400);
    }

    // Validate using BpmnTemplateManager.
    $is_valid = $this->templateManager->validateTemplate($bpmn_xml);
    $errors = $is_valid ? [] : $this->templateManager->getValidationErrors();

    return new JsonResponse([
      'valid' => $is_valid,
      'errors' => $errors,
      'timestamp' => time(),
    ]);
  }

  /**
   * Exports BPMN XML as downloadable file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   File download response.
   */
  public function export(Request $request) {
    $bpmn_xml = $request->request->get('bpmn_xml');
    $filename = $request->request->get('filename', 'workflow.bpmn');

    if (empty($bpmn_xml)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'No BPMN XML provided',
      ], 400);
    }

    $response = new \Symfony\Component\HttpFoundation\Response($bpmn_xml);
    $response->headers->set('Content-Type', 'application/xml');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

    return $response;
  }

  /**
   * Generates SVG preview from BPMN XML.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with SVG data.
   */
  public function generateSvg(Request $request): JsonResponse {
    $bpmn_xml = $request->request->get('bpmn_xml');

    if (empty($bpmn_xml)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'No BPMN XML provided',
      ], 400);
    }

    // For now, return placeholder.
    // In a production system, you would use a server-side BPMN renderer.
    return new JsonResponse([
      'success' => TRUE,
      'message' => 'SVG generation not yet implemented - use client-side rendering',
    ]);
  }

}
