<?php

declare(strict_types=1);

namespace Drupal\geofield_proximity_filter_extra\Plugin\GeofieldProximitySource;

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
 *   description = @Translation("Geocodes origin from combine fields."),
 *   exposedDescription = @Translation("Geocode origin from combine fields."),
 *   context = {
 *    "filter",
 *   },
 * )
 */
final class GeocodeOriginFromCombine extends GeocodeOrigin {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(array &$form, FormStateInterface $form_state, array $options_parents, $is_exposed = FALSE) {
    parent::buildOptionsForm($form, $form_state, $options_parents, $is_exposed);

    if (!$is_exposed) {

      $form['settings']['#access'] = FALSE;
      $form['use_autocomplete']['#access'] = FALSE;

      $filters = array_filter($this->viewHandler->view->getDisplay()->getHandlers('filter'), function(\Drupal\views\Plugin\views\filter\FilterPluginBase $filter) {
        return $filter instanceof \Drupal\geofield_proximity_filter_extra\Plugin\views\filter\GeocodeOriginCombine;
      });
      $filter_options = [];
      foreach ($filters as $id => $filter) {
        $filter_options[$id] = $filter->adminLabel();
      }
      $form['geocode_origin_combine_filter'] = [
        '#type' => 'select',
        '#title' => $this->t('Geocode origin combine filter'),
        '#options' => $filter_options,
        '#default_value' => $this->configuration['geocode_origin_combine_filter'] ?? NULL,
      ];
      $form['show_find_my_button'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable a find my location button'),
        '#default_value' => $this->configuration['show_find_my_button'] ?? TRUE,
      ];

    }
    else {
      $view_args = !empty(array_filter($this->viewHandler->view->args)) ? implode('||', $this->viewHandler->view->args) : 0;
      unset($form['origin_address']['#attributes']['class']);
      $form['origin_address']['#autocomplete_route_name'] = 'geofield_geocode_origin_combine.autocomplete';
      $form['origin_address']['#autocomplete_route_parameters'] = [
        'view_name' => $this->viewHandler->view->storage->get('id'),
        'view_display' => $this->viewHandler->view->current_display,
        'filter_name' => $this->configuration['geocode_origin_combine_filter'] ?? NULL,
        'view_args' => $view_args,
      ];
      if (isset($this->configuration['show_find_my_button']) && $this->configuration['show_find_my_button']) {
        // Workaround for https://www.drupal.org/project/drupal/issues/289240.
        $form['find_my_location'] = [
          '#type' => 'html_tag',
          '#tag' => 'button',
          '#value' => $this->t('Find my location'),
          '#attributes' => [
            'type' => 'button',
            'class' => ['button', 'button--locate_me']
          ],
          '#after_build' => [[$this, 'findMyLocationAfterBuild']],
        ];
      }

    }
  }

  public function findMyLocationAfterBuild(array $element, FormStateInterface $form_state) {
    $form = $form_state->getCompleteForm();
    array_pop($element['#array_parents']);
    $key_exists = NULL;
    $container = NestedArray::getValue($form, $element['#array_parents'], $key_exists);
    if ($key_exists === FALSE || !array_key_exists('origin_address', $container)) {
      $element['#access'] = FALSE;
      return $element;
    }
    $element['#attributes']['data-target-selector-id'] = $container['origin_address']['#attributes']['data-drupal-selector'];
    $element['#attached']['library'][] = 'geofield_proximity_filter_extra/locate_me';
    $element['#attached']['drupalSettings'] = [
      'geocode_origin_autocomplete' => [
        'providers' => array_keys($this->getEnabledProviderPlugins()),
      ],
    ];

    return $element;
  }

}
