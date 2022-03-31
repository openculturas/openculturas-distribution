<?php

declare(strict_types=1);

namespace Drupal\geofield_proximity_filter_extra\Plugin\views\filter;

/**
 * Filter handler which allows to search on multiple fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("geofield_proximity_filter_extra_combine")
 */
class GeocodeOriginCombine extends \Drupal\views\Plugin\views\filter\Combine {

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Without a value, do not query.
    if (empty($this->value)) {
      return;
    }
    parent::query();
  }


}
