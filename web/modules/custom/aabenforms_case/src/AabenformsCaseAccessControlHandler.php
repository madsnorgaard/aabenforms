<?php

declare(strict_types=1);

namespace Drupal\aabenforms_case;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control for the Case entity.
 *
 * Cases hold citizen casework data and must never be world-readable. Because
 * jsonapi_frontend exposes every entity by default, this handler is the gate
 * that keeps cases behind the caseworker permissions. View/update require
 * "view aabenforms_case"; everything else requires "administer aabenforms_case".
 */
class AabenformsCaseAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($account->hasPermission('administer aabenforms_case')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view aabenforms_case'),
      default => AccessResult::neutral()->cachePerPermissions(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'administer aabenforms_case');
  }

}
