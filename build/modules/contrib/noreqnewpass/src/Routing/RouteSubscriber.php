<?php

namespace Drupal\noreqnewpass\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Always deny access to '/user/password', if noreqnewpass_disable is set to
    // TRUE.
    if ($route = $collection->get('user.pass')) {
      $this->denyAccess($route);
    }
    if ($route = $collection->get('user.pass.http')) {
      $this->denyAccess($route);
    }
  }

  /**
   * Deny access to the route if noreqnewpass_disable is set to TRUE.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to deny access to.
   */
  private function denyAccess(Route $route): void {
    $noreqnewpass_disable = \Drupal::config('noreqnewpass.settings_form')
      ->get('noreqnewpass_disable');
    if ($noreqnewpass_disable) {
      $route->setRequirement('_access', 'FALSE');
    }
  }

}
