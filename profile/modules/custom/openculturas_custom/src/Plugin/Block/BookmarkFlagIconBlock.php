<?php

namespace Drupal\openculturas_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\openculturas_custom\CurrentEntityHelper;

/**
 * Provides a bookmark flag icon block.
 *
 * @Block(
 *   id = "openculturas_custom_bookmark_flag_icon",
 *   admin_label = @Translation("Bookmark flag icon"),
 *   category = @Translation("Openculturas")
 * )
 */
class BookmarkFlagIconBlock extends BlockBase {

  use UncacheableDependencyTrait;
  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $current_entity = CurrentEntityHelper::get_current_page_entity();
    if ($current_entity instanceof FieldableEntityInterface
      && ($current_entity->getEntityTypeId() == 'node'
        || $current_entity->getEntityTypeId() == 'taxonomy_term')
      ) {
      $build['flag_bookmark_' . $current_entity->getEntityTypeId()] = [
        '#lazy_builder' => ['flag.link_builder:build', [
          $current_entity->getEntityTypeId(),
          $current_entity->id(),
          ($current_entity->getEntityTypeId() == 'taxonomy_term') ? 'bookmark_term' : 'bookmark_' . $current_entity->getEntityTypeId(),
        ]],
        '#create_placeholder' => TRUE,
      ];

      if ($current_entity->getEntityTypeId() == 'node') {
        $build['flag_recommendation_node'] = [
          '#lazy_builder' => ['flag.link_builder:build', [
            $current_entity->getEntityTypeId(),
            $current_entity->id(),
            'recommendation_node',
          ]],
          '#create_placeholder' => TRUE,
        ];
      }

      $build['#cache'] = [
        'max-age' => 0
      ];
      return $build;
    }

    return NULL;
  }

}
