<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\node\NodeInterface;
use function assert;

/**
 * Helper class for getting the current entity regardless of entity type.
 */
final class CurrentEntityHelper {

  /**
   * Gets current page entity regardless of entity type.
   * // phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
   */
  public static function get_current_page_entity(): ?ContentEntityInterface {
    // phpcs:enable.
    $page_entity = &drupal_static(__FUNCTION__);
    if (!empty($page_entity)) {
      return $page_entity;
    }

    $types = array_keys(\Drupal::entityTypeManager()->getDefinitions());
    $route = \Drupal::routeMatch();
    $params = $route->getParameters()->all();
    foreach ($types as $type) {
      if (empty($params[$type])) {
        continue;
      }

      if (!$params[$type] instanceof ContentEntityInterface) {
        continue;
      }

      return $params[$type];
    }

    return NULL;
  }

  /**
   * The date bundle takes the values from the referenced event bundle entity.
   *
   * @see \Drupal\openculturas_custom\Plugin\Block\HeroImageBlock::build()
   * @see \Drupal\openculturas_custom\Plugin\Block\PageTitleBlock::build()
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public static function getEventReference(?ContentEntityInterface $entity): ?ContentEntityInterface {
    if (!$entity instanceof ContentEntityInterface) {
      return $entity;
    }

    if ($entity->bundle() !== 'date') {
      return $entity;
    }

    if (!$entity->hasField('field_event_description')) {
      return $entity;
    }

    if ($entity->get('field_event_description')->isEmpty()) {
      return $entity;
    }

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $event_list */
    $event_list = $entity->get('field_event_description');
    $event_item = $event_list->first();
    if (!$event_item instanceof EntityReferenceItem) {
      return $entity;
    }

    assert($event_item->entity instanceof NodeInterface);
    return $event_item->entity ?? NULL;
  }

}
