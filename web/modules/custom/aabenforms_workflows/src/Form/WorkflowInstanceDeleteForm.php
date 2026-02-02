<?php

namespace Drupal\aabenforms_workflows\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\aabenforms_workflows\Service\WorkflowTemplateInstantiator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for deleting workflow instances.
 */
class WorkflowInstanceDeleteForm extends ConfirmFormBase {

  /**
   * The template instantiator service.
   *
   * @var \Drupal\aabenforms_workflows\Service\WorkflowTemplateInstantiator
   */
  protected WorkflowTemplateInstantiator $instantiator;

  /**
   * The workflow ID to delete.
   *
   * @var string
   */
  protected string $workflowId;

  /**
   * Constructs a WorkflowInstanceDeleteForm object.
   *
   * @param \Drupal\aabenforms_workflows\Service\WorkflowTemplateInstantiator $instantiator
   *   The template instantiator service.
   */
  public function __construct(WorkflowTemplateInstantiator $instantiator) {
    $this->instantiator = $instantiator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aabenforms_workflows.template_instantiator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aabenforms_workflows_instance_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $workflow_id = NULL) {
    $this->workflowId = $workflow_id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the workflow instance %workflow?', [
      '%workflow' => $this->workflowId,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('aabenforms_workflows.template_browser');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will delete the workflow configuration and all associated ECA rules. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete Workflow');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->instantiator->deleteInstance($this->workflowId)) {
      $this->messenger()->addStatus(
        $this->t('Workflow instance %workflow has been deleted.', [
          '%workflow' => $this->workflowId,
        ])
      );
    }
    else {
      $this->messenger()->addError(
        $this->t('Failed to delete workflow instance %workflow.', [
          '%workflow' => $this->workflowId,
        ])
      );
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
