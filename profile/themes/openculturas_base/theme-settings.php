<?php

declare(strict_types=1);

use Drupal\Core\File\Exception\FileException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function openculturas_base_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state, ?string $form_id = NULL): void {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  $form['background_image'] = [
    '#type' => 'details',
    '#title' => t('Background image'),
  ];
  $form['background_image']['background_image_mode'] = [
    '#type'          => 'radios',
    '#default_value' => theme_get_setting('background_image.mode') ?? 'mood_image',
    '#description' => t('An image can be appended behind the content to cover the viewport background.'),
    '#options' => [
      'no_image' => t('None'),
      'mood_image' => t('Use header image from content page'),
      'global_image' => t('Use global image globally (upload here)'),
    ],
  ];
  $form['background_image']['background_image_path'] = [
    '#type' => 'textfield',
    '#title' => t('Path to custom background image'),
    '#default_value' => theme_get_setting('background_image.path'),
    '#states' => [
      'visible' => [
        ':input[name="background_image_mode"]' => ['value' => 'global_image'],
      ],
    ],
  ];

  $element = &$form['background_image']['background_image_path'];
  $friendly_path = NULL;
  $original_path = $element['#default_value'];
  $default = 'background.jpg';
  if (is_string($original_path) && StreamWrapperManager::getScheme($original_path) === 'public') {
    $friendly_path = StreamWrapperManager::getTarget($original_path);
    $element['#default_value'] = $friendly_path;
  }

  $element['#description'] = t('Examples: <code>@implicit-public-file</code> (for a file in the public filesystem), <code>@explicit-file</code>.', [
    '@implicit-public-file' => $friendly_path ?? $default,
    '@explicit-file' => is_string($original_path) && StreamWrapperManager::getScheme($original_path) !== FALSE ? $original_path : 'public://' . $default,
  ]);

  $form['background_image']['background_image_upload'] = [
    '#type' => 'file',
    '#title' => t('Upload background image'),
    '#description' => t("If you don't have direct file access to the server, use this field to upload your image."),
    '#upload_validators' => [
      'file_validate_is_image' => [],
    ],
    '#states' => [
      'visible' => [
        ':input[name="background_image_mode"]' => ['value' => 'global_image'],
      ],
    ],
  ];

  $form['#validate'][] = 'openculturas_base_form_system_theme_settings_validate';
  $form['#submit'][] = 'openculturas_base_form_system_theme_settings_form_submit';
}

/**
 * Validation handler for openculturas_base_form_system_theme_settings_alter().
 */
function openculturas_base_form_system_theme_settings_validate(array &$form, FormStateInterface $form_state): void {
  if (isset($form['background_image']['background_image_upload'])) {
    $file = _file_save_upload_from_form($form['background_image']['background_image_upload'], $form_state, 0);
    if ($file) {
      // Put the temporary file in form_values, so we can save it on submit.
      $form_state->setValue('background_image_upload', $file);
    }
  }

  if ($form_state->getValue('background_image_mode') !== 'global_image') {
    $form_state->unsetValue('background_image_path');
  }

  $background_image_path = $form_state->getValue('background_image_path');
  if ($background_image_path) {
    $path = is_string($background_image_path) ? _openculturas_base_form_system_theme_settings_validate_path($background_image_path) : FALSE;
    if (!$path) {
      $form_state->setErrorByName('background_image_path', (string) t('The custom image path is invalid.'));
    }
  }
}

/**
 * Submit handler for openculturas_base_form_system_theme_settings_alter().
 */
function openculturas_base_form_system_theme_settings_form_submit(array &$form, FormStateInterface $form_state): void {
  // If the user uploaded a new logo or favicon, save it to a permanent location
  // and use it in place of the default theme-provided file.
  $default_scheme = \Drupal::config('system.file')->get('default_scheme');
  /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
  $fileSystem = \Drupal::service('file_system');
  $values = $form_state->getValues();
  $config = \Drupal::configFactory()->getEditable($values['config_key']);
  $config->set('background_image.mode', $values['background_image_mode']);
  try {
    if (!empty($values['background_image_upload'])) {
      $filename = $fileSystem->copy($values['background_image_upload']->getFileUri(), $default_scheme . '://');
      $config->set('background_image.path', $filename);
      $values['background_image_path'] = $filename;
    }
  }
  catch (FileException) {
    // Ignore.
  }

  if (isset($values['background_image_path']) && is_string($values['background_image_path'])) {
    $config->set('background_image.path', _openculturas_base_form_system_theme_settings_validate_path($values['background_image_path']));
  }

  if ($values['background_image_mode'] === 'global_image' && (!is_string($values['background_image_path']) || $values['background_image_path'] === '')) {
    $config->clear('background_image.path');
    $config->set('background_image.mode', 'mood_image');
  }

  if ($values['background_image_mode'] !== 'global_image') {
    $config->clear('background_image.path');
  }

  $config->save();
  $form_state->unsetValue('background_image_path');
  $form_state->unsetValue('background_image_upload');
  $form_state->unsetValue('background_image_mode');
}

function _openculturas_base_form_system_theme_settings_validate_path(string $path): false|string {
  /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
  $fileSystem = \Drupal::service('file_system');
  // Absolute local file paths are invalid.
  if ($fileSystem->realpath($path) === $path) {
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
