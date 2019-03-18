<?php

namespace Drupal\bnald_core\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Source Document entity.
 *
 * @ingroup bnald_core
 *
 * @ContentEntityType(
 *   id = "source_document",
 *   label = @Translation("Source Document"),
 *   handlers = {
 *     "storage" = "Drupal\bnald_core\SourceDocumentStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\bnald_core\SourceDocumentListBuilder",
 *     "views_data" = "Drupal\bnald_core\Entity\SourceDocumentViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\bnald_core\Form\SourceDocumentForm",
 *       "add" = "Drupal\bnald_core\Form\SourceDocumentForm",
 *       "edit" = "Drupal\bnald_core\Form\SourceDocumentForm",
 *       "delete" = "Drupal\bnald_core\Form\SourceDocumentDeleteForm",
 *     },
 *     "access" = "Drupal\bnald_core\SourceDocumentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\bnald_core\SourceDocumentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "source_document",
 *   revision_table = "source_document_revision",
 *   revision_data_table = "source_document_field_revision",
 *   admin_permission = "administer source document entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "source",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/documents/{source_document}",
 *     "add-form" = "/documents/add",
 *     "edit-form" = "/documents/{source_document}/edit",
 *     "delete-form" = "/documents/{source_document}/delete",
 *     "version-history" = "/documents/{source_document}/revisions",
 *     "revision" = "/documents/{source_document}/revisions/{source_document_revision}/view",
 *     "revision_revert" = "/documents/{source_document}/revisions/{source_document_revision}/revert",
 *     "revision_delete" = "/documents/{source_document}/revisions/{source_document_revision}/delete",
 *     "collection" = "/documents",
 *   },
 *   field_ui_base_route = "source_document.settings"
 * )
 */
class SourceDocument extends RevisionableContentEntityBase implements SourceDocumentInterface {

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

    // If no revision author has been set explicitly, make the source_document owner the
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
  public function getYear() {
    return $this->get('year')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setYear($year) {
    $this->set('year', $year);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrinter() {
    return $this->get('printer')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrinterId() {
    $entity = $this->get('printer')->entity;
    if (!empty($entity)) {
      return $entity->id();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrinter(TermInterface $printer) {
    $this->set('printer', $printer->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrintLocation() {
    return $this->get('print_location')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrintLocationId() {
    $entity = $this->get('print_location')->entity;
    if (!empty($entity)) {
      return $entity->id();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->get('source')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    $this->set('source', $source);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrintLocation(TermInterface $location) {
    $this->set('print_location', $location->id());
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

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setSettings([
        'max_length' => 255,
      ])
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['year'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Year'))
      ->setDescription(t('Which year has the Document been printed?'))
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

    $fields['printer'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Printed By'))
      ->setDescription(t('Who has printed the Document?'))
      ->setSettings(
        [
          'target_type' => 'taxonomy_term',
          'handler_settings' => [
            'target_bundles' => [
              'printers' => 'printers',
            ],
            'auto_create' => FALSE,
          ],
        ]
      )
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions(
        'view',
        [
          'weight' => 2,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'options_select',
          'weight' => 2,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['print_location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Printed In'))
      ->setDescription(t('Where has the Document been printed?'))
      ->setSettings(
        [
          'target_type' => 'taxonomy_term',
          'handler' => 'default:taxonomy_term',
          'handler_settings' => [
            'target_bundles' => [
              'print_locations' => 'print_locations',
            ],
            'auto_create' => FALSE,
          ],
        ]
      )
      ->setCardinality(1)
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions(
        'view',
        [
          'weight' => 3,
        ]
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'options_select',
          'weight' => 3,
        ]
      )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source'))
      ->setDescription(t('The source of the document.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'weight' => 4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publish?'))
      ->setDescription(t('Whether the Document shall be publicly visible..'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 6,
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
