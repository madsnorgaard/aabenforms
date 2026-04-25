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
   * @param string|null $bpmn_xml_override
   *   BPMN XML edited in the wizard's visual editor (step 2). When
   *   present, takes precedence over reloading the template from disk
   *   so admin edits survive the round-trip into ECA config.
   *
   * @return array
   *   Result array:
   *   - success: Boolean success status
   *   - workflow_id: Generated workflow instance ID
   *   - message: Success/error message
   *   - errors: Array of error messages.
   */
  public function instantiate(string $template_id, array $configuration, ?string $bpmn_xml_override = NULL): array {
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

    // Stash the edited BPMN XML for generateEcaWorkflow() to use instead
    // of re-reading the on-disk template, so wizard edits in step 2 are
    // preserved when the workflow is created.
    if ($bpmn_xml_override !== NULL && trim($bpmn_xml_override) !== '') {
      $configuration['__bpmn_xml_override'] = $bpmn_xml_override;
    }

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
    // Wizard step 2 lets admins edit the BPMN diagram. When the wizard
    // submit hands us that edited XML via __bpmn_xml_override, parse it
    // directly instead of re-reading the on-disk template - otherwise
    // those edits silently disappear on instantiate.
    if (!empty($configuration['__bpmn_xml_override']) && is_string($configuration['__bpmn_xml_override'])) {
      $xml = @simplexml_load_string($configuration['__bpmn_xml_override']);
      if ($xml === FALSE) {
        throw new \Exception('Edited BPMN XML failed to parse for ' . $template_id);
      }
    }
    else {
      $xml = $this->templateManager->loadTemplate($template_id);
      if (!$xml) {
        throw new \Exception('Failed to load template: ' . $template_id);
      }
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
    // Filter by webform bundle so wizard-generated flows fire only on their
    // own webform, not every webform_submission entity in the system.
    // ECA content_entity:insert accepts 'entity_type bundle' (space-separated)
    // in the type config - see ContentEntityEvent.php line 367.
    $webform_id = $configuration['webform_id'] ?? '';
    $type = $webform_id !== ''
      ? 'webform_submission ' . $webform_id
      : 'webform_submission';
    return [
      'webform_submit' => [
        'plugin' => 'content_entity:insert',
        'configuration' => [
          'type' => $type,
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
    $namespaces = $xml->getNamespaces(TRUE);
    $bpmnNs = $namespaces['bpmn'] ?? $namespaces['bpmn2'] ?? NULL;
    if (!$bpmnNs) {
      return [];
    }
    $xml->registerXPathNamespace('bpmn', $bpmnNs);
    $aabenformsNs = $namespaces['aabenforms'] ?? 'http://aabenforms.dk/bpmn/eca';

    // Only walk nodes inside executable processes. Templates may carry
    // non-executable descriptive pools (e.g. the deferred voting phase in
    // MED elections); those declare isExecutable="false" and should not
    // produce ECA actions.
    $nodeTypes = ['startEvent', 'endEvent', 'serviceTask', 'userTask', 'exclusiveGateway', 'parallelGateway'];
    $nodes = [];
    $edges = [];
    $startIds = [];
    foreach ($xml->xpath('//bpmn:process') ?: [] as $process) {
      $executable = (string) $process['isExecutable'];
      if ($executable !== '' && strtolower($executable) !== 'true') {
        continue;
      }
      foreach ($nodeTypes as $type) {
        foreach ($process->xpath('.//bpmn:' . $type) ?: [] as $el) {
          $id = (string) $el['id'];
          $nodes[$id] = ['type' => $type, 'el' => $el];
          if ($type === 'startEvent') {
            $startIds[] = $id;
          }
        }
      }
      foreach ($process->xpath('.//bpmn:sequenceFlow') ?: [] as $flow) {
        $src = (string) $flow['sourceRef'];
        $tgt = (string) $flow['targetRef'];
        $edges[$src][] = [
          'target' => $tgt,
          'label' => (string) $flow['name'],
        ];
      }
    }
    if (empty($startIds)) {
      return [];
    }

    // Stub action that the event successors point at.
    $actions = [
      'start_workflow' => [
        'plugin' => 'aabenforms_log',
        'configuration' => [
          'level' => 'info',
          'message' => 'Starting workflow: ' . ($configuration['label'] ?? 'Workflow'),
        ],
        'successors' => [],
      ],
    ];

    $visited = [];
    foreach ($startIds as $startId) {
      $this->walkBpmnNode($startId, 'start_workflow', '', $nodes, $edges, $actions, $visited, $aabenformsNs, $bpmnNs, $configuration);
    }

    return $actions;
  }

  /**
   * Recursive DAG walker for BPMN nodes.
   *
   * Follows sequenceFlow edges and emits ECA actions in BPMN order. Handles
   * linear sequences, exclusive gateways (condition from edge name), parallel
   * gateways (fan-out to all targets), and end events (terminates the chain).
   *
   * Shared join targets (same node reached from multiple branches) emit the
   * action once and get wired up as a successor from every branch.
   */
  protected function walkBpmnNode(
    string $nodeId,
    string $prevActionId,
    string $condition,
    array $nodes,
    array $edges,
    array &$actions,
    array &$visited,
    string $aabenformsNs,
    string $bpmnNs,
    array $configuration,
  ): void {
    $info = $nodes[$nodeId] ?? NULL;
    if (!$info) {
      return;
    }
    $type = $info['type'];
    $outEdges = $edges[$nodeId] ?? [];

    // startEvent: pass through without emitting an action.
    if ($type === 'startEvent') {
      foreach ($outEdges as $edge) {
        $this->walkBpmnNode($edge['target'], $prevActionId, $edge['label'], $nodes, $edges, $actions, $visited, $aabenformsNs, $bpmnNs, $configuration);
      }
      return;
    }

    // endEvent: terminate chain.
    if ($type === 'endEvent') {
      return;
    }

    // Gateways: fan out. Exclusive attaches edge label as condition; parallel
    // treats all branches as unconditional. Real condition evaluation is a
    // followup (edge label is plain text, not yet a Drupal condition).
    if ($type === 'exclusiveGateway' || $type === 'parallelGateway') {
      foreach ($outEdges as $edge) {
        $edgeCondition = $type === 'exclusiveGateway' ? $edge['label'] : '';
        $this->walkBpmnNode($edge['target'], $prevActionId, $edgeCondition, $nodes, $edges, $actions, $visited, $aabenformsNs, $bpmnNs, $configuration);
      }
      return;
    }

    // Tasks: emit an ECA action and recurse into outgoing edges.
    $actionId = 'action_' . $nodeId;

    if (isset($visited[$nodeId])) {
      // Shared join target. Already emitted; just link prev to it.
      if (isset($actions[$prevActionId]) && isset($actions[$actionId])) {
        $actions[$prevActionId]['successors'][] = [
          'id' => $actionId,
          'condition' => $condition,
        ];
      }
      return;
    }
    $visited[$nodeId] = TRUE;

    $actions[$actionId] = $this->actionFromTask($info['el'], $aabenformsNs, $bpmnNs, $configuration);

    if (isset($actions[$prevActionId])) {
      $actions[$prevActionId]['successors'][] = [
        'id' => $actionId,
        'condition' => $condition,
      ];
    }

    foreach ($outEdges as $edge) {
      $this->walkBpmnNode($edge['target'], $actionId, $edge['label'], $nodes, $edges, $actions, $visited, $aabenformsNs, $bpmnNs, $configuration);
    }
  }

  /**
   * Builds an ECA action config from a BPMN task.
   *
   * Reads the aabenforms:ecaAction extension element if present
   * (the preferred, explicit path). Falls back to the legacy name-based
   * mapTaskToEcaAction heuristic for templates that haven't been annotated
   * yet, and to an aabenforms_log placeholder as a last resort.
   */
  protected function actionFromTask(
    \SimpleXMLElement $task,
    string $aabenformsNs,
    string $bpmnNs,
    array $configuration,
  ): array {
    $taskName = (string) $task['name'];

    foreach ($task->children($bpmnNs) as $bpmnChild) {
      if ($bpmnChild->getName() !== 'extensionElements') {
        continue;
      }
      foreach ($bpmnChild->children($aabenformsNs) as $ext) {
        if ($ext->getName() !== 'ecaAction') {
          continue;
        }
        // SimpleXML quirk: elements accessed via children($ns) don't expose
        // unprefixed attributes via [], need attributes() instead.
        $extAttrs = $ext->attributes();
        $plugin = (string) ($extAttrs['plugin'] ?? '');
        if ($plugin === '') {
          continue;
        }
        $config = [];
        foreach ($ext->children($aabenformsNs) as $cfgNode) {
          if ($cfgNode->getName() !== 'config') {
            continue;
          }
          $cfgAttrs = $cfgNode->attributes();
          $key = (string) ($cfgAttrs['key'] ?? '');
          if ($key === '') {
            continue;
          }
          $raw = trim((string) $cfgNode);
          if ($raw === 'true') {
            $config[$key] = TRUE;
          }
          elseif ($raw === 'false') {
            $config[$key] = FALSE;
          }
          elseif (is_numeric($raw)) {
            $config[$key] = $raw + 0;
          }
          else {
            $config[$key] = $raw;
          }
        }
        return [
          'label' => $taskName,
          'plugin' => $plugin,
          'configuration' => $config,
          'successors' => [],
        ];
      }
    }

    // Legacy fallback: keyword-based mapping. Retained so templates without
    // an ecaAction extension still produce something, but the extension is
    // the supported path going forward.
    $fallback = $this->mapTaskToEcaAction((string) $task['id'], $taskName, $configuration);
    if ($fallback) {
      $fallback['label'] = $taskName;
      $fallback['successors'] = [];
      return $fallback;
    }

    return [
      'label' => $taskName,
      'plugin' => 'aabenforms_log',
      'configuration' => [
        'level' => 'info',
        'message' => 'Executing: ' . $taskName,
      ],
      'successors' => [],
    ];
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
      // The old upstream 'eca_base_mail' plugin was removed from ECA and has
      // no direct replacement. Emit a placeholder log action so instantiation
      // produces a valid ECA config; the template's ecaAction extension is
      // the supported path to wire a real send (e.g. aabenforms_send_approval_email).
      $action_config = $configuration['actions'][$task_id] ?? [];
      $subject = $action_config['subject'] ?? $task_name;
      $recipient = $action_config['recipient'] ?? '';
      return [
        'plugin' => 'aabenforms_log',
        'configuration' => [
          'level' => 'info',
          'message' => 'Email stub (' . $subject . ') → ' . $recipient,
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
      'plugin' => 'aabenforms_log',
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
