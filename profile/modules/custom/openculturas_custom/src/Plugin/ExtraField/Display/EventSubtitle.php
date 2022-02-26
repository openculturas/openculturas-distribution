<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Subtitle field from field_event_description.
 *
 * @ExtraFieldDisplay(
 *   id = "event_subtitle",
 *   label = @Translation("Subtitle"),
 *   description = "Subtitle field via field_event_description reference",
 *   visible = false,
 *   bundles = {
 *     "node.date",
 *   }
 * )
 */
class EventSubtitle extends ExtraFieldBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    $this->setFieldname('field_subtitle');
    $this->setReferenceField('field_event_description');
    $build = parent::viewElements($entity);
    if ($build !== []) {
      $renderArray = $this->eventEntity->get('field_subtitle')->view(['label' => 'hidden']);
      $build['#markup'] = $this->renderer->render($renderArray);
    }

    return $build;
  }

}
