<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\extra_field\Attribute\ExtraFieldDisplay;
use Drupal\node\NodeInterface;
use function is_array;

/**
 * Field field_press_quotes via field_event_description reference.
 */
#[ExtraFieldDisplay(
  id: "event_press_quotes",
  label: new TranslatableMarkup("Press quotes"),
  description: new TranslatableMarkup("field_press_quotes via field_event_description reference"),
  bundles: [
    "node.date",
  ],
  visible: FALSE,
)]
final class EventPressQuotes extends ExtraFieldBase {

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
    return 'field_press_quotes';
  }

}
