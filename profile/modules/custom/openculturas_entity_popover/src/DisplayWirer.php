<?php

declare(strict_types=1);

namespace Drupal\openculturas_entity_popover;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_popover_swiffy_slider\PopoverFormatterSettings;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;

/**
 * Wires entity_popover onto OpenCulturas's gallery/media/node bundles.
 *
 * Every step checks whether it has already happened before doing anything,
 * so wireAll() is safe to call more than once.
 */
final readonly class DisplayWirer {

  public const string FIELD_NAME = 'field_entity_popover';

  /**
   * The paragraph bundles the field config is created on.
   *
   * Uninstall only removes the field configs on these bundles, so that a
   * bundle a site builder added the field to keeps its own field config.
   *
   * @var list<string>
   */
  public const array FIELD_BUNDLES = ['gallery', 'media'];

  /**
   * The module providing the popover setting on field_gallery formatters.
   */
  public const string THIRD_PARTY_PROVIDER = 'entity_popover_swiffy_slider';

  public function __construct(
    private PopoverFormatterSettings $popoverFormatterSettings,
  ) {}

  /**
   * The node displays the popover is wired onto field_gallery of.
   *
   * The list lives here rather than in wireAll() so that wiring and
   * unwiring read the same source and cannot drift apart.
   *
   * @var list<string>
   */
  public const array FIELD_GALLERY_DISPLAYS = [
    'node.event.full',
    'node.event.full_lb',
    'node.location.full',
    'node.location.full_lb',
    'node.profile.full',
    'node.profile.full_lb',
  ];

  /**
   * The field the popover is wired onto on the node displays above.
   */
  public const string FIELD_GALLERY_FIELD_NAME = 'field_gallery';

  /**
   * The media view mode field_gallery's popover renders its items in.
   *
   * Part of the profile's baseline config, with or without
   * openculturas_responsive_images.
   */
  private const string POPOVER_VIEW_MODE = 'gallery_big';

  /**
   * A single-item swiffy_slider preset for field_gallery's popover rendering.
   *
   * Only applies inside the popover dialog (see wireFieldGalleryBlock()) -
   * the base, non-popover formatter is left exactly as configured, since
   * this preset (no grid item-count params, small outside indicators) only
   * makes sense for how the field renders inside the dialog.
   */
  private const string POPOVER_SWIFFY_SLIDER_PERMALINK = 'https://swiffyslider.com/configuration/?slider-item-ratio-value=slider-item-ratio-1x1&slider-nav-dark=slider-nav-dark&slider-nav-arrows=slider-nav-outside&slider-nav-visible=slider-nav-visible&slider-nav-autohide=slider-nav-autohide&slider-indicators-dark=slider-indicators-dark&slider-indicators-outside=slider-indicators-outside&slider-indicators-sm=slider-indicators-sm&slider-nav-animation-style=slider-nav-animation-scale';

  /**
   * Wires the popover onto OpenCulturas's gallery/media/node bundles.
   *
   * Deliberately does not touch paragraph.gallery.default/search_index or
   * .slider: default is a stable fallback for sites that already customized
   * it, search_index displays are text-content-only by design, and slider
   * already shows enlarged assets on its own.
   */
  public function wireAll(): void {
    foreach (self::FIELD_BUNDLES as $bundle) {
      $this->ensureFieldOnBundle($bundle);
    }

    $this->ensureViewComponent('paragraph.gallery.grid', self::FIELD_NAME, [
      'type' => 'entity_popover',
      'label' => 'hidden',
      'settings' => ['view_mode' => 'slider'],
      'third_party_settings' => [],
      'weight' => 1,
      'region' => 'content',
    ]);
    $this->ensureViewComponent('paragraph.gallery.slider_duo', self::FIELD_NAME, [
      'type' => 'entity_popover',
      'label' => 'hidden',
      'settings' => ['view_mode' => 'slider'],
      'third_party_settings' => [],
      'weight' => 1,
      'region' => 'content',
    ]);
    $this->ensureViewComponent('paragraph.gallery.slider_multiple', self::FIELD_NAME, [
      'type' => 'entity_popover',
      'label' => 'hidden',
      'settings' => ['view_mode' => 'slider'],
      'third_party_settings' => [],
      'weight' => 1,
      'region' => 'content',
    ]);
    $this->ensureViewComponent('paragraph.media.default', self::FIELD_NAME, [
      'type' => 'entity_popover',
      'label' => 'hidden',
      'settings' => ['view_mode' => 'default'],
      'third_party_settings' => [],
      'weight' => 2,
      'region' => 'content',
    ]);

    $this->ensureFormComponent('paragraph.gallery.default', self::FIELD_NAME);
    $this->ensureFormComponent('paragraph.media.default', self::FIELD_NAME);

    foreach (self::FIELD_GALLERY_DISPLAYS as $displayId) {
      $this->wireFieldGallery($displayId);
    }
  }

  /**
   * The id of the field_gallery block in a Layout Builder node display.
   *
   * @param string $displayId
   *   The config name of the node entity view display.
   *
   * @return string
   *   The layout block id, for example "field_block:node:event:field_gallery".
   */
  public static function fieldGalleryBlockId(string $displayId): string {
    [$entityType, $bundle] = explode('.', $displayId);
    return "field_block:$entityType:$bundle:" . self::FIELD_GALLERY_FIELD_NAME;
  }

  private function ensureFieldOnBundle(string $bundle): void {
    if (!FieldStorageConfig::loadByName('paragraph', self::FIELD_NAME)) {
      FieldStorageConfig::create([
        'field_name' => self::FIELD_NAME,
        'entity_type' => 'paragraph',
        'type' => 'entity_popover',
        'cardinality' => 1,
        'translatable' => FALSE,
      ])->save();
    }

    if (!FieldConfig::loadByName('paragraph', $bundle, self::FIELD_NAME)) {
      FieldConfig::create([
        'field_name' => self::FIELD_NAME,
        'entity_type' => 'paragraph',
        'bundle' => $bundle,
        'label' => 'Enable popover',
        'description' => 'Adds a toggle that opens this ' . $bundle . ' item in an enlarged view inside a dialog.',
        'default_value' => [['value' => 1]],
      ])->save();
    }
  }

  private function ensureViewComponent(string $displayId, string $fieldName, array $component): void {
    $display = EntityViewDisplay::load($displayId);
    if (!$display instanceof EntityDisplayInterface || $display->getComponent($fieldName)) {
      return;
    }

    $display->setComponent($fieldName, $component)->save();
  }

  private function ensureFormComponent(string $displayId, string $fieldName): void {
    $display = EntityFormDisplay::load($displayId);
    if (!$display instanceof EntityDisplayInterface || $display->getComponent($fieldName)) {
      return;
    }

    $display->setComponent($fieldName, [
      'type' => 'entity_popover_checkbox',
      'weight' => 10,
      'region' => 'content',
      'settings' => ['display_label' => TRUE],
    ])->save();
  }

  /**
   * Enables the popover on field_gallery of a node display.
   *
   * Displays using Layout Builder render field_gallery as a field block, the
   * others as a regular display component. Sites updated from releases
   * before Layout Builder keep the regular component.
   *
   * @param string $displayId
   *   The config name of the node entity view display to wire.
   */
  private function wireFieldGallery(string $displayId): void {
    $display = EntityViewDisplay::load($displayId);
    if (!$display instanceof EntityViewDisplayInterface) {
      return;
    }

    if ($display instanceof LayoutBuilderEntityViewDisplay && $display->isLayoutBuilderEnabled()) {
      $this->wireFieldGalleryBlock($display, self::fieldGalleryBlockId($displayId));
      return;
    }

    $component = $display->getComponent(self::FIELD_GALLERY_FIELD_NAME);
    if ($component === NULL || isset($component['third_party_settings'][self::THIRD_PARTY_PROVIDER])) {
      return;
    }

    $component['third_party_settings'][self::THIRD_PARTY_PROVIDER] = $this->popoverSettings($component);
    $display->setComponent(self::FIELD_GALLERY_FIELD_NAME, $component)->save();
  }

  private function wireFieldGalleryBlock(LayoutBuilderEntityViewDisplay $display, string $blockId): void {
    $changed = FALSE;

    foreach ($display->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        $configuration = $component->get('configuration');
        if (($configuration['id'] ?? '') !== $blockId) {
          continue;
        }

        if (isset($configuration['formatter']['third_party_settings'][self::THIRD_PARTY_PROVIDER])) {
          continue;
        }

        $configuration['formatter']['third_party_settings'][self::THIRD_PARTY_PROVIDER] = $this->popoverSettings($configuration['formatter'] ?? []);
        $component->set('configuration', $configuration);
        $changed = TRUE;
      }
    }

    if ($changed) {
      $display->save();
    }
  }

  /**
   * The popover settings for a field_gallery formatter.
   *
   * Reuses the live formatter and its settings inside the dialog, with the
   * bigger view mode and a single-item slider — as far as the live formatter
   * takes those. A site whose field_gallery runs on another formatter keeps
   * that formatter for the dialog, without the slider's own settings.
   *
   * @param array<string, mixed> $formatter
   *   The formatter configuration (type and settings).
   *
   * @return array<string, mixed>
   *   The entity_popover_swiffy_slider third-party settings.
   */
  private function popoverSettings(array $formatter): array {
    $formatterType = $formatter['type'] ?? 'swiffy_slider_entity_reference';

    return [
      'enable' => TRUE,
      'details' => [
        'formatter' => $formatterType,
        // The overrides only make sense for a slider formatter: on a site
        // whose live formatter is something else, the popover keeps that
        // formatter and simply gets none of them.
        'formatter_settings' => $this->popoverFormatterSettings->filter($formatterType, [
          'view_mode' => self::POPOVER_VIEW_MODE,
          'swiffy_slider_permalink' => self::POPOVER_SWIFFY_SLIDER_PERMALINK,
        ] + ($formatter['settings'] ?? [])),
      ],
    ];
  }

}
