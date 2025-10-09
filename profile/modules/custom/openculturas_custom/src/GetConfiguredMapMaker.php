<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

use Drupal\Core\StreamWrapper\StreamWrapperManager;
use function file_exists;
use function is_string;
use function trim;

class GetConfiguredMapMaker {

  public static function getPath(): string {
    if (\Drupal::service('module_handler')->moduleExists('openculturas_map')) {
      $config = \Drupal::config('openculturas_map.settings');
      $marker_icon_path = $config->get('marker_icon_path');
      $marker_icon_path = is_string($marker_icon_path) ? trim($marker_icon_path) : NULL;
      if ($marker_icon_path !== NULL && file_exists($marker_icon_path)) {
        if (StreamWrapperManager::getScheme($marker_icon_path)) {
          /** @var \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator */
          $fileUrlGenerator = \Drupal::service('file_url_generator');
          $marker_icon_path = $fileUrlGenerator->generateString($marker_icon_path);
        }
      }
      else {
        $marker_icon_path = \Drupal::service('extension.list.module')->getPath('openculturas_map') . '/assets/map_marker.svg';
      }
    }
    else {
      $marker_icon_path = \Drupal::service('extension.list.theme')->getPath('openculturas_base') . '/images/map_marker.svg';
    }

    return '/' . ltrim($marker_icon_path, '/');
  }

}
