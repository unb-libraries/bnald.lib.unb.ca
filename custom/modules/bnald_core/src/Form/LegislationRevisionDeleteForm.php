<?php

namespace Drupal\bnald_core\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Legislation revision.
 *
 * @ingroup bnald_core
 */
class LegislationRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The Legislation revision.
   *
   * @var \Drupal\bnald_core\Entity\LegislationInterface
   */
  protected $revision;

  /**
   * The Legislation storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $LegislationStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new LegislationRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection, DateFormatterInterface $date_formatter) {
    $this->LegislationStorage = $entity_storage;
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
        ->getStorage('legislation'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'legislation_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter
        ->format($this->revision->getRevisionCreationTime())
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.legislation.version_history', [
      'legislation' => $this->revision->id()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $legislation_revision = NULL) {
    $this->revision = $this->LegislationStorage->loadRevision($legislation_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->LegislationStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Legislation: deleted %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId()
    ]);

    $this->messenger()->addStatus(t('Revision from %revision-date of Legislation %title has been deleted.', [
      '%revision-date' => $this->dateFormatter
        ->format($this->revision->getRevisionCreationTime()),
      '%title' => $this->revision->label()
    ]));

    $form_state->setRedirect('entity.legislation.canonical', [
      'legislation' => $this->revision->id()
    ]);

    $query = $this->connection
      ->query('SELECT COUNT(DISTINCT vid) FROM {legislation_field_revision} WHERE id = :id', [
        ':id' => $this->revision->id()
      ]);
    if ($query->fetchField() > 1) {
      $form_state->setRedirect('entity.legislation.version_history', [
        'legislation' => $this->revision->id()
      ]);
    }
  }

}
