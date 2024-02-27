<?php

declare(strict_types=1);

namespace Drupal\openculturas_map\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\smart_date\Plugin\Field\FieldFormatter\SmartDateDefaultFormatter;

/**
 * Plugin implementation of the 'Default' formatter for 'smartdate' that only shows dates in the future.
 *
 * This formatter renders the time range using <time> elements, with
 * configurable date formats (from the list of configured formats) and a
 * separator.
 *
 * @FieldFormatter(
 *   id = "smartdate_default_upcoming",
 *   label = @Translation("Smart Date Formatter (Upcoming)"),
 *   field_types = {
 *     "smartdate",
 *   }
 * )
 */
final class SmartDateUpcomingFormatter extends SmartDateDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode, mixed $format = ''): array {
    // Get Elements from Default Formatter.
    $elements = parent::viewElements($items, $langcode, $format);

    // Remove Dates that are in the past.
    foreach ($elements as $index => $element) {
      if ($element['#value'] < time()) {
        unset($elements[$index]);
      }
    }

    return $elements;
  }

}
