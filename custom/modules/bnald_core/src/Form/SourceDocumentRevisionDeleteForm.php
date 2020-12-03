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
 * Provides a form for deleting a Source Document revision.
 *
 * @ingroup bnald_core
 */
class SourceDocumentRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The Source Document revision.
   *
   * @var \Drupal\bnald_core\Entity\SourceDocumentInterface
   */
  protected $revision;

  /**
   * The Source Document storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $SourceDocumentStorage;

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
   * Constructs a new SourceDocumentRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param DateFormatterInterface $date_formatter
   *   The date formatter
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection, DateFormatterInterface $date_formatter) {
    $this->SourceDocumentStorage = $entity_storage;
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $container->get('entity_type.manager')
        ->getStorage('source_document'),
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'source_document_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime())
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.source_document.version_history', [
      'source_document' => $this->revision->id()
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
  public function buildForm(array $form, FormStateInterface $form_state, $source_document_revision = NULL) {
    $this->revision = $this->SourceDocumentStorage->loadRevision($source_document_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->SourceDocumentStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Source Document: deleted %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId()
    ]);

    $this->messenger()->addStatus(t('Revision from %revision-date of Source Document %title has been deleted.', [
      '%revision-date' => $this->dateFormatter->format($this->revision->getRevisionCreationTime()),
      '%title' => $this->revision->label()]));
    $form_state->setRedirect('entity.source_document.canonical', [
      'source_document' => $this->revision->id()
    ]);

    $query = $this->connection
      ->query('SELECT COUNT(DISTINCT vid) FROM {source_document_field_revision} WHERE id = :id', [
        ':id' => $this->revision->id()
      ]);
    if ($query->fetchField() > 1) {
      $form_state->setRedirect('entity.source_document.version_history', [
        'source_document' => $this->revision->id()
      ]);
    }
  }

}
