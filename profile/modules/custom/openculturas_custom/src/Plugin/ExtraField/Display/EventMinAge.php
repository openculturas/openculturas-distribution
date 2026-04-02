<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;
use function is_array;

/**
 * phpcs:ignore
 * field_min_age via field_event_description reference.
 *
 * @ExtraFieldDisplay(
 *   id = "event_min_age",
 *   label = @Translation("Minimum age"),
 *   description = "field_min_age via field_event_description reference",
 *   visible = false,
 *   bundles = {
 *     "node.date",
 *   }
 * )
 */
final class EventMinAge extends ExtraFieldBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity): array {
    $build = parent::viewElements($entity);
    if ($build !== [] && $this->eventEntity instanceof NodeInterface && is_array($this->referenceViewFormatterSettings)) {
      $renderArray = $this->eventEntity->get($this->getFieldNameInEntityReference())->view($this->referenceViewFormatterSettings);
      $build['#markup'] = $this->renderer->render($renderArray);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getInheritEntityReferenceFieldName(): string {
    return 'field_event_description';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNameInEntityReference(): string {
    return 'field_min_age';
  }

}
