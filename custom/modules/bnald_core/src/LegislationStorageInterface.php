<?php

namespace Drupal\bnald_core;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\bnald_core\Entity\LegislationInterface;

/**
 * Defines the storage handler class for Legislation entities.
 *
 * This extends the base storage class, adding required special handling for
 * Legislation entities.
 *
 * @ingroup bnald_core
 */
interface LegislationStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Legislation revision IDs for a specific Legislation.
   *
   * @param \Drupal\bnald_core\Entity\LegislationInterface $entity
   *   The Legislation entity.
   *
   * @return int[]
   *   Legislation revision IDs (in ascending order).
   */
  public function revisionIds(LegislationInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Legislation author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Legislation revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\bnald_core\Entity\LegislationInterface $entity
   *   The Legislation entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(LegislationInterface $entity);

  /**
   * Unsets the language for all Legislation with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
