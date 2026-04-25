<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ActionBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for ÅbenForms ECA actions.
 *
 * Provides common functionality for all workflow actions:
 * - Error handling and logging
 * - Access control
 * - Token support
 * - Audit logging integration.
 */
abstract class AabenFormsActionBase extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Collects workflow execution steps for the API response.
   */
  protected WorkflowExecutionCollector $executionCollector;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('eca.token_services'),
      $container->get('current_user'),
      $container->get('datetime.time'),
      $container->get('eca.state'),
      $container->get('logger.factory')->get('aabenforms_workflows')
    );
    $instance->setExecutionCollector(
      $container->get('aabenforms_core.workflow_execution_collector')
    );
    return $instance;
  }

  /**
   * Setter injection for the workflow execution collector.
   *
   * Public so unit tests can wire a collector mock without reaching into
   * private state via reflection. Production wiring goes through create().
   */
  public function setExecutionCollector(WorkflowExecutionCollector $collector): void {
    $this->executionCollector = $collector;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultInterface|bool {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * Logs an action execution.
   *
   * @param string $message
   *   The log message.
   * @param array $context
   *   Additional context.
   * @param string $level
   *   Log level (info, warning, error).
   */
  protected function log(string $message, array $context = [], string $level = 'info'): void {
    $context['action'] = $this->getPluginId();
    $this->logger->$level($message, $context);
  }

  /**
   * Handles action execution errors.
   *
   * Param is `\Throwable`, not `\Exception`, so a `\TypeError` or
   * `\ParseError` propagating out of an action's execute() still hits
   * the audit/logging path instead of escaping unobserved.
   *
   * @param \Throwable $e
   *   The throwable that aborted the action.
   * @param string $context_message
   *   Context about what was being attempted.
   */
  protected function handleError(\Throwable $e, string $context_message = ''): void {
    $this->log(
      'Action failed: {message}. Context: {context}',
      [
        'message' => $e->getMessage(),
        'context' => $context_message,
        'exception' => $e,
      ],
      'error'
    );
    $this->executionCollector->addStep(
      $this->getPluginId(),
      $context_message ?: 'Action failed',
      $e->getMessage(),
      'failed',
      $e->getMessage()
    );
  }

  /**
   * Records a completed workflow step for the API response.
   *
   * @param string $label
   *   Human-readable step name shown to the user.
   * @param string $description
   *   What this step accomplished.
   * @param string $status
   *   Step status: 'completed' or 'failed'.
   */
  protected function recordStep(string $label, string $description, string $status = 'completed'): void {
    $this->executionCollector->addStep($this->getPluginId(), $label, $description, $status);
  }

  /**
   * Sets a token value in the ECA token environment.
   *
   * @param string $token_name
   *   The token name.
   * @param mixed $value
   *   The value to set.
   */
  protected function setTokenValue(string $token_name, $value): void {
    $this->tokenService->addTokenData($token_name, $value);
  }

  /**
   * Gets a token value from the ECA token environment.
   *
   * @param string $fieldKey
   *   The token name to read.
   * @param string $default
   *   Default value if the token is not found.
   *
   * @return string
   *   The token value, or the default.
   */
  protected function getTokenValue(string $fieldKey, string $default): string {
    $value = $this->tokenService->getTokenData($fieldKey);
    if (is_string($value)) {
      return $value;
    }
    if ($value === NULL) {
      return $default;
    }
    // Guard against objects that don't implement __toString (e.g. FieldItemList
    // when a token resolves to an entity reference). A blind (string) cast on
    // those throws a fatal Error. Prefer the object's own scalar representation
    // when available, otherwise fall back to the default.
    if (is_object($value)) {
      if (method_exists($value, '__toString')) {
        return (string) $value;
      }
      if (method_exists($value, 'getString')) {
        return (string) $value->getString();
      }
      if (method_exists($value, 'value')) {
        $inner = $value->value();
        return is_scalar($inner) ? (string) $inner : $default;
      }
      return $default;
    }
    if (is_scalar($value)) {
      return (string) $value;
    }
    return $default;
  }

  /**
   * Resolves the webform submission for the current ECA invocation.
   *
   * Eight action plugins call this; before this lived on the base class
   * each had to inline its own resolution and several called a missing
   * method, throwing on every cross-fired event. The resolution order is:
   * (a) the entity ECA passes to execute() if it's already a submission;
   * (b) the 'webform_submission' ECA token (set by content_entity:insert
   *     events on webform_submission), the same pattern used in
   *     SendApprovalEmailAction.
   *
   * @param mixed $entity
   *   Whatever ECA passed to execute(). Often a webform submission;
   *   sometimes another entity for cross-fired events.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The resolved submission, or NULL when neither path produces one.
   */
  protected function getSubmission($entity = NULL): ?WebformSubmissionInterface {
    if ($entity instanceof WebformSubmissionInterface) {
      return $entity;
    }
    $token_value = $this->tokenService->getTokenData('webform_submission');
    return $token_value instanceof WebformSubmissionInterface ? $token_value : NULL;
  }

}
