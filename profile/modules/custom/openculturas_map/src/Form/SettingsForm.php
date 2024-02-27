<?php

declare(strict_types=1);

namespace Drupal\openculturas_map\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for the OpenCulturas map configuration.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  private FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->fileUrlGenerator = $container->get('file_url_generator');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openculturas_map_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['openculturas_map.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('openculturas_map.settings');

    $form['start_position'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Starting position'),
    ];

    $form['start_position']['start_lat_position'] = [
      '#group' => 'location',
      '#type' => 'number',
      '#step' => 0.01,
      '#title' => $this->t('Starting latitude coordinate of map'),
      '#default_value' => $config->get('start_lat_position'),
      '#required' => TRUE,
    ];

    $form['start_position']['start_lng_position'] = [
      '#group' => 'location',
      '#type' => 'number',
      '#step' => 0.01,
      '#title' => $this->t('Starting longitude coordinate of map'),
      '#default_value' => $config->get('start_lng_position'),
      '#required' => TRUE,
    ];

    $form['start_position']['start_zoom_position'] = [
      '#group' => 'location',
      '#type' => 'number',
      '#min' => 1,
      '#max' => 19,
      '#title' => $this->t('Starting zoom level on map (1 - 19)'),
      '#default_value' => $config->get('start_zoom_position'),
      '#required' => TRUE,
    ];

    $form['marker_icon'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Marker icon'),
    ];

    $form['marker_icon']['marker_icon_upload'] = [
      '#group' => 'marker_icon',
      '#type' => 'file',
      '#title' => $this->t('Upload the icon which will be used as marker on the map'),
      '#default_value' => $config->get('marker_icon_path'),
      '#element_validate' => ['::validateUpload'],
    ];

    $form['marker_icon']['marker_icon_width'] = [
      '#group' => 'marker_icon',
      '#type' => 'number',
      '#title' => $this->t('Width of the marker icon (in pixels)'),
      '#default_value' => $config->get('marker_icon_width'),
      '#required' => TRUE,
    ];

    $form['marker_icon']['marker_icon_height'] = [
      '#group' => 'marker_icon',
      '#type' => 'number',
      '#title' => $this->t('Height of the marker icon (in pixels)'),
      '#default_value' => $config->get('marker_icon_height'),
      '#required' => TRUE,
    ];

    $form['marker_icon']['marker_anchor_width'] = [
      '#group' => 'marker_icon',
      '#type' => 'number',
      '#title' => $this->t('Point of the marker width which will correspond to markers location (in pixels)'),
      '#default_value' => $config->get('marker_anchor_width'),
      '#required' => TRUE,
    ];

    $form['marker_icon']['marker_anchor_height'] = [
      '#group' => 'marker_icon',
      '#type' => 'number',
      '#title' => $this->t('Point of the marker height which will correspond to markers location (in pixels)'),
      '#default_value' => $config->get('marker_anchor_height'),
      '#required' => TRUE,
    ];

    $form['marker_icon']['marker_anchor_popup_width'] = [
      '#group' => 'marker_icon',
      '#type' => 'number',
      '#title' => $this->t('Point from which the popup should open relative to the marker width (in pixels)'),
      '#default_value' => $config->get('marker_anchor_popup_width'),
      '#required' => TRUE,
    ];

    $form['marker_icon']['marker_anchor_popup_height'] = [
      '#group' => 'marker_icon',
      '#type' => 'number',
      '#title' => $this->t('Point from which the popup should open relative to the marker height (in pixels)'),
      '#default_value' => $config->get('marker_anchor_popup_height'),
      '#required' => TRUE,
    ];

    $form['marker_cluster'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Marker cluster'),
    ];

    $form['marker_cluster']['marker_cluster_anchor_popup_width'] = [
      '#group' => 'marker_cluster',
      '#type' => 'number',
      '#title' => $this->t('Point from which the popup should open relative to the marker width (in pixels)'),
      '#default_value' => $config->get('marker_cluster_anchor_popup_width'),
      '#required' => TRUE,
    ];

    $form['marker_cluster']['marker_cluster_anchor_popup_height'] = [
      '#group' => 'marker_cluster',
      '#type' => 'number',
      '#title' => $this->t('Point from which the popup should open relative to the marker height (in pixels)'),
      '#default_value' => $config->get('marker_cluster_anchor_popup_height'),
      '#required' => TRUE,
    ];

    $form['radius'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Radius'),
    ];

    $form['radius']['radius_base'] = [
      '#group' => 'radius',
      '#type' => 'number',
      '#step' => 0.00001,
      '#title' => $this->t('Base number for radius calculation: higher = bigger radius'),
      '#default_value' => $config->get('radius_base'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);

  }

  public static function validateUpload(array &$element, FormStateInterface $form_state): void {
    $validators = [
      'file_validate_extensions' => ['jpg jpeg png gif svg webp'],
      'file_validate_size' => [1_048_576],
    ];

    $directoryPath = 'public://openculturas_map';
    $nameOfUploadField = 'marker_icon_upload';
    $uploadedFile = file_save_upload($nameOfUploadField, $validators, FALSE, 0, FileSystemInterface::EXISTS_REPLACE);
    if ($uploadedFile) {
      $existingDirectory = \Drupal::service('file_system')->prepareDirectory($directoryPath);

      if (!$existingDirectory) {
        \Drupal::service('file_system')->prepareDirectory($directoryPath, FileSystemInterface::CREATE_DIRECTORY);
      }

      if (!$uploadedFile instanceof FileInterface) {
        $form_state->setErrorByName($nameOfUploadField, (string) t('Invalid file uploaded!'));
        return;
      }

      $destination = $directoryPath . '/' . $uploadedFile->getFilename();
      $savedFile = \Drupal::service('file.repository')->move($uploadedFile, $destination, FileSystemInterface::EXISTS_REPLACE);
      $savedImage = \Drupal::service('image.factory')->get($savedFile->getFileUri());
      $form_state->setValue('marker_icon_upload', $destination);
      $form_state->setValue('marker_icon_width', $savedImage->getWidth() ?? 25);
      $form_state->setValue('marker_icon_height', $savedImage->getHeight() ?? 34);
      $form_state->setValue('marker_anchor_width', round($form_state->getValue('marker_icon_width') / 2));
      $form_state->setValue('marker_anchor_height', $form_state->getValue('marker_icon_height'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('openculturas_map.settings');
    $config->set('start_lat_position', $form_state->getValue('start_lat_position'));
    $config->set('start_lng_position', $form_state->getValue('start_lng_position'));
    $config->set('start_zoom_position', $form_state->getValue('start_zoom_position'));
    $config->set('marker_icon_path', ($form_state->getValue('marker_icon_upload') && $form_state->getValue('marker_icon_upload') === "/default") ? "default" : $this->fileUrlGenerator->generate($form_state->getValue('marker_icon_upload'))->toString());
    $config->set('marker_icon_width', $form_state->getValue('marker_icon_width'));
    $config->set('marker_icon_height', $form_state->getValue('marker_icon_height'));
    $config->set('marker_anchor_width', $form_state->getValue('marker_anchor_width'));
    $config->set('marker_anchor_height', $form_state->getValue('marker_anchor_height'));
    $config->set('marker_anchor_popup_width', $form_state->getValue('marker_anchor_popup_width'));
    $config->set('marker_anchor_popup_height', $form_state->getValue('marker_anchor_popup_height'));
    $config->set('marker_cluster_anchor_popup_width', $form_state->getValue('marker_cluster_anchor_popup_width'));
    $config->set('marker_cluster_anchor_popup_height', $form_state->getValue('marker_cluster_anchor_popup_height'));
    $config->set('radius_base', $form_state->getValue('radius_base'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
