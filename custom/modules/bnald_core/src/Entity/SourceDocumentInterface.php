<?php

namespace Drupal\bnald_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Source Document entities.
 *
 * @ingroup bnald_core
 */
interface SourceDocumentInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Source Document name.
   *
   * @return string
   *   Name of the Source Document.
   */
  public function getName();

  /**
   * Sets the Source Document name.
   *
   * @param string $name
   *   The Source Document name.
   *
   * @return \Drupal\bnald_core\Entity\SourceDocumentInterface
   *   The called Source Document entity.
   */
  public function setName($name);

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
