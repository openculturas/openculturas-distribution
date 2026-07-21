<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Theming and preprocessing hook implementations for openculturas_custom.
 */
class OpenculturasCustomThemeHooks {

  /**
   * Constructs a new OpenculturasCustomThemeHooks.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(protected ModuleExtensionList $moduleExtensionList) {
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme(): array {
    return [
      'page_title_custom' => [
        'variables' => [
          'title' => NULL,
          'subtitle' => NULL,
          'sub_type' => NULL,
          'profile_image' => NULL,
        ],
        'template' => 'page-title-custom',
      ],
    ];
  }

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(array &$theme_registry): void {
    if (isset($theme_registry['attribution_creative_commons_icons'])) {
      $theme_registry['attribution_creative_commons_icons']['theme path'] = $this->moduleExtensionList->getPath('openculturas_custom');
      $theme_registry['attribution_creative_commons_icons']['path'] = $this->moduleExtensionList->getPath('openculturas_custom') . '/templates';
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(array &$variables): void {
    if (!array_key_exists('#fieldgroups', $variables['elements'])) {
      return;
    }

    if (!is_array($variables['elements']['#fieldgroups'])) {
      return;
    }

    foreach ($variables['elements']['#fieldgroups'] as $field_group) {
      if (!is_object($field_group)) {
        continue;
      }

      if (!property_exists($field_group, 'format_type')) {
        continue;
      }

      if ($field_group->format_type !== 'sub_details') {
        continue;
      }

      $variables['title_attributes']['class'][] = 'visually-hidden';
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_details_alter')]
  public function themeSuggestionsDetailsAlter(array &$suggestions, array $variables): void {
    if (array_key_exists('#plugin_id', $variables['element'])) {
      $suggestions[] = 'details__' . $variables['element']['#plugin_id'];
    }
  }

}
