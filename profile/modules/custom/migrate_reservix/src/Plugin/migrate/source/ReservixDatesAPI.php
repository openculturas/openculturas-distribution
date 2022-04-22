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

    if ($artist = $row->getSourceProperty('artist')) {
      $artist = '<p><strong>' . $artist . '</strong></p>';
    }
    $row->setSourceProperty('_artist', $artist);

    $title = $row->getSourceProperty('name') . ' ' . $row->getSourceProperty('startdate');
    $row->setSourceProperty('_title', $title);

    $database = \Drupal::database();

    // @todo This can probably be solved with a migrate_lookup plugin instead.
    $results = $database
      // @fixme This will break, when the migration identifier changes.
      ->select('migrate_map_entity_import__reservix_event__event', 'mm')
      ->fields('mm', ['destid1'])
      ->condition('mm.sourceid1', $row->getSourceProperty('id'), '=')
      ->execute()
      ->fetchAll();
    if (!empty($results)) {
      foreach ($results as $result) {
        $row->setSourceProperty('_event_target_id', $result->destid1);
      }
    }

    $references = $row->getSourceProperty('references');
    if (!$references) {
      return TRUE;
    }

    foreach (array_keys($references) as $property_name) {
      if (!isset($references[$property_name])
        || !is_array($references[$property_name])) {
        break;
      }

      // Flatten the references for better accessibility in migration mapping.
      $i = 0;
      foreach ($references[$property_name] as $reference) {
        foreach ($reference as $field_name => $field_value) {
          // This results into keys like e.g. "_genre_0_id", "_genre_0_name" ...
          try {
            $row->setSourceProperty(
              '_' . $property_name . '_' . $i . '_' . $field_name,
              $field_value
            );
          }
          catch (\Exception $e) {
            \Drupal::logger('migrate_reservix')
              ->debug('Could not change source property: @message', [
                '@message' => $e->getMessage(),
              ]);
          }
        }
        $i++;
      }
    }

    // @todo This can probably be solved with a migrate_lookup plugin instead.
    $results = $database
      // @fixme This will break, when the migration identifier changes.
      ->select('migrate_map_entity_import__reservix_organizer__profile', 'mm')
      ->fields('mm', ['destid1'])
      ->condition('mm.sourceid1', reset($references['organizer'])['id'], '=')
      ->execute()
      ->fetchAll();
    if (!empty($results)) {
      foreach ($results as $result) {
        $row->setSourceProperty('_organizer_target_id', $result->destid1);
      }
    }

    // @todo This can probably be solved with a migrate_lookup plugin instead.
    $results = $database
      // @fixme This will break, when the migration identifier changes.
      ->select('migrate_map_entity_import__reservix_location__location', 'mm')
      ->fields('mm', ['destid1'])
      ->condition('mm.sourceid1', reset($references['venue'])['id'], '=')
      ->execute()
      ->fetchAll();
    if (!empty($results)) {
      foreach ($results as $result) {
        $row->setSourceProperty('_location_target_id', $result->destid1);
      }
    }

    return TRUE;
  }

}
