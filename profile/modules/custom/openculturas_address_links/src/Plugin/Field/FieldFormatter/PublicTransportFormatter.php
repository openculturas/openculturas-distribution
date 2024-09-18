<?php

namespace Drupal\openculturas_address_links\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\address\AddressInterface;

/**
 * Plugin implementation of the 'public_transport' formatter.
 *
 * @FieldFormatter(
 *   id = "public_transport",
 *   label = @Translation("Public transport link"),
 *   field_types = {
 *     "address",
 *   },
 * )
 */
final class PublicTransportFormatter extends AddressLinkFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected static string $purpose = 'public_transport';

  /**
   * {@inheritdoc}
   */
  protected function viewElement(FieldItemInterface $item, string $langcode): ?array {
    if (!$item instanceof AddressInterface) {
      return NULL;
    }

    return $this->viewAddress($item);
  }

}
