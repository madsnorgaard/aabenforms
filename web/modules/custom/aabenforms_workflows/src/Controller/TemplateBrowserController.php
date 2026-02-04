<?php

namespace Drupal\aabenforms_workflows\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\aabenforms_workflows\Service\BpmnTemplateManager;
use Drupal\aabenforms_workflows\Service\WorkflowTemplateInstantiator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the workflow template browser page.
 *
 * Provides a user-friendly interface for browsing templates and
 * viewing already-created workflow instances.
 */
class TemplateBrowserController extends ControllerBase {

  /**
   * The BPMN template manager service.
   *
   * @var \Drupal\aabenforms_workflows\Service\BpmnTemplateManager
   */
  protected BpmnTemplateManager $templateManager;

  /**
   * The template instantiator service.
   *
   * @var \Drupal\aabenforms_workflows\Service\WorkflowTemplateInstantiator
   */
  protected WorkflowTemplateInstantiator $instantiator;

  /**
   * Constructs a TemplateBrowserController object.
   *
   * @param \Drupal\aabenforms_workflows\Service\BpmnTemplateManager $template_manager
   *   The BPMN template manager service.
   * @param \Drupal\aabenforms_workflows\Service\WorkflowTemplateInstantiator $instantiator
   *   The template instantiator service.
   */
  public function __construct(
    BpmnTemplateManager $template_manager,
    WorkflowTemplateInstantiator $instantiator,
  ) {
    $this->templateManager = $template_manager;
    $this->instantiator = $instantiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aabenforms_workflows.bpmn_template_manager'),
      $container->get('aabenforms_workflows.template_instantiator')
    );
  }

  /**
   * Displays the template browser page.
   *
   * @return array
   *   Render array.
   */
  public function browse(): array {
    $build = [];

    // Page header.
    $build['header'] = [
      '#markup' => '<div class="template-browser-header">' .
      '<h1>' . $this->t('Workflow Templates') . '</h1>' .
      '<p>' . $this->t('Create new approval workflows from pre-built templates designed for Danish municipalities. No technical knowledge required.') . '</p>' .
      '</div>',
    ];

    // Available templates section.
    $build['templates_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Available Templates'),
      '#open' => TRUE,
    ];

    $templates = $this->templateManager->getAvailableTemplates();

    if (empty($templates)) {
      $build['templates_section']['empty'] = [
        '#markup' => '<p>' . $this->t('No workflow templates available.') . '</p>',
      ];
    }
    else {
      $build['templates_section']['templates'] = [
        '#theme' => 'item_list',
        '#items' => $this->buildTemplateList($templates),
        '#attributes' => ['class' => ['template-grid']],
      ];
    }

    // Active workflows section.
    $build['instances_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Active Workflows'),
      '#open' => TRUE,
    ];

    $instances = $this->instantiator->getInstances();

    if (empty($instances)) {
      $build['instances_section']['empty'] = [
        '#markup' => '<p>' . $this->t('No workflows created yet. Use the templates above to create your first workflow.') . '</p>',
      ];
    }
    else {
      $build['instances_section']['table'] = $this->buildInstancesTable($instances);
    }

    // Attach CSS and JS.
    $build['#attached']['library'][] = 'system/admin';
    $build['#attached']['library'][] = 'aabenforms_workflows/workflow_wizard';
    $build['#attached']['library'][] = 'aabenforms_workflows/bpmn_preview';

    return $build;
  }

  /**
   * Builds template list items.
   *
   * @param array $templates
   *   Array of templates.
   *
   * @return array
   *   Array of list items.
   */
  protected function buildTemplateList(array $templates): array {
    $items = [];

    foreach ($templates as $template_id => $template) {
      $description = preg_replace('/\[category:[^\]]+\]/', '', $template['description']);
      $description = trim($description);

      $use_link = Link::createFromRoute(
        $this->t('Use This Template'),
        'aabenforms_workflows.template_wizard',
        ['template_id' => $template_id],
        ['attributes' => ['class' => ['button', 'button--primary', 'button--small']]]
      );

      $category_badge = '<span class="badge badge--' . $template['category'] . '">' .
        ucfirst(str_replace('_', ' ', $template['category'])) .
        '</span>';

      // Generate BPMN preview placeholder.
      $preview_url = Url::fromRoute('aabenforms_workflows.template_preview', [
        'template_id' => $template_id,
      ])->toString();

      $item = [
        '#theme' => 'workflow_template_card',
        '#template_id' => $template_id,
        '#template_name' => $template['name'],
        '#category' => $template['category'],
        '#category_badge' => $category_badge,
        '#description' => $description,
        '#preview_url' => $preview_url,
        '#use_link' => $use_link,
      ];

      $items[] = $item;
    }

    return $items;
  }

  /**
   * Builds instances table.
   *
   * @param array $instances
   *   Array of workflow instances.
   *
   * @return array
   *   Table render array.
   */
  protected function buildInstancesTable(array $instances): array {
    $header = [
      $this->t('Workflow Name'),
      $this->t('Template'),
      $this->t('Webform'),
      $this->t('Status'),
      $this->t('Created'),
      $this->t('Operations'),
    ];

    $rows = [];

    foreach ($instances as $instance) {
      $status = $instance['status'] ? $this->t('Active') : $this->t('Inactive');
      $status_class = $instance['status'] ? 'status-active' : 'status-inactive';

      $created = isset($instance['created']) ? \Drupal::service('date.formatter')->format($instance['created'], 'short') : '';

      $operations = [];

      // Edit link.
      $operations[] = Link::createFromRoute(
        $this->t('Edit'),
        'aabenforms_workflows.template_wizard',
        ['workflow_id' => $instance['id']],
        ['attributes' => ['class' => ['button', 'button--small']]]
      )->toString();

      // View link (if webform exists).
      if (!empty($instance['webform_id'])) {
        $webform_url = Url::fromRoute('entity.webform.canonical', [
          'webform' => $instance['webform_id'],
        ]);
        $operations[] = Link::fromTextAndUrl(
          $this->t('View Form'),
          $webform_url
        )->toString();
      }

      // Delete link.
      $operations[] = Link::createFromRoute(
        $this->t('Delete'),
        'aabenforms_workflows.instance_delete',
        ['workflow_id' => $instance['id']],
        ['attributes' => ['class' => ['button', 'button--small', 'button--danger']]]
      )->toString();

      $rows[] = [
        $instance['label'],
        $instance['template_id'],
        $instance['webform_id'] ?? '',
        ['data' => ['#markup' => '<span class="' . $status_class . '">' . $status . '</span>']],
        $created,
        ['data' => ['#markup' => implode(' ', $operations)]],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No workflow instances found.'),
      '#attributes' => ['class' => ['instances-table']],
    ];
  }

  /**
   * Starts the wizard for a specific template.
   *
   * @param string $template_id
   *   The template ID.
   *
   * @return array
   *   Redirect response.
   */
  public function startWizard(string $template_id): array {
    // This will be handled by the wizard form route.
    return [];
  }

  /**
   * Displays a visual preview of a BPMN template.
   *
   * @param string $template_id
   *   The template ID.
   *
   * @return array
   *   Render array with BPMN preview.
   */
  public function templatePreview(string $template_id): array {
    $bpmn_xml = $this->templateManager->loadTemplate($template_id, TRUE);

    if (!$bpmn_xml) {
      return [
        '#markup' => '<p>' . $this->t('Template not found.') . '</p>',
      ];
    }

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'bpmn-preview-container',
        'class' => ['bpmn-preview-wrapper'],
      ],
    ];

    $build['canvas'] = [
      '#markup' => '<div id="bpmn-preview-canvas" class="bpmn-preview-canvas"></div>',
    ];

    $build['#attached']['library'][] = 'bpmn_io/ui';
    $build['#attached']['library'][] = 'aabenforms_workflows/bpmn_preview';
    $build['#attached']['drupalSettings']['aabenforms_workflows']['preview'] = [
      'xml' => $bpmn_xml,
      'template_id' => $template_id,
    ];

    return $build;
  }

}
