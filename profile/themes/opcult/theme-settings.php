<?php

declare(strict_types=1);

use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\file\FileInterface;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function opcult_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state, ?string $form_id = NULL): void {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  /** @var \Drupal\Core\Extension\ThemeSettingsProvider $themeSettingsProvider */
  $themeSettingsProvider = \Drupal::service(ThemeSettingsProvider::class);

  $form['background_image'] = [
    '#type' => 'details',
    '#title' => t('Background image'),
  ];
  $form['background_image']['background_image_mode'] = [
    '#type'          => 'radios',
    '#default_value' => $themeSettingsProvider->getSetting('background_image.mode') ?? 'mood_image',
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
    '#default_value' => $themeSettingsProvider->getSetting('background_image.path'),
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

  $form['hero_layout'] = [
    '#type' => 'details',
    '#title' => t('Hero section layout'),
  ];
  $form['hero_layout']['hero_layout'] = [
    '#type'          => 'radios',
    '#default_value' => $themeSettingsProvider->getSetting('hero_layout') ?? 'oc_hero_layout_1',
    '#description' => t('Basic layout of main image and title block.'),
    '#options' => [
      'oc_hero_layout_1' => t('Image with title block inset'),
      'oc_hero_layout_2' => t('Image above title block'),
      'oc_hero_layout_3' => t('Title block above image'),
      'oc_hero_layout_4' => t('Title block side by side with image'),
      'oc_hero_layout_custom' => t('Custom (stacked blocks)'),
    ],
  ];

  /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfoService */
  $bundleInfoService = \Drupal::service('entity_type.bundle.info');

  /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository */
  $entityDisplayRepository = \Drupal::service('entity_display.repository');

  /** @var array{string:array} $allViewModes */
  $allViewModes = $entityDisplayRepository->getAllViewModes();

  $form['layout_builder'] = [
    '#type' => 'details',
    '#title' => t('Layout builder'),
    '#tree' => TRUE,
  ];
  $added_view_modes_migration = FALSE;
  foreach ($allViewModes as $entityTypeId => $viewModes) {
    if (!isset($viewModes['full_lb'])) {
      continue;
    }

    $form['layout_builder'][$entityTypeId] = [
      '#type' => 'details',
      '#title' => $entityTypeId,
    ];
    /** @var array<string, array{label: string}> $bundles */
    $bundles = $bundleInfoService->getBundleInfo($entityTypeId);
    foreach ($bundles as $bundle => $bundleInfo) {
      $view_display = $entityDisplayRepository->getViewDisplay($entityTypeId, $bundle, 'full_lb');
      if ($view_display->isNew()) {
        continue;
      }

      $options = [
        'none' => t('Do nothing'),
      ];
      $view_display = $entityDisplayRepository->getViewDisplay($entityTypeId, $bundle, 'full_display');
      if (!$view_display->isNew()) {
        $options['full_restore'] = t('Restore classic display');
      }
      else {
        $options['full_lb'] = t('Enable layout builder output');
      }

      $form['layout_builder'][$entityTypeId][$bundle] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => 'none',
        '#title' => $bundleInfo['label'],
      ];
      $added_view_modes_migration = TRUE;
    }
  }

  if (!$added_view_modes_migration) {
    unset($form['layout_builder']);
  }

  $form['#validate'][] = 'opcult_form_system_theme_settings_validate';
  $form['#submit'][] = 'opcult_form_system_theme_settings_form_submit';
  $form['#attached']['library'][] = 'opcult/theme.settings';
}

/**
 * Validation handler for opcult_form_system_theme_settings_alter().
 */
function opcult_form_system_theme_settings_validate(array &$form, FormStateInterface $form_state): void {
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
    $path = is_string($background_image_path) ? _opcult_form_system_theme_settings_validate_path($background_image_path) : FALSE;
    if (!$path) {
      $form_state->setErrorByName('background_image_path', (string) t('The custom image path is invalid.'));
    }
  }
}

/**
 * Submit handler for opcult_form_system_theme_settings_alter().
 */
function opcult_form_system_theme_settings_form_submit(array &$form, FormStateInterface $form_state): void {
  // If the user uploaded a new logo or favicon, save it to a permanent location
  // and use it in place of the default theme-provided file.
  $defaultScheme = \Drupal::config('system.file')->get('default_scheme');
  assert(is_string($defaultScheme));
  /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
  $fileSystem = \Drupal::service('file_system');
  $values = $form_state->getValues();
  $config = \Drupal::configFactory()->getEditable($values['config_key']);
  $config->set('background_image.mode', $values['background_image_mode']);
  try {
    $uploadedFile = $values['background_image_upload'];
    $fileUri = $uploadedFile instanceof FileInterface ? $uploadedFile->getFileUri() : NULL;
    if ($fileUri !== NULL) {
      $filename = $fileSystem->copy($fileUri, $defaultScheme . '://');
      $config->set('background_image.path', $filename);
      $values['background_image_path'] = $filename;
    }
  }
  catch (FileException) {
    // Ignore.
  }

  if (isset($values['background_image_path']) && is_string($values['background_image_path'])) {
    $config->set('background_image.path', _opcult_form_system_theme_settings_validate_path($values['background_image_path']));
  }

  if ($values['background_image_mode'] === 'global_image' && (!is_string($values['background_image_path']) || $values['background_image_path'] === '')) {
    $config->clear('background_image.path');
    $config->set('background_image.mode', 'mood_image');
  }

  if ($values['background_image_mode'] !== 'global_image') {
    $config->clear('background_image.path');
  }

  if (isset($values['layout_builder']) && is_array($values['layout_builder'])) {
    $entityViewModeStorage = \Drupal::entityTypeManager()->getStorage('entity_view_mode');
    $entityViewDisplayStorage = \Drupal::entityTypeManager()->getStorage('entity_view_display');
    /** @var array<string, array<string, string>> $layoutBuilderValues */
    $layoutBuilderValues = $values['layout_builder'];
    foreach ($layoutBuilderValues as $entityTypeId => $bundles) {
      $bundles_with_full_lb = array_filter($bundles, static function (string $option): bool {
        return $option === 'full_lb';
      });
      $bundles_with_full_restore = array_filter($bundles, static function (string $option): bool {
        return $option === 'full_restore';
      });
      if ($bundles_with_full_lb !== []) {
        $id = $entityTypeId . '.full_display';
        if (!$entityViewModeStorage->load($id)) {
          $values = [
            'id' => $id,
            'label' => 'Full Display',
            'targetEntityType' => $entityTypeId,
            'status' => TRUE,
            'description' => 'Copy of content based view mode.',
          ];
          $viewMode = $entityViewModeStorage->create($values);
          $viewMode->save();
        }

        foreach (array_keys($bundles_with_full_lb) as $bundle) {
          $entityViewDisplayFullDisplayId = $entityTypeId . '.' . $bundle . '.full_display';
          $entityViewDisplayFullDisplay = $entityViewDisplayStorage->loadOverrideFree($entityViewDisplayFullDisplayId);
          // Only copy, when entity view display does not exist.
          if (!$entityViewDisplayFullDisplay) {
            $entityViewDisplayFullId = $entityTypeId . '.' . $bundle . '.full';
            /** @var \Drupal\Core\Entity\EntityDisplayModeInterface $entityViewDisplayFull */
            $entityViewDisplayFull = $entityViewDisplayStorage->loadOverrideFree($entityViewDisplayFullId);
            $entityViewDisplayFullDisplay = $entityViewDisplayFull->createDuplicate()->enforceIsNew()
              ->set('bundle', $bundle)
              ->set('mode', 'full_display');
            $entityViewDisplayFullDisplay->setStatus(FALSE);
            $entityViewDisplayFullDisplay->save();
            $entityViewDisplayFullLbId = $entityTypeId . '.' . $bundle . '.full_lb';
            /** @var \Drupal\Core\Entity\EntityDisplayModeInterface|null $entityViewDisplayFullLb */
            $entityViewDisplayFullLb = $entityViewDisplayStorage->load($entityViewDisplayFullLbId);
            if ($entityViewDisplayFullLb) {
              $newEntityViewDisplayFull = $entityViewDisplayFullLb;
              $newEntityViewDisplayFull
                ->set('id', $entityViewDisplayFull->id())
                ->set('uuid', $entityViewDisplayFull->uuid())
                ->setOriginalId($entityViewDisplayFullId)
                ->set('mode', 'full');
              $newEntityViewDisplayFull->save();

              $entityViewDisplayFullLbData = $entityViewDisplayFullLb->toArray();
              unset($entityViewDisplayFullLbData['uuid'], $entityViewDisplayFullLbData['id'], $entityViewDisplayFullLbData['mode']);
              $newEntityViewDisplayFullData = $newEntityViewDisplayFull->toArray();
              unset($newEntityViewDisplayFullData['uuid'], $newEntityViewDisplayFullData['id'], $newEntityViewDisplayFullData['mode']);
              if (md5(var_export($entityViewDisplayFullLbData, TRUE)) === md5(var_export($newEntityViewDisplayFullData, TRUE))) {
                \Drupal::messenger()->addMessage(t('Layout builder was enabled for bundle (@bundle).', ['@bundle' => $bundle]));
              }
              else {
                \Drupal::messenger()->addError(t('Layout builder could not be enabled for bundle (@bundle).', ['@bundle' => $bundle]));
              }
            }
          }
        }
      }

      foreach (array_keys($bundles_with_full_restore) as $bundle) {
        $entityViewDisplayFullDisplayID = $entityTypeId . '.' . $bundle . '.full_display';
        $entityViewDisplayFullDisplay = $entityViewDisplayStorage->load($entityViewDisplayFullDisplayID);
        if ($entityViewDisplayFullDisplay) {
          $entityViewDisplayFullId = $entityTypeId . '.' . $bundle . '.full';
          /** @var \Drupal\Core\Entity\EntityDisplayModeInterface|null $entityViewDisplayFull */
          $entityViewDisplayFull = $entityViewDisplayStorage->loadOverrideFree($entityViewDisplayFullId);
          if ($entityViewDisplayFull) {

            $newEntityViewDisplayFull = $entityViewDisplayFullDisplay;
            $newEntityViewDisplayFull
              ->set('id', $entityViewDisplayFull->id())
              ->set('uuid', $entityViewDisplayFull->uuid())
              ->setOriginalId($entityViewDisplayFullId)
              ->set('mode', 'full');
            $newEntityViewDisplayFull->setStatus(TRUE);
            $newEntityViewDisplayFull->save();

            $newEntityViewDisplayFullData = $newEntityViewDisplayFull->toArray();
            unset($newEntityViewDisplayFullData['uuid'], $newEntityViewDisplayFullData['id'], $newEntityViewDisplayFullData['mode']);
            $entityViewDisplayFullDisplay = $entityViewDisplayFullDisplay->toArray();
            unset($entityViewDisplayFullDisplay['uuid'], $entityViewDisplayFullDisplay['id'], $entityViewDisplayFullDisplay['mode']);
            if (md5(var_export($newEntityViewDisplayFullData, TRUE)) === md5(var_export($entityViewDisplayFullDisplay, TRUE))) {
              \Drupal::messenger()->addMessage(t('The entity view display full has been restored for bundle (@bundle).', ['@bundle' => $bundle]));
            }
            else {
              \Drupal::messenger()->addError(t('The entity view display full could not restored for bundle (@bundle).', ['@bundle' => $bundle]));
            }

            $entityViewDisplayFullDisplay = $entityViewDisplayStorage->loadOverrideFree($entityViewDisplayFullDisplayID);
            if ($entityViewDisplayFullDisplay) {
              $entityViewDisplayFullDisplay->delete();
            }
          }
        }

      }
    }
  }

  $config->clear('layout_builder');
  $config->save();

  $form_state->unsetValue('background_image_path');
  $form_state->unsetValue('background_image_upload');
  $form_state->unsetValue('background_image_mode');
  $form_state->unsetValue('layout_builder');
}

function _opcult_form_system_theme_settings_validate_path(string $path): false|string {
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
