<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\eca\Plugin\Action\ActionBase;
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
    $instance->executionCollector = $container->get('aabenforms_core.workflow_execution_collector');
    return $instance;
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
   * @param \Exception $e
   *   The exception.
   * @param string $context_message
   *   Context about what was being attempted.
   */
  protected function handleError(\Exception $e, string $context_message = ''): void {
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
   * @param string $token_name
   *   The token name.
   * @param string $default
   *   Default value if token not found.
   *
   * @return mixed
   *   The token value, or default.
   */
  protected function getTokenValue(string $fieldKey, string $default): string {
    $value = $this->tokenService->getTokenData($fieldKey);
    return is_string($value) ? $value : ($value !== NULL ? (string) $value : $default);
  }

}
