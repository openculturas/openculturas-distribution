<?php

namespace Drupal\migrate_reservix\Plugin\migrate\process;

use Drupal\entity_import\Plugin\migrate\process\EntityImportProcessTrait;
use Drupal\entity_import\Plugin\migrate\process\EntityImportProcessInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\ProcessPluginBase;

/**
 * Define migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "migrate_reservix_process_error_log",
 *   label = @Translation("Error log debug"),
 * )
 */
class ErrorLogDebug extends ProcessPluginBase implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfigurations(): array {
    return ['method' => 'debug'];
  }

  /**
   * Callback method.
   */
  public function debug($value) {
    error_log(print_r($value, TRUE));
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['description'] = [
      '#type' => '#markup',
      '#markup' => $this->t('There are no configuration options for this plugin.'),
    ];
    return $form;
  }

}
