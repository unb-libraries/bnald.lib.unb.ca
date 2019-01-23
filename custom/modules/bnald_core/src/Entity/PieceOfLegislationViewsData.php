<?php

namespace Drupal\bnald_core\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Piece of Legislation entities.
 */
class PieceOfLegislationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
