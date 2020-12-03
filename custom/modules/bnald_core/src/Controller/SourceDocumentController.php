<?php

namespace Drupal\bnald_core\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\bnald_core\Entity\SourceDocumentInterface;

/**
 * Class SourceDocumentController.
 *
 *  Returns responses for Source Document routes.
 */
class SourceDocumentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Source Document  revision.
   *
   * @param int $source_document_revision
   *   The Source Document  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($source_document_revision) {
    $source_document = $this->entityTypeManager()
      ->getStorage('source_document')
      ->loadRevision($source_document_revision);
    $view_builder = $this->entityTypeManager()
      ->getViewBuilder('source_document');

    return $view_builder->view($source_document);
  }

  /**
   * Page title callback for a Source Document  revision.
   *
   * @param int $source_document_revision
   *   The Source Document  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($source_document_revision) {
    $source_document = $this->entityTypeManager()
      ->getStorage('source_document')
      ->loadRevision($source_document_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $source_document->label(),
      '%date' => \Drupal::service('date.formatter')
        ->format($source_document->getRevisionCreationTime())
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Source Document .
   *
   * @param \Drupal\bnald_core\Entity\SourceDocumentInterface $source_document
   *   A Source Document  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(SourceDocumentInterface $source_document) {
    $account = $this->currentUser();
    $langcode = $source_document->language()->getId();
    $langname = $source_document->language()->getName();
    $languages = $source_document->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $source_document_storage = $this->entityTypeManager()
      ->getStorage('source_document');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $source_document->label()]) : $this->t('Revisions for %title', ['%title' => $source_document->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all source document revisions") || $account->hasPermission('administer source document entities')));
    $delete_permission = (($account->hasPermission("delete all source document revisions") || $account->hasPermission('administer source document entities')));

    $rows = [];

    $vids = $source_document_storage->revisionIds($source_document);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\bnald_core\SourceDocumentInterface $revision */
      $revision = $source_document_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $source_document->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, Url::fromRoute('entity.source_document.revision', [
            'source_document' => $source_document->id(),
            'source_document_revision' => $vid,
          ]));
        }
        else {
          $link = Link::fromTextAndUrl($source_document->label(), $source_document->toUrl());
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList()
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => Url::fromRoute('entity.source_document.revision_revert', [
                'source_document' => $source_document->id(),
                'source_document_revision' => $vid
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.source_document.revision_delete', [
                'source_document' => $source_document->id(),
                'source_document_revision' => $vid
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['source_document_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
