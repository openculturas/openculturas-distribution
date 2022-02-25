<?php

namespace Drupal\migrate_reservix\Plugin\migrate\source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Row;

/**
 * Provides a flexible, generic import source for the Reservix API.
 *
 * @MigrateSource(
 *   id = "migrate_reservix_dates_source",
 *   label = @Translation("Reservix Dates API"),
 * )
 */
class ReservixDatesAPI extends ReservixBaseAPI {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['endpoint']['#value'] = ['sale/event'];
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

    $source_start_date = $row->getSourceProperty('startdate');
    $source_start_time = $row->getSourceProperty('starttime');
    $source_end_date = $row->getSourceProperty('enddate') ?? $source_start_date;
    $source_end_time = $row->getSourceProperty('endtime') ?? $source_start_time;

    $start = \DateTime::createFromFormat('Y-m-d H:m', $source_start_date . ' ' . $source_start_time, new \DateTimeZone('Europe/Berlin'));
    $row->setSourceProperty('_start_datetime', $start->format('U'));
    $end = \DateTime::createFromFormat('Y-m-d H:m', $source_end_date . ' ' . $source_end_time, new \DateTimeZone('Europe/Berlin'));
    $row->setSourceProperty('_end_datetime', $end->format('U'));

    return TRUE;
  }

}
