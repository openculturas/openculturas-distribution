<?php

declare(strict_types = 1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\node\NodeInterface;

/**
 * phpcs:ignore
 * body field via field_event_description reference.
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
final class EventBody extends ExtraFieldBase {

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
    return 'body';
  }

}
