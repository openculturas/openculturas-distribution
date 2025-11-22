<?php

/**
 * @file
 * Updates the adjusted core-dev composer.json based on the current Drupal version.
 *
 * This script downloads the composer.json from the drupal/core-dev repository
 * matching the current Drupal version. Replaces the name and description fields
 * with the values from the local composer.json file and removes some packages.
 */

$target_file_path = __DIR__ . '/../composer-packages/core-dev/composer.json';

try {
  $drupal_version = \Drupal::VERSION;
  echo "Detected Drupal version: $drupal_version" . PHP_EOL;

  if (!file_exists($target_file_path)) {
    throw new \RuntimeException(sprintf('Target file not found: %s', $target_file_path));
  }

  $local_content = file_get_contents($target_file_path);
  if ($local_content === FALSE) {
    throw new \RuntimeException(sprintf('Failed to read local file: %s', $target_file_path));
  }

  $local_data = json_decode($local_content, TRUE, 512, JSON_THROW_ON_ERROR);
  $preserved_name = $local_data['name'] ?? NULL;

  $remote_url = sprintf(
    'https://raw.githubusercontent.com/drupal/core-dev/refs/tags/%s/composer.json',
    $drupal_version
  );
  echo "Downloading from: $remote_url" . PHP_EOL;

  $remote_content = @file_get_contents($remote_url);
  if ($remote_content === FALSE) {
    throw new \RuntimeException(sprintf('Failed to download composer.json from %s', $remote_url));
  }

  $remote_data = json_decode($remote_content, TRUE, 512, JSON_THROW_ON_ERROR);

  if ($preserved_name) {
    $remote_data['name'] = $preserved_name;
  }

  $remote_data['conflict'] = $local_data['conflict'] ?? [];
  $remote_data['version'] = '1.0.0';

  $packages_to_remove = [
    'phpstan/phpstan',
    'phpstan/phpstan-phpunit',
    'phpstan/extension-installer',
    'drupal/coder',
    'mglaman/phpstan-drupal',
    'micheh/phpcs-gitlab'
  ];

  if (isset($remote_data['require'])) {
    foreach ($packages_to_remove as $package) {
      if (isset($remote_data['require'][$package])) {
        unset($remote_data['require'][$package]);
        echo "Removed dependency: $package" . PHP_EOL;
      }
    }
  }


  $json_output = json_encode(
    $remote_data,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
  );

  // Append a newline to comply with POSIX standards.
  $json_output .= "\n";

  $write_result = file_put_contents($target_file_path, $json_output);
  if ($write_result === FALSE) {
    throw new \RuntimeException(sprintf('Failed to write to %s', $target_file_path));
  }

  echo "Successfully updated $target_file_path" . PHP_EOL;
  echo "Do not forget to run composer update openculturas/drupal-core-dev" . PHP_EOL;

}
catch (\JsonException $e) {
  fwrite(STDERR, 'JSON Error: ' . $e->getMessage() . PHP_EOL);
  exit(1);
}
catch (\Exception $e) {
  fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
  exit(1);
}
