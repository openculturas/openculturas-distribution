<?php

declare(strict_types=1);

namespace Drupal\openculturas_address_links\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Url;
use Drupal\address\AddressInterface;
use Drupal\geofield\Plugin\Field\FieldType\GeofieldItem;

/**
 * Plugin implementation of the 'directions' formatter.
 *
 * @FieldFormatter(
 *   id = "directions",
 *   label = @Translation("Directions Link"),
 *   field_types = {
 *     "address",
 *     "geofield",
 *   },
 * )
 */
final class DirectionsFormatter extends AddressLinkFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected static string $purpose = 'directions';

  /**
   * {@inheritdoc}
   */
  protected function viewElement(FieldItemInterface $item, string $langcode): ?array {
    if ($item instanceof AddressInterface) {
      return $this->viewAddress($item);
    }

    if ($item instanceof GeofieldItem) {
      return $this->viewGeofield($item);
    }

    return NULL;
  }

  /**
   * Renders a single geofield item.
   */
  protected function viewGeofield(GeofieldItem $item): ?array {
    if (($url = $this->addressService->buildUrlFromGeofield($item, self::$purpose)) instanceof Url) {
      return $this->buildLinkItem($item, $url);
    }

    return NULL;
  }

}
