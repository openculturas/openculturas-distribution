<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_popover_swiffy_slider\ExistingSite;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\FormState;
use Drupal\entity_popover_swiffy_slider\FieldPopoverBuilder;
use Drupal\entity_popover_swiffy_slider\Hook\EntityPopoverSwiffySliderHooks;
use Drupal\entity_popover_swiffy_slider\PopoverFormatterSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Checks what saving the popover settings form stores for the chosen formatter.
 *
 * The 'formatter_settings' subform is built from the formatter selected when
 * the form was built, so a formatter change would otherwise leave the previous
 * formatter's values under the newly stored formatter — a config schema
 * violation, since the schema types those settings by the stored formatter.
 *
 * It also checks the other half of the form's job: a popover that is not
 * enabled must leave nothing behind. Both embedding contexts store the
 * submitted values verbatim, and every key below 'third_party_settings' turns
 * into a module dependency of the display, so leftover values would make
 * displays without any popover depend on this module.
 *
 * The validator is invoked directly rather than through a browser: it only
 * reads and writes form state values under its element's '#parents', which is
 * exactly the part that differs between Field UI's settings subform and Layout
 * Builder's block form.
 */
#[Group('openculturas')]
final class PopoverSettingsFormValidationTest extends ExistingSiteBase {

  /**
   * The display used for the dependency check.
   */
  private const string DISPLAY_ID = 'node.profile.teaser';

  /**
   * The display configuration to restore after the dependency check.
   *
   * @var array<string, mixed>|null
   */
  private ?array $savedDisplayConfiguration = NULL;

  /**
   * The formatter the settings subform is built from.
   */
  private const string SLIDER_FORMATTER = 'swiffy_slider_entity_reference';

  /**
   * A formatter without any of the slider's settings.
   */
  private const string OTHER_FORMATTER = 'entity_reference_entity_view';

  /**
   * A setting only the slider formatter defines.
   */
  private const string SLIDER_SETTING = 'swiffy_slider_permalink';

  /**
   * A setting only an entity view formatter defines.
   */
  private const string VIEW_MODE_SETTING = 'view_mode';

  /**
   * Where the settings form finds itself in Field UI's settings subform.
   *
   * @var list<string>
   */
  private const array FIELD_UI_PARENTS = [
    'settings',
    'third_party_settings',
    'entity_popover_swiffy_slider',
    'details',
  ];

  /**
   * Where the same form finds itself in Layout Builder's block form.
   *
   * Not invented: this is what building the real
   * \Drupal\layout_builder\Plugin\Block\FieldBlock form for
   * "field_block:node:event:field_gallery" yields.
   *
   * @var list<string>
   */
  private const array LAYOUT_BUILDER_PARENTS = [
    'formatter',
    'third_party_settings',
    'entity_popover_swiffy_slider',
    'details',
  ];

  /**
   * The settings form's own validator.
   *
   * @var \Drupal\entity_popover_swiffy_slider\Hook\EntityPopoverSwiffySliderHooks
   */
  private EntityPopoverSwiffySliderHooks $hooks;

  /**
   * The module's own popover service under test.
   */
  private PopoverFormatterSettings $popoverFormatterSettings;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function setUp(): void {
    parent::setUp();

    $this->popoverFormatterSettings = $this->container->get(PopoverFormatterSettings::class);
    $this->hooks = new EntityPopoverSwiffySliderHooks(
      $this->container->get('plugin.manager.field.formatter'),
      $this->popoverFormatterSettings,
    );
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function tearDown(): void {
    if ($this->savedDisplayConfiguration !== NULL) {
      $this->container->get('config.factory')
        ->getEditable('core.entity_view_display.' . self::DISPLAY_ID)
        ->setData($this->savedDisplayConfiguration)
        ->save();
    }

    parent::tearDown();
  }

  /**
   * A formatter change stores the new formatter's defaults, not the old values.
   *
   * Run in both nesting contexts the form is embedded in.
   */
  #[DataProvider('formContexts')]
  public function testFormatterChangeStoresTheNewFormattersDefaults(array $parents): void {
    $formState = $this->submit($parents, self::SLIDER_FORMATTER, self::OTHER_FORMATTER, [
      self::VIEW_MODE_SETTING => 'gallery',
      self::SLIDER_SETTING => 'https://example.com/slider',
    ]);

    $settings = $formState->getValue([...$parents, 'formatter_settings']);
    $this->assertSame(
      $this->popoverFormatterSettings->filter(self::OTHER_FORMATTER, []),
      $settings,
      'The submitted settings of the previous formatter did not survive the change.',
    );
    $this->assertArrayNotHasKey(
      self::SLIDER_SETTING,
      $settings,
      'The slider preset is not a setting of the newly chosen formatter.',
    );
  }

  /**
   * Keeping the formatter stores its submitted settings, minus foreign keys.
   */
  #[DataProvider('formContexts')]
  public function testUnchangedFormatterKeepsItsOwnSettings(array $parents): void {
    $formState = $this->submit($parents, self::OTHER_FORMATTER, self::OTHER_FORMATTER, [
      self::VIEW_MODE_SETTING => 'gallery_big',
      self::SLIDER_SETTING => 'https://example.com/slider',
    ]);

    $settings = $formState->getValue([...$parents, 'formatter_settings']);
    $this->assertSame('gallery_big', $settings[self::VIEW_MODE_SETTING]);
    $this->assertArrayNotHasKey(
      self::SLIDER_SETTING,
      $settings,
      'The settings of the unchanged formatter were kept, without the keys it does not have.',
    );
  }

  /**
   * Clearing the selection stores no settings at all.
   */
  #[DataProvider('formContexts')]
  public function testEmptySelectionStoresNoSettings(array $parents): void {
    $formState = $this->submit($parents, self::SLIDER_FORMATTER, '', [
      self::VIEW_MODE_SETTING => 'gallery',
    ]);

    $this->assertNull(
      $formState->getValue([...$parents, 'formatter_settings']),
      'Without a chosen formatter there are no settings to store.',
    );
  }

  /**
   * The settings form is only shown for the formatter it was built for.
   *
   * The form cannot rebuild itself when the select changes, so the settings
   * of the previous formatter stay on screen. They are hidden by '#states'
   * instead: the select's name is matched by suffix, because the two contexts
   * this form is embedded in nest it differently.
   */
  public function testSettingsFormIsOnlyVisibleForItsOwnFormatter(): void {
    $details = $this->hookElement();

    $this->assertSame('container', $details['formatter_settings']['#type'] ?? NULL, 'The settings form has a wrapper #states can hide.');
    $this->assertSame(
      [
        'visible' => [
          ':input[name$="[entity_popover_swiffy_slider][details][formatter]"]' => ['value' => self::SLIDER_FORMATTER],
        ],
      ],
      $details['formatter_settings']['#states'] ?? NULL,
    );
  }

  /**
   * A popover that is not enabled leaves nothing behind for the display.
   *
   * Not 'enable' => FALSE, and not an empty array either: both are stored
   * under 'third_party_settings', and both make the display depend on this
   * module.
   */
  #[DataProvider('formContexts')]
  public function testDisabledPopoverIsRemovedFromTheSubmission(array $parents): void {
    $formState = $this->submit($parents, self::SLIDER_FORMATTER, self::SLIDER_FORMATTER, [
      self::VIEW_MODE_SETTING => 'gallery',
    ], FALSE);

    $moduleParents = array_slice($parents, 0, -1);
    $this->assertFalse(
      NestedArray::keyExists($formState->getValues(), $moduleParents),
      'The values of a popover that is not enabled are still part of the submission.',
    );
    $this->assertFalse(
      $formState->hasValue($moduleParents),
      'The values of a popover that is not enabled can still be read from the form state.',
    );
  }

  /**
   * A popover that is not enabled makes a real display depend on no module.
   *
   * The submitted values go through the same validator the form runs, and
   * their outcome is written into an actual display component — what both
   * embedding contexts save verbatim — so the dependency is the one a site
   * builder would really end up with.
   */
  public function testDisabledPopoverLeavesNoModuleDependency(): void {
    $displayStorage = $this->container->get('entity_type.manager')->getStorage('entity_view_display');
    $displayConfigurationName = 'core.entity_view_display.' . self::DISPLAY_ID;
    $this->savedDisplayConfiguration = $this->container->get('config.factory')->get($displayConfigurationName)->getRawData();

    $display = $displayStorage->load(self::DISPLAY_ID);
    $this->assertInstanceOf(EntityDisplayInterface::class, $display);
    $this->assertNotContains(
      FieldPopoverBuilder::SETTINGS_KEY,
      $display->getDependencies()['module'],
      'The display under test already depends on this module.',
    );

    $formState = $this->submit(self::FIELD_UI_PARENTS, self::OTHER_FORMATTER, self::OTHER_FORMATTER, [
      self::VIEW_MODE_SETTING => 'gallery',
    ], FALSE);
    $moduleParents = array_slice(self::FIELD_UI_PARENTS, 0, -1);

    $component = $display->getComponent(self::FIELD_NAME);
    $submitted = NestedArray::getValue($formState->getValues(), $moduleParents);
    $component['third_party_settings'] = $submitted === NULL
      ? []
      : [FieldPopoverBuilder::SETTINGS_KEY => $submitted];
    $display->setComponent(self::FIELD_NAME, $component);
    $display->save();

    $displayStorage->resetCache([self::DISPLAY_ID]);
    $saved = $displayStorage->load(self::DISPLAY_ID);
    $this->assertInstanceOf(EntityDisplayInterface::class, $saved);
    $savedComponent = $saved->getComponent(self::FIELD_NAME);
    $this->assertNotNull($savedComponent, 'The display under test has no field component.');
    $this->assertArrayNotHasKey(
      FieldPopoverBuilder::SETTINGS_KEY,
      $savedComponent['third_party_settings'] ?? [],
      'The display stores settings for a popover that is not enabled.',
    );
    $this->assertNotContains(
      FieldPopoverBuilder::SETTINGS_KEY,
      $saved->getDependencies()['module'],
      'A display without a popover depends on this module.',
    );
  }

  /**
   * A bundle with its own popover field stores nothing for this module.
   *
   * On such a bundle the hook only adds a notice. A form without any input
   * still submits an empty array under the module's key, so the notice's
   * validate callback removes it. Otherwise the display would store an empty
   * setting and depend on this module, the same config noise as an unchecked
   * checkbox.
   */
  public function testNativePopoverFieldStoresNothing(): void {
    $fieldDefinitions = $this->container->get('entity_field.manager')->getFieldDefinitions('paragraph', self::NATIVE_FIELD_BUNDLE);
    $this->assertArrayHasKey(self::NATIVE_FIELD_NAME, $fieldDefinitions);
    $fieldDefinition = $fieldDefinitions[self::NATIVE_FIELD_NAME];

    $plugin = $this->container->get('plugin.manager.field.formatter')->createInstance(self::OTHER_FORMATTER, [
      'field_definition' => $fieldDefinition,
      'view_mode' => 'default',
      'settings' => [self::VIEW_MODE_SETTING => 'default'],
      'label' => 'hidden',
      'third_party_settings' => [],
    ]);
    $this->assertInstanceOf(FormatterInterface::class, $plugin);

    $element = $this->hooks->thirdPartySettingsForm($plugin, $fieldDefinition, 'default', [], new FormState());
    $this->assertSame(
      ['entity_popover_native_field_notice'],
      array_keys($element),
      'A bundle with its own popover field is only offered a notice.',
    );
    $this->assertArrayHasKey('#element_validate', $element['entity_popover_native_field_notice']);

    $noticeParents = [...array_slice(self::FIELD_UI_PARENTS, 0, -1), 'entity_popover_native_field_notice'];
    $formState = new FormState();
    $formState->setValues(self::nest(array_slice(self::FIELD_UI_PARENTS, 0, -1), []));

    $notice = $element['entity_popover_native_field_notice'];
    $notice['#parents'] = $noticeParents;
    foreach ($notice['#element_validate'] as $callback) {
      $callback($notice, $formState);
    }

    $this->assertFalse(
      NestedArray::keyExists($formState->getValues(), array_slice(self::FIELD_UI_PARENTS, 0, -1)),
      'The notice-only form still submits an empty array under the module key.',
    );
  }

  /**
   * The 'details' element the hook builds for a popover already saved.
   *
   * @return array
   *   The 'details' element.
   */
  private function hookElement(): array {
    $fieldDefinitions = $this->container->get('entity_field.manager')->getFieldDefinitions('node', self::FIELD_BUNDLE);
    $this->assertArrayHasKey(self::FIELD_NAME, $fieldDefinitions);
    $fieldDefinition = $fieldDefinitions[self::FIELD_NAME];

    $plugin = $this->container->get('plugin.manager.field.formatter')->createInstance(self::SLIDER_FORMATTER, [
      'field_definition' => $fieldDefinition,
      'view_mode' => 'teaser',
      'settings' => [self::VIEW_MODE_SETTING => 'teaser', self::SLIDER_SETTING => NULL],
      'label' => 'hidden',
      'third_party_settings' => [
        FieldPopoverBuilder::SETTINGS_KEY => [
          'enable' => TRUE,
          'details' => [
            'formatter' => self::SLIDER_FORMATTER,
            'formatter_settings' => [
              self::VIEW_MODE_SETTING => self::SAVED_VIEW_MODE,
              self::SLIDER_SETTING => 'https://example.com/slider',
            ],
          ],
        ],
      ],
    ]);
    $this->assertInstanceOf(FormatterInterface::class, $plugin);

    $element = $this->hooks->thirdPartySettingsForm($plugin, $fieldDefinition, 'teaser', [], new FormState());
    $this->assertArrayHasKey('details', $element);

    return $element['details'];
  }

  /**
   * An entity reference field on a node bundle without a native popover field.
   */
  private const string FIELD_NAME = 'field_portrait';

  /**
   * The bundle of that field.
   */
  private const string FIELD_BUNDLE = 'profile';

  /**
   * The view mode the popover settings were saved with.
   */
  private const string SAVED_VIEW_MODE = 'gallery_big';

  /**
   * An entity reference field on a bundle with its own popover field.
   */
  private const string NATIVE_FIELD_NAME = 'field_gallery';

  /**
   * The bundle of that field.
   */
  private const string NATIVE_FIELD_BUNDLE = 'gallery';

  /**
   * The nesting contexts the settings form is built in.
   *
   * @return array<string, array{list<string>}>
   *   The '#parents' of the 'details' element per context.
   */
  public static function formContexts(): array {
    return [
      'Field UI settings subform' => [self::FIELD_UI_PARENTS],
      'Layout Builder block form' => [self::LAYOUT_BUILDER_PARENTS],
    ];
  }

  /**
   * Runs the validator over a prepared form state.
   *
   * @param array<int, string> $parents
   *   The '#parents' of the 'details' element.
   * @param string $settingsFormatter
   *   The formatter the settings subform was built for.
   * @param string $submittedFormatter
   *   The formatter the site builder submitted.
   * @param array<string, mixed> $submittedSettings
   *   The settings values on the submitted form.
   * @param bool $enabled
   *   Whether the submitted form has the popover enabled.
   *
   * @return \Drupal\Core\Form\FormState
   *   The form state after validation.
   */
  private function submit(array $parents, string $settingsFormatter, string $submittedFormatter, array $submittedSettings, bool $enabled = TRUE): FormState {
    $formState = new FormState();
    $formState->setValues(self::nest($parents, [
      'formatter' => $submittedFormatter,
      'formatter_settings' => $submittedSettings,
    ]));
    // 'enable' is a sibling of 'details', not one of its children.
    $formState->setValue([...array_slice($parents, 0, -1), 'enable'], $enabled);

    $element = [
      '#parents' => $parents,
      '#popover_settings_formatter' => $settingsFormatter,
    ];

    EntityPopoverSwiffySliderHooks::validateDetails($element, $formState);

    return $formState;
  }

  /**
   * Spreads a set of values into a form state's array under a parent path.
   *
   * @param array<int, string> $parents
   *   The parent path the values live under.
   * @param array<string, mixed> $values
   *   The values to set.
   *
   * @return array<mixed>
   *   The values nested under the parent path.
   */
  private static function nest(array $parents, array $values): array {
    return $parents === [] ? $values : [array_shift($parents) => self::nest($parents, $values)];
  }

}
