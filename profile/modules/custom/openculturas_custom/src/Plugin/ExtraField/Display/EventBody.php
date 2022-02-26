<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Body field via field_event_description reference.
 *
 * @ExtraFieldDisplay(
 *   id = "event_body",
 *   label = @Translation("Body"),
 *   description = "Body field via field_event_description reference",
 *   visible = FALSE,
 *   bundles = {
 *     "node.date",
 *   }
 * )
 */
class EventBody extends ExtraFieldBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    $this->setFieldname('body');
    $this->setReferenceField('field_event_description');
    $build = parent::viewElements($entity);
    if ($build !== []) {
      $renderArray = $this->eventEntity->get('body')->view(['label' => 'hidden']);
      $build['#markup'] = $this->renderer->render($renderArray);
    }

    return $build;
  }

}
