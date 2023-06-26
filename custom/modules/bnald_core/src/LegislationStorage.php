<?php

namespace Drupal\bnald_core;

use Drupal\bnald_core\Entity\LegislationInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the storage handler class for Legislation entities.
 *
 * This extends the base storage class, adding required special handling for
 * Legislation entities.
 *
 * @ingroup bnald_core
 */
class LegislationStorage extends SqlContentEntityStorage implements LegislationStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(LegislationInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {legislation_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {legislation_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(LegislationInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {legislation_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('legislation_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
