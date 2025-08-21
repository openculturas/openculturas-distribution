<?php

declare(strict_types=1);

namespace Drupal\dark_mode_toggle\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a dark mode toggle block.
 */
#[Block(
  id: 'dark_mode_toggle',
  admin_label:  new TranslatableMarkup('Color scheme'),
  category:  new TranslatableMarkup('Dark mode toggle'),
)]
class DarkModeToggleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      'content' => [
        '#theme' => 'dark_mode_toggle',
      ],
    ];
  }

}
