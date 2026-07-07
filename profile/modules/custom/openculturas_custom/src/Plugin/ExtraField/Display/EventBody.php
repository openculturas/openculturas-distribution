<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\extra_field\Attribute\ExtraFieldDisplay;
use Drupal\node\NodeInterface;

/**
 * Body field via field_event_description reference.
 */
#[ExtraFieldDisplay(
  id: "event_body",
  label: new TranslatableMarkup("Body"),
  description: new TranslatableMarkup("Body field via field_event_description reference"),
  bundles: [
    "node.date",
  ],
  visible: FALSE
)]
final class EventBody extends ExtraFieldBase {

  /**
   * {@inheritdoc}
   */
  #[\Override]
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
