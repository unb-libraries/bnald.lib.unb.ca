<?php

namespace Drupal\tvi\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters the route for using Taxonomy Views Integrator.
 *
 * Views is allowed to alter and builds the routes first, and we then alter this
 * route after the fact. The idea is we can have multiple views that define
 * `taxonomy/term/%` as the page path, and the settings form on the term/vocab
 * let the user determine which view displays.
 *
 * Otherwise, Views will always control the path, and that's not what we want.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // Update the term router to use the TVI controller.
    if ($route = $collection->get('entity.taxonomy_term.canonical')) {
      $route->setDefault('_controller', '\Drupal\tvi\Controller\TaxonomyViewsIntegratorTermPageController::render')
        ->setRequirement('_entity_access', 'taxonomy_term.view');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    // After the Views subscriber has run, start running.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
    return $events;
  }

}
