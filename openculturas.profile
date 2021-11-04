<?php

/**
 * @file
 * Enables modules and site configuration.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function openculturas_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  $form['site_information']['site_name']['#placeholder'] = t('OpenCulturas');
  $form['site_information']['site_name']['#value'] = t('OpenCulturas');
}
