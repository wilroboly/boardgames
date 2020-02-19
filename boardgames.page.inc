<?php

/**
 * @file
 * Contains boardgame.page.inc.
 *
 * Page callback for Boardgame entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Boardgame templates.
 *
 * Default template: boardgame.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_boardgame(array &$variables) {
  // Fetch Boardgame Entity Object.
  $boardgame = $variables['elements']['#boardgame'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
