<?php

namespace Drupal\migrate_reservix\Plugin\migrate\source;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Row;

/**
 * Provides a flexible, generic import source for the Reservix API.
 *
 * @MigrateSource(
 *   id = "migrate_reservix_images_slideshow_source",
 *   label = @Translation("Reservix Images Slideshow API"),
 * )
 */
class ReservixImagesSlideshowAPI extends ReservixBaseAPI {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['endpoint']['#value'] = ['custom/imagesslideshow'];
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

    /** @var \Drupal\Core\File\FileSystem $file_system */
    $file_system = \Drupal::service('file_system');

    $folder = 'public://images-event/';
    if (!file_exists($folder)) {
      $file_system->mkdir($folder);
    }

    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties([
        'uri' => $folder . basename($row->getSourceProperty('url')),
      ]);

    /** @var \Drupal\file\Entity\File $file */
    if (!count($files)) {
      $file_data = file_get_contents($row->getSourceProperty('url'));
      $file = file_save_data($file_data, $folder . basename($row->getSourceProperty('url'), FileSystemInterface::EXISTS_REPLACE));
    }
    else {
      $file = reset($files);
    }
    $row->setSourceProperty('_image_file', $file);

    return TRUE;
  }

}
