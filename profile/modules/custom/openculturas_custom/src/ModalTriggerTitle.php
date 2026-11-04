<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

/**
 * Moves the title of a block into its modal-trigger popover.
 */
final class ModalTriggerTitle {

  /**
   * Moves a block title into the popover of the block content.
   *
   * An enabled "Display title" would otherwise show at the page level,
   * outside the popover. The title is stored on the content build of this
   * block, so every placement and reference of the same block_content keeps
   * its own title.
   *
   * @param array<string, mixed> $build
   *   A block render array with '#theme' => 'block' and its built content in
   *   'content'.
   *
   * @return array<string, mixed>
   *   The block render array.
   */
  public static function moveIntoPopover(array $build): array {
    if (empty($build['#configuration']['label_display']) || !isset($build['content']['#modal_trigger_settings'])) {
      return $build;
    }

    $label = $build['#configuration']['label'] ?? '';
    if ($label !== '') {
      $build['content']['#modal_trigger_settings']['title'] = $label;
    }

    // An empty label is hidden at the page level as well.
    $build['#configuration']['label_display'] = '0';
    return $build;
  }

}
