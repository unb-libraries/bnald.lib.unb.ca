<?php

namespace Drupal\bnald_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Piece of Legislation entities.
 *
 * @ingroup bnald_core
 */
class PieceOfLegislationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['legislation_title'] = $this->t('Legislation Title');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\bnald_core\Entity\PieceOfLegislation */
    $row['id'] = $entity->id();
    $row['legislation_title'] = Link::createFromRoute(
      $entity->label(),
      'entity.piece_legislation.edit_form',
      ['piece_legislation' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
