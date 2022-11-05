<?php

declare(strict_types=1);

namespace Drupal\geofield_proximity_filter_extra\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Combine;

/**
 * Filter handler which allows to search on multiple fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("geofield_proximity_filter_extra_combine")
 */
final class GeocodeOriginCombine extends Combine {

  /**
   * {@inheritdoc}
   */
  public function canExpose(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // Without a value, do not query.
    if (empty($this->value)) {
      return;
    }
    parent::query();
  }


}
