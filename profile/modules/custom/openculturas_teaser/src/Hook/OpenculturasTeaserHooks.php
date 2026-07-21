<?php

declare(strict_types=1);

namespace Drupal\openculturas_teaser\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for openculturas_teaser.
 */
class OpenculturasTeaserHooks {

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(array $existing, string $type, string $theme, string $path): array {
    return [
      'teaser' => [
        'variables' => [
          'title' => NULL,
          'subtitle' => NULL,
          'description' => NULL,
          'media' => NULL,
          'url' => NULL,
          'attributes' => [],
        ],
      ],
    ];
  }

  /**
   * Implements hook_field_widget_single_element_form_alter().
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function fieldWidgetSingleElementFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    if (array_key_exists('#paragraph_type', $element)) {
      switch ($element['#paragraph_type']) {
        case 'teaser_node':
        case 'teaser_term':
        case 'teaser_external':
          if (isset($element['subform']) && is_array($element['subform'])) {
            $element['subform']['#weight'] = 1;
          }

          if (isset($element['behavior_plugins']) && is_array($element['behavior_plugins'])) {
            $element['behavior_plugins']['#weight'] = 2;
          }

          break;
      }
    }
  }

}
