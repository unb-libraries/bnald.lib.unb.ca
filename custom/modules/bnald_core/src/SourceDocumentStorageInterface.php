<?php

namespace Drupal\bnald_core;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\bnald_core\Entity\SourceDocumentInterface;

/**
 * Defines the storage handler class for Source Document entities.
 *
 * This extends the base storage class, adding required special handling for
 * Source Document entities.
 *
 * @ingroup bnald_core
 */
interface SourceDocumentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Source Document revision IDs for a specific Source Document.
   *
   * @param \Drupal\bnald_core\Entity\SourceDocumentInterface $entity
   *   The Source Document entity.
   *
   * @return int[]
   *   Source Document revision IDs (in ascending order).
   */
  public function revisionIds(SourceDocumentInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Source Document author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Source Document revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\bnald_core\Entity\SourceDocumentInterface $entity
   *   The Source Document entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(SourceDocumentInterface $entity);

  /**
   * Unsets the language for all Source Document with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
