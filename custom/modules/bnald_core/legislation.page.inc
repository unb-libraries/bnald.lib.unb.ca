<?php

/**
 * @file
 * Contains legislation.page.inc.
 *
 * Page callback for Legislation entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Legislation templates.
 *
 * Default template: legislation.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_legislation(array &$variables) {
  $variables['legislation']['entity'] = $variables['elements']['#legislation'];
  foreach (Element::children($variables['elements']) as $key) {
    $variables['legislation'][$key] = $variables['elements'][$key];
  }

}
