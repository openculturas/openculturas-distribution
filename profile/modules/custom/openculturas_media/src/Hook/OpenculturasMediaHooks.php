<?php

declare(strict_types=1);

namespace Drupal\openculturas_media\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for openculturas_media.
 */
class OpenculturasMediaHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'media_download' => [
        'variables' => [
          'link' => NULL,
          'filesize' => NULL,
          'mimetype' => NULL,
          'inlanguage' => NULL,
          'attributes' => [],
        ],
      ],
    ];
  }

}
