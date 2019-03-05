<?php

namespace Drupal\bnald_core\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\bnald_core\Entity\LegislationInterface;

/**
 * Class PieceOfLegislationController.
 *
 *  Returns responses for Legislation routes.
 */
class PieceOfLegislationController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Legislation  revision.
   *
   * @param int $legislation_revision
   *   The Legislation  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($legislation_revision) {
    $legislation = $this->entityManager()->getStorage('legislation')->loadRevision($legislation_revision);
    $view_builder = $this->entityManager()->getViewBuilder('legislation');

    return $view_builder->view($legislation);
  }

  /**
   * Page title callback for a Legislation  revision.
   *
   * @param int $legislation_revision
   *   The Legislation  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($legislation_revision) {
    $legislation = $this->entityManager()->getStorage('legislation')->loadRevision($legislation_revision);
    return $this->t('Revision of %title from %date', ['%title' => $legislation->label(), '%date' => format_date($legislation->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Legislation .
   *
   * @param \Drupal\bnald_core\Entity\LegislationInterface $legislation
   *   A Legislation  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(LegislationInterface $legislation) {
    $account = $this->currentUser();
    $langcode = $legislation->language()->getId();
    $langname = $legislation->language()->getName();
    $languages = $legislation->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $legislation_storage = $this->entityManager()->getStorage('legislation');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $legislation->label()]) : $this->t('Revisions for %title', ['%title' => $legislation->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all legislation revisions") || $account->hasPermission('administer legislation entities')));
    $delete_permission = (($account->hasPermission("delete all legislation revisions") || $account->hasPermission('administer legislation entities')));

    $rows = [];

    $vids = $legislation_storage->revisionIds($legislation);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\bnald_core\PieceOfLegislationInterface $revision */
      $revision = $legislation_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $legislation->getRevisionId()) {
          $link = $this->l($date, new Url('entity.legislation.revision', ['legislation' => $legislation->id(), 'legislation_revision' => $vid]));
        }
        else {
          $link = $legislation->link($date);
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
              'url' => Url::fromRoute('entity.legislation.revision_revert', ['legislation' => $legislation->id(), 'legislation_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.legislation.revision_delete', ['legislation' => $legislation->id(), 'legislation_revision' => $vid]),
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

    $build['legislation_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
