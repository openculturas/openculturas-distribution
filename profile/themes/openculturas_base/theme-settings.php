<?php

/**
 * @file
 * Theme settings form for OpenCulturas Base theme.
 */

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function openculturas_base_form_system_theme_settings_alter(&$form, &$form_state) {

  $form['openculturas_base'] = [
    '#type' => 'details',
    '#title' => t('OpenCulturas Base'),
    '#open' => TRUE,
  ];

  // Get config schema definition for reading labels from there.
  // We use the schema as base for the editable config so it is not defined redundantly.
  $definition = \Drupal::service('config.typed')->getDefinition('openculturas_base.settings');

  foreach($definition['mapping'] as $key => $schema) {

    // Add color fields to theme config.
    if (strpos($key, 'color-') !== false) {
      $form['openculturas_base'][$key] = [
        '#type' => 'color',
        '#title' => $definition['mapping'][$key]["label"],
        '#default_value' => theme_get_setting($key),
      ];
    }
  }

}
