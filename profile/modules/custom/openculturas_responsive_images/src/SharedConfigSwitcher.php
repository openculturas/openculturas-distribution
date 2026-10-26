<?php

declare(strict_types=1);

namespace Drupal\openculturas_responsive_images;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Switches shared config between legacy image styles and the oc_* versions.
 *
 * The switched config objects (media view and form displays, a view, metatag
 * defaults, crop type labels, gallery paragraph displays) are owned by the
 * profile, so this module can't ship them in its config/install. Fresh
 * installs switch in the profile's install task, existing sites from this
 * module's settings form. Uninstalling the module reverts, and so does the
 * config_devel export of the profile, so its config/install keeps the state
 * of a site without this module.
 *
 * Only the formatter type and settings are switched, so label, weight,
 * region and third party settings stay as the site configured them. Revert
 * only touches config that still holds this module's values.
 *
 * Each switched entity has one switched component. It is in the standard
 * state while it matches the legacy values the profile ships, and switched
 * while it holds this module's values. Anything else is a deviation: a value
 * the site configured itself. Switching to responsive skips deviating
 * components unless their key (see deviation()) is approved. An approved
 * component that is reverted later gets the OpenCulturas standard, not the
 * value the site had before the switch.
 */
final readonly class SharedConfigSwitcher {

  /**
   * Media view display formatter settings, keyed by display ID.
   *
   * @var array<string, array{legacy: array<string, mixed>, responsive: array<string, mixed>}>
   */
  private const array VIEW_DISPLAY_SETTINGS = [
    'media.image.header_image' => [
      'legacy' => [
        'image_link' => '',
        'image_style' => 'header_image',
        'image_loading' => ['attribute' => 'lazy'],
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
      ],
      'responsive' => [
        'responsive_image_style' => 'oc_2_1',
        'image_link' => '',
        'image_loading' => ['attribute' => 'eager'],
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
      ],
    ],
    'media.image.teaser_image' => [
      'legacy' => [
        'image_link' => '',
        'image_style' => 'teaser',
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
        'image_loading' => ['attribute' => 'lazy'],
      ],
      'responsive' => [
        'responsive_image_style' => 'oc_16_9_teaser',
        'image_link' => '',
        'image_loading' => ['attribute' => 'lazy'],
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
      ],
    ],
    'media.image.teaser_image_big' => [
      'legacy' => [
        'image_link' => '',
        'image_style' => 'teaser_big',
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
        'image_loading' => ['attribute' => 'lazy'],
      ],
      'responsive' => [
        'responsive_image_style' => 'oc_16_9_teaser_big',
        'image_link' => '',
        'image_loading' => ['attribute' => 'lazy'],
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
      ],
    ],
    'media.user_profile_picture.default' => [
      'legacy' => [
        'image_link' => '',
        'image_style' => 'profile',
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
        'image_loading' => ['attribute' => 'lazy'],
      ],
      'responsive' => [
        'responsive_image_style' => 'oc_1_1_profile',
        'image_link' => '',
        'image_loading' => ['attribute' => 'lazy'],
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
      ],
    ],
    'media.user_profile_picture.profile_image' => [
      'legacy' => [
        'image_link' => '',
        'image_style' => 'profile',
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
        'image_loading' => ['attribute' => 'lazy'],
      ],
      'responsive' => [
        'responsive_image_style' => 'oc_1_1_profile',
        'image_link' => '',
        'image_loading' => ['attribute' => 'lazy'],
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
      ],
    ],
    'media.image.gallery' => [
      'legacy' => [
        'image_link' => '',
        'image_style' => 'gallery',
        'image_loading' => ['attribute' => 'lazy'],
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
      ],
      'responsive' => [
        'responsive_image_style' => 'oc_bounds_600',
        'image_link' => '',
        'image_loading' => ['attribute' => 'lazy'],
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
      ],
    ],
    'media.image.content_asset' => [
      'legacy' => [
        'image_link' => '',
        'image_style' => 'content_image',
        'svg_attributes' => ['width' => 680, 'height' => NULL],
        'svg_render_as_image' => TRUE,
        'image_loading' => ['attribute' => 'lazy'],
      ],
      'responsive' => [
        'responsive_image_style' => 'oc_bounds_800',
        'image_link' => '',
        'svg_attributes' => ['width' => 680, 'height' => NULL],
        'svg_render_as_image' => TRUE,
        'image_loading' => ['attribute' => 'lazy'],
      ],
    ],
    // media.remote_video.gallery_big is not switched: oEmbed video has no
    // responsive variant, and it must stay a fixed 1600x1200 matching the
    // plain "Gallery" display. Drupal core's OEmbed URL resolver caches a
    // resource per request keyed only by URL, so rendering the same video at
    // two sizes in one request (e.g. the entity_popover dialog and the normal
    // display) makes the second one reuse the first's dimensions.
    'media.image.gallery_big' => [
      'legacy' => [
        'image_link' => '',
        'image_style' => 'teaser_big',
        'image_loading' => ['attribute' => 'lazy'],
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
      ],
      'responsive' => [
        'responsive_image_style' => 'oc_bounds_1600',
        'image_link' => '',
        'image_loading' => ['attribute' => 'lazy'],
        'svg_attributes' => ['width' => NULL, 'height' => NULL],
        'svg_render_as_image' => TRUE,
      ],
    ],
  ];

  /**
   * Crop types offered by the media image widgets, keyed by form display ID.
   *
   * @var array<string, array{legacy: list<string>, responsive: list<string>}>
   */
  private const array CROP_LISTS = [
    'media.image.default' => [
      'legacy' => ['16_9', 'free_format', 'image_crop'],
      'responsive' => ['oc_16_9', 'oc_2_1', 'free_format'],
    ],
    'media.image.media_library' => [
      'legacy' => ['16_9', 'free_format', 'image_crop'],
      'responsive' => ['oc_16_9', 'oc_2_1', 'free_format'],
    ],
    'media.user_profile_picture.default' => [
      'legacy' => ['1_1'],
      'responsive' => ['oc_1_1'],
    ],
    'media.user_profile_picture.media_library' => [
      'legacy' => ['1_1'],
      'responsive' => ['oc_1_1'],
    ],
  ];

  private const string MEDIA_IMAGE_FIELD = 'field_media_image';

  /**
   * The image style of the media.image.token display, in both states.
   */
  private const array TOKEN_IMAGE_STYLES = ['legacy' => 'header_image', 'responsive' => 'oc_2_1_1200'];

  /**
   * The image style of the related_dates_export view, in both states.
   */
  private const array RELATED_DATES_EXPORT_IMAGE_STYLES = ['legacy' => 'teaser_big', 'responsive' => 'oc_16_9_1200'];

  /**
   * The media view mode of the gallery paragraph displays, in both states.
   */
  private const array GALLERY_VIEW_MODES = ['legacy' => 'gallery', 'responsive' => 'gallery_big'];

  private const string DEPRECATED_SUFFIX = ' (deprecated)';

  private const array GALLERY_PARAGRAPH_DISPLAYS = ['paragraph.gallery.default', 'paragraph.gallery.slider'];

  /**
   * Entity types whose switch only replaces this module's token or suffix.
   *
   * They have no component state, so revert does not depend on one.
   */
  private const array STATELESS_ENTITY_TYPES = ['metatag_defaults', 'crop_type'];

  /**
   * Component state: the legacy values the profile ships.
   */
  public const string STANDARD = 'standard';

  /**
   * Component state: this module's responsive values.
   */
  public const string SWITCHED = 'switched';

  /**
   * Component state: a value the site configured itself.
   */
  public const string DEVIATING = 'deviating';

  /**
   * The property each switched config entity type changes.
   *
   * @var array<string, string>
   */
  public const array SWITCHED_PROPERTIES = [
    'entity_view_display' => 'content',
    'entity_form_display' => 'content',
    'view' => 'display',
    'metatag_defaults' => 'tags',
    'crop_type' => 'label',
  ];

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Switches the shared config to this module's oc_* image styles.
   *
   * @param list<string> $approvedKeys
   *   Keys of deviating components (see deviation()) that may be overwritten.
   *   Deviating components with other keys are left alone.
   */
  public function switchToResponsive(array $approvedKeys = []): void {
    $this->switchAll(TRUE, $approvedKeys);
  }

  /**
   * Reverts the shared config to the legacy image styles.
   */
  public function revertToLegacy(): void {
    $this->switchAll(FALSE, []);
  }

  /**
   * Whether revertToLegacy() would change anything.
   *
   * Reverts a copy of every switched entity, so the answer follows the same
   * logic as the revert itself and the loaded entities stay unchanged. This
   * includes metatag defaults and crop type labels, which have no state.
   */
  public function hasRevertibleValues(): bool {
    foreach ($this->switchedEntities() as $entity) {
      if ($this->switchEntity(clone $entity, FALSE)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Whether the header image display uses this module's image styles.
   */
  public function isResponsive(): bool {
    $display = $this->entityTypeManager->getStorage('entity_view_display')->load('media.image.header_image');
    $component = $display instanceof EntityDisplayInterface ? $display->getComponent(self::MEDIA_IMAGE_FIELD) : NULL;
    return $component !== NULL && $this->isOwnResponsiveComponent($component);
  }

  /**
   * Describes how a switched component deviates from the standard state.
   *
   * @return array{key: string, current: string, standard: string}|null
   *   The component key ("<entity type id>:<entity id>") and short readable
   *   descriptions of the current and the standard value, or NULL if the
   *   component is in the standard or switched state, is missing, or the
   *   entity is not switched by this class.
   */
  public function deviation(ConfigEntityInterface $entity): ?array {
    $assessment = $this->componentAssessment($entity);
    if ($assessment === NULL || $assessment['state'] !== self::DEVIATING) {
      return NULL;
    }

    return [
      'key' => $this->componentKey($entity),
      'current' => $assessment['current'],
      'standard' => $assessment['standard'],
    ];
  }

  /**
   * The state of an entity's switched component.
   *
   * @return 'standard'|'switched'|'deviating'|null
   *   The state, or NULL if the component or its switched value is missing, or
   *   the entity has no component with a state (metatag defaults, crop types
   *   and entities this class does not switch).
   */
  public function componentState(ConfigEntityInterface $entity): ?string {
    return $this->componentAssessment($entity)['state'] ?? NULL;
  }

  /**
   * The states of the switched components of all switched entities.
   *
   * Metatag defaults and crop types are left out, and so are components or
   * values that are missing.
   *
   * @return array<string, 'standard'|'switched'|'deviating'>
   *   The states, keyed by component key.
   */
  public function componentStates(): array {
    $states = [];
    foreach ($this->switchedEntities() as $entity) {
      $state = $this->componentState($entity);
      if ($state !== NULL) {
        $states[$this->componentKey($entity)] = $state;
      }
    }

    return $states;
  }

  /**
   * The key of an entity's switched component: "<entity type id>:<entity id>".
   */
  private function componentKey(ConfigEntityInterface $entity): string {
    return $entity->getEntityTypeId() . ':' . $entity->id();
  }

  /**
   * Lists the deviating components of all switched entities.
   *
   * @return array<string, array{key: string, current: string, standard: string}>
   *   The deviations, keyed by component key.
   */
  public function deviations(): array {
    $deviations = [];
    foreach ($this->switchedEntities() as $entity) {
      $deviation = $this->deviation($entity);
      if ($deviation !== NULL) {
        $deviations[$deviation['key']] = $deviation;
      }
    }

    return $deviations;
  }

  /**
   * Switches one config entity in place, without saving it.
   *
   * Revert sets the OpenCulturas standard only on a component in the switched
   * state; this is the one place that decides it. Metatag defaults and crop
   * type labels have no state, their switches only replace this module's
   * token or suffix.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The config entity.
   * @param bool $toResponsive
   *   TRUE to switch to responsive, FALSE to revert.
   * @param bool $overwriteDeviation
   *   Whether switching to responsive may overwrite a deviating component.
   *   Has no effect on revert, which only touches this module's values.
   *
   * @return bool
   *   TRUE if the entity changed.
   */
  public function switchEntity(ConfigEntityInterface $entity, bool $toResponsive, bool $overwriteDeviation = FALSE): bool {
    $state = $this->componentState($entity);
    if ($toResponsive && !$overwriteDeviation && $state === self::DEVIATING) {
      return FALSE;
    }

    if (!$toResponsive && $state !== self::SWITCHED && !in_array($entity->getEntityTypeId(), self::STATELESS_ENTITY_TYPES, TRUE)) {
      return FALSE;
    }

    $id = (string) $entity->id();
    return match ($entity->getEntityTypeId()) {
      'entity_view_display' => $entity instanceof EntityDisplayInterface && $this->switchViewDisplay($entity, $id, $toResponsive),
      'entity_form_display' => $entity instanceof EntityDisplayInterface && isset(self::CROP_LISTS[$id]) && $this->switchCropList($entity, self::CROP_LISTS[$id], $toResponsive),
      'view' => $id === 'related_dates_export' && $this->switchRelatedDatesExport($entity, $toResponsive),
      'metatag_defaults' => in_array($id, ['node', 'node__date', 'taxonomy_term'], TRUE) && $this->switchMetatagDefaults($entity, $toResponsive),
      'crop_type' => in_array($id, ['16_9', '1_1', 'image_crop'], TRUE) && $this->switchCropTypeLabel($entity, $toResponsive),
      default => FALSE,
    };
  }

  /**
   * Loads, switches and saves every config entity this module switches.
   *
   * @param bool $toResponsive
   *   TRUE to switch to responsive, FALSE to revert.
   * @param list<string> $approvedKeys
   *   Keys of deviating components that may be overwritten.
   */
  private function switchAll(bool $toResponsive, array $approvedKeys): void {
    foreach ($this->switchedEntities() as $entity) {
      if ($this->switchEntity($entity, $toResponsive, in_array($this->componentKey($entity), $approvedKeys, TRUE))) {
        $entity->save();
      }
    }
  }

  /**
   * Loads every config entity this module switches.
   *
   * @return iterable<\Drupal\Core\Config\Entity\ConfigEntityInterface>
   *   The existing switched entities.
   */
  private function switchedEntities(): iterable {
    $ids = [
      'entity_view_display' => [
        ...array_keys(self::VIEW_DISPLAY_SETTINGS),
        'media.image.token',
        ...self::GALLERY_PARAGRAPH_DISPLAYS,
      ],
      'entity_form_display' => array_keys(self::CROP_LISTS),
      'view' => ['related_dates_export'],
      'metatag_defaults' => ['node', 'node__date', 'taxonomy_term'],
      'crop_type' => ['16_9', '1_1', 'image_crop'],
    ];

    foreach ($ids as $entityTypeId => $entityIds) {
      foreach ($this->entityTypeManager->getStorage($entityTypeId)->loadMultiple($entityIds) as $entity) {
        if ($entity instanceof ConfigEntityInterface) {
          yield $entity;
        }
      }
    }
  }

  /**
   * Decides the state of an entity's switched component, by entity type.
   *
   * This is the one place per kind of component that tells standard, switched
   * and deviating apart; deviation(), componentState() and switchEntity() all
   * build on it. Metatag defaults and crop type labels have no state: their
   * switches only replace this module's token or suffix and leave everything
   * else alone.
   *
   * @return array{state: 'standard'|'switched'|'deviating', current: string, standard: string}|null
   *   The state and short readable descriptions of the current and the
   *   standard value, or NULL if the component or its value is missing or the
   *   entity has no component with a state.
   */
  private function componentAssessment(ConfigEntityInterface $entity): ?array {
    $id = (string) $entity->id();
    return match ($entity->getEntityTypeId()) {
      'entity_view_display' => $entity instanceof EntityDisplayInterface ? $this->viewDisplayAssessment($entity, $id) : NULL,
      'entity_form_display' => $entity instanceof EntityDisplayInterface && isset(self::CROP_LISTS[$id]) ? $this->cropListAssessment($entity, self::CROP_LISTS[$id]) : NULL,
      'view' => $id === 'related_dates_export' ? $this->relatedDatesExportAssessment($entity) : NULL,
      default => NULL,
    };
  }

  /**
   * Decides the state of a view display component, dispatched by display ID.
   *
   * @return array{state: 'standard'|'switched'|'deviating', current: string, standard: string}|null
   *   The state and descriptions, or NULL if the component or value is missing.
   */
  private function viewDisplayAssessment(EntityDisplayInterface $display, string $id): ?array {
    if (isset(self::VIEW_DISPLAY_SETTINGS[$id])) {
      return $this->mediaImageFormatterAssessment($display, self::VIEW_DISPLAY_SETTINGS[$id]['legacy']);
    }

    if ($id === 'media.image.token') {
      $component = $display->getComponent(self::MEDIA_IMAGE_FIELD);
      return $this->valueAssessment($component['settings']['image_style'] ?? NULL, self::TOKEN_IMAGE_STYLES['legacy'], self::TOKEN_IMAGE_STYLES['responsive'], 'image style');
    }

    if (in_array($id, self::GALLERY_PARAGRAPH_DISPLAYS, TRUE)) {
      $component = $display->getComponent('field_gallery');
      return $this->valueAssessment($component['settings']['view_mode'] ?? NULL, self::GALLERY_VIEW_MODES['legacy'], self::GALLERY_VIEW_MODES['responsive'], 'view mode');
    }

    return NULL;
  }

  /**
   * Decides the state of a media image formatter.
   *
   * The standard state is the image formatter with the legacy settings, the
   * switched state the responsive image formatter with an oc_* style. The
   * image_loading setting is ignored on both sides: a different loading
   * attribute is an improvement worth keeping, not a customization to protect.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The media view display.
   * @param array<string, mixed> $legacySettings
   *   The legacy formatter settings.
   *
   * @return array{state: 'standard'|'switched'|'deviating', current: string, standard: string}|null
   *   The state and descriptions, or NULL if the component is missing.
   */
  private function mediaImageFormatterAssessment(EntityDisplayInterface $display, array $legacySettings): ?array {
    $component = $display->getComponent(self::MEDIA_IMAGE_FIELD);
    if ($component === NULL) {
      return NULL;
    }

    $state = match (TRUE) {
      $this->isOwnResponsiveComponent($component) => self::SWITCHED,
      $this->isStandardMediaImageFormatter($component, $legacySettings) => self::STANDARD,
      default => self::DEVIATING,
    };
    $current = $this->describeFormatter($component);
    $standard = $this->describeFormatter(['type' => 'image', 'settings' => $legacySettings]);
    return [
      'state' => $state,
      'current' => $state === self::DEVIATING && $current === $standard ? $current . ' (changed settings)' : $current,
      'standard' => $standard,
    ];
  }

  /**
   * Whether a media image formatter is the image formatter with legacy settings.
   *
   * @param array<string, mixed> $component
   *   The display component.
   * @param array<string, mixed> $legacySettings
   *   The legacy formatter settings.
   */
  private function isStandardMediaImageFormatter(array $component, array $legacySettings): bool {
    $settings = $component['settings'] ?? NULL;
    if (($component['type'] ?? NULL) !== 'image' || !is_array($settings)) {
      return FALSE;
    }

    unset($settings['image_loading'], $legacySettings['image_loading']);
    return $this->sortedByKey($settings) === $this->sortedByKey($legacySettings);
  }

  /**
   * Describes a formatter as its type and image style, e.g. "image: large".
   *
   * @param array<string, mixed> $component
   *   The display component.
   */
  private function describeFormatter(array $component): string {
    $type = $component['type'] ?? NULL;
    $description = is_string($type) ? $type : '(none)';
    $style = $component['settings']['responsive_image_style'] ?? $component['settings']['image_style'] ?? NULL;
    return is_string($style) ? $description . ': ' . $this->describeValue($style) : $description;
  }

  /**
   * Decides the state of a crop list, ignoring the order of the crop types.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The media form display.
   * @param array{legacy: list<string>, responsive: list<string>} $cropLists
   *   The crop lists of both states.
   *
   * @return array{state: 'standard'|'switched'|'deviating', current: string, standard: string}|null
   *   The state and descriptions, or NULL if the crop list is missing.
   */
  private function cropListAssessment(EntityDisplayInterface $display, array $cropLists): ?array {
    $component = $display->getComponent(self::MEDIA_IMAGE_FIELD);
    $cropList = $component['settings']['crop_list'] ?? NULL;
    if (!is_array($cropList)) {
      return NULL;
    }

    $cropList = array_values(array_map(static fn (mixed $cropType): string => is_scalar($cropType) ? (string) $cropType : '', $cropList));
    return [
      'state' => match (TRUE) {
        $this->isSameSet($cropList, $cropLists['legacy']) => self::STANDARD,
        $this->isSameSet($cropList, $cropLists['responsive']) => self::SWITCHED,
        default => self::DEVIATING,
      },
      'current' => 'crop types: ' . ($cropList === [] ? '(none)' : implode(', ', $cropList)),
      'standard' => 'crop types: ' . implode(', ', $cropLists['legacy']),
    ];
  }

  /**
   * Decides the state of the related_dates_export view's image style.
   *
   * @return array{state: 'standard'|'switched'|'deviating', current: string, standard: string}|null
   *   The state and descriptions, or NULL if the image style is missing.
   */
  private function relatedDatesExportAssessment(ConfigEntityInterface $view): ?array {
    /** @var array<string, array<string, mixed>> $displayOptions */
    $displayOptions = $view->get('display');
    $currentStyle = $displayOptions['default']['display_options']['fields']['field_media_image_1']['settings']['image_style'] ?? NULL;
    return $this->valueAssessment($currentStyle, self::RELATED_DATES_EXPORT_IMAGE_STYLES['legacy'], self::RELATED_DATES_EXPORT_IMAGE_STYLES['responsive'], 'image style');
  }

  /**
   * Decides the state of a single switched value.
   *
   * A missing value has no state: there is nothing to protect.
   *
   * @return array{state: 'standard'|'switched'|'deviating', current: string, standard: string}|null
   *   The state and descriptions, or NULL if the value is missing.
   */
  private function valueAssessment(mixed $current, string $legacy, string $responsive, string $label): ?array {
    if ($current === NULL) {
      return NULL;
    }

    return [
      'state' => match ($current) {
        $legacy => self::STANDARD,
        $responsive => self::SWITCHED,
        default => self::DEVIATING,
      },
      'current' => $label . ': ' . $this->describeValue($current),
      'standard' => $label . ': ' . $legacy,
    ];
  }

  /**
   * A short readable form of a config value.
   */
  private function describeValue(mixed $value): string {
    if (!is_scalar($value)) {
      return '(' . get_debug_type($value) . ')';
    }

    return (string) $value === '' ? '(none)' : (string) $value;
  }

  /**
   * Whether two lists hold the same entries, in any order.
   *
   * @param list<string> $first
   *   The first list.
   * @param list<string> $second
   *   The second list.
   */
  private function isSameSet(array $first, array $second): bool {
    sort($first);
    sort($second);
    return $first === $second;
  }

  /**
   * Sorts a mapping and all nested arrays by key.
   *
   * @param array<mixed> $value
   *   The array to sort.
   *
   * @return array<mixed>
   *   The sorted array.
   */
  private function sortedByKey(array $value): array {
    ksort($value);
    return array_map(fn (mixed $item): mixed => is_array($item) ? $this->sortedByKey($item) : $item, $value);
  }

  /**
   * Dispatches a view display to the switch for its ID.
   */
  private function switchViewDisplay(EntityDisplayInterface $display, string $id, bool $toResponsive): bool {
    if (isset(self::VIEW_DISPLAY_SETTINGS[$id])) {
      return $this->switchMediaImageFormatter($display, self::VIEW_DISPLAY_SETTINGS[$id], $toResponsive);
    }

    if ($id === 'media.image.token') {
      return $this->switchTokenDisplay($display, $toResponsive);
    }

    if (in_array($id, self::GALLERY_PARAGRAPH_DISPLAYS, TRUE)) {
      return $this->switchGalleryParagraphDisplay($display, $toResponsive);
    }

    return FALSE;
  }

  /**
   * Media view display formatter: image <-> responsive_image.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The media view display.
   * @param array{legacy: array<string, mixed>, responsive: array<string, mixed>} $settings
   *   The formatter settings of both states.
   * @param bool $toResponsive
   *   TRUE to switch to responsive, FALSE to revert.
   */
  private function switchMediaImageFormatter(EntityDisplayInterface $display, array $settings, bool $toResponsive): bool {
    $component = $display->getComponent(self::MEDIA_IMAGE_FIELD);
    if ($component === NULL) {
      return FALSE;
    }

    $switched = $component;
    $switched['type'] = $toResponsive ? 'responsive_image' : 'image';
    $switched['settings'] = $toResponsive ? $settings['responsive'] : $settings['legacy'];
    return $this->setChangedComponent($display, self::MEDIA_IMAGE_FIELD, $component, $switched);
  }

  /**
   * Media image widget crop list: legacy crop types <-> oc_* crop types.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The media form display.
   * @param array{legacy: list<string>, responsive: list<string>} $cropLists
   *   The crop lists of both states.
   * @param bool $toResponsive
   *   TRUE to switch to responsive, FALSE to revert.
   */
  private function switchCropList(EntityDisplayInterface $display, array $cropLists, bool $toResponsive): bool {
    $component = $display->getComponent(self::MEDIA_IMAGE_FIELD);
    if (!isset($component['settings']['crop_list'])) {
      return FALSE;
    }

    $switched = $component;
    $switched['settings']['crop_list'] = $toResponsive ? $cropLists['responsive'] : $cropLists['legacy'];
    return $this->setChangedComponent($display, self::MEDIA_IMAGE_FIELD, $component, $switched);
  }

  /**
   * The media.image.token view display points straight at an image style.
   */
  private function switchTokenDisplay(EntityDisplayInterface $display, bool $toResponsive): bool {
    $component = $display->getComponent(self::MEDIA_IMAGE_FIELD);
    $style = $this->switchedValue($component['settings']['image_style'] ?? NULL, self::TOKEN_IMAGE_STYLES['legacy'], self::TOKEN_IMAGE_STYLES['responsive'], $toResponsive);
    if ($component === NULL || $style === NULL) {
      return FALSE;
    }

    $component['settings']['image_style'] = $style;
    $display->setComponent(self::MEDIA_IMAGE_FIELD, $component);
    return TRUE;
  }

  /**
   * The related_dates_export view's raw image URL field.
   */
  private function switchRelatedDatesExport(ConfigEntityInterface $view, bool $toResponsive): bool {
    /** @var array<string, array<string, mixed>> $displayOptions */
    $displayOptions = $view->get('display');
    $currentStyle = $displayOptions['default']['display_options']['fields']['field_media_image_1']['settings']['image_style'] ?? NULL;
    $style = $this->switchedValue($currentStyle, self::RELATED_DATES_EXPORT_IMAGE_STYLES['legacy'], self::RELATED_DATES_EXPORT_IMAGE_STYLES['responsive'], $toResponsive);
    if ($style === NULL) {
      return FALSE;
    }

    $displayOptions['default']['display_options']['fields']['field_media_image_1']['settings']['image_style'] = $style;
    $view->set('display', $displayOptions);
    return TRUE;
  }

  /**
   * OG/Twitter metatag image tokens on node/date/taxonomy_term defaults.
   */
  private function switchMetatagDefaults(ConfigEntityInterface $defaults, bool $toResponsive): bool {
    $legacyToken = ':header_image:';
    $responsiveToken = ':oc_2_1_1200:';
    [$search, $replace] = $toResponsive ? [$legacyToken, $responsiveToken] : [$responsiveToken, $legacyToken];

    /** @var array<string, mixed> $tags */
    $tags = $defaults->get('tags');
    $switchedTags = array_map(
      static fn (mixed $value): mixed => is_string($value) ? str_replace($search, $replace, $value) : $value,
      $tags
    );

    if ($switchedTags === $tags) {
      return FALSE;
    }

    $defaults->set('tags', $switchedTags);
    return TRUE;
  }

  /**
   * Legacy crop type label: plain <-> a "(deprecated)" suffix.
   *
   * These types only read as deprecated once this module's oc_* equivalents
   * exist to replace them. On a site without the module, calling the site's
   * only crop types "deprecated" would be confusing.
   */
  private function switchCropTypeLabel(ConfigEntityInterface $cropType, bool $toResponsive): bool {
    $label = (string) $cropType->label();
    $baseLabel = str_ends_with($label, self::DEPRECATED_SUFFIX) ? substr($label, 0, -strlen(self::DEPRECATED_SUFFIX)) : $label;
    $switchedLabel = $toResponsive ? $baseLabel . self::DEPRECATED_SUFFIX : $baseLabel;
    if ($switchedLabel === $label) {
      return FALSE;
    }

    $cropType->set('label', $switchedLabel);
    return TRUE;
  }

  /**
   * Media view mode of the gallery paragraph's single-item displays.
   *
   * "default" and "slider" show one item at a time, at widths comparable to
   * teaser_big. The "gallery" view mode is sized for multi-item grid and
   * slider cells and gets upscaled there, so these switch to the wider
   * "gallery_big" view mode (oc_bounds_1600). "grid", "slider_duo" and
   * "slider_multiple" keep using "gallery" either way.
   */
  private function switchGalleryParagraphDisplay(EntityDisplayInterface $display, bool $toResponsive): bool {
    $component = $display->getComponent('field_gallery');
    $viewMode = $this->switchedValue($component['settings']['view_mode'] ?? NULL, self::GALLERY_VIEW_MODES['legacy'], self::GALLERY_VIEW_MODES['responsive'], $toResponsive);
    if ($component === NULL || $viewMode === NULL) {
      return FALSE;
    }

    $component['settings']['view_mode'] = $viewMode;
    $display->setComponent('field_gallery', $component);
    return TRUE;
  }

  /**
   * Sets a display component if it differs from the current one.
   *
   * @param \Drupal\Core\Entity\Display\EntityDisplayInterface $display
   *   The display.
   * @param string $fieldName
   *   The field name.
   * @param array<string, mixed> $current
   *   The current component.
   * @param array<string, mixed> $switched
   *   The switched component.
   */
  private function setChangedComponent(EntityDisplayInterface $display, string $fieldName, array $current, array $switched): bool {
    if ($switched === $current) {
      return FALSE;
    }

    $display->setComponent($fieldName, $switched);
    return TRUE;
  }

  /**
   * Returns the value to switch to, or NULL if the current value already is it.
   *
   * Whether a revert may touch the value is decided in switchEntity().
   */
  private function switchedValue(mixed $current, string $legacy, string $responsive, bool $toResponsive): ?string {
    $target = $toResponsive ? $responsive : $legacy;
    return $current === $target ? NULL : $target;
  }

  /**
   * Whether a view display component renders through an oc_* style.
   *
   * @param array<string, mixed> $component
   *   The display component.
   */
  private function isOwnResponsiveComponent(array $component): bool {
    $style = $component['settings']['responsive_image_style'] ?? NULL;
    return ($component['type'] ?? NULL) === 'responsive_image' && is_string($style) && str_starts_with($style, 'oc_');
  }

}
