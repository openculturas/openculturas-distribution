<?php

namespace Drupal\openculturas_custom\Commands;

use Drush\Commands\DrushCommands;
use Symfony\Component\Yaml\Yaml;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class OpenculturasCustomCommands extends DrushCommands {

  /**
   * Verify and fix theme STARTERKIT integrity.
   *
   * @option fixit
   *   Copy over files from theme to STARTERKIT Folder.
   * @usage checkstarterkit [--fixit]
   *
   *  Helper to keep base_theme and STARTERKIT files synced.
   * - In pipeline run `drush checkstarterkit` to fail on inconsistencies.
   * - Local you can copy files over with `drush checkstarterkit --fixit` after changes or updates.
   *
   * @command openculturas_custom:themeCheckStarterKit
   * @aliases checkstarterkit
   */
  public function themeCheckStarterKit($options = ['fixit' => FALSE]) {

    $action = $options['fixit'] ? 'sync' : 'check';
    $filePaths = $this->_getFiles();

    // Verbose.
    $this->logger()->info(dt('Source: @dir', ['@dir' => $filePaths->source]));
    $this->logger()->info(dt('Starterkit/dest: @dir',['@dir' => $filePaths->starterKit]));
    $this->logger()->info(dt('Action @action files.', ['@action' => $action]));
    $this->logger()->info(dt('Files: @files', [
      '@files' => "\n           " . implode($filePaths->syncFiles, "\n           ")
    ]));

    foreach ($filePaths->syncFiles as $fileName) {
      $source_file = $filePaths->source . '/' . $fileName;
      $dest_file = $filePaths->starterKit . '/' . $fileName;

      // Validate source file.
      if (!file_exists($source_file)) {
        throw new \Exception(dt('Missing source file: @file', ['@file' => $source_file]));
      }

      // Copy files "--fixit"
      if ($options['fixit']) {
        if (!copy($source_file, $dest_file)) {
          throw new \Exception(dt('Error updating file: @file', ['@file' => $dest_file]));
        }
        else {
          // Keep timstamp.
          touch($dest_file, filemtime($source_file));
          $this->logger()->success(dt('Updated: ' . $dest_file));
        }
      }

      // Check only (e.g. for pipeline usage).
      if (!$options['fixit']) {
        // Check exists.
        if (!file_exists($dest_file)) {
          throw new \Exception(dt('Missing required file: @file', ['@file' => $dest_file]));
        }
        // Compare.
        if (sha1_file($source_file) !== sha1_file($dest_file)) {
          throw new \Exception(dt('File not synced: @file', ['@file' => $dest_file]));
        }
      }

    } // End foreach.

    // Be nice.
    if ($options['fixit']) {
      $this->logger()->success(dt('Done updating STARTERKIT files.'));
    }
    else {
      $this->logger()->success(dt('Required files are in sync.'));
    }
  }

  /**
   * Get config paths and file list.
   *
   * @return object
   * @throws \Exception
   */
  private function _getFiles() {
    $src_root = \Drupal::root() . '/profiles/contrib/openculturas-profile/themes/openculturas_base';
    $config_file = $src_root . '/startertkit-upgrade.yml';

    // Check Config.
    if (!file_exists($config_file)) {
      throw new \Exception(dt('Missing source file: @file', ['@file' => $config_file]));
    }
    $cnf = Yaml::parseFile($config_file);
    if (!isset($cnf['syncFiles']) || !is_array($cnf['syncFiles']) || count($cnf['syncFiles']) < 1) {
      throw new \Exception(dt('Missing "syncFiles" array in file: @file', ['@file' => $config_file]));
    }

    return (object) [
      'source' => $src_root,
      'starterKit' => $src_root . '/STARTERKIT',
      'syncFiles' => $cnf['syncFiles'],
    ];
  }
}
