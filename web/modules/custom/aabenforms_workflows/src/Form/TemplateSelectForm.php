<?php

namespace Drupal\aabenforms_workflows\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\aabenforms_workflows\Service\BpmnTemplateManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Provides a form for browsing and managing BPMN workflow templates.
 *
 * This admin form allows users to:
 * - View all available BPMN templates
 * - Export templates as BPMN files
 * - Import custom BPMN files
 * - Delete templates.
 */
class TemplateSelectForm extends FormBase {

  /**
   * The BPMN template manager service.
   *
   * @var \Drupal\aabenforms_workflows\Service\BpmnTemplateManager
   */
  protected BpmnTemplateManager $templateManager;

  /**
   * Constructs a TemplateSelectForm object.
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aabenforms_workflows_template_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Handle export operation.
    if ($form_state->get('operation') === 'export') {
      return $this->handleExport($form_state);
    }

    // Handle delete operation.
    if ($form_state->get('operation') === 'delete') {
      return $this->buildDeleteConfirmation($form, $form_state);
    }

    $form['#attached']['library'][] = 'system/admin';

    // Description.
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Browse and manage BPMN workflow templates. These templates provide pre-built workflows for common Danish municipal use cases.') . '</p>',
    ];

    // Templates table.
    $form['templates'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Category'),
        $this->t('Description'),
        $this->t('Actions'),
      ],
      '#empty' => $this->t('No BPMN templates found.'),
    ];

    $templates = $this->templateManager->getAvailableTemplates();
    foreach ($templates as $template_id => $template) {
      $form['templates'][$template_id]['name'] = [
        '#markup' => '<strong>' . htmlspecialchars($template['name']) . '</strong><br><small>' . htmlspecialchars($template_id) . '</small>',
      ];

      $form['templates'][$template_id]['category'] = [
        '#markup' => htmlspecialchars(ucfirst(str_replace('_', ' ', $template['category']))),
      ];

      $description = $template['description'];
      // Remove category tag from description.
      $description = preg_replace('/\[category:[^\]]+\]/', '', $description);
      $description = trim($description);

      $form['templates'][$template_id]['description'] = [
        '#markup' => htmlspecialchars($description),
      ];

      // Action buttons.
      $form['templates'][$template_id]['actions'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['action-links']],
      ];

      $form['templates'][$template_id]['actions']['export'] = [
        '#type' => 'submit',
        '#value' => $this->t('Export'),
        '#name' => 'export_' . $template_id,
        '#submit' => ['::submitExport'],
        '#template_id' => $template_id,
        '#attributes' => ['class' => ['button', 'button--small']],
      ];

      $form['templates'][$template_id]['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#name' => 'delete_' . $template_id,
        '#submit' => ['::submitDelete'],
        '#template_id' => $template_id,
        '#attributes' => ['class' => ['button', 'button--small', 'button--danger']],
      ];
    }

    // Import section.
    $form['import'] = [
      '#type' => 'details',
      '#title' => $this->t('Import Custom Template'),
      '#open' => FALSE,
    ];

    $form['import']['file'] = [
      '#type' => 'file',
      '#title' => $this->t('BPMN File'),
      '#description' => $this->t('Upload a BPMN 2.0 XML file (.bpmn extension).'),
    ];

    $form['import']['template_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template ID'),
      '#description' => $this->t('Machine name for this template (e.g., custom_workflow). Only lowercase letters, numbers, and underscores allowed.'),
      '#pattern' => '^[a-z0-9_]+$',
      '#required' => FALSE,
    ];

    $form['import']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Template'),
      '#submit' => ['::submitImport'],
    ];

    return $form;
  }

  /**
   * Form submission handler for export operation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitExport(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $template_id = $triggering_element['#template_id'];

    // Set operation state for rebuild.
    $form_state->set('operation', 'export');
    $form_state->set('template_id', $template_id);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Handles the export operation.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|null
   *   The file download response or NULL.
   */
  protected function handleExport(FormStateInterface $form_state) {
    $template_id = $form_state->get('template_id');
    $file_path = $this->templateManager->exportTemplate($template_id);

    if ($file_path) {
      $response = new BinaryFileResponse($file_path);
      $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        $template_id . '.bpmn'
      );
      $form_state->setResponse($response);
      return NULL;
    }

    $this->messenger()->addError(
      $this->t('Failed to export template: @id', ['@id' => $template_id])
    );
    $form_state->set('operation', NULL);
    $form_state->setRebuild(TRUE);
    return NULL;
  }

  /**
   * Form submission handler for delete operation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitDelete(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $template_id = $triggering_element['#template_id'];

    // Set operation state for confirmation form.
    $form_state->set('operation', 'delete');
    $form_state->set('template_id', $template_id);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Builds the delete confirmation form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  protected function buildDeleteConfirmation(array $form, FormStateInterface $form_state) {
    $template_id = $form_state->get('template_id');
    $templates = $this->templateManager->getAvailableTemplates();
    $template = $templates[$template_id] ?? NULL;

    if (!$template) {
      $this->messenger()->addError(
        $this->t('Template not found: @id', ['@id' => $template_id])
      );
      $form_state->set('operation', NULL);
      $form_state->setRebuild(TRUE);
      return $form;
    }

    $form['confirmation'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('Are you sure you want to delete this template?') . '</h2>' .
      '<p><strong>' . htmlspecialchars($template['name']) . '</strong> (' . htmlspecialchars($template_id) . ')</p>' .
      '<p>' . $this->t('This action cannot be undone.') . '</p>',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#submit' => ['::confirmDelete'],
      '#attributes' => ['class' => ['button', 'button--danger']],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancelDelete'],
    ];

    return $form;
  }

  /**
   * Form submission handler for delete confirmation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function confirmDelete(array &$form, FormStateInterface $form_state) {
    $template_id = $form_state->get('template_id');

    if ($this->templateManager->deleteTemplate($template_id)) {
      $this->messenger()->addStatus(
        $this->t('Template @id has been deleted.', ['@id' => $template_id])
      );
    }
    else {
      $this->messenger()->addError(
        $this->t('Failed to delete template: @id', ['@id' => $template_id])
      );
    }

    $form_state->set('operation', NULL);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Form submission handler for delete cancellation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function cancelDelete(array &$form, FormStateInterface $form_state) {
    $form_state->set('operation', NULL);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Form submission handler for import operation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitImport(array &$form, FormStateInterface $form_state) {
    // Validate file upload.
    $validators = [
      'FileExtension' => [
        'extensions' => 'bpmn xml',
      ],
    ];

    $files = $this->getRequest()->files->get('files', []);
    if (!isset($files['file'])) {
      $form_state->setErrorByName('file', $this->t('No file was uploaded.'));
      return;
    }

    $file = $files['file'];
    $template_id = $form_state->getValue('template_id');

    if (empty($template_id)) {
      $form_state->setErrorByName('template_id', $this->t('Template ID is required.'));
      return;
    }

    // Validate template ID format.
    if (!preg_match('/^[a-z0-9_]+$/', $template_id)) {
      $form_state->setErrorByName('template_id',
        $this->t('Template ID can only contain lowercase letters, numbers, and underscores.')
      );
      return;
    }

    // Move uploaded file to temporary location.
    $destination = 'temporary://' . $file->getClientOriginalName();
    try {
      $file->move(dirname($destination), basename($destination));
      $temp_path = \Drupal::service('file_system')->realpath($destination);

      if ($this->templateManager->importTemplate($temp_path, $template_id)) {
        $this->messenger()->addStatus(
          $this->t('Template @id has been imported successfully.', ['@id' => $template_id])
        );
        // Clean up temporary file.
        @unlink($temp_path);
      }
      else {
        $this->messenger()->addError(
          $this->t('Failed to import template. Please ensure the file is a valid BPMN 2.0 XML file.')
        );
      }
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('file',
        $this->t('Error uploading file: @message', ['@message' => $e->getMessage()])
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Default submit handler - not used as we use custom submit handlers.
  }

}
