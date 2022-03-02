<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\openculturas_custom\CurrentEntityHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a page title with subtitle block.
 *
 * @Block(
 *   id = "openculturas_custom_page_title",
 *   admin_label = @Translation("Page title with subtitle"),
 *   category = @Translation("Openculturas")
 * )
 */
class PageTitleBlock extends BlockBase implements TitleBlockPluginInterface,ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array
   */
  protected $title = '';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    return $instance;
  }


  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_entity = CurrentEntityHelper::getEventReference(CurrentEntityHelper::get_current_page_entity());
    $subtitle = NULL;
    $profile_image = NULL;

    if ($current_entity !== NULL) {
      $this->setTitle($current_entity->label());
      if ($current_entity->hasField('field_subtitle')
        && !$current_entity->get('field_subtitle')->isEmpty()) {
        $subtitle = $current_entity->get('field_subtitle')->view(['label' => 'hidden']);
      }
      if ($current_entity->hasField('field_portrait')
        && !$current_entity->get('field_portrait')->isEmpty()) {
        $display_options = [
          'type' => 'entity_reference_entity_view',
          'label' => 'hidden',
          'settings' => ['view_mode' => 'profile_image',]
        ];
        $profile_image = $current_entity->get('field_portrait')->view($display_options);
      }
    }

    $build = [
      '#theme' => 'page_title_custom',
      '#title' => $this->title,
      '#subtitle' => $subtitle,
      '#profile_image' => $profile_image
    ];
    $this->renderer->addCacheableDependency($build, $current_entity);
    return $build;
  }

}
