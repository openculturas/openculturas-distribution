<?php

namespace Drupal\openculturas_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\TitleBlockPluginInterface;
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
    if (!empty($current_entity)
      && $current_entity->hasField('field_subtitle')
      && !$current_entity->get('field_subtitle')->isEmpty()) {
      $subtitle = $current_entity->get('field_subtitle')->value;
    }

    return [
      '#theme' => 'page_title_custom',
      '#title' => $this->title,
      '#subtitle' => $subtitle
    ];
  }

}
