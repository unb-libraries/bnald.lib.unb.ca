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

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Piece of Legislation name.
   *
   * @return string
   *   Name of the Piece of Legislation.
   */
  public function getName();

  /**
   * Sets the Piece of Legislation name.
   *
   * @param string $name
   *   The Piece of Legislation name.
   *
   * @return \Drupal\bnald_core\Entity\PieceOfLegislationInterface
   *   The called Piece of Legislation entity.
   */
  public function setName($name);

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
