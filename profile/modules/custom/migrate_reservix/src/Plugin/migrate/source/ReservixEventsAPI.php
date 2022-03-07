<?php

namespace Drupal\migrate_reservix\Plugin\migrate\source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Row;
use Drupal\migrate_reservix\ReservixApiClientInterface;

/**
 * Provides a flexible, generic import source for the Reservix API.
 *
 * @MigrateSource(
 *   id = "migrate_reservix_events_source",
 *   label = @Translation("Reservix Events API"),
 * )
 */
class ReservixEventsAPI extends ReservixBaseAPI {

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

    // There is no paragraphs lookup plugin for migrate, so we do it ourselves
    // until published.
    // @see https://git.drupalcode.org/project/paragraphs/-/blob/8.x-1.x/src/Plugin/migrate/process/ParagraphsLookup.php
    $paragraphs = [];
    $database = \Drupal::database();

    // Massage values for simplified usage in migrate plugins.
    $description = $row->getSourceProperty('description');
    $description = str_replace(['<br>', '<br/>', '<br />'], [' '], $description);
    $row->setSourceProperty('_description', $description);

    // Duration seconds to ISO 8601 duration.
    $duration_minutes = $row->getSourceProperty('duration');
    $row->setSourceProperty('_duration_seconds', $duration_minutes * 60);
    $row->setSourceProperty('_duration_duration', ReservixBaseAPI::timestampToIso8601Duration($duration_minutes * 60));

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

    $image_ids = [];
    foreach ($references['image'] as $image) {
      if ($image['type'] === ReservixApiClientInterface::IMAGE_SLIDESHOW) {
        $image_ids[] = $image['id'];
      }
    }

    // @todo This can probably be solved with a migrate_lookup plugin instead.
    $results = $database
      // @fixme This will break, when the migration identifier changes.
      ->select('migrate_map_entity_import__reservix_images__image', 'mm')
      ->fields('mm', ['destid1'])
      ->condition('mm.sourceid1', reset($image_ids), '=')
      ->execute()
      ->fetchAll();
    if (!empty($results)) {
      foreach ($results as $result) {
        $row->setSourceProperty('_image_target_id', $result->destid1);
      }
    }

    if (count($image_ids)) {
      $image_target_ids = [];

      // @todo This can probably be solved with a migrate_lookup plugin instead.
      $results = $database
        // @fixme This will break, when the migration identifier changes.
        ->select('migrate_map_entity_import__reservix_images__image', 'mm')
        ->fields('mm', ['destid1'])
        ->condition('mm.sourceid1', (array) $image_ids, 'IN')
        ->execute()
        ->fetchAll();
      if (!empty($results)) {
        foreach ($results as $result) {
          $image_target_ids[] = $result->destid1;
        }
        $row->setSourceProperty('_image_target_ids', $image_target_ids);
      }
    }

    return TRUE;
  }

  /**
   * Timestamp to duration.
   *
   * @param int $seconds
   *   The seconds of duration.
   *
   * @return string
   *   The ISO 8601 duration string.
   */
  private static function timestampToIso8601Duration(int $seconds): string {
    $units = [
      'Y' => 365 * 24 * 3600,
      'D' => 24 * 3600,
      'H' => 3600,
      'M' => 60,
      'S' => 1,
    ];

    $str = "P";
    $is_time = FALSE;

    foreach ($units as $unitName => &$unit) {
      $quot = intval($seconds / $unit);
      $seconds -= $quot * $unit;
      $unit = $quot;
      if ($unit > 0) {
        if (
          !$is_time
          && in_array($unitName, ["H", "M", "S"])
        ) {
          $str .= "T";
          $is_time = TRUE;
        }
        $str .= strval($unit) . $unitName;
      }
    }

    if ($str === "P") {
      $str = "P0D";
    }
    return $str;
  }

}
