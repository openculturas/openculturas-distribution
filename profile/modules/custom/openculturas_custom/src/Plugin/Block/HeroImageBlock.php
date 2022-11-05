<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\openculturas_custom\CurrentEntityHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a hero image from current entity block.
 *
 * @Block(
 *   id = "openculturas_custom_hero_image",
 *   admin_label = @Translation("Hero image from current entity (from field_mood_image)"),
 *   category = @Translation("Openculturas")
 * )
 */
final class HeroImageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): HeroImageBlock {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];
    $page_entity = CurrentEntityHelper::get_current_page_entity();
    $current_entity = CurrentEntityHelper::getEventReference($page_entity);
    if ($current_entity !== NULL && !$current_entity->hasField('field_mood_image')) {
      return $build;
    }
    if ($current_entity !== NULL
      && $current_entity->hasField('field_mood_image')
      && !$current_entity->get('field_mood_image')->isEmpty()) {
      $display_options = [
        'type' => 'entity_reference_entity_view',
        'label' => 'hidden',
        'settings' => [
          'view_mode' => 'header_image',
        ],
      ];
      $build = $current_entity->get('field_mood_image')->view($display_options);
    }
    $this->renderer->addCacheableDependency($build, $current_entity);
    $this->renderer->addCacheableDependency($build, $page_entity);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'route',
    ]);
  }

}
