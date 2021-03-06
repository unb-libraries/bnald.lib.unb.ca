<?php

namespace Drupal\bnald_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Legislation entities.
 *
 * @ingroup bnald_core
 */
class LegislationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Title');
    $header['year'] = $this->t('Year');
    $header['province'] = $this->t('Province');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\bnald_core\Entity\Legislation */
    $row['id'] = $entity->id();
    $row['title'] = Link::createFromRoute(
      $entity->label(),
      'entity.legislation.canonical',
      ['legislation' => $entity->id()]
    );
    $row['year'] = $entity->getYear();

    $province = $entity->getProvince();
    $row['province'] = isset($province) ? $province->label() : '';

    return $row + parent::buildRow($entity);
  }

}
