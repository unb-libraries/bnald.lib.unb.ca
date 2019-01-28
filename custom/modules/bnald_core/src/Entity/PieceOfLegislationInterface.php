<?php

namespace Drupal\bnald_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Piece of Legislation entities.
 *
 * @ingroup bnald_core
 */
interface PieceOfLegislationInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the title of the Piece of Legislation.
   *
   * @return string
   *   Title of the Piece of Legislation.
   */
  public function getLegislationTitle();

  /**
   * Sets the title for the Piece of Legislation .
   *
   * @param string $title
   *   The title of the Piece of Legislation.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setLegislationTitle($title);

  /**
   * Gets the legislative summary of the Piece of Legislation.
   *
   * @return string
   *   Legislative summary of this Piece of Legislation.
   */
  public function getLegislativeSummary();

  /**
   * Sets the legislative summary for this Piece of Legislation.
   *
   * @param string $summary
   *   The legislative summary of this Piece of Legislation.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setLegislativeSummary($summary);

  /**
   * Gets the legislative full text of the Piece of Legislation.
   *
   * @return string
   *   Legislative full text of this Piece of Legislation.
   */
  public function getLegislativeFullText();

  /**
   * Sets the legislative full text for this Piece of Legislation.
   *
   * @param string $full_text
   *   The legislative full text of this Piece of Legislation.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setLegislativeFullText($full_text);

  /**
   * Gets any item notes associated with this Piece of Legislation.
   *
   * @return string
   *   Item notes associated with this Piece of Legislation.
   */
  public function getItemNotes();

  /**
   * Sets item notes for this Piece of Legislation.
   *
   * @param string $notes
   *   The item notes of this Piece of Legislation.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setItemNotes($notes);

  /**
   * Gets the year this Piece of Legislation was passed.
   *
   * @return int
   *   The year this Piece of Legislation was passed.
   */
  public function getYearPassed();

  /**
   * Sets the year this Piece of Legislation was passed.
   *
   * @param int $year
   *   The year this Piece of Legislation was passed.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setYearPassed($year);

  /**
   * Gets the chapter this Piece of Legislation appears in.
   *
   * @return string
   *   The chapter this Piece of Legislation appears in.
   */
  public function getChapter();

  /**
   * Sets the chapter this Piece of Legislation appears in.
   *
   * @param string $chapter
   *   The chapter this Piece of Legislation appears in.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setChapter($chapter);

  /**
   * Gets the sort key of the chapter this Piece of Legislation appears in.
   *
   * @return string
   *   The sort key of the chapter this Piece of Legislation appears in.
   */
  public function getChapterSort();

  /**
   * Sets the sort key of the chapter this Piece of Legislation appears in.
   *
   * @param string $sort_key
   *   The sort key of the chapter this Piece of Legislation appears in.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setChapterSort($sort_key);

  /**
   * Gets the Piece of Legislation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Piece of Legislation.
   */
  public function getCreatedTime();

  /**
   * Sets the Piece of Legislation creation timestamp.
   *
   * @param int $timestamp
   *   The Piece of Legislation creation timestamp.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Piece of Legislation published status indicator.
   *
   * Unpublished Piece of Legislation are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Piece of Legislation is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Piece of Legislation.
   *
   * @param bool $published
   *   TRUE to set this Piece of Legislation to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setPublished($published);

  /**
   * Gets the Piece of Legislation revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Piece of Legislation revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Piece of Legislation revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Piece of Legislation revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setRevisionUserId($uid);

}
