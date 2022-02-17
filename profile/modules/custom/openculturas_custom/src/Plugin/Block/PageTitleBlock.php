<?php

namespace Drupal\openculturas_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\TitleBlockPluginInterface;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\openculturas_custom\CurrentEntityHelper;


/**
 * Provides a page title with subtitle block.
 *
 * @Block(
 *   id = "openculturas_custom_page_title",
 *   admin_label = @Translation("Page title with subtitle"),
 *   category = @Translation("Openculturas")
 * )
 */
class PageTitleBlock extends BlockBase implements TitleBlockPluginInterface {

  use UncacheableDependencyTrait;
  /**
   * The page title: a string (plain title) or a render array (formatted title).
   *
   * @var string|array
   */
  protected $title = '';

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
    $current_entity = CurrentEntityHelper::get_current_page_entity();
    $subtitle = NULL;
    $profile_image = NULL;

    if (!empty($current_entity) && method_exists($current_entity, 'hasField')) {
      if ($current_entity->hasField('field_subtitle')
        && !$current_entity->get('field_subtitle')->isEmpty()) {
        $subtitle = $current_entity->get('field_subtitle')->view(['label' => 'hidden']);
      }
      if ($current_entity->hasField('field_portrait')
        && !$current_entity->get('field_portrait')->isEmpty()) {
        $profile_image = $current_entity->get('field_portrait')->view('profile_image');
      }
    }

    return [
      '#theme' => 'page_title_custom',
      '#title' => $this->title,
      '#subtitle' => $subtitle,
      '#profile_image' => $profile_image
    ];
  }

}
