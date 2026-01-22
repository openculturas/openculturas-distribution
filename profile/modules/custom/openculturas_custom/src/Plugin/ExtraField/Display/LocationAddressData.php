<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\extra_field\Attribute\ExtraFieldDisplay;
use Drupal\node\NodeInterface;
use function is_array;

/**
 * Field field_address_data from field_location reference.
 */
#[ExtraFieldDisplay(
  id: "location_address_data",
  label: new TranslatableMarkup("Address data"),
  description: new TranslatableMarkup("Field field_address_data from field_location reference"),
  bundles: [
    "node.date",
  ],
  visible: FALSE,
)]
final class LocationAddressData extends ExtraFieldBase {

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
    return 'field_location';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNameInEntityReference(): string {
    return 'field_address_data';
  }

}
