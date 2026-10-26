<?php

declare(strict_types=1);

namespace Drupal\openculturas_entity_popover;

/**
 * Removes what DisplayWirer added from raw entity display config data.
 *
 * Works on the stored array form, so it covers config_devel exports and
 * plain config edits alike, without loading the display entity.
 */
final class DisplayUnwirer {

  /**
   * Config names of the entity_popover fields DisplayWirer creates.
   *
   * @var list<string>
   */
  private const array FIELD_CONFIG_NAMES = [
    'field.field.paragraph.gallery.' . DisplayWirer::FIELD_NAME,
    'field.field.paragraph.media.' . DisplayWirer::FIELD_NAME,
  ];

  /**
   * Modules a display only depends on because of the wiring.
   *
   * @var list<string>
   */
  private const array MODULES = ['entity_popover', DisplayWirer::THIRD_PARTY_PROVIDER];

  /**
   * Returns the display config data without the popover wiring.
   *
   * @param array<string, mixed> $data
   *   The entity view or form display config data.
   *
   * @return array<string, mixed>
   *   The data without the field_entity_popover component, the
   *   entity_popover_swiffy_slider settings on display components and
   *   Layout Builder field blocks, and the dependencies on both.
   */
  public static function unwire(array $data): array {
    unset($data['content'][DisplayWirer::FIELD_NAME], $data['hidden'][DisplayWirer::FIELD_NAME]);

    foreach (array_keys($data['content'] ?? []) as $fieldName) {
      unset($data['content'][$fieldName]['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER]);
    }

    foreach ($data['third_party_settings']['layout_builder']['sections'] ?? [] as $sectionDelta => $section) {
      foreach ($section['components'] ?? [] as $uuid => $component) {
        unset($data['third_party_settings']['layout_builder']['sections'][$sectionDelta]['components'][$uuid]['configuration']['formatter']['third_party_settings'][DisplayWirer::THIRD_PARTY_PROVIDER]);
      }
    }

    foreach (['config' => self::FIELD_CONFIG_NAMES, 'module' => self::MODULES] as $type => $names) {
      if (!isset($data['dependencies'][$type])) {
        continue;
      }

      $data['dependencies'][$type] = array_values(array_diff($data['dependencies'][$type], $names));
      if ($data['dependencies'][$type] === []) {
        unset($data['dependencies'][$type]);
      }
    }

    return $data;
  }

}
