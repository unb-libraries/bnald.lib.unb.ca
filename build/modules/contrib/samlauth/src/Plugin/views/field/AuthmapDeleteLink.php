<?php

namespace Drupal\samlauth\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\externalauth\Plugin\views\field\AuthmapDeleteLink as ExtAuthLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to delete an authmap entry.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("samlauth_link_delete")
 *
 * @deprecated in samlauth:3.10 and is removed from samlauth:4.0. Use
 *   "authmap_link_delete" field plugin instead.
 */
class AuthmapDeleteLink extends ExtAuthLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row): ?Url {
    // Comatibility with externalauth 2.0.7.
    $destinationService = $this->redirectDestination ?? \Drupal::service('redirect.destination');

    // Keep using our delete form instead of externalauth's, only so
    // permissions don't need to be changed yet.
    $uid = $row->uid ?? $row->authmap_uid ?? NULL;
    return $uid
      ? Url::fromRoute('samlauth.authmap_delete_form',
        ['uid' => $uid],
        ['query' => $destinationService->getAsArray()]
      ) : NULL;
  }

}
