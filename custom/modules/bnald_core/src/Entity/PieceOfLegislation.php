<?php

namespace Drupal\bnald_core\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Piece of Legislation entity.
 *
 * @ingroup bnald_core
 *
 * @ContentEntityType(
 *   id = "piece_legislation",
 *   label = @Translation("Piece of Legislation"),
 *   handlers = {
 *     "storage" = "Drupal\bnald_core\PieceOfLegislationStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bnald_core\PieceOfLegislationListBuilder",
 *     "views_data" = "Drupal\bnald_core\Entity\PieceOfLegislationViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\bnald_core\Form\PieceOfLegislationForm",
 *       "add" = "Drupal\bnald_core\Form\PieceOfLegislationForm",
 *       "edit" = "Drupal\bnald_core\Form\PieceOfLegislationForm",
 *       "delete" = "Drupal\bnald_core\Form\PieceOfLegislationDeleteForm",
 *     },
 *     "access" = "Drupal\bnald_core\PieceOfLegislationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\bnald_core\PieceOfLegislationHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "piece_legislation",
 *   revision_table = "piece_legislation_revision",
 *   revision_data_table = "piece_legislation_field_revision",
 *   admin_permission = "administer piece of legislation entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "legislation_title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/piece_legislation/{piece_legislation}",
 *     "add-form" = "/admin/structure/piece_legislation/add",
 *     "edit-form" = "/admin/structure/piece_legislation/{piece_legislation}/edit",
 *     "delete-form" = "/admin/structure/piece_legislation/{piece_legislation}/delete",
 *     "version-history" = "/admin/structure/piece_legislation/{piece_legislation}/revisions",
 *     "revision" = "/admin/structure/piece_legislation/{piece_legislation}/revisions/{piece_legislation_revision}/view",
 *     "revision_revert" = "/admin/structure/piece_legislation/{piece_legislation}/revisions/{piece_legislation_revision}/revert",
 *     "revision_delete" = "/admin/structure/piece_legislation/{piece_legislation}/revisions/{piece_legislation_revision}/delete",
 *     "collection" = "/admin/structure/piece_legislation",
 *   },
 *   field_ui_base_route = "piece_legislation.settings"
 * )
 */
class PieceOfLegislation extends RevisionableContentEntityBase implements PieceOfLegislationInterface {

  const MIN_YEAR = 1758;
  const MAX_YEAR = 1867;
  const MIN_ARTICLES = 0;

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the piece_legislation owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getProvince() {
    return $this->get('province')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setProvince(TermInterface $province) {
    $this->set('province', $province->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLegislationTitle() {
    return $this->get('legislation_title');
  }

  /**
   * {@inheritdoc}
   */
  public function setLegislationTitle($title) {
    $this->set('legislation_title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJurisdictionalRelevance() {
    $terms = $this->get('jurisdictional_relevance')->referencedEntities();
    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function appendJurisdictionalRelevance($term_to_add) {
    $terms = $this->get('jurisdictional_relevance');
    $terms->appendItem($term_to_add);
    $this->set('jurisdictional_relevance', $terms);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeJurisdictionalRelevance($term_to_remove) {
    $terms = $this->get('jurisdictional_relevance');
    foreach ($terms as $index => $term) {
      if ($term === $term_to_remove) {
        $terms->removeitem($index);
        break;
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfArticles() {
    return $this->get('number_articles');
  }

  /**
   * {@inheritdoc}
   */
  public function setNumberOfArticles($number) {
    if ($number >= self::MIN_ARTICLES) {
      $this->set('number_articles', $number);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConcepts() {
    $terms = $this->get('concepts')->referencedEntities();
    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function appendConcept($term_to_add) {
    $terms = $this->get('concepts');
    $terms->appendItem($term_to_add);
    $this->set('concepts', $terms);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeConcept($term_to_remove) {
    $terms = $this->get('concepts');
    foreach ($terms as $index => $term) {
      if ($term === $term_to_remove) {
        $terms->removeitem($index);
        break;
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLegislativeSummary() {
    return $this->get('legislative_summary');
  }

  /**
   * {@inheritdoc}
   */
  public function setLegislativeSummary($summary) {
    $this->set('legislative_summary', $summary);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLegislativeFullText() {
    return $this->get('legislative_full_text');
  }

  /**
   * {@inheritdoc}
   */
  public function setLegislativeFullText($full_text) {
    $this->set('legislative_full_text', $full_text);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemNotes() {
    return $this->get('item_notes');
  }

  /**
   * {@inheritdoc}
   */
  public function setItemNotes($notes) {
    $this->set('item_notes', $notes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceDocument() {
    return $this->get('source_document')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceDocumentId() {
    $entity = $this->get('source_document')->entity;
    if (!empty($entity)) {
      return $entity->id();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceDocument(SourceDocumentInterface $source_document) {
    $this->set('source_document', $source_document->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getYearPassed() {
    return $this->get('year_passed')->value;
  }

  /**
   * {@inheritdoc}
   *
   * Value must be within PieceOfLegislation::MIN_YEAR and PieceOfLegislation::MAX_YEAR.
   */
  public function setYearPassed($year) {
    if ($year >= self::MIN_YEAR && $year <= self::MAX_YEAR) {
      $this->set('year_passed', $year);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChapter() {
    return $this->get('chapter');
  }

  /**
   * {@inheritdoc}
   */
  public function setChapter($chapter) {
    $this->set('chapter', $chapter);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChapterSort() {
    return $this->get('chapter_sort');
  }

  /**
   * {@inheritdoc}
   */
  public function setChapterSort($sort_key) {
    $this->set('chapter_sort', $sort_key);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['province'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Province'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'provinces' => 'provinces',
        ],
      ])
      ->setDisplayOptions('view', [
        'weight' => 1,
      ])
      ->setDisplayOptions(('form'), [
        'target' => 'provinces',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['legislation_title'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Legislation Title'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'max_length' => 0,
      ])
      ->setDisplayOptions('view', [
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'weight' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['jurisdictional_relevance'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Jurisdictional Relevance'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setTranslatable(FALSE)
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'jurisdictional_relevance' => 'jurisdictional_relevance',
        ],
      ])
      ->setDisplayOptions('view', [
        'weight' => 3,
      ])
      ->setDisplayOptions(('form'), [
        'target' => 'jurisdictional_relevance',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['number_articles'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of Articles'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'min' => self::MIN_ARTICLES,
      ])
      ->setDisplayOptions('view', [
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['concepts'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Concepts'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setTranslatable(FALSE)
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'concepts' => 'concepts',
        ],
      ])
      ->setDisplayOptions('view', [
        'weight' => 5,
      ])
      ->setDisplayOptions(('form'), [
        'target' => 'concepts',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['legislative_summary'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Legislative Summary'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'max_length' => 0,
      ])
      ->setDisplayOptions('view', [
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['legislative_full'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Legislative Full Text'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'max_length' => 0,
      ])
      ->setDisplayOptions('view', [
        'weight' => 9,
      ])
      ->setDisplayOptions('form', [
        'weight' => 11,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['item_notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Item Notes'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'max_length' => 0,
      ])
      ->setDisplayOptions('view', [
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'weight' => 12,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['source_document'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Source Document'))
      ->setDescription(t('The Source Document of the Piece of Legislation'))
      ->setSettings(
        [
          'target_type' => 'source_document',
          'handler' => 'default',
        ]
      )
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions(
        'view',
        [
          'label' => 'above',
          'weight' => 0,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'entity_reference_autocomplete',
          'weight' => 0,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => '60',
            'autocomplete_type' => 'tags',
            'placeholder' => '',
          ],
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['year_passed'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Year Passed'))
      ->setDescription(t('The Year this Piece of Legislation passed.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'min' => self::MIN_YEAR,
        'max' => self::MAX_YEAR,
      ])
      ->setDisplayOptions('view', [
        'weight' => 13,
      ])
      ->setDisplayOptions('form', [
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['chapter'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Chapter'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'weight' => 14,
      ])
      ->setDisplayOptions('form', [
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['chapter_sort'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Chapter Sort'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'type' => 'hidden',
        'weight' => 15,
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Piece of Legislation entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 98,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Piece of Legislation is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 99,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
