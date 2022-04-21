<?php

namespace Drupal\migrate_kulturimkreis\Commands;

use Drupal\Core\File\FileSystemInterface;
use Drupal\media\Entity\Media;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class MigrateKulturimkreisCommands extends DrushCommands {

  /**
   * WP Image styles, ordered by size.
   */
  const SIZES = [
    '2048x2048',
    '1536x1536',
    'large',
    'medium_large',
    'medium',
    'thumbnail',
  ];

  const FILENAME = __DIR__ . '/../../assets/kul_Bildzuordnung-kulturimkreis_20211118-mst.csv';

  const BASE_URL = 'https://kulturimkreis.info/wp-content/uploads/';

  const SOURCE = 'Bilder für die Region';

  const SOURCE_LINK = 'https://kulturimkreis.info/mediennutzung';

  const AUTHOR_LINKS = [
    'Günter Jentsch' => 'https://kulturimkreis.info',
    'Guenter Jentsch' => 'https://kulturimkreis.info',
    'Ralf Koenig' => 'https://www.rk-fotodesign.de/',
  ];

  const LICENSE = 'cc_by_4_0';

  /**
   * Command description here.
   *
   * @usage migrate_kulturimkreis-import
   *   Import images from kulturimkreis.
   *
   * @command migrate_kulturimkreis:import
   */
  public function import() {
    /** @var \Drupal\Core\File\FileSystem $file_system */
    $file_system = \Drupal::service('file_system');

    $folder = 'public://kulturimkreis/';
    if (!file_exists($folder)) {
      $this->logger()->success(dt('Creating folder...'));
      $file_system->mkdir($folder);
    }

    $this->logger()->success(dt('Starting import...'));
    $filename = static::FILENAME;
    if (($handle = fopen($filename, 'r')) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        if (!isset($data[8])) {
          continue;
        }

        $data[8] = unserialize($data[8]);

        $row = [
          'filename' => $data[1],
          'title' => $data[2],
          'source' => static::SOURCE,
          'source_link' => static::SOURCE_LINK,
          'author' => '',
          'author_link' => '',
          'date' => $data[6],
          'url' => self::getOriginalImage($data),
        ];
        if (empty($row['url'])) {
          continue;
        }

        $files = \Drupal::entityTypeManager()
          ->getStorage('file')
          ->loadByProperties([
            'uri' => $folder . basename($row['url']),
          ]);

        /** @var \Drupal\file\Entity\File $file */
        if (!count($files)) {
          $this->logger()->success(dt('Loading file from URI ' . $row['url'] . '...'));
          $file_data = file_get_contents($row['url']);
          $file = file_save_data($file_data, $folder . basename($row['url'], FileSystemInterface::EXISTS_REPLACE));
        }
        else {
          $file = reset($files);
          $this->logger()->success(dt('File exists in file system ' . $file->getFileUri() . '...'));
        }

        if ($exif = exif_read_data($file_system->realpath($file->getFileUri()))) {
          if (isset($exif['Artist'])) {
            $row['author'] = trim(str_replace(['(C)'], [], $exif['Artist']));
            $row['author_link'] = static::AUTHOR_LINKS[$row['author']] ?? '';
          }

          if (isset($exif['DateTime'])) {
            $row['date'] = $exif['DateTime'];
          }
        }
        else {
          echo 'Could not read exif: ' . $row['url'], PHP_EOL;
        }

        $medias = \Drupal::entityTypeManager()
          ->getStorage('media')
          ->loadByProperties([
            'field_media_image' => $file->id(),
          ]);
        if (!count($medias)) {
          echo 'Create media entity', PHP_EOL;
          $media = Media::create([
            'bundle' => 'image',
            'field_media_image' => $file,
            'field_licenses' => [
              'author_name' => $row['author'],
              'author_link' => $row['author_link'],
              'source_name' => $row['source'],
              'source_link' => $row['source_link'],
              'license' => static::LICENSE,
            ],
          ])->save();
        }
        else {
          echo 'Do not create media entity', PHP_EOL;
        }
      }
      fclose($handle);
    }
    $this->logger()->success(dt('Achievement unlocked.'));
  }

  /**
   * Get the original image, if any.
   *
   * @param array $data
   *   The data set.
   *
   * @return string
   *   The image URL.
   */
  private static function getOriginalImage(array $data): string {
    if (!$data
      || !isset($data[8])
      || empty($data[8])
      || empty($data[8]['file'])) {
      return '';
    }

    if (isset($data[8]['file'])) {

      // Ignore the following files.
      if (strpos($data[8]['file'], 'HP-square-') !== FALSE
        || strpos($data[8]['file'], '/favicon.jpg') !== FALSE
        || strpos($data[8]['file'], '/cropped-Icons-1.jpg') !== FALSE
        || strpos($data[8]['file'], '/Icons-1.jpg') !== FALSE
        || strpos($data[8]['file'], '/Icons.jpg') !== FALSE
        || strpos($data[8]['file'], '/logodummy.jpg') !== FALSE
        || strpos($data[8]['file'], '/aaa-dieregion-komplett.jpg') !== FALSE
        || strpos($data[8]['file'], '/image.png') !== FALSE
        || strpos($data[8]['file'], '/LEADER-GoettingerLand-scaled.gif') !== FALSE
        || strpos($data[8]['file'], '/logos-LKGoe.jpg') !== FALSE
        || strpos($data[8]['file'], '/logos-PFEIL.jpg') !== FALSE
        || strpos($data[8]['file'], '/SPK-Logo-Triple.jpg') !== FALSE
        || strpos($data[8]['file'], '/logos-europaunionLWS.jpg') !== FALSE
        || strpos($data[8]['file'], '/logos-heidestock.jpg') !== FALSE
        || strpos($data[8]['file'], '/logos-ihf.jpg') !== FALSE
        || strpos($data[8]['file'], '/logos-europaNDS.jpg') !== FALSE
        || strpos($data[8]['file'], '/HP-dieregion-gesamt.jpg') !== FALSE
      ) {
        return '';
      }

      return self::BASE_URL . str_replace('%2F', '/', urlencode($data[8]['file']));
    }
    return '';
  }

}
