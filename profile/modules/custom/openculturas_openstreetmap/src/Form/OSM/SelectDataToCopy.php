<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Form\OSM;

use Drupal\Core\Form\FormStateInterface;

final class SelectDataToCopy extends WizardFormTableBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'select_data';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    /** @var array|null $cached_values */
    $cached_values = $form_state->getTemporaryValue('wizard');
    if (!$cached_values) {
      return $form;
    }

    foreach ($form['data'] as $field_name => &$table_row) {
      if (isset($cached_values['data'][$field_name]['operation'])) {
        $table_row['operation']['#default_value'] = $cached_values['data'][$field_name]['operation'];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $keys = [
      'data',
    ];
    /** @var array|null $cached_values */
    $cached_values = $form_state->getTemporaryValue('wizard');
    foreach ($keys as $key) {
      $cached_values[$key] = $form_state->getValue($key);
    }

    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
