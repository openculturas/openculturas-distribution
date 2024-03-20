<?php

declare(strict_types=1);

namespace Drupal\openculturas_map\Form;

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\file\FileInterface;
use Drupal\file\FileRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function file_save_upload;
use function is_string;
use function round;
use function trim;

/**
 * Settings form for the OpenCulturas map configuration.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected ImageFactory $imageFactory;

  /**
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected FileRepositoryInterface $fileRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->fileSystem = $container->get('file_system');
    $instance->imageFactory = $container->get('image.factory');
    $instance->fileRepository = $container->get('file.repository');
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

    $form['marker_icon']['marker_icon_default'] = [
      '#group' => 'marker_icon',
      '#type' => 'checkbox',
      '#title' => $this->t('Use the marker icon supplied by the module'),
      '#default_value' => $config->get('marker_icon_default'),
    ];

    $form['marker_icon']['marker_icon_path'] = [
      '#group' => 'marker_icon',
      '#type' => 'textfield',
      '#title' => $this->t('Path to custom marker icon'),
      '#default_value' => $config->get('marker_icon_path'),
      '#states' => [
        'visible' => [
          ':input[name="marker_icon_default"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $element = &$form['marker_icon']['marker_icon_path'];
    $friendly_path = NULL;
    $original_path = $element['#default_value'];
    $default = 'openculturas_map/map_maker.svg';
    if (is_string($original_path) && StreamWrapperManager::getScheme($original_path) === 'public') {
      $friendly_path = StreamWrapperManager::getTarget($original_path);
      $element['#default_value'] = $friendly_path;
    }

    $element['#description'] = $this->t('Examples: <code>@implicit-public-file</code> (for a file in the public filesystem), <code>@explicit-file</code>.', [
      '@implicit-public-file' => $friendly_path ?? $default,
      '@explicit-file' => is_string($original_path) && StreamWrapperManager::getScheme($original_path) !== FALSE ? $original_path : 'public://' . $default,
    ]);
    $form['marker_icon']['marker_icon_upload'] = [
      '#group' => 'marker_icon',
      '#type' => 'file',
      '#title' => $this->t('Upload the icon which will be used as marker on the map'),
      '#states' => [
        'visible' => [
          ':input[name="marker_icon_default"]' => ['checked' => FALSE],
        ],
      ],
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

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    if ($form_state->getValue('marker_icon_default')) {
      return;
    }

    if ($form_state->getValue('marker_icon_path')) {
      $path = $this->validatePath(ltrim($form_state->getValue('marker_icon_path'), '/'));
      if (!$path) {
        $form_state->setErrorByName('marker_icon_path', (string) $this->t('The custom marker path is invalid.'));
        return;
      }
    }

    if (!$form_state->getValue('marker_icon_upload')) {
      return;
    }

    $validators = [
      'FileIsImage' => [],
      'FileSizeLimit' => ['fileLimit' => 1_048_576],
    ];

    $directoryPath = 'public://openculturas_map';
    $nameOfUploadField = 'marker_icon_upload';
    $uploadedFile = file_save_upload($nameOfUploadField, $validators, FALSE, 0, FileSystemInterface::EXISTS_REPLACE);
    if ($uploadedFile instanceof FileInterface) {
      $existingDirectory = $this->fileSystem->prepareDirectory($directoryPath);

      if (!$existingDirectory) {
        $this->fileSystem->prepareDirectory($directoryPath, FileSystemInterface::CREATE_DIRECTORY);
      }

      $destination = $directoryPath . '/' . $uploadedFile->getFilename();
      try {
        $savedFile = $this->fileRepository->move($uploadedFile, $destination, FileSystemInterface::EXISTS_REPLACE);
      }
      catch (\Throwable $exception) {
        $form_state->setErrorByName($nameOfUploadField, $exception->getMessage());
        return;
      }

      $savedImage = $this->imageFactory->get($savedFile->getFileUri());
      $form_state->setValue('marker_icon_upload', $savedFile);
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
    $values = $form_state->getValues();
    $file_uri = NULL;
    try {
      if (isset($values['marker_icon_upload']) && $values['marker_icon_upload'] instanceof FileInterface) {
        $file_uri = $values['marker_icon_upload']->getFileUri();
      }
    }
    catch (FileException) {
      // Ignore.
    }

    $values['marker_icon_path'] = ltrim(trim($values['marker_icon_path']), '/');
    if ($values['marker_icon_path'] !== '') {
      $file_uri = $values['marker_icon_path'];
    }

    if ($file_uri) {
      $valid_path = $this->validatePath($file_uri);
      if ($valid_path) {
        $config->set('marker_icon_path', $valid_path);
      }
      else {
        $file_uri = NULL;
      }
    }

    if (!$file_uri || $form_state->getValue('marker_icon_default')) {
      $config->set('marker_icon_path', NULL);
      $config->set('marker_icon_default', TRUE);
      // Resets this values.
      $config->set('marker_icon_width', 25);
      $config->set('marker_icon_height', 34);
      $config->set('marker_anchor_width', 13);
      $config->set('marker_anchor_height', 34);
    }
    else {
      $config->set('marker_icon_default', $form_state->getValue('marker_icon_default'));
      $config->set('marker_icon_width', $form_state->getValue('marker_icon_width'));
      $config->set('marker_icon_height', $form_state->getValue('marker_icon_height'));
      $config->set('marker_anchor_width', $form_state->getValue('marker_anchor_width'));
      $config->set('marker_anchor_height', $form_state->getValue('marker_anchor_height'));
    }

    $config->set('start_lat_position', $form_state->getValue('start_lat_position'));
    $config->set('start_lng_position', $form_state->getValue('start_lng_position'));
    $config->set('start_zoom_position', $form_state->getValue('start_zoom_position'));
    $config->set('marker_anchor_popup_width', $form_state->getValue('marker_anchor_popup_width'));
    $config->set('marker_anchor_popup_height', $form_state->getValue('marker_anchor_popup_height'));
    $config->set('marker_cluster_anchor_popup_width', $form_state->getValue('marker_cluster_anchor_popup_width'));
    $config->set('marker_cluster_anchor_popup_height', $form_state->getValue('marker_cluster_anchor_popup_height'));
    $config->set('radius_base', $form_state->getValue('radius_base'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

  protected function validatePath(string $path): false|string {
    // Absolute local file paths are invalid.
    if ($this->fileSystem->realpath($path) === $path) {
      return FALSE;
    }

    // A path relative to the Drupal root or a fully qualified URI is valid.
    if (is_file($path)) {
      return $path;
    }

    // Prepend 'public://' for relative file paths within public filesystem.
    if (StreamWrapperManager::getScheme($path) === FALSE) {
      $path = 'public://' . $path;
    }

    if (is_file($path)) {
      return $path;
    }

    return FALSE;
  }

}
