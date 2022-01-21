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

  $form['openculturas_base']['primary_color'] = [
    '#type' => 'color',
    '#title' => t('Primary color'),
    '#default_value' => theme_get_setting('primary_color'),
  ];

}
