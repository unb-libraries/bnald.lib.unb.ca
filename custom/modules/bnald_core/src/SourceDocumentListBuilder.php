<?php

namespace Drupal\bnald_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Source Document entities.
 *
 * @ingroup bnald_core
 */
class SourceDocumentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Document');
    $header['year'] = $this->t('Year');
    $header['printer'] = $this->t('Printed By');
    $header['location'] = $this->t('Printed In');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\bnald_core\Entity\SourceDocument */
    $row['id'] = $entity->id();
    $row['title'] = Link::createFromRoute(
      $entity->getTitle(),
      'entity.source_document.canonical',
      ['source_document' => $entity->id()]
    );
    $row['year'] = $entity->getYear();

    $printer = $entity->getPrinter();
    $row['printer'] = isset($printer) ? $printer->label() : '';

    $location = $entity->getPrintLocation();
    $row['location'] = isset($location) ? $location->label() : '';

    return $row + parent::buildRow($entity);
  }

}
