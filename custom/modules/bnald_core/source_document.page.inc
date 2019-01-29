<?php

/**
 * @file
 * Contains source_document.page.inc.
 *
 * Page callback for Source Document entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Source Document templates.
 *
 * Default template: source_document.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_source_document(array &$variables) {
  // Fetch SourceDocument Entity Object.
  $source_document = $variables['elements']['#source_document'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
