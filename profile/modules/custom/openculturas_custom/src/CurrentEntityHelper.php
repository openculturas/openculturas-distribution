<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Helper class for getting the current entity regardless of entity type.
 */
class CurrentEntityHelper {

  /**
   * Gets current page entity regardless of entity type.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   */
  public static function get_current_page_entity() {
    $page_entity = &drupal_static(__FUNCTION__);
    if (!empty($page_entity)) {
      return $page_entity;
    }
    $types = array_keys(\Drupal::entityTypeManager()->getDefinitions());
    $route = \Drupal::routeMatch();
    $params = $route->getParameters()->all();
    foreach ($types as $type) {
      if (!empty($params[$type]) && $params[$type] instanceof ContentEntityInterface) {
        return $params[$type];
      }
    }
    return NULL;
  }

  /**
   * The date bundle takes the values from the referenced event bundle entity.
   *
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $entity
   *
   * @see \Drupal\openculturas_custom\Plugin\Block\HeroImageBlock::build()
   * @see \Drupal\openculturas_custom\Plugin\Block\PageTitleBlock::build()
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public static function getEventReference(?ContentEntityInterface $entity): ?ContentEntityInterface {
    if ($entity !== NULL && $entity->bundle() === 'date'
      && $entity->hasField('field_event_description')
      && !$entity->get('field_event_description')->isEmpty()) {
      return $entity->get('field_event_description')->first()->entity;
    }
    return $entity;
  }

}
