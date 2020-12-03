<?php

namespace Drupal\bnald_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Source Document edit forms.
 *
 * @ingroup bnald_core
 */
class SourceDocumentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\bnald_core\Entity\SourceDocument */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 50,
      ];
    }

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Source Document.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Source Document.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.source_document.canonical', [
      'source_document' => $entity->id()
    ]);
  }

}
