<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * field_category field via field_event_description reference.
 *
 * @ExtraFieldDisplay(
 *   id = "event_category",
 *   label = @Translation("Category"),
 *   description = "field_category field via field_event_description reference",
 *   visible = false,
 *   bundles = {
 *     "node.date",
 *   }
 * )
 */
class EventCategory extends ExtraFieldBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    $this->fieldname = 'field_category';
    $build = parent::viewElements($entity);
    if ($build !== []) {
      $renderArray = $this->eventEntity->get($this->fieldname)->view(['label' => 'hidden']);
      $build['#markup'] = $this->renderer->render($renderArray);
    }

    return $build;
  }

}
