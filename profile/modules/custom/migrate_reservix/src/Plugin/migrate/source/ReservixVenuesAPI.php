<?php

namespace Drupal\migrate_reservix\Plugin\migrate\source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Row;

/**
 * Provides a flexible, generic import source for the Reservix API.
 *
 * @MigrateSource(
 *   id = "migrate_reservix_venues_source",
 *   label = @Translation("Reservix Venues API"),
 * )
 */
class ReservixVenuesAPI extends ReservixBaseAPI {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['endpoint']['#value'] = ['sale/venue'];
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

    $results = $database
      // @fixme This will break, when the migration identifier changes.
      ->select('migrate_map_entity_import__reservix_address__address_data', 'mm')
      ->fields('mm', ['destid1'])
      ->condition('mm.sourceid1', $row->getSourceProperty('id'), '=')
      ->execute()
      ->fetchAll();
    if (!empty($results)) {
      foreach ($results as $result) {
        $paragraphs[] = [
          'target_id' => $result->destid1,
          'target_revision_id' => $result->destid1,
        ];
      }
    }
    $row->setSourceProperty('_paragraphs_address', $paragraphs);

    $row->setSourceProperty('_geo_wks_type', 'Point');
    $row->setSourceProperty('_geo_wks_value', 'POINT(' . $row->getSourceProperty('longitude') . ' ' . $row->getSourceProperty('latitude') . ')');

    return TRUE;
  }

}
