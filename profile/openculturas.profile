<?php

declare(strict_types = 1);

/**
 * @file
 * Enables modules and site configuration.
 */

/**
 * Implements hook_form_FORM_ID_alter().
 */
function openculturas_form_install_configure_form_alter(array &$form): void {
  $form['site_information']['site_name']['#placeholder'] = 'OpenCulturas';
  $form['site_information']['site_name']['#value'] = 'OpenCulturas';
}
