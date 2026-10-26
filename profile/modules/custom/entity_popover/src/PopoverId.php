<?php

declare(strict_types=1);

namespace Drupal\entity_popover;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds deterministic ids for entity popover dialogs.
 */
final class PopoverId {

  /**
   * Returns the dialog id for one popover-enabled field rendering.
   *
   * Html::getUniqueId() must not be used for these ids: it only numbers
   * ids within the current request, while the id of a cached render array
   * is stored together with the cache entry. A render cache hit replays
   * the stored id, but the freshly built markup — which still calls
   * getUniqueId() before the cache hit discards it — consumes the next
   * number, so the same id is emitted twice within one response. A
   * deterministic id repeats only when the same entity, field, view mode
   * and language render twice. Both are properties of the rendering, not
   * of the dialog content: the view mode the dialog content itself is
   * rendered in is a formatter setting of that rendering, so the same
   * view mode also implies the same dialog content, and sharing one id is
   * correct.
   *
   * @param string $entityTypeId
   *   The entity type id of the entity the dialog previews.
   * @param string $entityId
   *   The identifier of the entity the dialog previews, as returned by
   *   ::entityIdentifier().
   * @param string $fieldName
   *   The name of the popover-enabled field.
   * @param string $viewMode
   *   The view mode of the rendering that contains the popover-enabled
   *   field, not the view mode of the dialog content.
   * @param string $langcode
   *   The language code of the rendering that contains the
   *   popover-enabled field.
   *
   * @return string
   *   The id, for example "entity-popover-node-40-field-gallery-full-en",
   *   with "full" being the view mode of the rendering.
   *   Html::getId() lowercases the parts and collapses runs of separators
   *   to a single hyphen, so the double hyphen of the input disappears.
   */
  public static function build(string $entityTypeId, string $entityId, string $fieldName, string $viewMode, string $langcode): string {
    return Html::getId('entity-popover--' . $entityTypeId . '-' . $entityId . '-' . $fieldName . '-' . $viewMode . '-' . $langcode);
  }

  /**
   * Returns the identifier of an entity for dialog ids and recursion checks.
   *
   * An unsaved entity has no id yet, so every unsaved entity of one type
   * would share the same dialog id and recursion guard key, for example two
   * paragraphs added in a node preview. Content entities get their UUID on
   * creation and a clone keeps it, so the UUID tells unsaved entities apart.
   * Saved entities keep their id, so existing dialog ids stay unchanged. The
   * UUID never reaches the render cache: core only adds cache keys for
   * entities that are not new (EntityViewBuilder::getBuildDefaults()).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the dialog previews.
   *
   * @return string
   *   The entity id, or the UUID while the entity has no id.
   */
  public static function entityIdentifier(EntityInterface $entity): string {
    $id = $entity->id();

    return $id !== NULL ? (string) $id : (string) $entity->uuid();
  }

}
