<?php

namespace Drupal\aabenforms_workflows\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\aabenforms_workflows\Service\BpmnTemplateManager;
use Drupal\aabenforms_workflows\Service\WorkflowTemplateMetadata;
use Drupal\aabenforms_workflows\Service\WorkflowTemplateInstantiator;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Multi-step wizard form for creating workflows from templates.
 *
 * This user-friendly wizard guides municipality staff through:
 * 1. Template selection
 * 2. Webform configuration
 * 3. Action configuration
 * 4. Data visibility settings (if applicable)
 * 5. Preview and activation.
 */
class WorkflowTemplateWizardForm extends FormBase {

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
   * The template instantiator service.
   *
   * @var \Drupal\aabenforms_workflows\Service\WorkflowTemplateInstantiator
   */
  protected WorkflowTemplateInstantiator $instantiator;

  /**
   * Constructs a WorkflowTemplateWizardForm object.
   *
   * @param \Drupal\aabenforms_workflows\Service\BpmnTemplateManager $template_manager
   *   The BPMN template manager service.
   * @param \Drupal\aabenforms_workflows\Service\WorkflowTemplateMetadata $template_metadata
   *   The template metadata service.
   * @param \Drupal\aabenforms_workflows\Service\WorkflowTemplateInstantiator $instantiator
   *   The template instantiator service.
   */
  public function __construct(
    BpmnTemplateManager $template_manager,
    WorkflowTemplateMetadata $template_metadata,
    WorkflowTemplateInstantiator $instantiator,
  ) {
    $this->templateManager = $template_manager;
    $this->templateMetadata = $template_metadata;
    $this->instantiator = $instantiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aabenforms_workflows.bpmn_template_manager'),
      $container->get('aabenforms_workflows.template_metadata'),
      $container->get('aabenforms_workflows.template_instantiator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aabenforms_workflows_template_wizard_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Initialize step if not set.
    $step = $form_state->get('step') ?? 1;
    $form_state->set('step', $step);

    // Add wizard navigation.
    $form['#attached']['library'][] = 'system/admin';
    $form['#attached']['library'][] = 'aabenforms_workflows/workflow_wizard';
    $form['#attributes']['class'][] = 'workflow-wizard-form';

    // Add progress indicator.
    $form['progress'] = [
      '#theme' => 'item_list',
      '#items' => $this->getProgressSteps($step),
      '#attributes' => ['class' => ['wizard-progress']],
    ];

    // Build step-specific form.
    switch ($step) {
      case 1:
        $this->buildStepSelectTemplate($form, $form_state);
        break;

      case 2:
        $this->buildStepConfigureWebform($form, $form_state);
        break;

      case 3:
        $this->buildStepConfigureActions($form, $form_state);
        break;

      case 4:
        $this->buildStepDataVisibility($form, $form_state);
        break;

      case 5:
        $this->buildStepPreviewActivate($form, $form_state);
        break;
    }

    // Add navigation buttons.
    $this->addNavigationButtons($form, $form_state, $step);

    return $form;
  }

  /**
   * Builds Step 1: Select Template.
   */
  protected function buildStepSelectTemplate(array &$form, FormStateInterface $form_state): void {
    $form['step_title'] = [
      '#markup' => '<h2>' . $this->t('Step 1: Select a Workflow Template') . '</h2>',
    ];

    $form['help'] = [
      '#markup' => '<p>' . $this->t('Choose a pre-built workflow template that matches your needs. Each template is designed for common municipality use cases.') . '</p>',
    ];

    $templates = $this->templateManager->getAvailableTemplates();

    $options = [];
    $descriptions = [];

    foreach ($templates as $template_id => $template) {
      $options[$template_id] = '<strong>' . $template['name'] . '</strong>';
      $clean_description = preg_replace('/\[category:[^\]]+\]/', '', $template['description']);
      $descriptions[$template_id] = trim($clean_description);
    }

    $form['template_id'] = [
      '#type' => 'radios',
      '#title' => $this->t('Available Templates'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('template_id'),
    ];

    // Add descriptions for each template.
    foreach ($descriptions as $template_id => $description) {
      $form['template_id'][$template_id]['#description'] = $description;
    }

    // Preview section.
    if ($selected_template = $form_state->getValue('template_id')) {
      $preview = $this->templateMetadata->getTemplatePreview($selected_template);

      $form['preview'] = [
        '#type' => 'details',
        '#title' => $this->t('Template Preview'),
        '#open' => TRUE,
      ];

      $form['preview']['description'] = [
        '#markup' => '<p>' . $preview['description'] . '</p>',
      ];

      if (!empty($preview['steps'])) {
        $steps_list = [];
        foreach ($preview['steps'] as $step) {
          $steps_list[] = $this->t('Step @num: @name', [
            '@num' => $step['step'],
            '@name' => $step['name'],
          ]);
        }

        $form['preview']['steps'] = [
          '#theme' => 'item_list',
          '#title' => $this->t('Workflow Steps'),
          '#items' => $steps_list,
        ];
      }
    }
  }

  /**
   * Builds Step 2: Configure Webform.
   */
  protected function buildStepConfigureWebform(array &$form, FormStateInterface $form_state): void {
    $template_id = $form_state->getValue('template_id');

    $form['step_title'] = [
      '#markup' => '<h2>' . $this->t('Step 2: Configure Webform Integration') . '</h2>',
    ];

    $form['help'] = [
      '#markup' => '<p>' . $this->t('Connect this workflow to a webform and map the form fields to workflow variables.') . '</p>',
    ];

    // Webform selection.
    $webforms = Webform::loadMultiple();
    $webform_options = [];
    foreach ($webforms as $webform_id => $webform) {
      $webform_options[$webform_id] = $webform->label();
    }

    $form['webform_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Webform'),
      '#description' => $this->t('Which webform should trigger this workflow?'),
      '#options' => $webform_options,
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('webform_id'),
      '#ajax' => [
        'callback' => '::updateFieldMappings',
        'wrapper' => 'field-mappings-wrapper',
        'event' => 'change',
      ],
    ];

    // Field mappings container.
    $form['field_mappings'] = [
      '#type' => 'container',
      '#prefix' => '<div id="field-mappings-wrapper">',
      '#suffix' => '</div>',
    ];

    if ($webform_id = $form_state->getValue('webform_id')) {
      $webform = Webform::load($webform_id);
      $webform_fields = $webform ? $webform->getElementsDecodedAndFlattened() : [];
      $field_options = [];

      foreach ($webform_fields as $field_id => $field) {
        $field_options[$field_id] = $field['#title'] ?? $field_id;
      }

      // Get template parameters that need field mapping.
      $parameters = $this->templateMetadata->getTemplateParameters($template_id);

      foreach ($parameters as $param_id => $param) {
        if ($param['type'] === 'webform_field') {
          $form['field_mappings'][$param_id] = [
            '#type' => 'select',
            '#title' => $param['label'],
            '#description' => $param['description'],
            '#options' => $field_options,
            '#required' => $param['required'],
            '#default_value' => $form_state->getValue($param_id) ?? $param['default'],
          ];
        }
      }
    }
    else {
      $form['field_mappings']['message'] = [
        '#markup' => '<p>' . $this->t('Select a webform to configure field mappings.') . '</p>',
      ];
    }
  }

  /**
   * Builds Step 3: Configure Actions.
   */
  protected function buildStepConfigureActions(array &$form, FormStateInterface $form_state): void {
    $template_id = $form_state->getValue('template_id');

    $form['step_title'] = [
      '#markup' => '<h2>' . $this->t('Step 3: Configure Actions') . '</h2>',
    ];

    $form['help'] = [
      '#markup' => '<p>' . $this->t('Customize email templates, approval pages, and notification settings for each workflow action.') . '</p>',
    ];

    // Get template parameters (non-field mappings).
    $parameters = $this->templateMetadata->getTemplateParameters($template_id);

    $form['parameters'] = [
      '#type' => 'details',
      '#title' => $this->t('Workflow Settings'),
      '#open' => TRUE,
    ];

    foreach ($parameters as $param_id => $param) {
      if ($param['type'] !== 'webform_field') {
        $element = $this->buildParameterElement($param, $form_state);
        $form['parameters'][$param_id] = $element;
      }
    }

    // Get configurable actions.
    $actions = $this->templateMetadata->getConfigurableActions($template_id);

    if (!empty($actions)) {
      $form['actions'] = [
        '#type' => 'details',
        '#title' => $this->t('Action Configuration'),
        '#open' => TRUE,
        '#tree' => TRUE,
      ];

      foreach ($actions as $action_id => $action) {
        $form['actions'][$action_id] = [
          '#type' => 'details',
          '#title' => $action['name'],
          '#open' => FALSE,
        ];

        foreach ($action['configurable_fields'] as $field_id => $field) {
          $form['actions'][$action_id][$field_id] = [
            '#type' => $field['type'] === 'textarea' ? 'textarea' : 'textfield',
            '#title' => $field['label'],
            '#required' => $field['required'],
            '#default_value' => $form_state->getValue(['actions', $action_id, $field_id]),
          ];

          if ($field['type'] === 'textarea') {
            $form['actions'][$action_id][$field_id]['#rows'] = 5;
          }
        }
      }
    }
  }

  /**
   * Builds Step 4: Data Visibility.
   */
  protected function buildStepDataVisibility(array &$form, FormStateInterface $form_state): void {
    $template_id = $form_state->getValue('template_id');

    $form['step_title'] = [
      '#markup' => '<h2>' . $this->t('Step 4: Data Visibility (Optional)') . '</h2>',
    ];

    // Only show for templates with multi-party workflows.
    $multi_party_templates = ['building_permit', 'foi_request'];

    if (!in_array($template_id, $multi_party_templates)) {
      $form['not_applicable'] = [
        '#markup' => '<p>' . $this->t('This template does not require data visibility configuration.') . '</p>',
      ];
      return;
    }

    $form['help'] = [
      '#markup' => '<p>' . $this->t('Configure which data is visible to each party involved in the workflow. This ensures GDPR compliance.') . '</p>',
    ];

    $form['visibility_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Visibility Mode'),
      '#options' => [
        'full' => $this->t('Full transparency - All parties see all data'),
        'restricted' => $this->t('Restricted - Each party sees only relevant data'),
      ],
      '#default_value' => $form_state->getValue('visibility_mode') ?? 'restricted',
      '#required' => TRUE,
    ];

    $form['visibility_note'] = [
      '#markup' => '<div class="messages messages--warning">' . $this->t('Note: CPR numbers and other sensitive data are always encrypted per GDPR requirements.') . '</div>',
    ];
  }

  /**
   * Builds Step 5: Preview and Activate.
   */
  protected function buildStepPreviewActivate(array &$form, FormStateInterface $form_state): void {
    $template_id = $form_state->getValue('template_id');
    $templates = $this->templateManager->getAvailableTemplates();
    $template = $templates[$template_id];

    $form['step_title'] = [
      '#markup' => '<h2>' . $this->t('Step 5: Preview and Activate') . '</h2>',
    ];

    $form['help'] = [
      '#markup' => '<p>' . $this->t('Review your workflow configuration and activate it.') . '</p>',
    ];

    // Workflow name.
    $form['workflow_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Workflow Name'),
      '#description' => $this->t('Give this workflow a descriptive name'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('workflow_label') ?? $template['name'],
    ];

    // Summary.
    $form['summary'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuration Summary'),
      '#open' => TRUE,
    ];

    $webform_id = $form_state->getValue('webform_id');
    $webform = $webform_id ? Webform::load($webform_id) : NULL;

    $summary_items = [
      $this->t('<strong>Template:</strong> @name', ['@name' => $template['name']]),
      $this->t('<strong>Webform:</strong> @form', ['@form' => $webform ? $webform->label() : 'N/A']),
    ];

    $form['summary']['items'] = [
      '#theme' => 'item_list',
      '#items' => $summary_items,
    ];

    // Preview diagram.
    $preview = $this->templateMetadata->getTemplatePreview($template_id);

    if (!empty($preview['steps'])) {
      $form['workflow_steps'] = [
        '#type' => 'details',
        '#title' => $this->t('Workflow Steps'),
        '#open' => TRUE,
      ];

      $steps_list = [];
      foreach ($preview['steps'] as $step) {
        $steps_list[] = $this->t('@num. @name', [
          '@num' => $step['step'],
          '@name' => $step['name'],
        ]);
      }

      $form['workflow_steps']['list'] = [
        '#theme' => 'item_list',
        '#items' => $steps_list,
      ];
    }

    // Status.
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate workflow immediately'),
      '#description' => $this->t('If unchecked, workflow will be created but not active.'),
      '#default_value' => TRUE,
    ];
  }

  /**
   * Builds a form element for a parameter.
   */
  protected function buildParameterElement(array $param, FormStateInterface $form_state): array {
    $element = [];

    switch ($param['type']) {
      case 'integer':
        $element = [
          '#type' => 'number',
          '#title' => $param['label'],
          '#description' => $param['description'],
          '#required' => $param['required'],
          '#default_value' => $form_state->getValue($param['id']) ?? $param['default'],
        ];
        break;

      case 'boolean':
        $element = [
          '#type' => 'checkbox',
          '#title' => $param['label'],
          '#description' => $param['description'],
          '#default_value' => $form_state->getValue($param['id']) ?? ($param['default'] === 'true'),
        ];
        break;

      case 'textarea':
        $element = [
          '#type' => 'textarea',
          '#title' => $param['label'],
          '#description' => $param['description'],
          '#required' => $param['required'],
          '#default_value' => $form_state->getValue($param['id']) ?? $param['default'],
          '#rows' => 4,
        ];
        break;

      case 'email':
        $element = [
          '#type' => 'email',
          '#title' => $param['label'],
          '#description' => $param['description'],
          '#required' => $param['required'],
          '#default_value' => $form_state->getValue($param['id']) ?? $param['default'],
        ];
        break;

      default:
        $element = [
          '#type' => 'textfield',
          '#title' => $param['label'],
          '#description' => $param['description'],
          '#required' => $param['required'],
          '#default_value' => $form_state->getValue($param['id']) ?? $param['default'],
        ];
        break;
    }

    return $element;
  }

  /**
   * Gets progress step labels.
   */
  protected function getProgressSteps(int $current_step): array {
    $steps = [
      1 => $this->t('Select Template'),
      2 => $this->t('Configure Webform'),
      3 => $this->t('Configure Actions'),
      4 => $this->t('Data Visibility'),
      5 => $this->t('Preview & Activate'),
    ];

    $items = [];
    foreach ($steps as $step_num => $label) {
      $class = $step_num === $current_step ? 'current' : ($step_num < $current_step ? 'completed' : 'pending');
      $items[] = '<span class="wizard-step ' . $class . '">' . $label . '</span>';
    }

    return $items;
  }

  /**
   * Adds navigation buttons to form.
   */
  protected function addNavigationButtons(array &$form, FormStateInterface $form_state, int $step): void {
    $form['actions'] = ['#type' => 'actions'];

    if ($step > 1) {
      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        '#submit' => ['::previousStep'],
        '#limit_validation_errors' => [],
        '#attributes' => ['class' => ['button']],
      ];
    }

    if ($step < 5) {
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#submit' => ['::nextStep'],
        '#attributes' => ['class' => ['button', 'button--primary']],
      ];
    }
    else {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Create Workflow'),
        '#attributes' => ['class' => ['button', 'button--primary']],
      ];
    }

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('aabenforms_workflows.template_browser'),
      '#attributes' => ['class' => ['button']],
    ];
  }

  /**
   * AJAX callback for field mappings.
   */
  public function updateFieldMappings(array &$form, FormStateInterface $form_state) {
    return $form['field_mappings'];
  }

  /**
   * Submit handler for next step.
   */
  public function nextStep(array &$form, FormStateInterface $form_state): void {
    $step = $form_state->get('step');
    $form_state->set('step', $step + 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for previous step.
   */
  public function previousStep(array &$form, FormStateInterface $form_state): void {
    $step = $form_state->get('step');
    $form_state->set('step', $step - 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Gather all configuration.
    $template_id = $form_state->getValue('template_id');

    // Build parameters array.
    $parameters = [];
    $template_params = $this->templateMetadata->getTemplateParameters($template_id);

    foreach ($template_params as $param_id => $param) {
      $value = $form_state->getValue($param_id);
      if ($value !== NULL) {
        $parameters[$param_id] = $value;
      }
    }

    // Build configuration array.
    $configuration = [
      'label' => $form_state->getValue('workflow_label'),
      'webform_id' => $form_state->getValue('webform_id'),
      'parameters' => $parameters,
      'actions' => $form_state->getValue('actions') ?? [],
      'visibility_mode' => $form_state->getValue('visibility_mode'),
      'status' => $form_state->getValue('status') ?? TRUE,
    ];

    // Instantiate workflow.
    $result = $this->instantiator->instantiate($template_id, $configuration);

    if ($result['success']) {
      $this->messenger()->addStatus(
        $this->t('Workflow "@label" has been created successfully!', [
          '@label' => $configuration['label'],
        ])
      );

      // Redirect to template browser.
      $form_state->setRedirect('aabenforms_workflows.template_browser');
    }
    else {
      $this->messenger()->addError(
        $this->t('Failed to create workflow: @message', [
          '@message' => $result['message'],
        ])
      );

      foreach ($result['errors'] as $error) {
        $this->messenger()->addError($error);
      }
    }
  }

}
