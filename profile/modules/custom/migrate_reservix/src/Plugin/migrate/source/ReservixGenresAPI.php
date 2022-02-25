<?php

namespace Drupal\migrate_reservix\Plugin\migrate\source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Row;

/**
 * Provides a flexible, generic import source for the Reservix API.
 *
 * @MigrateSource(
 *   id = "migrate_reservix_genres_source",
 *   label = @Translation("Reservix Genres API"),
 * )
 */
class ReservixGenresAPI extends ReservixBaseAPI {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['endpoint']['#value'] = ['sale/genre'];
    $form['endpoint']['#attributes']['disabled'] = 'disabled';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    // Always include this fragment at the beginning of every prepareRow()
    // implementation, so parent classes can ignore rows.
    if (parent::prepareRow($row) === FALSE) {
      return FALSE;
    }
    return TRUE;
  }

}
