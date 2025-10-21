<?php

namespace Drupal\tvi\Enhancer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\EnhancerInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\tvi\Service\TaxonomyViewsIntegratorManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Enhancer to set correct views defaults arguments for Term based on TVI.
 *
 * By default, TVI only alter route and change renderer for taxonomy term page.
 * But some modules, like Search API, Facets and others is hardly rely on
 * current_route_match service. This service allows developers to get router
 * parameters for current request current_route_match:getParameters(). This
 * method returns parameters for current route, but if TVI replaces views they
 * still remain taxonomy_term/page_1, or the first view page found by view with
 * taxonomy/term/%. This makes views detection incorrect, and modules behave
 * not as expected.
 *
 * This service fix it. It uses the same logic to find current view id and
 * display id, and set it as defaults.
 */
class RouteEnhancer implements EnhancerInterface {

  /**
   * The TVI Manager.
   *
   * @var \Drupal\tvi\Service\TaxonomyViewsIntegratorManagerInterface
   */
  protected $tviManager;

  /**
   * Use EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * RouteEnhancer constructor.
   */
  public function __construct(
    TaxonomyViewsIntegratorManagerInterface $tvi_manager,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->tviManager = $tvi_manager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Check if the route applies to the current path.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route that is being enhanced.
   *
   * @return bool
   *   Return true if the route path applies.
   */
  private function applies(Route $route) {
    return $route->getPath() == '/taxonomy/term/{taxonomy_term}';
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    // Guard the route.
    if (!$this->applies($defaults['_route_object'])) {
      return $defaults;
    }

    // If the route has no term, ignore it.
    if (empty($defaults['taxonomy_term'])) {
      return $defaults;
    }

    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $defaults['taxonomy_term'];

    // Attempt to load the term if passed a TID.
    if (is_numeric($term)) {
      $storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $term = $storage->load($term);
    }

    // Enhance the route if we have a full term.
    if ($term instanceof TermInterface) {
      $term_view = $this->tviManager->getTaxonomyTermViewAndDisplayId($term);
      $defaults['view_id'] = $term_view['view_id'];
      $defaults['display_id'] = $term_view['display_id'];
    }

    return $defaults;
  }

}
