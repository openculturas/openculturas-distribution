<?php

namespace Drupal\migrate_reservix\Plugin\migrate\process;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_import\Plugin\migrate\process\EntityImportProcessInterface;
use Drupal\entity_import\Plugin\migrate\process\EntityImportProcessTrait;
use Drupal\migrate\Plugin\migrate\process\Download;

/**
 * Define the entity import machine name plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "migrate_reservix_entity_import_download",
 *   label = @Translation("Download")
 * )
 */
class EntityImportDownload extends Download implements EntityImportProcessInterface {

  use EntityImportProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfigurations() {
    return [
      'source_url',
      'destination_uri',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['source_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source URL'),
      '#required' => TRUE,
      '#default_value' => $configuration['source_url'],
      '#description' => $this->t('The source URL to download.'),
      '#size' => 255,
    ];
    $form['destination_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination URI'),
      '#required' => TRUE,
      '#default_value' => $configuration['destination_uri'],
      '#description' => $this->t('The destination URI to download the file to.'),
      '#size' => 255,
    ];
    return $form;
  }

}
