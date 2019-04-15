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
 * Defines the Legislation entity.
 *
 * @ingroup bnald_core
 *
 * @ContentEntityType(
 *   id = "legislation",
 *   label = @Translation("Legislation"),
 *   handlers = {
 *     "storage" = "Drupal\bnald_core\LegislationStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bnald_core\LegislationListBuilder",
 *     "views_data" = "Drupal\bnald_core\Entity\LegislationViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\bnald_core\Form\LegislationForm",
 *       "add" = "Drupal\bnald_core\Form\LegislationForm",
 *       "edit" = "Drupal\bnald_core\Form\LegislationForm",
 *       "delete" = "Drupal\bnald_core\Form\LegislationDeleteForm",
 *     },
 *     "access" = "Drupal\bnald_core\LegislationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\bnald_core\LegislationHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legislation",
 *   revision_table = "legislation_revision",
 *   revision_data_table = "legislation_field_revision",
 *   admin_permission = "administer legislation entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/legislation/{legislation}",
 *     "add-form" = "/legislation/add",
 *     "edit-form" = "/legislation/{legislation}/edit",
 *     "delete-form" = "/legislation/{legislation}/delete",
 *     "version-history" = "/legislation/{legislation}/revisions",
 *     "revision" = "/legislation/{legislation}/revisions/{legislation_revision}/view",
 *     "revision_revert" = "/legislation/{legislation}/revisions/{legislation_revision}/revert",
 *     "revision_delete" = "/legislation/{legislation}/revisions/{legislation_revision}/delete",
 *     "collection" = "/legislation",
 *   },
 *   field_ui_base_route = "legislation.settings"
 * )
 */
class Legislation extends RevisionableContentEntityBase implements LegislationInterface {

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

    // If no revision author has been set explicitly, make the legislation owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChapter() {
    return $this->get('chapter')->value;
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
  public function getYear() {
    return $this->get('year')->value;
  }

  /**
   * {@inheritdoc}
   *
   * Value must be within Legislation::MIN_YEAR and Legislation::MAX_YEAR.
   */
  public function setYear($year) {
    if ($year >= self::MIN_YEAR && $year <= self::MAX_YEAR) {
      $this->set('year', $year);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfArticles() {
    return $this->get('article_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNumberOfArticles($number) {
    if ($number >= self::MIN_ARTICLES) {
      $this->set('article_count', $number);
    }
    return $this;
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
  public function getSummary() {
    return $this->get('summary')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSummary($summary) {
    $this->set('summary', $summary);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFullText() {
    return $this->get('full_text')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFullText($full_text) {
    $this->set('full_text', $full_text);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPdfTranscribed() {
    return $this->get('pdf_transcribed')->entity->getFileUri();
  }

  /**
   * {@inheritdoc}
   */
  public function setPdfTranscribed($pdf) {
    return $this->set('pdf_transcribed', $pdf);
  }

  /**
   * {@inheritdoc}
   */
  public function getPdfOriginal() {
    return $this->get('pdf_original')->entity->getFileUri();
  }

  /**
   * {@inheritdoc}
   */
  public function setPdfOriginal($pdf) {
    return $this->set('pdf_original', $pdf);
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
  public function getOrigin() {
    return $this->get('origin')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrigin(SourceDocumentInterface $source_document) {
    $this->set('origin', $source_document->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemNotes() {
    return $this->get('notes')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setItemNotes($notes) {
    $this->set('notes', $notes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChapterSort() {
    return $this->get('chapter_sort')->value;
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
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['origin'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Source'))
      ->setDescription(t('Which source document can this Legislation be found in?'))
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
          'weight' => 11,
          'settings' => [
            'link' => FALSE,
          ],
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'entity_reference_autocomplete',
          'settings' => [
            'size' => 180,
            'placeholder' => 'Search by Title',
          ],
          'weight' => -1,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'max_length' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['chapter'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Chapter'))
      ->setDescription(t('Which chapter of its source document did this Legislation appear in?'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['year'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Year'))
      ->setDescription(t('When did this Legislation pass?'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'min' => self::MIN_YEAR,
        'max' => self::MAX_YEAR,
      ])
      ->setDisplayOptions('view', [
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['article_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Number of Articles'))
      ->setDescription(t('How many articles does this Legislation comprise?'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'min' => self::MIN_ARTICLES,
      ])
      ->setDisplayOptions('view', [
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'weight' => 3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['province'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Province'))
      ->setDescription(t('Which (historical) province does/did this Legislation apply to?'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [
            'provinces' => 'provinces',
          ],
          'auto_create' => TRUE,
          'auto_create_bundle' => 'provinces',
        ],
      ])
      ->setDisplayOptions('view', [
        'weight' => 4,
      ])
      ->setDisplayOptions(('form'), [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'size' => 180,
          'placeholder' => 'Search and select. Non-existing terms will be created.',
        ],
        'weight' => 4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['summary'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'max_length' => 0,
      ])
      ->setDisplayOptions('view', [
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['full_text'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Full Text'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'max_length' => 0,
      ])
      ->setDisplayOptions('view', [
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'weight' => 6,
        'settings' => [
          'rows' => 20,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['pdf_original'] = BaseFieldDefinition::create('file')
      ->setLabel(t('PDF (Original)'))
      ->setDescription(t('Upload a PDF of the originally printed version of the Legislation.'))
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setRequired(TRUE)
      ->setSettings([
        'file_extensions' => 'pdf',
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'weight' => 7,
        'type' => 'pdfpreview',
        'settings' => [
          'image_style' => 'large',
          'show_description' => 0,
          'tag' => 'div',
          'image_link' => 'file',
        ],
      ])->setDisplayOptions('form', [
        'weight' => 7,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['pdf_transcribed'] = BaseFieldDefinition::create('file')
      ->setLabel(t('PDF (Transcribed)'))
      ->setDescription(t('Upload a PDF of a transcribed copy of the Legislation'))
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setRequired(TRUE)
      ->setSettings([
        'file_extensions' => 'pdf',
      ])
      ->setDisplayOptions('view', [
        'weight' => 8,
      ])
      ->setDisplayOptions('form', [
        'weight' => 8,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['jurisdictional_relevance'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Jurisdictional Relevance'))
      ->setDescription(t('Tag with Jurisdictional Relevance terms'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setTranslatable(FALSE)
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [
            'jurisdictional_relevance' => 'jurisdictional_relevance',
          ],
          'auto_create' => TRUE,
          'auto_create_bundle' => 'jurisdictional_relevance',
        ],
      ])
      ->setDisplayOptions('view', [
        'weight' => 9,
      ])
      ->setDisplayOptions(('form'), [
        'type' => 'entity_reference_autocomplete_tags',
        'settings' => [
          'size' => 180,
          'placeholder' => 'Search and select, separate multiple by comma. Non-existing terms will be created.',
        ],
        'weight' => 9,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['concepts'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Concepts'))
      ->setDescription(t('Tag with Concepts terms.'))
      ->setRevisionable(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setTranslatable(FALSE)
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [
            'concepts' => 'concepts',
          ],
          'auto_create' => TRUE,
          'auto_create_bundle' => 'concepts',
        ],
      ])
      ->setDisplayOptions('view', [
        'weight' => 10,
      ])
      ->setDisplayOptions(('form'), [
        'type' => 'entity_reference_autocomplete_tags',
        'settings' => [
          'size' => 180,
          'placeholder' => 'Search and select, separate multiple by comma. Non-existing terms will be created.',
        ],
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Any notes related to this Legislation will not be visible to the public.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setSettings([
        'max_length' => 0,
      ])
      ->setDisplayOptions('form', [
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['chapter_sort'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Chapter Sort'))
      ->setDescription(t('Auto-populated field to define Chapter sort order in Search results.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Legislation entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publish?'))
      ->setDescription(t('Whether the Legislation shall be publicly visible.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 14,
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
