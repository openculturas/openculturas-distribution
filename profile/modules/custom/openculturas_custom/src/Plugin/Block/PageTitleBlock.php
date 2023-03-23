<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\openculturas_custom\CurrentEntityHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function rtrim;
use function strip_tags;

/**
 * Provides a page title with subtitle block.
 *
 * @Block(
 *   id = "openculturas_custom_page_title",
 *   admin_label = @Translation("Page title with subtitle"),
 *   category = @Translation("Openculturas")
 * )
 */
final class PageTitleBlock extends BlockBase implements TitleBlockPluginInterface, ContainerFactoryPluginInterface {

  protected RendererInterface $renderer;

  protected EntityRepositoryInterface $entityRepository;

  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array
   */
  protected $title = '';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): PageTitleBlock {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    $instance->entityRepository = $container->get('entity.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title): PageTitleBlock {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @return array{label_display: false}
   */
  public function defaultConfiguration(): array {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $page_entity = CurrentEntityHelper::get_current_page_entity();
    $current_entity = CurrentEntityHelper::getEventReference($page_entity);
    $subtitle = NULL;
    $profile_image = NULL;

    if ($current_entity !== NULL) {
      $title_markup = [];
      if ($page_entity !== NULL && $page_entity->hasField('field_premiere')
        && !$page_entity->get('field_premiere')->isEmpty()) {
        $field_premiere_render_array = $page_entity->get('field_premiere')->view(['label' => 'hidden']);
        $title_markup[] = ['#plain_text' => rtrim(strip_tags((string) $this->renderer->renderPlain($field_premiere_render_array))) . ': '];
      }
      $current_entity = $this->entityRepository->getTranslationFromContext($current_entity);
      $title_markup[] = ['#plain_text' => $current_entity->label()];
      $this->title = $title_markup;
      if ($current_entity->hasField('field_subtitle')
        && !$current_entity->get('field_subtitle')->isEmpty()) {
        $subtitle = $current_entity->get('field_subtitle')->view(['label' => 'hidden']);
      }
      if ($current_entity->hasField('field_portrait')
        && !$current_entity->get('field_portrait')->isEmpty()) {
        $display_options = [
          'type' => 'entity_reference_entity_view',
          'label' => 'hidden',
          'settings' => ['view_mode' => 'profile_image'],
        ];
        $profile_image = $current_entity->get('field_portrait')->view($display_options);
      }
    }

    $build = [
      '#theme' => 'page_title_custom',
      '#title' => $this->title,
      '#subtitle' => $subtitle,
      '#profile_image' => $profile_image,
    ];
    /*
     * Needs the cache dependency also for no-content, so that a update of the entity invalidates the cache.
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
