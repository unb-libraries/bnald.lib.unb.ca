<?php

namespace Drupal\bnald_core\Form;

use Drupal\bnald_core\Entity\LegislationInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a Legislation revision.
 *
 * @ingroup bnald_core
 */
class LegislationRevisionRevertForm extends ConfirmFormBase {


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
  protected $legislationStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new LegislationRevisionRevertForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The Legislation storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityStorageInterface $entity_storage, DateFormatterInterface $date_formatter) {
    $this->legislationStorage = $entity_storage;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
        ->getStorage('legislation'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'legislation_revision_revert_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to revert to the revision from %revision-date?', [
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
    return t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $legislation_revision = NULL) {
    $this->revision = $this->legislationStorage->loadRevision($legislation_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The revision timestamp will be updated when the revision is saved. Keep
    // the original one for the confirmation message.
    $original_revision_timestamp = $this->revision->getRevisionCreationTime();

    $this->revision = $this->prepareRevertedRevision($this->revision, $form_state);
    $this->revision->revision_log = t('Copy of the revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]);
    $this->revision->save();

    $this->logger('content')->notice('Legislation: reverted %title revision %revision.', [
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId()
    ]);
    $this->messenger()->addStatus(t('Legislation %title has been reverted to the revision from %revision-date.', [
      '%title' => $this->revision->label(),
      '%revision-date' => $this->dateFormatter->format($original_revision_timestamp)
    ]));
    $form_state->setRedirect('entity.legislation.version_history', [
      'legislation' => $this->revision->id()
    ]);
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\bnald_core\Entity\LegislationInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\bnald_core\Entity\LegislationInterface
   *   The prepared revision ready to be stored.
   */
  protected function prepareRevertedRevision(LegislationInterface $revision, FormStateInterface $form_state) {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);
    $revision->setRevisionCreationTime(\Drupal::time()->getRequestTime());

    return $revision;
  }

}
