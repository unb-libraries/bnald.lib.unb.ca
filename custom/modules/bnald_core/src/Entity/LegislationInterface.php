<?php

namespace Drupal\bnald_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\file\FileInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Legislation entities.
 *
 * @ingroup bnald_core
 */
interface LegislationInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the title of the Legislation.
   *
   * @return string
   *   Title of the Legislation.
   */
  public function getTitle();

  /**
   * Sets the title for the Legislation .
   *
   * @param string $title
   *   The title of the Legislation.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setTitle($title);

  /**
   * Gets the chapter this Legislation appears in.
   *
   * @return string
   *   The chapter this Legislation appears in.
   */
  public function getChapter();

  /**
   * Sets the chapter this Legislation appears in.
   *
   * @param string $chapter
   *   The chapter this Legislation appears in.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setChapter($chapter);

  /**
   * Gets the year this Legislation was passed.
   *
   * @return int
   *   The year this Legislation was passed.
   */
  public function getYear();

  /**
   * Sets the year this Legislation was passed.
   *
   * @param int $year
   *   The year this Legislation was passed.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setYear($year);

  /**
   * Gets the title of the Legislation.
   *
   * @return int
   *   Number of Articles this Legislation consists of.
   */
  public function getNumberOfArticles();

  /**
   * Sets the number of articles this Legislation consists of.
   *
   * @param int $number
   *   The number of articles.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setNumberOfArticles($number);

  /**
   * Gets the province this Legislation applies to.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   Province of the Legislation.
   */
  public function getProvince();

  /**
   * Sets the province this Legislation applies to.
   *
   * @param \Drupal\taxonomy\TermInterface $province
   *   The province of the Legislation.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setProvince(TermInterface $province);

  /**
   * Gets the legislative summary of the Legislation.
   *
   * @return string
   *   Legislative summary of this Legislation.
   */
  public function getSummary();

  /**
   * Sets the legislative summary for this Legislation.
   *
   * @param string $summary
   *   The legislative summary of this Legislation.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setSummary($summary);

  /**
   * Gets the legislative full text of the Legislation.
   *
   * @return string
   *   Legislative full text of this Legislation.
   */
  public function getFullText();

  /**
   * Sets the legislative full text for this Legislation.
   *
   * @param string $full_text
   *   The legislative full text of this Legislation.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setFullText($full_text);

  /**
   * Gets the PDF of the Transcribed Act for this Legislation.
   *
   * @return \Drupal\file\FileInterface
   *   The PDF file entity.
   */
  public function getPdfTranscribed();

  /**
   * Sets the PDF of the Transcribed Act for this Legislation.
   *
   * @param \Drupal\file\FileInterface $pdf
   *   The PDF file entity.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setPdfTranscribed(FileInterface $pdf);

  /**
   * Gets the PDF of the Original Act for this Legislation.
   *
   * @return \Drupal\file\FileInterface
   *   The PDF file entity.
   */
  public function getPdfOriginal();

  /**
   * Sets the PDF of the Original Act for this Legislation.
   *
   * @param \Drupal\file\FileInterface $pdf
   *   The PDF file entity.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setPdfOriginal(FileInterface $pdf);

  /**
   * Gets all 'Jurisdictional Relevance' terms associated with this Legislation.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of 'Jurisdictional Relevance' terms related to this Legislation.
   */
  public function getJurisdictionalRelevance();

  /**
   * Adds a 'Jurisdictional Relevance' term to the end of the list.
   *
   * @param \Drupal\taxonomy\TermInterface $term_to_add
   *   The 'Jurisdictional Relevance' term to add.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function appendJurisdictionalRelevance(TermInterface $term_to_add);

  /**
   * Removes the given 'Jurisdictional Relevance' term.
   *
   * @param \Drupal\taxonomy\TermInterface $term_to_remove
   *   The 'Jurisdictional Relevance' term to remove.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function removeJurisdictionalRelevance(TermInterface $term_to_remove);

  /**
   * Gets all 'Concept' terms associated with this Legislation.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of 'Jurisdictional Relevance' terms related to this Legislation.
   */
  public function getConcepts();

  /**
   * Adds a 'Concept' term to the end of the list.
   *
   * @param \Drupal\taxonomy\TermInterface $term_to_add
   *   The 'Concept' term to add.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function appendConcept(TermInterface $term_to_add);

  /**
   * Removes the given 'Concept' term.
   *
   * @param \Drupal\taxonomy\TermInterface $term_to_remove
   *   The 'Concept' term to remove.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function removeConcept(TermInterface $term_to_remove);

  /**
   * Gets the Legislation's Source Document.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocumentInterface
   *   The SourceDocument entity.
   */
  public function getOrigin();

  /**
   * Gets the Legislation's Source ID.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocumentInterface
   *   The SourceDocument entity.
   */
  public function getOriginId();

  /**
   * Sets the Legislation's Source Document.
   *
   * @param \Drupal\bnald_core\Entity\SourceDocumentInterface $source_document
   *   The SourceDocument entity.
   *
   * @return \Drupal\bnald_core\Entity\Legislation
   *   The called Legislation entity.
   */
  public function setOrigin(SourceDocumentInterface $source_document);

  /**
   * Gets any item notes associated with this Legislation.
   *
   * @return string
   *   Item notes associated with this Legislation.
   */
  public function getItemNotes();

  /**
   * Sets item notes for this Legislation.
   *
   * @param string $notes
   *   The item notes of this Legislation.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setItemNotes($notes);

  /**
   * Gets the sort key of the chapter this Legislation appears in.
   *
   * @return string
   *   The sort key of the chapter this Legislation appears in.
   */
  public function getChapterSort();

  /**
   * Sets the sort key of the chapter this Legislation appears in.
   *
   * @param string $sort_key
   *   The sort key of the chapter this Legislation appears in.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setChapterSort($sort_key);

  /**
   * Returns the Legislation published status indicator.
   *
   * Unpublished Legislation are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Legislation is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Legislation.
   *
   * @param bool $published
   *   TRUE to set this Legislation to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setPublished($published);

  /**
   * Gets the Legislation revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Legislation revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Gets the Legislation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Legislation.
   */
  public function getCreatedTime();

  /**
   * Sets the Legislation creation timestamp.
   *
   * @param int $timestamp
   *   The Legislation creation timestamp.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Legislation revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Legislation revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The called Legislation entity.
   */
  public function setRevisionCreationTime($timestamp);

}
