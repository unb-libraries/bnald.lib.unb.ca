<?php

namespace Drupal\einbaum\Entity;

/**
 * Interface for entity test data managers.
 */
interface EntityTestDataManagerInterface {

  /**
   * Remove the most recently created entity of the give type.
   *
   * @param string $entity_type_id
   *   An entity type ID.
   */
  public function deleteLatest(string $entity_type_id);

}
