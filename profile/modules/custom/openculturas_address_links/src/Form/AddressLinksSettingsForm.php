<?php

declare(strict_types=1);

namespace Drupal\openculturas_address_links\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class AddressLinksSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openculturas_address_links_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('openculturas_address_links.settings');

    $token_types = ['language', 'geofield', 'address'];

    $form['directions'] = [
      '#type' => 'url',
      '#title' => $this->t('Directions service base URL'),
      '#description' => $this->t('Enter the base URL of your directions provider. Tokens are supported.'),
      '#default_value' => $config->get('directions'),
      '#element_validate' => ['token_element_validate'],
      '#token_types' => $token_types,
    ];

    $form['public_transport'] = [
      '#type' => 'url',
      '#title' => $this->t('Public transport base URL'),
      '#description' => $this->t('Enter the base URL of your public transport provider. Tokens are supported.'),
      '#default_value' => $config->get('public_transport'),
      '#element_validate' => ['token_element_validate'],
      '#token_types' => $token_types,
    ];

    $form['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => $token_types,
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('openculturas_address_links.settings');
    foreach (['directions', 'public_transport'] as $key) {
      $value = $form_state->getValue($key);
      if ($value) {
        $config->set($key, $value);
      }
      else {
        $config->set($key, NULL);
      }
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'openculturas_address_links.settings',
    ];
  }

}
