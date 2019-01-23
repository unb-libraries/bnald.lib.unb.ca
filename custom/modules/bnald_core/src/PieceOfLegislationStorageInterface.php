<?php

namespace Drupal\bnald_core;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface PieceOfLegislationStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Piece of Legislation revision IDs for a specific Piece of Legislation.
   *
   * @param \Drupal\bnald_core\Entity\PieceOfLegislationInterface $entity
   *   The Piece of Legislation entity.
   *
   * @return int[]
   *   Piece of Legislation revision IDs (in ascending order).
   */
  public function revisionIds(PieceOfLegislationInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Piece of Legislation author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Piece of Legislation revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\bnald_core\Entity\PieceOfLegislationInterface $entity
   *   The Piece of Legislation entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(PieceOfLegislationInterface $entity);

  /**
   * Unsets the language for all Piece of Legislation with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
