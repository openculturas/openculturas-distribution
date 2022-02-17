<?php

namespace Drupal\openculturas_custom;

/**
 * Helper class for getting the current entity regardless of entity type.
 */
class CurrentEntityHelper {

  // Gets current page entity regardless of entity type.
  public static function get_current_page_entity() {
    $page_entity = &drupal_static(__FUNCTION__, NULL);
    if (!empty($page_entity)) {
      return $page_entity;
    }
    $types = array_keys(\Drupal::entityTypeManager()->getDefinitions());
    $route = \Drupal::routeMatch();
    $params = $route->getParameters()->all();
    foreach ($types as $type) {
      if (!empty($params[$type])) {
        return $params[$type];
      }
    }
    return NULL;
  }
}
