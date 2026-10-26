<?php

declare(strict_types=1);

namespace Drupal\entity_popover_swiffy_slider;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_popover\PopoverId;

/**
 * Adds the popover dialog as a sibling of a field's render array, if enabled.
 *
 * Shared between the two different rendering pipelines a field can go
 * through: classic entity rendering (hook_entity_view_alter()) and Layout
 * Builder's own, separate field block rendering (an event subscriber, since
 * Layout Builder never invokes hook_block_view_alter() for its own blocks).
 *
 * The dialog is added as a real sibling render array (matching how
 * entity_popover's own field does it), not rendered to a string via
 * renderInIsolation() and stuffed into the field's own #suffix: isolation
 * is deliberate — it does not bubble #attached libraries or cache tags out
 * to the page, which silently dropped both the module's CSS and any cache
 * dependency on the dialog's own (differently formatted) content.
 */
final class FieldPopoverBuilder {

  public const string SETTINGS_KEY = 'entity_popover_swiffy_slider';

  /**
   * Checks whether the field's own bundle already has a native popover field.
   *
   * The entity_popover module's own field (an "entity_popover"-typed field
   * added directly to the bundle) is the normal, go-to mechanism; this formatter
   * setting exists only as a fallback for bundles that have no wrapping
   * entity to attach that field to (e.g. a multi-value field on a bare node
   * bundle). Once a bundle gains its own native field, this setting must
   * stop taking effect there — even if it was enabled before the native
   * field existed and a stale value is still stored — so a site builder
   * never sees the same entity offer two independent "enlarge" toggles.
   *
   * This is a per-bundle check, not a per-purpose one: it assumes a bundle
   * only ever wants a single popover mechanism. A bundle that legitimately
   * wants both — e.g. a native field previewing the whole entity in one
   * view mode, and a separately configured popover just for one of its
   * entity reference fields opening a different view mode — cannot
   * currently have both; the native field wins bundle-wide. Believed to be
   * a rare combination (@see README.md "Scope"), left unsolved for now.
   */
  public static function hasNativePopoverField(FieldDefinitionInterface $fieldDefinition): bool {
    $bundle = $fieldDefinition->getTargetBundle();
    if ($bundle === NULL) {
      return FALSE;
    }

    $fields = \Drupal::service('entity_field.manager')
      ->getFieldDefinitions($fieldDefinition->getTargetEntityTypeId(), $bundle);
    foreach ($fields as $candidate) {
      if ($candidate->getType() === 'entity_popover') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Adds the popover dialog as a sibling of $parent[$key], if enabled.
   *
   * @param array $parent
   *   The render array containing the field at $key as a direct child.
   * @param int|string $key
   *   The field's key within $parent.
   * @param \Drupal\entity_popover_swiffy_slider\PopoverFormatterSettings $popoverFormatterSettings
   *   Reduces the stored settings to the ones the stored formatter knows, so
   *   config saved before a formatter change cannot reach a formatter that
   *   does not have those settings.
   */
  public static function wrapFieldWithPopover(array &$parent, int|string $key, PopoverFormatterSettings $popoverFormatterSettings): void {
    $fieldBuild = $parent[$key];
    $settings = $fieldBuild['#third_party_settings'][self::SETTINGS_KEY] ?? NULL;
    $nested = $settings['details'] ?? [];
    if (empty($settings['enable']) || empty($nested['formatter']) || empty($fieldBuild['#items'])) {
      return;
    }

    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $fieldBuild['#items'];

    if (self::hasNativePopoverField($items->getFieldDefinition())) {
      return;
    }

    $alternateFormatter = \Drupal::service('plugin.manager.field.formatter')->getInstance([
      'field_definition' => $items->getFieldDefinition(),
      'view_mode' => $fieldBuild['#view_mode'] ?? 'default',
      'configuration' => [
        'type' => $nested['formatter'],
        'settings' => $popoverFormatterSettings->filter($nested['formatter'], $nested['formatter_settings'] ?? []),
        'label' => 'hidden',
        'third_party_settings' => [],
      ],
    ]);

    if (!$alternateFormatter) {
      return;
    }

    $dialogContent = $alternateFormatter->view($items, $fieldBuild['#language'] ?? NULL);

    $entity = $items->getEntity();
    $uniqueId = PopoverId::build(
      $entity->getEntityTypeId(),
      PopoverId::entityIdentifier($entity),
      $fieldBuild['#field_name'] ?? 'field',
      $fieldBuild['#view_mode'] ?? 'default',
      $fieldBuild['#language'] ?? $items->getLangcode(),
    );

    $dialogKey = $key . '__entity_popover';
    $parent[$dialogKey] = [
      '#theme' => 'entity_popover_dialog',
      '#unique_id' => $uniqueId,
      '#toggle_label' => new TranslatableMarkup('Enlarge'),
      '#close_label' => new TranslatableMarkup('Close'),
      '#dialog_content' => $dialogContent,
      '#weight' => ($fieldBuild['#weight'] ?? 0),
      '#attached' => [
        'library' => ['entity_popover/dialog'],
      ],
    ];

    // field_group moves only the children it knows into a group, so the
    // dialog would otherwise end up outside its field's group.
    if (isset($parent['#group_children'][$key])) {
      $parent['#group_children'][$dialogKey] = $parent['#group_children'][$key];
    }
  }

}
