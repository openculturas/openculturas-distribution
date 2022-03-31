<?php

declare(strict_types=1);

namespace Drupal\geofield_proximity_filter_extra\Plugin\Geocoder\Formatter;

use Drupal\geocoder\Plugin\Geocoder\Formatter\FormatterBase;
use Geocoder\Model\Address;

/**
 * Provides a Formatted Address plugin.
 *
 * @GeocoderFormatter(
 *   id = "localitity_postalcode",
 *   name = "Postalcode and Locality"
 * )
 */
class FormattedAddress extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function format(Address $address) {
    $formatted_address = $this->formatter->format($address, '%z %L');
    // Clean the address, from double whitespaces, ending/starting commas, etc.
    $this->cleanFormattedAddress($formatted_address);
    return $formatted_address;
  }

}
