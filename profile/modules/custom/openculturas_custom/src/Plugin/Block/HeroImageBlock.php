<?php

namespace Drupal\openculturas_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\openculturas_custom\CurrentEntityHelper;

/**
 * Provides a hero image from current entity block.
 *
 * @Block(
 *   id = "openculturas_custom_hero_image",
 *   admin_label = @Translation("Hero image from current entity (from field_mood_image)"),
 *   category = @Translation("Openculturas")
 * )
 */
class HeroImageBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_entity = CurrentEntityHelper::get_current_page_entity();
    if (!empty($current_entity)
      && $current_entity->hasField('field_mood_image')
      && !$current_entity->get('field_mood_image')->isEmpty()) {
      $field_mood_image = $current_entity->get('field_mood_image')->referencedEntities()[0];
      $media = \Drupal::entityTypeManager()->getViewBuilder('media')->view($field_mood_image, 'header_image');
      return $media;
    }
    return NULL;
  }

}
