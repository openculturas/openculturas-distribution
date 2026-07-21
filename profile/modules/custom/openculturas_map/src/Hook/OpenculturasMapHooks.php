<?php

declare(strict_types=1);

namespace Drupal\openculturas_map\Hook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\openculturas_custom\GetConfiguredMapMaker;

/**
 * Hook implementations for openculturas_map.
 */
class OpenculturasMapHooks {

  /**
   * Constructs a new OpenculturasMapHooks.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $keyValueExpirableFactory
   *   The expirable key value factory.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cache tags invalidator.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected KeyValueExpirableFactoryInterface $keyValueExpirableFactory,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
    protected TimeInterface $time,
  ) {
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'block__openculturas_map' => [
        'base hook' => 'block',
        'template' => 'block--openculturas-map',
      ],
    ];
  }

  /**
   * Implements hook_preprocess_HOOK() for block.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(array &$variables): void {
    if ($variables['elements']['#plugin_id'] === 'openculturas_map_block') {
      // Create Unique-Id and attach to configuration and drupalSettings.
      $id = Html::getUniqueId('openculturas_map');
      $variables['configuration']['identifier'] = $id;
      $variables['#attached']['drupalSettings']['openCulturasMap']['block'][$id] = $variables['configuration'];
      if (isset($variables['elements']['content']['filter']['form']['#pager']->options)) {
        $variables['#attached']['drupalSettings']['openCulturasMap']['block'][$id]['pager'] = $variables['elements']['content']['filter']['form']['#pager']->options;
        $variables['#attached']['drupalSettings']['openCulturasMap']['block'][$id]['pagerPluginId'] = $variables['elements']['content']['filter']['form']['#pager']->getPluginId();
      }

      if (isset($variables['elements']['content']['filter']['form']['#delta_limit'])) {
        $variables['#attached']['drupalSettings']['openCulturasMap']['global']['delta_limit'] = $variables['elements']['content']['filter']['form']['#delta_limit'];
      }

      $config = $this->configFactory->get('openculturas_map.settings');
      /** @var array<string,mixed> $configValues */
      $configValues = $config->get();
      foreach ($configValues as $configKey => $configValue) {
        if (str_starts_with($configKey, '_')) {
          continue;
        }

        if ($configKey === 'marker_icon_path') {
          $configValue = GetConfiguredMapMaker::getPath();
        }

        $variables['#attached']['drupalSettings']['openCulturasMap']['global'][$configKey] = $configValue;
      }

      $variables['#attached']['library'][] = 'openculturas_map/openculturas_map_block';
      $cache = new CacheableMetadata();
      $cache->addCacheableDependency($config);
      $cache->applyTo($variables);
    }
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $collection = $this->keyValueExpirableFactory->get('openculturas_map_cron');
    if ($collection->get('needs_cache_invalidation', TRUE) === FALSE) {
      return;
    }

    $this->cacheTagsInvalidator->invalidateTags([
      'config:views.view.oc_map_dates',
    ]);
    $current_timestamp = $this->time->getCurrentTime();
    $expire_timestamp = strtotime('tomorrow');
    if ($current_timestamp < $expire_timestamp) {
      $collection->setWithExpire('needs_cache_invalidation', FALSE, $expire_timestamp - $current_timestamp);
    }
  }

}
