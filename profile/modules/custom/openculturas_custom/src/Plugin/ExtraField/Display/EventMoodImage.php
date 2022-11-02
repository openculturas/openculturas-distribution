<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * field_mood_image via field_event_description reference
 *
 * @ExtraFieldDisplay(
 *   id = "event_mood_image",
 *   label = "Main image",
 *   description = "field_mood_image via field_event_description reference",
 *   visible = FALSE,
 *   bundles = {
 *     "node.date",
 *   }
 * )
 */
final class EventMoodImage extends ExtraFieldBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    $this->setFieldname('field_mood_image');
    $this->setReferenceField('field_event_description');

    $build = parent::viewElements($entity);
    if ($build !== []) {
      $renderArray = $this->eventEntity->get('field_mood_image')->view($this->referenceViewFormatterSettings);
      $build['#markup'] = $this->renderer->render($renderArray);
    }

    return $build;
  }
}
