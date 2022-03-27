<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Plugin\GeofieldProximitySource;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geocoder\Plugin\GeofieldProximitySource\GeocodeOrigin;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_pop;
use function implode;

/**
 * Defines 'Geocode Origin (with Autocomplete option from combine fields)' proximity source plugin.
 *
 * @GeofieldProximitySource(
 *   id = "geofield_geocode_origin_combine",
 *   label = @Translation("Geocode Origin (with Autocomplete option from combine fields)"),
 *   description = @Translation("Geocodes origin from free text input, with Autocomplete option."),
 *   exposedDescription = @Translation("Geocode origin from free text input, with Autocomplete option."),
 *   context = {},
 * )
 */
final class GeocodeOriginFromCombine extends GeocodeOrigin {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(array &$form, FormStateInterface $form_state, array $options_parents, $is_exposed = FALSE) {
    parent::buildOptionsForm($form, $form_state, $options_parents, $is_exposed);

    if (!$is_exposed) {

      $filters = array_filter($this->viewHandler->view->getDisplay()->getHandlers('filter'), function(\Drupal\views\Plugin\views\filter\FilterPluginBase $filter) {
        return $filter->canExpose() && $filter->isExposed() === FALSE;
      });
      $filter_options = [];
      foreach ($filters as $id => $filter) {
        $filter_options[$id] = $filter->adminLabel();
      }
      $form['settings']['autocomplete']['filtersource'] = [
        '#type' => 'select',
        '#title' => $this->t('Data source'),
        '#options' => $filter_options,
        '#default_value' => $this->configuration['settings']['autocomplete']['filtersource'] ?? NULL,
      ];

      $stop = 1;
    }
    else {
      #return;
      // Add autocomplete path to the exposed textfield.
      $view_args = !empty(array_filter($this->viewHandler->view->args)) ? implode('||', $this->viewHandler->view->args) : 0;
      unset($form['origin_address']['#attributes']['class']);
      $form['origin_address']['#autocomplete_route_name'] = 'geofield_geocode_origin_combine.autocomplete';
      $form['origin_address']['#autocomplete_route_parameters'] = [
        'view_name' => $this->viewHandler->view->storage->get('id'),
        'view_display' => $this->viewHandler->view->current_display,
        'filter_name' => $this->configuration['settings']['autocomplete']['filtersource'] ?? NULL,
        'view_args' => $view_args,
      ];
      $form['locate_me'] = [
        '#type' => 'button',
        '#value' => $this->t('Find my location'),
        '#limit_validation_errors' => [],
        '#after_build' => [[$this, 'locateMeAfterBuild']],
        '#button_type' => 'locate_me',
        '#name' => 'locate',
        '#is_button' => FALSE,
      ];
    }
  }

  public function locateMeAfterBuild(array $element, FormStateInterface $form_state) {
    $form = $form_state->getCompleteForm();
    array_pop($element['#array_parents']);
    $key_exists = NULL;
    $container = NestedArray::getValue($form, $element['#array_parents'], $key_exists);
    if ($key_exists === FALSE || !array_key_exists('origin_address', $container)) {
      $element['#access'] = FALSE;
      return $element;
    }
    $element['#attributes']['data-target-selector-id'] = $container['origin_address']['#attributes']['data-drupal-selector'];
    $element['#attached']['library'][] = 'openculturas_custom/locate_me';
    $element['#attached']['library'][] = 'geocoder/geocoder';
    $element['#attached']['drupalSettings'] = [
      'geocode_origin_autocomplete' => [
        'providers' => array_keys($this->getEnabledProviderPlugins()),
      ],
    ];

    return $element;
  }

}
