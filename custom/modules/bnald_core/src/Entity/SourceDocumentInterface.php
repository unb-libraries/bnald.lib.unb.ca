<?php

namespace Drupal\bnald_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Source Document entities.
 *
 * @ingroup bnald_core
 */
interface SourceDocumentInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Source Document Title.
   *
   * @return string
   *   The Title of the Source Document.
   */
  public function getTitle();

  /**
   * Sets the Source Document Title.
   *
   * @param string $title
   *   The Title of the Source Document.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocument
   *   The called Source Document entity.
   */
  public function setTitle($title);

  /**
   * Clears the Source Document Title value.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocument
   *   The called Source Document entity.
   */
  public function clearTitle();

  /**
   * Gets the Source Document Short Title.
   *
   * @return string
   *   The Short Title of the Source Document.
   */
  public function getShortTitle();

  /**
   * Sets the Source Document Short Title.
   *
   * @param string $short_title
   *   The Short Title of the Source Document.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocument
   *   The called Source Document entity.
   */
  public function setShortTitle($short_title);

  /**
   * Clears the Source Document Short Title value.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocument
   *   The called Source Document entity.
   */
  public function clearShortTitle();

  /**
   * Gets the Source Document Printer entity.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The Printer Term entity.
   */
  public function getPrinter();

  /**
   * Gets the Source Document Printer entity ID.
   *
   * @return int
   *   The Printer term entity ID.
   */
  public function getPrinterId();

  /**
   * Sets the Source Document Printer.
   *
   * @param \Drupal\taxonomy\TermInterface $printer
   *   A Printer taxonomy term entity.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocument
   *   The called Source Document entity.
   */
  public function setPrinter(TermInterface $printer);

  /**
   * Gets the year the Document was printed.
   *
   * @return int
   *   The year the Document was printed.
   */
  public function getYear();

  /**
   * Sets the year the Document was printed.
   *
   * @param int $year
   *   The year the Document was printed.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setYear($year);

  /**
   * Clears the Source Document Printer value.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocument
   *   The called Source Document entity.
   */
  public function clearPrinter();

  /**
   * Gets the Source Document Location entity.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The Location Term entity.
   */
  public function getLocation();

  /**
   * Gets the Source Document Location entity ID.
   *
   * @return int
   *   The Location term entity ID.
   */
  public function getLocationId();

  /**
   * Sets the Source Document Location.
   *
   * @param \Drupal\taxonomy\TermInterface $location
   *   A Location taxonomy term entity.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocument
   *   The called Source Document entity.
   */
  public function setLocation(TermInterface $location);

  /**
   * Clears the Source Document Location value.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocument
   *   The called Source Document entity.
   */
  public function clearLocation();

  /**
   * Gets the Source Document creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Source Document.
   */
  public function getCreatedTime();

  /**
   * Sets the Source Document creation timestamp.
   *
   * @param int $timestamp
   *   The Source Document creation timestamp.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocumentInterface
   *   The called Source Document entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Source Document published status indicator.
   *
   * Unpublished Source Document are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Source Document is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Source Document.
   *
   * @param bool $published
   *   TRUE to set this Source Document to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocumentInterface
   *   The called Source Document entity.
   */
  public function setPublished($published);

  /**
   * Gets the Source Document revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Source Document revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocumentInterface
   *   The called Source Document entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Source Document revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Source Document revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocumentInterface
   *   The called Source Document entity.
   */
  public function setRevisionUserId($uid);

}
