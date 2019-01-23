<?php

/**
 * @file
 * Contains piece_legislation.page.inc.
 *
 * Page callback for Piece of Legislation entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Piece of Legislation templates.
 *
 * Default template: piece_legislation.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_piece_legislation(array &$variables) {
  // Fetch PieceOfLegislation Entity Object.
  $piece_legislation = $variables['elements']['#piece_legislation'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
