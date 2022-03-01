<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * field_address_data from field_location reference.
 *
 * @ExtraFieldDisplay(
 *   id = "location_address_data",
 *   label = @Translation("Address data"),
 *   description = "field_address_data from field_location reference",
 *   visible = false,
 *   bundles = {
 *     "node.date",
 *   }
 * )
 */
final class LocationAddressData extends ExtraFieldBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(ContentEntityInterface $entity) {
    $this->setFieldname('field_address_data');
    $this->setReferenceField('field_location');
    $build = parent::viewElements($entity);
    if ($build !== []) {
      $display_options = ['type' => 'entity_reference_entity_view', 'viewmode' => 'address_line', 'label' => 'hidden'];
      $renderArray = $this->eventEntity->get('field_address_data')->view($display_options);
      $build['#markup'] = $this->renderer->render($renderArray);
    }
    return $build;
  }

}
