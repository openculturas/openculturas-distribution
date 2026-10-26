<?php

declare(strict_types=1);

namespace Drupal\entity_popover\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for entity_popover.
 */
final class EntityPopoverHooks {

  #[Hook('theme')]
  public function theme(): array {
    return [
      'entity_popover_dialog' => [
        'variables' => [
          'unique_id' => NULL,
          'toggle_label' => NULL,
          'close_label' => NULL,
          'dialog_content' => NULL,
        ],
      ],
    ];
  }

}
