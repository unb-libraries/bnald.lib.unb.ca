<?php

namespace Drupal\bnald_core\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\bnald_core\Entity\PieceOfLegislationInterface;

/**
 * Class PieceOfLegislationController.
 *
 *  Returns responses for Piece of Legislation routes.
 */
class PieceOfLegislationController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Piece of Legislation  revision.
   *
   * @param int $piece_legislation_revision
   *   The Piece of Legislation  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($piece_legislation_revision) {
    $piece_legislation = $this->entityManager()->getStorage('piece_legislation')->loadRevision($piece_legislation_revision);
    $view_builder = $this->entityManager()->getViewBuilder('piece_legislation');

    return $view_builder->view($piece_legislation);
  }

  /**
   * Page title callback for a Piece of Legislation  revision.
   *
   * @param int $piece_legislation_revision
   *   The Piece of Legislation  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($piece_legislation_revision) {
    $piece_legislation = $this->entityManager()->getStorage('piece_legislation')->loadRevision($piece_legislation_revision);
    return $this->t('Revision of %title from %date', ['%title' => $piece_legislation->label(), '%date' => format_date($piece_legislation->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Piece of Legislation .
   *
   * @param \Drupal\bnald_core\Entity\PieceOfLegislationInterface $piece_legislation
   *   A Piece of Legislation  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(PieceOfLegislationInterface $piece_legislation) {
    $account = $this->currentUser();
    $langcode = $piece_legislation->language()->getId();
    $langname = $piece_legislation->language()->getName();
    $languages = $piece_legislation->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $piece_legislation_storage = $this->entityManager()->getStorage('piece_legislation');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $piece_legislation->label()]) : $this->t('Revisions for %title', ['%title' => $piece_legislation->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all piece of legislation revisions") || $account->hasPermission('administer piece of legislation entities')));
    $delete_permission = (($account->hasPermission("delete all piece of legislation revisions") || $account->hasPermission('administer piece of legislation entities')));

    $rows = [];

    $vids = $piece_legislation_storage->revisionIds($piece_legislation);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\bnald_core\PieceOfLegislationInterface $revision */
      $revision = $piece_legislation_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $piece_legislation->getRevisionId()) {
          $link = $this->l($date, new Url('entity.piece_legislation.revision', ['piece_legislation' => $piece_legislation->id(), 'piece_legislation_revision' => $vid]));
        }
        else {
          $link = $piece_legislation->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
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
              'url' => Url::fromRoute('entity.piece_legislation.revision_revert', ['piece_legislation' => $piece_legislation->id(), 'piece_legislation_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.piece_legislation.revision_delete', ['piece_legislation' => $piece_legislation->id(), 'piece_legislation_revision' => $vid]),
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

    $build['piece_legislation_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
