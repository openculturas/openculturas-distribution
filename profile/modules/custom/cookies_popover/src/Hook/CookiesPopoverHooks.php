<?php

declare(strict_types=1);

namespace Drupal\cookies_popover\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for cookies_popover.
 */
class CookiesPopoverHooks {

  /**
   * Implements hook_preprocess_HOOK() for the cookies_block theme hook.
   */
  #[Hook('preprocess_cookies_block')]
  public function preprocessCookiesBlock(array &$variables): void {
    $variables['#attached']['library'][] = 'cookies_popover/cookies_popover';
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'cookies_popover_settings_button' => [
        'variables' => [
          'text' => NULL,
        ],
      ],
    ];
  }

}
