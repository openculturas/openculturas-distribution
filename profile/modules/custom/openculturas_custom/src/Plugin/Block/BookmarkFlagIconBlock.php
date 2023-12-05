<?php

declare(strict_types = 1);

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
 *   admin_label = @Translation("Bookmark flag icon")
 * )
 */
final class BookmarkFlagIconBlock extends BlockBase {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function build(): ?array {
    $build = [];
    $page_entity = CurrentEntityHelper::get_current_page_entity();
    $current_entity = CurrentEntityHelper::getEventReference($page_entity);
    if ($current_entity instanceof FieldableEntityInterface
      && ($current_entity->getEntityTypeId() === 'node'
        || $current_entity->getEntityTypeId() === 'taxonomy_term')
      ) {
      $build['flag_bookmark_' . $current_entity->getEntityTypeId()] = [
        '#lazy_builder' => [
          'flag.link_builder:build', [
            $current_entity->getEntityTypeId(),
            $current_entity->id(),
            ($current_entity->getEntityTypeId() === 'taxonomy_term') ? 'bookmark_term' : 'bookmark_node',
          ],
        ],
        '#create_placeholder' => TRUE,
      ];
      return $build;
    }

    return NULL;
  }

}
