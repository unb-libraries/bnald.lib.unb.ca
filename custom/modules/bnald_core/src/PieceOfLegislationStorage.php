<?php

namespace Drupal\bnald_core;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\bnald_core\Entity\PieceOfLegislationInterface;

/**
 * Defines the storage handler class for Piece of Legislation entities.
 *
 * This extends the base storage class, adding required special handling for
 * Piece of Legislation entities.
 *
 * @ingroup bnald_core
 */
class PieceOfLegislationStorage extends SqlContentEntityStorage implements PieceOfLegislationStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(PieceOfLegislationInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {piece_legislation_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {piece_legislation_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(PieceOfLegislationInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {piece_legislation_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('piece_legislation_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
