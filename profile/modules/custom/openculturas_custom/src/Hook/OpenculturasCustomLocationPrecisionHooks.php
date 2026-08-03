<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\geofield\Plugin\Field\FieldWidget\GeofieldBaseWidget;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Keeps manually-drawn area locations from being overwritten by geocoding.
 */
class OpenculturasCustomLocationPrecisionHooks {

  /**
   * Constructs a new OpenculturasCustomLocationPrecisionHooks.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected ModuleHandlerInterface $moduleHandler,
    protected ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   *
   * Address data paragraphs with a "field_location_precision" of "manual"
   * are expected to hold a manually-drawn boundary in "field_address_location";
   * geocoder_field would otherwise overwrite it from the address text on
   * every save. This runs before geocoder_field's own hook_entity_presave(),
   * since hook_ENTITY_TYPE_presave() implementations are always invoked
   * before the generic hook_entity_presave() for the same entity.
   *
   * @see \geocoder_field_entity_presave()
   */
  #[Hook('paragraph_presave')]
  public function paragraphPresave(ParagraphInterface $paragraph): void {
    if ($paragraph->bundle() !== 'address_data' || !$paragraph->hasField('field_location_precision')) {
      return;
    }

    $request = $this->requestStack->getCurrentRequest();
    if (!$request instanceof Request) {
      return;
    }

    $is_manual = $paragraph->get('field_location_precision')->value === 'manual';
    $request->attributes->set('geocoder_presave_disabled', $is_manual);
  }

  /**
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   *
   * "Locality" is normally required on the address field (helpful for a
   * single address), but that becomes a hurdle for manually-drawn areas that
   * span several localities (e.g. neighbouring cities). Address widgets
   * build their "#field_overrides" fresh on every render from the field's
   * static settings, so it can be relaxed per paragraph here.
   *
   * @see \Drupal\address\Plugin\Field\FieldWidget\AddressDefaultWidget::formElement()
   */
  #[Hook('field_widget_single_element_address_default_form_alter')]
  public function addressWidgetSingleElementFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    if (!empty($context['default']) || !($context['items'] ?? NULL) instanceof FieldItemListInterface) {
      return;
    }

    $items = $context['items'];
    if ($items->getFieldDefinition()->getName() !== 'field_address') {
      return;
    }

    $paragraph = $items->getEntity();
    if (!$paragraph instanceof ParagraphInterface || $paragraph->bundle() !== 'address_data' || !$paragraph->hasField('field_location_precision')) {
      return;
    }

    if ($paragraph->get('field_location_precision')->value === 'manual' && isset($element['address']['#field_overrides']['locality'])) {
      $element['address']['#field_overrides']['locality'] = FieldOverride::OPTIONAL;
    }
  }

  /**
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   *
   * Rebuilds only the sibling "field_address" widget when the precision
   * radios change, so "Locality"'s required-ness updates immediately without
   * touching (and re-initializing) the Leaflet map widget on
   * "field_address_location".
   */
  #[Hook('field_widget_single_element_options_buttons_form_alter')]
  public function precisionWidgetSingleElementFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    if (!empty($context['default']) || !($context['items'] ?? NULL) instanceof FieldItemListInterface) {
      return;
    }

    $items = $context['items'];
    if ($items->getFieldDefinition()->getName() !== 'field_location_precision') {
      return;
    }

    $paragraph = $items->getEntity();
    if (!$paragraph instanceof ParagraphInterface || $paragraph->bundle() !== 'address_data') {
      return;
    }

    $element['#ajax'] = [
      'callback' => [self::class, 'precisionChangeAjaxCallback'],
      'event' => 'change',
      'progress' => ['type' => 'none'],
    ];
  }

  /**
   * Ajax callback: replaces the "field_address" field container.
   *
   * Targeting the field container (not its inner "widget" key) matches the
   * "...-field-address-wrapper" selector that is actually rendered in the
   * DOM.
   */
  public static function precisionChangeAjaxCallback(array $form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $triggering_element = $form_state->getTriggeringElement();
    if (!isset($triggering_element['#array_parents'])) {
      return $response;
    }

    // The clicked radio input lives at
    // [...subform, 'field_location_precision', 'widget', <option key>];
    // step back to the subform to reach the sibling field_address widget.
    $subform_parents = array_slice($triggering_element['#array_parents'], 0, -3);
    $address_widget = NestedArray::getValue($form, array_merge($subform_parents, ['field_address']));
    if (is_array($address_widget) && isset($address_widget['#attributes']['data-drupal-selector'])) {
      $selector = sprintf('[data-drupal-selector="%s"]', $address_widget['#attributes']['data-drupal-selector']);
      $response->addCommand(new ReplaceCommand($selector, $address_widget));
    }

    return $response;
  }

  /**
   * Implements hook_leaflet_default_widget_alter().
   *
   * Reuses the "OpenCulturas Map" module's configured starting position, so
   * a brand-new location's map opens on the platform's usual area instead of
   * Leaflet's built-in fallback (which is the middle of the Atlantic).
   */
  #[Hook('leaflet_default_widget_alter')]
  public function leafletDefaultWidgetAlter(array &$map, GeofieldBaseWidget $widget): void {
    $id = $map['id'] ?? '';
    if (!str_contains((string) $id, 'address-data') || !str_contains((string) $id, 'field-address-location')) {
      return;
    }

    if (!$this->moduleHandler->moduleExists('openculturas_map')) {
      return;
    }

    $settings = $this->configFactory->get('openculturas_map.settings');
    $lat = $settings->get('start_lat_position');
    $lon = $settings->get('start_lng_position');
    $zoom = $settings->get('start_zoom_position');
    if ($lat === NULL || $lon === NULL || $zoom === NULL) {
      return;
    }

    $map['settings']['center'] = ['lat' => (float) $lat, 'lon' => (float) $lon];
    $map['settings']['zoom'] = (int) $zoom;
  }

}
