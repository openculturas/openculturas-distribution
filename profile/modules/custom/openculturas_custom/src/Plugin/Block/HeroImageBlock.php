<?php

declare(strict_types = 1);

namespace Drupal\openculturas_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\openculturas_custom\CurrentEntityHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a hero image from current entity block.
 *
 * @Block(
 *   id = "openculturas_custom_hero_image",
 *   admin_label = @Translation("Hero image from current entity (from field_mood_image)")
 * )
 */
final class HeroImageBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): HeroImageBlock {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    $instance->entityRepository = $container->get('entity.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];
    $page_entity = CurrentEntityHelper::get_current_page_entity();
    $current_entity = CurrentEntityHelper::getEventReference($page_entity);
    if (!$current_entity instanceof ContentEntityInterface) {
      return $build;
    }
    if (!$current_entity->hasField('field_mood_image')) {
      return $build;
    }
    if (!$current_entity->get('field_mood_image')->isEmpty()) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $current_entity */
      $current_entity = $this->entityRepository->getTranslationFromContext($current_entity);
      $display_options = [
        'type' => 'entity_reference_entity_view',
        'label' => 'hidden',
        'settings' => [
          'view_mode' => 'header_image',
        ],
      ];
      $build = $current_entity->get('field_mood_image')->view($display_options);
    }
    /*
     * Needs the cache dependency also for no-content, so that a update of the event entity invalidates the cache.
     * No-cache is also not a option.
     */
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
