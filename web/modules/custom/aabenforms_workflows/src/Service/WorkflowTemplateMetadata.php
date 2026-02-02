<?php

namespace Drupal\aabenforms_workflows\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for extracting and managing workflow template metadata.
 *
 * This service analyzes BPMN templates to extract configuration parameters,
 * customizable actions, and field mapping requirements needed by the wizard.
 */
class WorkflowTemplateMetadata {

  /**
   * The BPMN template manager service.
   *
   * @var \Drupal\aabenforms_workflows\Service\BpmnTemplateManager
   */
  protected BpmnTemplateManager $templateManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a WorkflowTemplateMetadata service.
   *
   * @param \Drupal\aabenforms_workflows\Service\BpmnTemplateManager $template_manager
   *   The BPMN template manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    BpmnTemplateManager $template_manager,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->templateManager = $template_manager;
    $this->logger = $logger_factory->get('aabenforms_workflows');
  }

  /**
   * Gets all configuration parameters for a template.
   *
   * @param string $template_id
   *   The template identifier.
   *
   * @return array
   *   Array of parameter definitions:
   *   - id: Parameter machine name
   *   - label: Human-readable label
   *   - type: Parameter type (webform_field, integer, text, email_template, etc.)
   *   - required: Whether parameter is required
   *   - default: Default value
   *   - description: Help text
   *   - options: Array of valid options (for select/radio types)
   */
  public function getTemplateParameters(string $template_id): array {
    $xml = $this->templateManager->loadTemplate($template_id);

    if (!$xml) {
      return [];
    }

    // Register namespaces.
    $namespaces = $xml->getNamespaces(TRUE);
    $ns = $namespaces['bpmn'] ?? $namespaces['bpmn2'] ?? NULL;

    if (!$ns) {
      return [];
    }

    $xml->registerXPathNamespace('bpmn', $ns);

    // Extract parameters from documentation element.
    $parameters = [];
    $docs = $xml->xpath('//bpmn:process/bpmn:documentation');

    if (!empty($docs)) {
      $doc_content = (string) $docs[0];

      // Parse parameters section.
      if (preg_match('/<parameters>(.*?)<\/parameters>/s', $doc_content, $matches)) {
        $params_xml = simplexml_load_string('<root>' . $matches[1] . '</root>');
        if ($params_xml) {
          foreach ($params_xml->parameter as $param) {
            $param_id = (string) $param['id'];
            $parameters[$param_id] = [
              'id' => $param_id,
              'label' => (string) $param['label'],
              'type' => (string) ($param['type'] ?? 'text'),
              'required' => ((string) ($param['required'] ?? 'false')) === 'true',
              'default' => (string) ($param['default'] ?? ''),
              'description' => (string) ($param['description'] ?? ''),
              'options' => $this->parseOptions($param),
            ];
          }
        }
      }
    }

    // Add default parameters based on template type.
    return $this->addDefaultParameters($template_id, $parameters);
  }

  /**
   * Gets configurable actions for a template.
   *
   * @param string $template_id
   *   The template identifier.
   *
   * @return array
   *   Array of configurable action definitions:
   *   - id: Action identifier (from BPMN task ID)
   *   - name: Human-readable name
   *   - type: Action type (email, notification, approval, etc.)
   *   - configurable_fields: Array of fields that can be customized
   */
  public function getConfigurableActions(string $template_id): array {
    $xml = $this->templateManager->loadTemplate($template_id);

    if (!$xml) {
      return [];
    }

    $namespaces = $xml->getNamespaces(TRUE);
    $ns = $namespaces['bpmn'] ?? $namespaces['bpmn2'] ?? NULL;

    if (!$ns) {
      return [];
    }

    $xml->registerXPathNamespace('bpmn', $ns);

    $actions = [];

    // Find all service tasks and user tasks (actions).
    $service_tasks = $xml->xpath('//bpmn:serviceTask');
    $user_tasks = $xml->xpath('//bpmn:userTask');

    foreach (array_merge($service_tasks ?: [], $user_tasks ?: []) as $task) {
      $task_id = (string) $task['id'];
      $task_name = (string) $task['name'];

      // Determine action type from task name.
      $action_type = $this->determineActionType($task_name);

      if ($action_type !== 'none') {
        $actions[$task_id] = [
          'id' => $task_id,
          'name' => $task_name,
          'type' => $action_type,
          'configurable_fields' => $this->getActionConfigurableFields($action_type),
        ];
      }
    }

    return $actions;
  }

  /**
   * Validates a template configuration.
   *
   * @param string $template_id
   *   The template identifier.
   * @param array $config
   *   The configuration to validate.
   *
   * @return array
   *   Array of validation errors (empty if valid).
   */
  public function validateConfiguration(string $template_id, array $config): array {
    $errors = [];
    $parameters = $this->getTemplateParameters($template_id);

    // Check required parameters.
    foreach ($parameters as $param_id => $param) {
      if ($param['required'] && empty($config['parameters'][$param_id])) {
        $errors[] = $this->t('Required parameter missing: @label', [
          '@label' => $param['label'],
        ]);
      }
    }

    // Validate webform_id is set.
    if (empty($config['webform_id'])) {
      $errors[] = $this->t('Webform must be selected.');
    }

    // Validate email addresses.
    foreach ($config['parameters'] ?? [] as $param_id => $value) {
      $param = $parameters[$param_id] ?? NULL;
      if ($param && $param['type'] === 'email' && !empty($value)) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
          $errors[] = $this->t('Invalid email address for @label', [
            '@label' => $param['label'],
          ]);
        }
      }
    }

    // Validate integer values.
    foreach ($config['parameters'] ?? [] as $param_id => $value) {
      $param = $parameters[$param_id] ?? NULL;
      if ($param && $param['type'] === 'integer' && !empty($value)) {
        if (!is_numeric($value) || intval($value) != $value) {
          $errors[] = $this->t('Invalid integer value for @label', [
            '@label' => $param['label'],
          ]);
        }
      }
    }

    return $errors;
  }

  /**
   * Gets preview information for a template.
   *
   * @param string $template_id
   *   The template identifier.
   *
   * @return array
   *   Preview information:
   *   - diagram: SVG or image representation (if available)
   *   - steps: Array of workflow steps
   *   - description: Workflow description
   */
  public function getTemplatePreview(string $template_id): array {
    $templates = $this->templateManager->getAvailableTemplates();
    $template = $templates[$template_id] ?? NULL;

    if (!$template) {
      return [];
    }

    $xml = $this->templateManager->loadTemplate($template_id);
    if (!$xml) {
      return [];
    }

    $namespaces = $xml->getNamespaces(TRUE);
    $ns = $namespaces['bpmn'] ?? $namespaces['bpmn2'] ?? NULL;

    if (!$ns) {
      return [];
    }

    $xml->registerXPathNamespace('bpmn', $ns);

    // Extract workflow steps.
    $steps = [];
    $service_tasks = $xml->xpath('//bpmn:serviceTask');
    $user_tasks = $xml->xpath('//bpmn:userTask');

    foreach (array_merge($service_tasks ?: [], $user_tasks ?: []) as $index => $task) {
      $steps[] = [
        'step' => $index + 1,
        'name' => (string) $task['name'],
        'type' => $task->getName(),
      ];
    }

    return [
      'description' => $template['description'],
      'steps' => $steps,
    // Could be enhanced to generate SVG from BPMN.
      'diagram' => NULL,
    ];
  }

  /**
   * Parse options from parameter definition.
   *
   * @param \SimpleXMLElement $param
   *   The parameter XML element.
   *
   * @return array
   *   Array of options (key => label).
   */
  protected function parseOptions(\SimpleXMLElement $param): array {
    $options = [];

    if (isset($param->options)) {
      foreach ($param->options->option as $option) {
        $key = (string) $option['value'];
        $label = (string) $option;
        $options[$key] = $label;
      }
    }

    return $options;
  }

  /**
   * Add default parameters based on template type.
   *
   * @param string $template_id
   *   The template identifier.
   * @param array $parameters
   *   Existing parameters.
   *
   * @return array
   *   Parameters with defaults added.
   */
  protected function addDefaultParameters(string $template_id, array $parameters): array {
    // Common parameters for all workflows.
    if (!isset($parameters['workflow_label'])) {
      $parameters['workflow_label'] = [
        'id' => 'workflow_label',
        'label' => 'Workflow Name',
        'type' => 'text',
        'required' => TRUE,
        'default' => '',
        'description' => 'A descriptive name for this workflow instance',
        'options' => [],
      ];
    }

    // Template-specific defaults.
    switch ($template_id) {
      case 'building_permit':
        $parameters += $this->getBuildingPermitDefaults();
        break;

      case 'contact_form':
        $parameters += $this->getContactFormDefaults();
        break;

      case 'company_verification':
        $parameters += $this->getCompanyVerificationDefaults();
        break;

      case 'foi_request':
        $parameters += $this->getFoiRequestDefaults();
        break;

      case 'address_change':
        $parameters += $this->getAddressChangeDefaults();
        break;
    }

    return $parameters;
  }

  /**
   * Get default parameters for building permit template.
   *
   * @return array
   *   Default parameters.
   */
  protected function getBuildingPermitDefaults(): array {
    return [
      'applicant_email_field' => [
        'id' => 'applicant_email_field',
        'label' => 'Applicant Email Field',
        'type' => 'webform_field',
        'required' => TRUE,
        'default' => 'email',
        'description' => 'Webform field containing the applicant email address',
        'options' => [],
      ],
      'cpr_field' => [
        'id' => 'cpr_field',
        'label' => 'CPR Number Field',
        'type' => 'webform_field',
        'required' => TRUE,
        'default' => 'cpr',
        'description' => 'Webform field containing the CPR number',
        'options' => [],
      ],
      'address_field' => [
        'id' => 'address_field',
        'label' => 'Address Field',
        'type' => 'webform_field',
        'required' => TRUE,
        'default' => 'address',
        'description' => 'Webform field containing the property address',
        'options' => [],
      ],
      'caseworker_email' => [
        'id' => 'caseworker_email',
        'label' => 'Case Worker Email',
        'type' => 'email',
        'required' => TRUE,
        'default' => '',
        'description' => 'Email address for case worker notifications',
        'options' => [],
      ],
      'approval_deadline_days' => [
        'id' => 'approval_deadline_days',
        'label' => 'Approval Deadline (days)',
        'type' => 'integer',
        'required' => FALSE,
        'default' => '30',
        'description' => 'Number of days before application auto-rejects',
        'options' => [],
      ],
      'sbsys_integration' => [
        'id' => 'sbsys_integration',
        'label' => 'SBSYS Integration',
        'type' => 'boolean',
        'required' => FALSE,
        'default' => 'false',
        'description' => 'Create case in SBSYS when approved',
        'options' => [],
      ],
    ];
  }

  /**
   * Get default parameters for contact form template.
   *
   * @return array
   *   Default parameters.
   */
  protected function getContactFormDefaults(): array {
    return [
      'submitter_email_field' => [
        'id' => 'submitter_email_field',
        'label' => 'Submitter Email Field',
        'type' => 'webform_field',
        'required' => TRUE,
        'default' => 'email',
        'description' => 'Webform field containing the submitter email',
        'options' => [],
      ],
      'recipient_email' => [
        'id' => 'recipient_email',
        'label' => 'Recipient Email',
        'type' => 'email',
        'required' => TRUE,
        'default' => '',
        'description' => 'Department email address',
        'options' => [],
      ],
      'auto_reply' => [
        'id' => 'auto_reply',
        'label' => 'Send Auto-Reply',
        'type' => 'boolean',
        'required' => FALSE,
        'default' => 'true',
        'description' => 'Send confirmation email to submitter',
        'options' => [],
      ],
      'confirmation_message' => [
        'id' => 'confirmation_message',
        'label' => 'Confirmation Message',
        'type' => 'textarea',
        'required' => FALSE,
        'default' => 'Thank you for your inquiry. We will respond within 3 business days.',
        'description' => 'Message shown to user after submission',
        'options' => [],
      ],
    ];
  }

  /**
   * Get default parameters for company verification template.
   *
   * @return array
   *   Default parameters.
   */
  protected function getCompanyVerificationDefaults(): array {
    return [
      'cvr_field' => [
        'id' => 'cvr_field',
        'label' => 'CVR Number Field',
        'type' => 'webform_field',
        'required' => TRUE,
        'default' => 'cvr',
        'description' => 'Webform field containing the CVR number',
        'options' => [],
      ],
      'contact_email_field' => [
        'id' => 'contact_email_field',
        'label' => 'Contact Email Field',
        'type' => 'webform_field',
        'required' => TRUE,
        'default' => 'email',
        'description' => 'Webform field containing contact email',
        'options' => [],
      ],
      'verification_email' => [
        'id' => 'verification_email',
        'label' => 'Verification Team Email',
        'type' => 'email',
        'required' => TRUE,
        'default' => '',
        'description' => 'Email for verification team notifications',
        'options' => [],
      ],
    ];
  }

  /**
   * Get default parameters for FOI request template.
   *
   * @return array
   *   Default parameters.
   */
  protected function getFoiRequestDefaults(): array {
    return [
      'requester_email_field' => [
        'id' => 'requester_email_field',
        'label' => 'Requester Email Field',
        'type' => 'webform_field',
        'required' => TRUE,
        'default' => 'email',
        'description' => 'Webform field containing requester email',
        'options' => [],
      ],
      'foi_officer_email' => [
        'id' => 'foi_officer_email',
        'label' => 'FOI Officer Email',
        'type' => 'email',
        'required' => TRUE,
        'default' => '',
        'description' => 'Email address of FOI officer',
        'options' => [],
      ],
      'response_deadline_days' => [
        'id' => 'response_deadline_days',
        'label' => 'Response Deadline (days)',
        'type' => 'integer',
        'required' => FALSE,
        'default' => '7',
        'description' => 'Days to respond per FOI law (typically 7)',
        'options' => [],
      ],
    ];
  }

  /**
   * Get default parameters for address change template.
   *
   * @return array
   *   Default parameters.
   */
  protected function getAddressChangeDefaults(): array {
    return [
      'cpr_field' => [
        'id' => 'cpr_field',
        'label' => 'CPR Number Field',
        'type' => 'webform_field',
        'required' => TRUE,
        'default' => 'cpr',
        'description' => 'Webform field containing the CPR number',
        'options' => [],
      ],
      'new_address_field' => [
        'id' => 'new_address_field',
        'label' => 'New Address Field',
        'type' => 'webform_field',
        'required' => TRUE,
        'default' => 'new_address',
        'description' => 'Webform field containing the new address',
        'options' => [],
      ],
      'notification_email' => [
        'id' => 'notification_email',
        'label' => 'Notification Email',
        'type' => 'email',
        'required' => TRUE,
        'default' => '',
        'description' => 'Email for address change notifications',
        'options' => [],
      ],
    ];
  }

  /**
   * Determine action type from task name.
   *
   * @param string $task_name
   *   The task name.
   *
   * @return string
   *   The action type.
   */
  protected function determineActionType(string $task_name): string {
    $task_name_lower = strtolower($task_name);

    if (strpos($task_name_lower, 'send') !== FALSE || strpos($task_name_lower, 'email') !== FALSE || strpos($task_name_lower, 'notify') !== FALSE) {
      return 'email';
    }
    if (strpos($task_name_lower, 'review') !== FALSE || strpos($task_name_lower, 'approval') !== FALSE) {
      return 'approval';
    }
    if (strpos($task_name_lower, 'mitid') !== FALSE || strpos($task_name_lower, 'auth') !== FALSE) {
      return 'authentication';
    }
    if (strpos($task_name_lower, 'lookup') !== FALSE || strpos($task_name_lower, 'verify') !== FALSE) {
      return 'data_lookup';
    }
    if (strpos($task_name_lower, 'log') !== FALSE || strpos($task_name_lower, 'audit') !== FALSE) {
      return 'audit';
    }

    return 'none';
  }

  /**
   * Get configurable fields for an action type.
   *
   * @param string $action_type
   *   The action type.
   *
   * @return array
   *   Array of configurable fields.
   */
  protected function getActionConfigurableFields(string $action_type): array {
    switch ($action_type) {
      case 'email':
        return [
          'subject' => [
            'label' => 'Email Subject',
            'type' => 'text',
            'required' => TRUE,
          ],
          'body' => [
            'label' => 'Email Body',
            'type' => 'textarea',
            'required' => TRUE,
          ],
          'recipient' => [
            'label' => 'Recipient',
            'type' => 'email',
            'required' => TRUE,
          ],
        ];

      case 'approval':
        return [
          'page_title' => [
            'label' => 'Approval Page Title',
            'type' => 'text',
            'required' => TRUE,
          ],
          'instructions' => [
            'label' => 'Instructions',
            'type' => 'textarea',
            'required' => TRUE,
          ],
        ];

      default:
        return [];
    }
  }

  /**
   * Wrapper for translation.
   *
   * @param string $string
   *   The string to translate.
   * @param array $args
   *   Translation arguments.
   *
   * @return string
   *   Translated string.
   */
  protected function t(string $string, array $args = []): string {
    return strtr($string, $args);
  }

}
