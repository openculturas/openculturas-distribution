<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\geofield\Plugin\views\filter\GeofieldProximityFilter;
use Drupal\leaflet_views\Plugin\views\style\LeafletMap;
use Drupal\openculturas_custom\GetConfiguredMapMaker;
use Drupal\views\Plugin\views\ViewsHandlerInterface;
use Drupal\views\Render\ViewsRenderPipelineMarkup;

/**
 * Leaflet/OpenStreetMap hook implementations for openculturas_custom.
 */
class OpenculturasCustomLeafletHooks {
  use StringTranslationTrait;

  /**
   * Constructs a new OpenculturasCustomLeafletHooks.
   *
   * @param \Drupal\Core\Asset\LibrariesDirectoryFileFinder $librariesDirectoryFileFinder
   *   The libraries directory file finder.
   */
  public function __construct(protected LibrariesDirectoryFileFinder $librariesDirectoryFileFinder) {
  }

  /**
   * Implements hook_preprocess_HOOK() for leaflet-map.html.twig.
   */
  #[Hook('preprocess_leaflet_map')]
  public function preprocessLeafletMap(array &$variables): void {
    $variables['#attached']['library'][] = 'openculturas_custom/leaflet_extra';
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(array &$libraries, string $extension): void {
    if ($extension === 'leaflet_markercluster') {
      $library = 'leaflet-markercluster';
      if (isset($libraries[$library])) {
        if ($path = $this->librariesDirectoryFileFinder->find('leaflet.markercluster/dist/MarkerCluster.css')) {
          $libraries[$library]['css']['component'] = [
            '/' . $path => [],
          ];
          unset($libraries[$library]['css']['component']['js/leaflet_markercluster/dist/MarkerCluster.css']);
        }

        if ($path = $this->librariesDirectoryFileFinder->find('leaflet.markercluster/dist/MarkerCluster.Default.css')) {
          $libraries[$library]['css']['component']['/' . $path] = [];
          unset($libraries[$library]['css']['component']['js/leaflet_markercluster/dist/MarkerCluster.Default.css']);
        }

        if ($path = $this->librariesDirectoryFileFinder->find('leaflet.markercluster/dist/leaflet.markercluster.js')) {
          $libraries[$library]['js']['/' . $path] = [
            'minified' => TRUE,
          ];
          unset($libraries[$library]['js']['js/leaflet_markercluster/dist/leaflet.markercluster.js']);
        }
      }
    }
    elseif ($extension === 'leaflet') {
      $library = 'leaflet';
      if (isset($libraries[$library])) {
        $libraries[$library]['js']['js/leaflet/dist/leaflet.js']['minified'] = TRUE;
      }

      $library = 'leaflet-geoman';
      if (isset($libraries[$library])) {
        $libraries[$library]['js']['js/leaflet-geoman-free/dist/leaflet-geoman.min.js']['minified'] = TRUE;
      }

      $library = 'leaflet.fullscreen';
      if (isset($libraries[$library])) {
        if ($path = $this->librariesDirectoryFileFinder->find('leaflet.fullscreen/dist/leaflet.fullscreen.css')) {
          $libraries[$library]['css']['component'] = [
            '/' . $path => [],
          ];
        }

        if ($path = $this->librariesDirectoryFileFinder->find('leaflet.fullscreen/dist/Leaflet.fullscreen.min.js')) {
          $libraries[$library]['js'] = [
            '/' . $path => [
              'minified' => TRUE,
            ],
          ];
        }
      }

      $library = 'leaflet.gesture_handling';
      if (isset($libraries[$library])) {
        if ($path = $this->librariesDirectoryFileFinder->find('leaflet.gesture-handling/dist/leaflet-gesture-handling.min.css')) {
          $libraries[$library]['css']['component'] = [
            '/' . $path => [
              'minified' => TRUE,
            ],
          ];
        }

        if ($path = $this->librariesDirectoryFileFinder->find('leaflet.gesture-handling/dist/leaflet-gesture-handling.min.js')) {
          $libraries[$library]['js'] = [
            '/' . $path => [
              'minified' => TRUE,
            ],
          ];
        }
      }
    }
    elseif ($extension === 'social_media_links') {
      $library = 'fontawesome.component';
      if (isset($libraries[$library]) && $path = $this->librariesDirectoryFileFinder->find('font-awesome/css/all.min.css')) {
        $libraries[$library]['css']['component'] = [
          '/' . $path => [
            'minified' => TRUE,
          ],
        ];
      }
    }
  }

  /**
   * Implements hook_leaflet_map_view_style_alter().
   */
  #[Hook('leaflet_map_view_style_alter')]
  public function leafletMapViewStyleAlter(array &$map_settings, LeafletMap $view_style): void {
    $filters = $view_style->displayHandler->getHandlers('filter');
    /** @var \Drupal\geofield\Plugin\views\filter\GeofieldProximityFilter[] $proximityFilters */
    $proximityFilters = array_filter($filters, static fn (ViewsHandlerInterface $filter): bool => $filter instanceof GeofieldProximityFilter);
    if ($proximityFilters !== []) {
      $proximityFilter = reset($proximityFilters);
      try {
        $property = new \ReflectionProperty($proximityFilter, 'sourcePlugin');
      }
      catch (\Throwable) {
        return;
      }

      /** @var \Drupal\geofield\Plugin\GeofieldProximitySourceInterface $source_plugin */
      $source_plugin = $property->getValue($proximityFilter);
      /** @var array $value */
      $value = $proximityFilter->value;
      $origin = $source_plugin->getOrigin();
      if (!empty($origin['lat']) && !empty($origin['lon'])) {
        $origin_marker = $origin;
        $origin_marker['type'] = 'point';
        $origin_marker['label'] = $this->t('Origin');
        $origin_marker['popup'] = ViewsRenderPipelineMarkup::create($this->t('Origin'));
        $origin_marker['icon'] = [
          'iconType' => 'circle_marker',
          'options' => '{"radius":9,"color":"#fff","fillColor":"#2A93EE","fillOpacity":1,"opacity":1}',
        ];
        $map_settings['features'][] = $origin_marker;
        $map_settings['map']['settings']['center'] = array_merge((array) ($map_settings['map']['settings']['center'] ?? []), $origin);
        $map_settings['map']['settings']['radius'] = $value['value'] ?? NULL;
        $map_settings['map']['settings']['map_position_force'] = TRUE;
      }
    }
  }

  /**
   * Implements hook_leaflet_map_info_alter().
   */
  #[Hook('leaflet_map_info_alter')]
  public function leafletMapInfoAlter(array &$map_info): void {
    // A field formatter can override this.
    // See web/modules/contrib/leaflet/src/Plugin/Field/FieldFormatter/LeafletDefaultFormatter.php:363.
    $map_ids = [
      'openstreetmap',
      'OSM Mapnik',
    ];
    // OSM Mapnik was switch to openstreetmap in release 10.4.6.
    // In case this will be reverted in the future, we just check both.
    foreach ($map_ids as $map_id) {
      if (isset($map_info[$map_id])) {
        $map_info[$map_id]['icon']['iconUrl'] = GetConfiguredMapMaker::getPath();
      }
    }
  }

}
