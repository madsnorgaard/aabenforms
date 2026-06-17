<?php

declare(strict_types=1);

namespace Drupal\aabenforms_case\Plugin\Action;

use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\aabenforms_core\Service\WorkflowExecutionCollector;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for ÅbenForms Case ECA actions.
 *
 * Deliberately decoupled from aabenforms_workflows so the case foundation
 * depends only on aabenforms_core + eca + webform (lighter, easier to test).
 * It re-uses the same conventions as aabenforms_workflows' AabenFormsActionBase
 * (token helpers, execution-collector steps, audit logging) via setter
 * injection, because eca's ActionBase constructor is final.
 */
abstract class CaseActionBase extends ConfigurableActionBase {

  /**
   * Collects workflow execution steps for the synchronous API response.
   */
  protected WorkflowExecutionCollector $executionCollector;

  /**
   * The audit logger.
   */
  protected AuditLogger $auditLogger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    /** @var static $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->executionCollector = $container->get('aabenforms_core.workflow_execution_collector');
    $instance->auditLogger = $container->get('aabenforms_core.audit_logger');
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
   * Records a workflow step for the API response.
   */
  protected function recordStep(string $label, string $description, string $status = 'completed'): void {
    $this->executionCollector->addStep($this->getPluginId(), $label, $description, $status);
  }

  /**
   * Logs an action execution.
   *
   * @param string $message
   *   The log message (may contain {placeholders}).
   * @param array $context
   *   Placeholder context.
   * @param string $level
   *   Log level (info, warning, error).
   */
  protected function log(string $message, array $context = [], string $level = 'info'): void {
    $context['action'] = $this->getPluginId();
    $this->logger->$level($message, $context);
  }

  /**
   * Routes an action failure to the log and the execution collector.
   */
  protected function handleError(\Throwable $e, string $contextMessage = ''): void {
    $this->log('Action failed: {message}. Context: {context}', [
      'message' => $e->getMessage(),
      'context' => $contextMessage,
      'exception' => $e,
    ], 'error');
    $this->executionCollector->addStep(
      $this->getPluginId(),
      $contextMessage ?: 'Action failed',
      $e->getMessage(),
      'failed',
      $e->getMessage()
    );
  }

  /**
   * Sets a token value in the ECA token environment.
   */
  protected function setTokenValue(string $tokenName, mixed $value): void {
    $this->tokenService->addTokenData($tokenName, $value);
  }

  /**
   * Reads a token value, resolving bracketed references.
   *
   * Mirrors AabenFormsActionBase::getTokenValue: a '[...]' reference is
   * replaced against the token environment; a bare key is a direct data-bag
   * lookup.
   */
  protected function getTokenValue(string $fieldKey, string $default): string {
    if (str_contains($fieldKey, '[')) {
      $replaced = $this->tokenService->replaceClear($fieldKey, []);
      $replaced = is_string($replaced) ? trim($replaced) : '';
      return $replaced !== '' ? $replaced : $default;
    }
    $value = $this->tokenService->getTokenData($fieldKey);
    if (is_string($value)) {
      return $value;
    }
    if ($value === NULL) {
      return $default;
    }
    if (is_scalar($value)) {
      return (string) $value;
    }
    // ECA wraps scalars set via addTokenData() in a DataTransferObject, so a
    // bare token name resolves to an object here, not a string. Honour its
    // scalar representation before giving up (matches AabenFormsActionBase).
    if (is_object($value)) {
      if (method_exists($value, '__toString')) {
        return (string) $value;
      }
      if (method_exists($value, 'getString')) {
        return (string) $value->getString();
      }
    }
    return $default;
  }

  /**
   * Resolves the webform submission for the current ECA invocation.
   */
  protected function getSubmission(mixed $entity = NULL): ?WebformSubmissionInterface {
    if ($entity instanceof WebformSubmissionInterface) {
      return $entity;
    }
    $token_value = $this->tokenService->getTokenData('webform_submission');
    return $token_value instanceof WebformSubmissionInterface ? $token_value : NULL;
  }

}
