<?php

declare(strict_types=1);

namespace Drupal\openculturas_map\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\openculturas_map\Plugin\Form\OpenCulturasMapFilterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id = "openculturas_map_block",
 *   admin_label = @Translation("OpenCulturas - Map"),
 * )
 */
final class OpenCulturasMapBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  private FormBuilderInterface $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'type' => 'locations',
      'show_filter' => TRUE,
      'show_results' => TRUE,
      'refresh_results_on_user_interaction' => TRUE,
      'control_locate' => TRUE,
      'control_geocode' => TRUE,
      'control_reset' => TRUE,
      'start_lat_position' => 0,
      'start_lng_position' => 0,
      'start_zoom_position' => 0,
      'show_radius' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): OpenCulturasMapBlock {
    $instance = new OpenCulturasMapBlock($configuration, $plugin_id, $plugin_definition);
    $instance->formBuilder = $container->get('form_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $block = [];
    $block['filter'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'openculturas--map--filter--form',
        ],
      ],
    ];
    $block['filter']['form'] = $this->formBuilder->getForm(OpenCulturasMapFilterForm::class);

    $block['map'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'openculturas--map--leaflet',
        ],
      ],
    ];

    $block['results'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'openculturas--map--results--list',
        ],
      ],
    ];

    if ($this->configuration['type'] === 'dates') {
      $block['results']['#attributes']['class'][] = 'view-related-date';
    }

    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['type'] = [
      '#title' => $this->t('Type'),
      '#type' => 'select',
      '#options' => [
        'locations' => $this->t('Locations'),
      ],
      '#default_value' => $this->configuration['type'],
      '#weight' => 0,
    ];

    $form['show_filter'] = [
      '#title' => $this->t('Show filter'),
      '#description' => $this->t('Enable to offer a collapsible form with filters.'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['show_filter'],
    ];

    $form['show_results'] = [
      '#title' => $this->t('Show results'),
      '#description' => $this->t('Enable to show a results list below the map.'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['show_results'],
    ];

    $form['refresh_results_on_user_interaction'] = [
      '#title' => $this->t('Refresh results only on user interaction'),
      '#description' => $this->t('Disable to continuously refresh the results list during map interaction. Enable to offer a button for manual update of the result list.'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['refresh_results_on_user_interaction'],
    ];

    $form['show_radius'] = [
      '#title' => $this->t('Show search radius'),
      '#description' => $this->t('Deselect/select the map interaction that results in a visualization of the search radius. This supports understanding that the visual map excerpt may exceed that radius.'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => [
        'init' => $this->t('After map initialization'),
        'move' => $this->t('After map move'),
        'locate' => $this->t('After locate (GPS)'),
        'geocode' => $this->t('After geocode'),
      ],
      '#default_value' => $this->configuration['show_radius'] ?? ['init', 'move', 'locate', 'geocode'],
      '#weight' => 0,
    ];

    $form['controls'] = [
      '#type' => 'details',
      '#title' => $this->t('Map control settings'),
    ];

    $form['controls']['control_locate'] = [
      '#title' => $this->t('Allow Locate'),
      '#description' => $this->t('Enable to expose a locator button that will trigger the corresponding web browser function.'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['control_locate'],
    ];

    $form['controls']['control_geocode'] = [
      '#title' => $this->t('Allow Geocode'),
      '#description' => $this->t('Enable to expose a search button that will trigger geocoding of an entered location.'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['control_geocode'],
    ];

    $form['controls']['control_reset'] = [
      '#title' => $this->t('Allow Reset'),
      '#description' => $this->t('Enable to expose a button that will reset the map to its default latitude, longitude, and zoom level.'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['control_reset'],
    ];

    $form['overrides'] = [
      '#type' => 'details',
      '#title' => $this->t('Override global settings'),
    ];

    $form['overrides']['start_lat_position'] = [
      '#group' => 'location',
      '#type' => 'number',
      '#step' => 0.01,
      '#title' => $this->t('Latitude coordinate (initial)'),
      '#default_value' => $this->configuration['start_lat_position'],
    ];

    $form['overrides']['start_lng_position'] = [
      '#group' => 'location',
      '#type' => 'number',
      '#step' => 0.01,
      '#title' => $this->t('Longitude coordinate (initial)'),
      '#default_value' => $this->configuration['start_lng_position'],
    ];

    $form['overrides']['start_zoom_position'] = [
      '#title' => $this->t('Zoom level (initial)'),
      '#description' => $this->t('The zoom level at which the map will start. For orientation: 5 would cover most of Europe, 14 an average city'),
      '#type' => 'number',
      '#default_value' => $this->configuration['start_zoom_position'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {

    $blockSettings = ['type', 'show_results', 'show_filter', 'refresh_results_on_user_interaction'];
    foreach ($blockSettings as $blockSetting) {
      $this->configuration[$blockSetting] = $form_state->getValue($blockSetting);
    }

    $this->configuration['show_radius'] = array_values($form_state->getValue('show_radius'));

    $controlSettings = ['control_locate', 'control_geocode', 'control_reset'];
    foreach ($controlSettings as $controlSetting) {
      $this->configuration[$controlSetting] = $form_state->getValue('controls')[$controlSetting] ?? 0;
    }

    $overrideSettings = ['start_lat_position', 'start_lng_position', 'start_zoom_position'];
    foreach ($overrideSettings as $overrideSetting) {
      if ($form_state->getValue('overrides')[$overrideSetting] > 0) {
        $this->configuration[$overrideSetting] = $form_state->getValue('overrides')[$overrideSetting];
      }
    }
  }

}
