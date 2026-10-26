<?php

declare(strict_types=1);

namespace Drupal\entity_popover_swiffy_slider\Hook;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\OrderAfter;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_popover_swiffy_slider\FieldPopoverBuilder;
use Drupal\entity_popover_swiffy_slider\PopoverFormatterSettings;

/**
 * Adds an alternate-formatter popover to entity reference fields.
 *
 * For bundles with no wrapping paragraph to attach the generic
 * entity_popover field to (e.g. field_gallery on bare node bundles), this
 * augments whichever formatter is currently live for the field with a
 * third-party setting: an alternate formatter (any formatter applicable to
 * the field, freely chosen — not necessarily Swiffy Slider) rendered inside
 * the same shared popover dialog as entity_popover's own field.
 *
 * Rendering for Layout Builder's own field blocks is handled separately, by
 * \Drupal\entity_popover_swiffy_slider\EventSubscriber\WrapFieldBlockPopover
 * — Layout Builder bypasses classic entity rendering (and
 * hook_entity_view_alter()) entirely for its own blocks.
 */
final class EntityPopoverSwiffySliderHooks {

  use StringTranslationTrait;

  public function __construct(
    private readonly FormatterPluginManager $formatterPluginManager,
    private readonly PopoverFormatterSettings $popoverFormatterSettings,
  ) {}

  /**
   * Implements hook_field_formatter_third_party_settings_form().
   */
  #[Hook('field_formatter_third_party_settings_form')]
  public function thirdPartySettingsForm(FormatterInterface $plugin, FieldDefinitionInterface $fieldDefinition, string $viewMode, array $form, FormStateInterface $formState): array {
    if ($fieldDefinition->getType() !== 'entity_reference') {
      return [];
    }

    if (FieldPopoverBuilder::hasNativePopoverField($fieldDefinition)) {
      // entity_popover's own field is already the go-to mechanism on this
      // bundle; this formatter-level setting only exists as a fallback for
      // bundles with no wrapping entity to attach that field to. Hide it
      // rather than let a site builder configure two independent popovers
      // for the same entity — FieldPopoverBuilder::wrapFieldWithPopover()
      // also refuses to act in this situation, in case a value was already
      // stored here before the native field was added.
      return [
        'entity_popover_native_field_notice' => [
          '#type' => 'item',
          '#markup' => $this->t('This bundle already has a dedicated "Enable popover" field. Configure the popover there instead of on this field.'),
          // A form that contributes nothing but this notice still submits an
          // empty array under this module's key, which would be stored and
          // make the display depend on this module for nothing. Dropping it
          // here — before anything is saved — is what keeps that dependency
          // away, for a bundle that never configures a popover on the
          // formatter level.
          '#element_validate' => [[self::class, 'validateNoticeOnly']],
        ],
      ];
    }

    $settings = $plugin->getThirdPartySettings(FieldPopoverBuilder::SETTINGS_KEY);
    $enabled = !empty($settings['enable']);
    // 'formatter'/'formatter_settings' live nested under 'details' rather
    // than as direct siblings of 'enable': nesting them under a real
    // '#type' => 'details' element (rather than a #prefix/#suffix-only
    // <details> tag) is what lets #states hide the whole thing, summary
    // included, when the checkbox is unchecked — #states can only toggle a
    // real element's own wrapper, not arbitrary surrounding #prefix/#suffix
    // markup. This does add 'details' as a real segment in the stored data
    // path (config schema and all reads below are written to match).
    $nested = $settings['details'] ?? [];

    $element = [];
    $element['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable popover'),
      '#default_value' => $enabled,
    ];

    $formatterOptions = $this->formatterPluginManager->getOptions($fieldDefinition->getType());
    // Default to the field's own currently-active formatter when nothing
    // has been explicitly chosen for the popover yet: it's always a valid
    // option (it's already running on this field), and saves a
    // select-then-save-then-reopen round trip for the common case of
    // reusing the same formatter with different settings for the dialog.
    $selectedFormatter = $nested['formatter'] ?? $plugin->getPluginId();

    // The suffix selector (rather than a full '#parents'-based path) is
    // deliberate: this form gets embedded inside two structurally different
    // parents depending on context — Field UI's classic per-field settings
    // subform, and Layout Builder's own block configuration form. Including
    // this module's own settings key in the suffix (Field UI automatically
    // nests each module's third-party settings form under its own module
    // name) keeps the match specific to this checkbox, rather than any
    // other module's own, possibly identically-named, "enable" field
    // nested in the same settings subform.
    $element['details'] = [
      '#type' => 'details',
      '#title' => $this->t('Popover settings'),
      '#open' => $enabled,
      '#states' => [
        'visible' => [
          ':input[name$="[' . FieldPopoverBuilder::SETTINGS_KEY . '][enable]"]' => ['checked' => TRUE],
        ],
      ],
      // Which formatter the 'formatter_settings' subform below belongs to.
      // The form cannot rebuild itself when the select changes, so the two
      // can disagree on submit — the settings form of the old formatter is
      // then hidden by #states rather than shown as if it belonged to the
      // new one, and validateDetails() stores the new formatter's defaults
      // instead of the values that were on the form.
      '#popover_settings_formatter' => $selectedFormatter,
      // A static callback, not [$this, …]: an Ajax submission of either
      // context puts this form into the form cache, which serializes the
      // callback, and the formatter plugin manager behind this service
      // reaches the database connection, which is not serializable.
      '#element_validate' => [[self::class, 'validateDetails']],
    ];

    $element['details']['formatter'] = [
      '#type' => 'select',
      '#title' => $this->t('Popover formatter'),
      '#description' => $this->t('The formatter used to render this field inside the popover dialog — any formatter valid for this field, independent of the one used for its normal display above. After changing this selection, save the display and reopen this settings form to configure the newly selected formatter; its own settings only appear once the selection has been saved.'),
      '#options' => $formatterOptions,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $selectedFormatter,
    ];

    if ($selectedFormatter !== '' && isset($formatterOptions[$selectedFormatter])) {
      // Likewise, seed the nested formatter's own settings from the live
      // formatter's current settings when it's the one selected (whether
      // by the default above or an explicit, matching choice) and nothing
      // has been saved for the popover yet — otherwise the nested settings
      // form would show that formatter's generic defaults instead of the
      // values already visible in its normal display, which would be an
      // odd first impression for a "reuse this same formatter" choice.
      $nestedSettings = $nested['formatter_settings'] ?? ($selectedFormatter === $plugin->getPluginId() ? $plugin->getSettings() : []);

      $alternateFormatter = $this->formatterPluginManager->getInstance([
        'field_definition' => $fieldDefinition,
        'view_mode' => $viewMode,
        'configuration' => [
          'type' => $selectedFormatter,
          'settings' => $nestedSettings,
          'label' => 'hidden',
          'third_party_settings' => [],
        ],
      ]);

      if ($alternateFormatter) {
        // Deliberately calling settingsForm() directly rather than also
        // invoking hook_field_formatter_third_party_settings_form() for
        // this nested formatter: it would otherwise also pick up unrelated
        // third-party settings (e.g. field_formatter_class's own CSS class
        // field), which don't apply to a formatter that only ever renders
        // inside this dialog.
        $element['details']['formatter_settings'] = $alternateFormatter->settingsForm($form, $formState);
        $element['details']['formatter_settings']['#tree'] = TRUE;
        // A settings form is only shown for the formatter it was built
        // from: without this, picking another formatter on the form above
        // would leave the previous formatter's settings on screen, looking
        // like they belonged to the new one. The suffix selector keeps the
        // match specific to this module's own form, whichever of the two
        // nesting contexts it is embedded in — see the 'details' element
        // above.
        $element['details']['formatter_settings']['#states'] = [
          'visible' => [
            ':input[name$="[' . FieldPopoverBuilder::SETTINGS_KEY . '][details][formatter]"]' => ['value' => $selectedFormatter],
          ],
        ];
        // The settings form of a formatter can be a bare array of elements
        // without a '#type' of its own (swiffy_slider's is), which has no
        // wrapper for #states to hide. A container supplies one.
        if (empty($element['details']['formatter_settings']['#type'])) {
          $element['details']['formatter_settings']['#type'] = 'container';
        }
      }
    }

    return $element;
  }

  /**
   * Reconciles the submitted settings with the submitted popover formatter.
   *
   * The 'formatter_settings' subform was built from the formatter selected
   * when the form was built, so after a formatter change it still carries
   * the previous formatter's values — which the newly stored formatter's
   * config schema would reject. Written back through the element's own
   * '#parents', since this form is nested differently depending on whether
   * Field UI's settings subform or Layout Builder's block form embeds it.
   *
   * An unchecked 'enable' removes this module's values from the submission
   * entirely, so that a display which uses no popover does not gain a
   * dependency on this module.
   *
   * @param array $element
   *   The 'details' element, with '#popover_settings_formatter' set to the
   *   formatter its settings subform was built from.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state to correct the values in.
   */
  public static function validateDetails(array &$element, FormStateInterface $formState): void {
    $moduleParents = self::moduleParents($element);

    if (empty($formState->getValue([...$moduleParents, 'enable']))) {
      // Not enabled: none of these settings are used, and keeping them would
      // cost the display a module dependency it does not need. Returning
      // before any setValueForElement() call is what keeps the removed path
      // from being written back.
      $formState->unsetValue($moduleParents);
      return;
    }

    $formatter = (string) ($formState->getValue([...$element['#parents'], 'formatter']) ?? '');
    $settingsParents = [...$element['#parents'], 'formatter_settings'];

    if ($formatter === '') {
      $formState->unsetValue($settingsParents);
      return;
    }

    if ($formatter === $element['#popover_settings_formatter']) {
      $settings = (array) ($formState->getValue($settingsParents) ?? []);
      $formState->setValueForElement(['#parents' => $settingsParents], self::popoverFormatterSettings()->filter($formatter, $settings));
      return;
    }

    // A different formatter was chosen: the values on the form belong to the
    // old one, so the new formatter starts from its own defaults. Its settings
    // form only appears after saving and reopening, which is what the select's
    // description tells the site builder.
    $formState->setValueForElement(['#parents' => $settingsParents], self::popoverFormatterSettings()->filter($formatter, []));
  }

  /**
   * Removes this module's settings where its form has nothing to configure.
   *
   * A form that contributes nothing but a notice still submits an empty array
   * under this module's key, and both embedding contexts store that verbatim
   * (EntityDisplayFormBase::copyFormValuesToEntity() and FieldBlock), so that
   * empty array alone would make the display depend on this module.
   *
   * @param array $element
   *   The notice element carrying the validate callback.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state to remove the settings from.
   */
  public static function validateNoticeOnly(array $element, FormStateInterface $formState): void {
    $formState->unsetValue(self::moduleParents($element));
  }

  /**
   * The path this module's own settings live under.
   *
   * The element's own '#parents' without its last segment: the third-party
   * settings subform this module's form is nested in. Deriving it from the
   * element instead of hardcoding it keeps the validators working in both
   * nesting contexts.
   *
   * @param array $element
   *   An element of this module's settings form.
   *
   * @return list<string>
   *   The parents of this module's settings subform.
   */
  private static function moduleParents(array $element): array {
    return array_values(array_slice($element['#parents'], 0, -1));
  }

  /**
   * The settings filter, for the static validator above.
   *
   * @return \Drupal\entity_popover_swiffy_slider\PopoverFormatterSettings
   *   The module's own service.
   */
  private static function popoverFormatterSettings(): PopoverFormatterSettings {
    return \Drupal::service(PopoverFormatterSettings::class);
  }

  /**
   * Implements hook_field_formatter_settings_summary_alter().
   */
  #[Hook('field_formatter_settings_summary_alter')]
  public function settingsSummaryAlter(array &$summary, array $context): void {
    if (FieldPopoverBuilder::hasNativePopoverField($context['field_definition'])) {
      return;
    }

    $settings = $context['formatter']->getThirdPartySettings(FieldPopoverBuilder::SETTINGS_KEY);
    $formatter = $settings['details']['formatter'] ?? '';
    if (!empty($settings['enable']) && $formatter !== '') {
      $summary[] = $this->t('Popover: @formatter', ['@formatter' => $formatter]);
    }
  }

  /**
   * Implements hook_entity_view_alter().
   *
   * Covers classic (non-Layout Builder) entity rendering, where all of an
   * entity's fields are still siblings within one render array. Runs after
   * field_group, so a dialog can join its field's group.
   */
  #[Hook('entity_view_alter', order: new OrderAfter(modules: ['field_group']))]
  public function entityViewAlter(array &$build): void {
    foreach (Element::children($build) as $fieldName) {
      FieldPopoverBuilder::wrapFieldWithPopover($build, $fieldName, $this->popoverFormatterSettings);
    }
  }

}
