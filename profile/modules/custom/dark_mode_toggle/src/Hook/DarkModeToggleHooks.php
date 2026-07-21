<?php

declare(strict_types=1);

namespace Drupal\dark_mode_toggle\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Hook implementations for dark_mode_toggle.
 */
class DarkModeToggleHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'dark_mode_toggle' => [
        'variables' => [
          'attributes' => [],
        ],
      ],
      'block__dark_mode_toggle' => [
        'base hook' => 'block',
        'variables' => [
          'attributes' => [],
        ],
      ],
    ];
  }

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar(): array {
    return [
      'dark_mode_toggle' => [
        '#type' => 'toolbar_item',
        'tab' => [
          '#type' => 'link',
          '#title' => $this->t('Color scheme'),
          '#url' => Url::fromRoute('<none>'),
          '#attributes' => [
            'class' => [
              'toolbar-icon',
              'toolbar-icon-dark_mode_toggle',
              'dark-mode-toggle--trigger',
            ],
          ],
        ],
        'tray' => [
          '#theme' => 'dark_mode_toggle',
        ],
      ],
    ];
  }

}
