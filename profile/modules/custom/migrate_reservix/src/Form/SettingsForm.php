<?php

namespace Drupal\migrate_reservix\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Migrate Reservix settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_reservix_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['migrate_reservix.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('The API key '),
      '#default_value' => $this->config('migrate_reservix.settings')->get('api_key'),
    ];
    $form['latitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Latitude'),
      '#description' => $this->t('The default latitude value for supporting endpoints'),
      '#default_value' => $this->config('migrate_reservix.settings')->get('latitude'),
    ];
    $form['longitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Longitude'),
      '#description' => $this->t('The default longitude value for supporting endpoints'),
      '#default_value' => $this->config('migrate_reservix.settings')->get('longitude'),
    ];
    $form['radius'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Radius'),
      '#description' => $this->t('The default radius value for supporting endpoints'),
      '#default_value' => $this->config('migrate_reservix.settings')->get('radius'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('migrate_reservix.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('latitude', $form_state->getValue('latitude'))
      ->set('longitude', $form_state->getValue('longitude'))
      ->set('radius', $form_state->getValue('radius'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
