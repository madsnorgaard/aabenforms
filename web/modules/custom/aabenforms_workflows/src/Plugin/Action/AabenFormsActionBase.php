<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
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

}
