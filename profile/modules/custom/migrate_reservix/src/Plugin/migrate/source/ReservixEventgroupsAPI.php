<?php

namespace Drupal\migrate_reservix\Plugin\migrate\source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Row;

/**
 * Provides a flexible, generic import source for the Reservix API.
 *
 * @MigrateSource(
 *   id = "migrate_reservix_eventgroups_source",
 *   label = @Translation("Reservix Eventgroups API"),
 * )
 */
class ReservixEventgroupsAPI extends ReservixBaseAPI {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['endpoint']['#value'] = ['sale/eventgroup'];
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

    // Add additional data to the event that is part of an event
    // instance on Reservix.

    /** @var \Drupal\migrate_reservix\ReservixApiClient $service */
    $service = \Drupal::service('migrate_reservix.client');
    $events = $service->getEvents(['eventgroupId' => $row->getSourceProperty('id')]);

    if ($event = reset($events['data'])) {
      $row->setSourceProperty('_name', $event['name']);
      $row->setSourceProperty('_description', $event['description']);
      $duration_minutes = $row->getSourceProperty('duration');
      $row->setSourceProperty('_duration_seconds', $duration_minutes * 60);
      $row->setSourceProperty('_duration_iso_8601', ReservixBaseAPI::timestampToIso8601Duration($duration_minutes * 60));
      $row->setSourceProperty('_artist', $event['artist']);
      $row->setSourceProperty('_affiliateSaleUrl', $event['affiliateSaleUrl']);
      $row->setSourceProperty('_', $event['']);
      $row->setSourceProperty('_', $event['']);
      $row->setSourceProperty('_', $event['']);
      $row->setSourceProperty('_', $event['']);
    }

    return TRUE;
  }

}
