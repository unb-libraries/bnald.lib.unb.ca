<?php

namespace Drupal\bnald_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Source Document entity.
 *
 * @see \Drupal\bnald_core\Entity\SourceDocument.
 */
class SourceDocumentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\bnald_core\Entity\SourceDocumentInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished source document entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published source document entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit source document entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete source document entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add source document entities');
  }

}
