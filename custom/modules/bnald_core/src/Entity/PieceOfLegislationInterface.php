<?php

namespace Drupal\bnald_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Piece of Legislation entities.
 *
 * @ingroup bnald_core
 */
interface PieceOfLegislationInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the province this Piece of Legislation applies to.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   Province of the Piece of Legislation.
   */
  public function getProvince();

  /**
   * Sets the province this Piece of Legislation applies to.
   *
   * @param \Drupal\taxonomy\TermInterface $province
   *   The province of the Piece of Legislation.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setProvince(TermInterface $province);

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
   * Gets all 'Jurisdictional Relevance' terms associated with this Piece of Legislation.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of 'Jurisdictional Relevance' terms related to this Piece of Legislation.
   */
  public function getJurisdictionalRelevance();

  /**
   * Adds a 'Jurisdictional Relevance' term to the end of the list.
   *
   * @param \Drupal\taxonomy\TermInterface $term_to_add
   *   The 'Jurisdictional Relevance' term to add.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function appendJurisdictionalRelevance(TermInterface $term_to_add);

  /**
   * Removes the given 'Jurisdictional Relevance' term.
   *
   * @param \Drupal\taxonomy\TermInterface $term_to_remove
   *   The 'Jurisdictional Relevance' term to remove.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function removeJurisdictionalRelevance(TermInterface $term_to_remove);

  /**
   * Gets the title of the Piece of Legislation.
   *
   * @return int
   *   Number of Articles this Piece of Legislation consists of.
   */
  public function getNumberOfArticles();

  /**
   * Sets the number of articles this Piece of Legislation consists of.
   *
   * @param int $number
   *   The number of articles.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setNumberOfArticles($number);

  /**
   * Gets all 'Concept' terms associated with this Piece of Legislation.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of 'Jurisdictional Relevance' terms related to this Piece of Legislation.
   */
  public function getConcepts();

  /**
   * Adds a 'Concept' term to the end of the list.
   *
   * @param \Drupal\taxonomy\TermInterface $term_to_add
   *   The 'Concept' term to add.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function appendConcept(TermInterface $term_to_add);

  /**
   * Removes the given 'Concept' term.
   *
   * @param \Drupal\taxonomy\TermInterface $term_to_remove
   *   The 'Concept' term to remove.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function removeConcept(TermInterface $term_to_remove);

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
   * Gets the Piece Of Legislation's Source Document.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocumentInterface
   *   The SourceDocument entity.
   */
  public function getSourceDocument();

  /**
   * Sets the Piece Of Legislation's Source Document.
   *
   * @param \Drupal\bnald_core\Entity\SourceDocumentInterface $source_document
   *   The SourceDocument entity.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislation
   *   The called Piece Of Legislation entity.
   */
  public function setSourceDocument(SourceDocumentInterface $source_document);

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
