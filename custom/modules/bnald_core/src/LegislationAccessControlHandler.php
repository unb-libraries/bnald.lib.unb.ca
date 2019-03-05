<?php

namespace Drupal\bnald_core;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Legislation entity.
 *
 * @see \Drupal\bnald_core\Entity\Legislation.
 */
class LegislationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\bnald_core\Entity\LegislationInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished legislation entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published legislation entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit legislation entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete legislation entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add legislation entities');
  }

}
