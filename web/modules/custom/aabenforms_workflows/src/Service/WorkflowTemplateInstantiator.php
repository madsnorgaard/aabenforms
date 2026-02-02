<?php

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for instantiating workflow templates into active workflows.
 *
 * This service takes a template ID and user configuration, then generates
 * all necessary components: ECA configs, routes, email templates, etc.
 */
class WorkflowTemplateInstantiator {

  /**
   * The BPMN template manager service.
   *
   * @var \Drupal\aabenforms_workflows\Service\BpmnTemplateManager
   */
  protected BpmnTemplateManager $templateManager;

  /**
   * The template metadata service.
   *
   * @var \Drupal\aabenforms_workflows\Service\WorkflowTemplateMetadata
   */
  protected WorkflowTemplateMetadata $templateMetadata;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected RouteBuilderInterface $routeBuilder;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a WorkflowTemplateInstantiator service.
   *
   * @param \Drupal\aabenforms_workflows\Service\BpmnTemplateManager $template_manager
   *   The BPMN template manager service.
   * @param \Drupal\aabenforms_workflows\Service\WorkflowTemplateMetadata $template_metadata
   *   The template metadata service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    BpmnTemplateManager $template_manager,
    WorkflowTemplateMetadata $template_metadata,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    RouteBuilderInterface $route_builder,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->templateManager = $template_manager;
    $this->templateMetadata = $template_metadata;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeBuilder = $route_builder;
    $this->logger = $logger_factory->get('aabenforms_workflows');
  }

  /**
   * Instantiates a workflow from a template.
   *
   * @param string $template_id
   *   The template identifier.
   * @param array $configuration
   *   The user-provided configuration:
   *   - label: Workflow instance label
   *   - webform_id: Associated webform ID
   *   - parameters: Array of parameter values
   *   - actions: Array of action configurations
   *   - status: Active/inactive (defaults to active).
   *
   * @return array
   *   Result array:
   *   - success: Boolean success status
   *   - workflow_id: Generated workflow instance ID
   *   - message: Success/error message
   *   - errors: Array of error messages.
   */
  public function instantiate(string $template_id, array $configuration): array {
    // Validate configuration.
    $errors = $this->templateMetadata->validateConfiguration($template_id, $configuration);
    if (!empty($errors)) {
      return [
        'success' => FALSE,
        'workflow_id' => NULL,
        'message' => 'Configuration validation failed',
        'errors' => $errors,
      ];
    }

    // Generate unique workflow instance ID.
    $workflow_id = $this->generateWorkflowId($template_id, $configuration);

    try {
      // Create template instance config entity.
      $this->createTemplateInstanceConfig($workflow_id, $template_id, $configuration);

      // Generate and save ECA workflow config.
      $this->generateEcaWorkflow($workflow_id, $template_id, $configuration);

      // Create necessary routes (for approval pages, etc.).
      $this->createWorkflowRoutes($workflow_id, $template_id, $configuration);

      // Generate email templates.
      $this->generateEmailTemplates($workflow_id, $template_id, $configuration);

      // Rebuild routes.
      $this->routeBuilder->rebuild();

      // Clear caches.
      drupal_flush_all_caches();

      $this->logger->info('Successfully instantiated workflow @workflow from template @template', [
        '@workflow' => $workflow_id,
        '@template' => $template_id,
      ]);

      return [
        'success' => TRUE,
        'workflow_id' => $workflow_id,
        'message' => 'Workflow created successfully',
        'errors' => [],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to instantiate workflow: @message', [
        '@message' => $e->getMessage(),
      ]);

      return [
        'success' => FALSE,
        'workflow_id' => NULL,
        'message' => 'Failed to create workflow: ' . $e->getMessage(),
        'errors' => [$e->getMessage()],
      ];
    }
  }

  /**
   * Generates a unique workflow instance ID.
   *
   * @param string $template_id
   *   The template identifier.
   * @param array $configuration
   *   The configuration array.
   *
   * @return string
   *   The workflow instance ID.
   */
  protected function generateWorkflowId(string $template_id, array $configuration): string {
    $base_id = $configuration['id'] ?? $template_id . '_' . time();
    $base_id = preg_replace('/[^a-z0-9_]/', '_', strtolower($base_id));

    // Ensure uniqueness.
    $counter = 1;
    $workflow_id = $base_id;
    while ($this->workflowExists($workflow_id)) {
      $workflow_id = $base_id . '_' . $counter;
      $counter++;
    }

    return $workflow_id;
  }

  /**
   * Check if a workflow ID already exists.
   *
   * @param string $workflow_id
   *   The workflow ID to check.
   *
   * @return bool
   *   TRUE if exists, FALSE otherwise.
   */
  protected function workflowExists(string $workflow_id): bool {
    $config = $this->configFactory->get('aabenforms_workflows.template_instance.' . $workflow_id);
    return !$config->isNew();
  }

  /**
   * Creates template instance configuration.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   * @param string $template_id
   *   The template identifier.
   * @param array $configuration
   *   The configuration array.
   */
  protected function createTemplateInstanceConfig(string $workflow_id, string $template_id, array $configuration): void {
    $config = $this->configFactory->getEditable('aabenforms_workflows.template_instance.' . $workflow_id);

    $config->setData([
      'id' => $workflow_id,
      'label' => $configuration['label'] ?? $workflow_id,
      'template_id' => $template_id,
      'webform_id' => $configuration['webform_id'],
      'configuration' => $configuration,
      'status' => $configuration['status'] ?? TRUE,
      'created' => time(),
      'updated' => time(),
    ]);

    $config->save();
  }

  /**
   * Generates ECA workflow configuration.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   * @param string $template_id
   *   The template identifier.
   * @param array $configuration
   *   The configuration array.
   */
  protected function generateEcaWorkflow(string $workflow_id, string $template_id, array $configuration): void {
    // Load template.
    $xml = $this->templateManager->loadTemplate($template_id);
    if (!$xml) {
      throw new \Exception('Failed to load template: ' . $template_id);
    }

    // Build ECA config structure.
    $eca_config = [
      'langcode' => 'en',
      'status' => $configuration['status'] ?? TRUE,
      'dependencies' => [
        'module' => ['eca', 'webform', 'aabenforms_workflows'],
      ],
      'id' => $workflow_id,
      'label' => $configuration['label'] ?? $workflow_id,
      'modeller' => 'fallback',
      'version' => '1.0.0',
      'events' => $this->buildEcaEvents($xml, $configuration),
      'gateways' => [],
      'conditions' => [],
      'actions' => $this->buildEcaActions($xml, $configuration),
    ];

    // Save ECA config.
    $config = $this->configFactory->getEditable('eca.eca.' . $workflow_id);
    $config->setData($eca_config);
    $config->save();
  }

  /**
   * Builds ECA events configuration.
   *
   * @param \SimpleXMLElement $xml
   *   The BPMN XML.
   * @param array $configuration
   *   The configuration array.
   *
   * @return array
   *   ECA events configuration.
   */
  protected function buildEcaEvents(\SimpleXMLElement $xml, array $configuration): array {
    return [
      'webform_submit' => [
        'plugin' => 'content_entity:insert',
        'configuration' => [
          'type' => 'webform_submission',
        ],
        'successors' => [
          ['id' => 'start_workflow', 'condition' => ''],
        ],
      ],
    ];
  }

  /**
   * Builds ECA actions configuration.
   *
   * @param \SimpleXMLElement $xml
   *   The BPMN XML.
   * @param array $configuration
   *   The configuration array.
   *
   * @return array
   *   ECA actions configuration.
   */
  protected function buildEcaActions(\SimpleXMLElement $xml, array $configuration): array {
    $actions = [];
    $namespaces = $xml->getNamespaces(TRUE);
    $ns = $namespaces['bpmn'] ?? $namespaces['bpmn2'] ?? NULL;

    if (!$ns) {
      return $actions;
    }

    $xml->registerXPathNamespace('bpmn', $ns);

    // Get all tasks from template.
    $service_tasks = $xml->xpath('//bpmn:serviceTask');
    $user_tasks = $xml->xpath('//bpmn:userTask');
    $all_tasks = array_merge($service_tasks ?: [], $user_tasks ?: []);

    // Map template tasks to ECA actions.
    $previous_action_id = 'start_workflow';

    foreach ($all_tasks as $task) {
      $task_id = (string) $task['id'];
      $task_name = (string) $task['name'];

      // Generate ECA action based on task type.
      $action = $this->mapTaskToEcaAction($task_id, $task_name, $configuration);

      if ($action) {
        $action_id = 'action_' . $task_id;
        $actions[$action_id] = $action;

        // Link to previous action.
        if (isset($actions[$previous_action_id])) {
          $actions[$previous_action_id]['successors'][] = [
            'id' => $action_id,
            'condition' => '',
          ];
        }

        $previous_action_id = $action_id;
      }
    }

    // Add initial action.
    $actions = ['start_workflow' => [
      'plugin' => 'eca_base_log',
      'configuration' => [
        'level' => 'info',
        'message' => 'Starting workflow: ' . ($configuration['label'] ?? 'Workflow'),
      ],
      'successors' => [],
    ]] + $actions;

    return $actions;
  }

  /**
   * Maps a BPMN task to an ECA action.
   *
   * @param string $task_id
   *   The task ID.
   * @param string $task_name
   *   The task name.
   * @param array $configuration
   *   The workflow configuration.
   *
   * @return array|null
   *   ECA action configuration or NULL.
   */
  protected function mapTaskToEcaAction(string $task_id, string $task_name, array $configuration): ?array {
    $task_name_lower = strtolower($task_name);

    // Determine action type and map configuration.
    if (strpos($task_name_lower, 'mitid') !== FALSE || strpos($task_name_lower, 'auth') !== FALSE) {
      return [
        'plugin' => 'aabenforms_mitid_validate',
        'configuration' => [
          'workflow_id_token' => 'workflow_id',
          'result_token' => 'mitid_valid',
        ],
        'successors' => [],
      ];
    }

    if (strpos($task_name_lower, 'cpr') !== FALSE) {
      $cpr_field = $configuration['parameters']['cpr_field'] ?? 'cpr';
      return [
        'plugin' => 'aabenforms_cpr_lookup',
        'configuration' => [
          'cpr_token' => $cpr_field,
          'result_token' => 'person_data',
          'use_cache' => TRUE,
        ],
        'successors' => [],
      ];
    }

    if (strpos($task_name_lower, 'cvr') !== FALSE) {
      $cvr_field = $configuration['parameters']['cvr_field'] ?? 'cvr';
      return [
        'plugin' => 'aabenforms_cvr_lookup',
        'configuration' => [
          'cvr_token' => $cvr_field,
          'result_token' => 'company_data',
        ],
        'successors' => [],
      ];
    }

    if (strpos($task_name_lower, 'send') !== FALSE || strpos($task_name_lower, 'email') !== FALSE) {
      // Get email configuration for this action.
      $action_config = $configuration['actions'][$task_id] ?? [];

      return [
        'plugin' => 'eca_base_mail',
        'configuration' => [
          'to' => $action_config['recipient'] ?? '',
          'subject' => $action_config['subject'] ?? $task_name,
          'body' => $action_config['body'] ?? '',
        ],
        'successors' => [],
      ];
    }

    if (strpos($task_name_lower, 'log') !== FALSE || strpos($task_name_lower, 'audit') !== FALSE) {
      return [
        'plugin' => 'aabenforms_audit_log',
        'configuration' => [
          'event_type' => 'workflow_action',
          'message_template' => $task_name,
        ],
        'successors' => [],
      ];
    }

    // Default log action for unrecognized tasks.
    return [
      'plugin' => 'eca_base_log',
      'configuration' => [
        'level' => 'info',
        'message' => 'Executing: ' . $task_name,
      ],
      'successors' => [],
    ];
  }

  /**
   * Creates workflow-specific routes.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   * @param string $template_id
   *   The template identifier.
   * @param array $configuration
   *   The configuration array.
   */
  protected function createWorkflowRoutes(string $workflow_id, string $template_id, array $configuration): void {
    // For templates with approval steps, create approval routes.
    $actions = $this->templateMetadata->getConfigurableActions($template_id);

    foreach ($actions as $action_id => $action) {
      if ($action['type'] === 'approval') {
        // Route will be dynamically created via hook_menu().
        // Store route info in template instance config.
        $config = $this->configFactory->getEditable('aabenforms_workflows.template_instance.' . $workflow_id);
        $routes = $config->get('routes') ?? [];
        $routes[$action_id] = [
          'path' => '/workflow/' . $workflow_id . '/approval/' . $action_id . '/{token}',
          'controller' => 'Drupal\aabenforms_workflows\Controller\WorkflowApprovalController::approvalPage',
          'title' => $configuration['actions'][$action_id]['page_title'] ?? 'Approval Required',
        ];
        $config->set('routes', $routes);
        $config->save();
      }
    }
  }

  /**
   * Generates email templates for workflow.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   * @param string $template_id
   *   The template identifier.
   * @param array $configuration
   *   The configuration array.
   */
  protected function generateEmailTemplates(string $workflow_id, string $template_id, array $configuration): void {
    // Store email templates in config.
    $config = $this->configFactory->getEditable('aabenforms_workflows.template_instance.' . $workflow_id);
    $email_templates = [];

    foreach ($configuration['actions'] ?? [] as $action_id => $action_config) {
      if (isset($action_config['subject']) && isset($action_config['body'])) {
        $email_templates[$action_id] = [
          'subject' => $action_config['subject'],
          'body' => $action_config['body'],
          'recipient' => $action_config['recipient'] ?? '',
        ];
      }
    }

    $config->set('email_templates', $email_templates);
    $config->save();
  }

  /**
   * Deletes a workflow instance.
   *
   * @param string $workflow_id
   *   The workflow instance ID.
   *
   * @return bool
   *   TRUE if successful, FALSE otherwise.
   */
  public function deleteInstance(string $workflow_id): bool {
    try {
      // Delete template instance config.
      $config = $this->configFactory->getEditable('aabenforms_workflows.template_instance.' . $workflow_id);
      if (!$config->isNew()) {
        $config->delete();
      }

      // Delete ECA workflow.
      $eca_config = $this->configFactory->getEditable('eca.eca.' . $workflow_id);
      if (!$eca_config->isNew()) {
        $eca_config->delete();
      }

      // Rebuild routes.
      $this->routeBuilder->rebuild();

      $this->logger->info('Deleted workflow instance: @workflow', [
        '@workflow' => $workflow_id,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to delete workflow instance: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets all workflow instances.
   *
   * @return array
   *   Array of workflow instances.
   */
  public function getInstances(): array {
    $instances = [];

    // Load all template instance configs.
    $config_names = $this->configFactory->listAll('aabenforms_workflows.template_instance.');

    foreach ($config_names as $config_name) {
      $config = $this->configFactory->get($config_name);
      $instances[] = $config->getRawData();
    }

    return $instances;
  }

}
