<?php

namespace Drupal\bnald_core;

use Drupal\bnald_core\Entity\SourceDocumentInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the storage handler class for Source Document entities.
 *
 * This extends the base storage class, adding required special handling for
 * Source Document entities.
 *
 * @ingroup bnald_core
 */
class SourceDocumentStorage extends SqlContentEntityStorage implements SourceDocumentStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(SourceDocumentInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {source_document_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {source_document_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(SourceDocumentInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {source_document_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('source_document_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->accessCheck(FALSE)
      ->execute();
  }

}
