<?php

namespace Drupal\bnald_migrate\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d7\NodeType;

/**
 * Drupal 7 Node types source from database.
 *
 * @MigrateSource(
 *   id = "node_type_filter",
 *   source_module = "node"
 * )
 *
 * * @code
 * field_file:
 *   plugin: node_type_filter
 *   node_types:
 *     - page
 *     - article
 * @endcode
 */
class NodeTypeFilter extends NodeType {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    if (!empty($this->configuration['node_types'])) {
      if (is_array($this->configuration['node_types'])) {
        $query->condition('t.type', $this->configuration['node_types'], 'in');
      }
      else {
        $query->condition('t.type', $this->configuration['node_types'], '=');
      }
    }
    return $query;
  }
}