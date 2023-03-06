<?php

namespace Drupal\einbaum\Entity;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Manager to interact with entity instances created,altered during a test.
 */
class EntityTestDataManager implements EntityTestDataManagerInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   An entity type manager instance.
   */
  protected function entityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Construct a new EntityTestDataManager instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   An entity type manager instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteLatest(string $entity_type_id) {
  }

}
