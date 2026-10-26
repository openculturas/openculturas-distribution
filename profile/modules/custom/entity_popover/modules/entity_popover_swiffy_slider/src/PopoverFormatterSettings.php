<?php

declare(strict_types=1);

namespace Drupal\entity_popover_swiffy_slider;

use Drupal\Core\Field\FormatterPluginManager;

/**
 * Keeps stored popover formatter settings to what the formatter actually has.
 *
 * The popover's settings live under details.formatter_settings, which the
 * config schema types as field.formatter.settings.[%parent.formatter]: the
 * keys a value may hold depend on the formatter chosen next to it. Every
 * place that writes those settings — the settings form, the installer of
 * openculturas_entity_popover, and rendering — goes through this filter, so a
 * formatter can never end up with settings it has no default for.
 */
final readonly class PopoverFormatterSettings {

  public function __construct(
    private FormatterPluginManager $formatterPluginManager,
  ) {}

  /**
   * Reduces formatter settings to the keys that formatter defines.
   *
   * Keys the formatter does not know are dropped, and keys it defines but
   * the input lacks are filled from the formatter's own defaults. An unknown
   * formatter type has no known keys at all, so nothing survives it.
   *
   * @param string $formatterType
   *   The plugin id of the popover's formatter.
   * @param array<string, mixed> $settings
   *   The settings to filter.
   *
   * @return array<string, mixed>
   *   The settings the formatter defines, defaults included.
   */
  public function filter(string $formatterType, array $settings): array {
    $defaultSettings = $this->formatterPluginManager->getDefaultSettings($formatterType);
    if ($defaultSettings === []) {
      return [];
    }

    return array_intersect_key($settings, $defaultSettings) + $defaultSettings;
  }

}
