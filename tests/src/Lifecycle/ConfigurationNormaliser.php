<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Lifecycle;

use Drupal\Component\Serialization\Yaml;

/**
 * Brings configuration objects into one canonical form before they are compared.
 *
 * Core sorts the keys of a mapping by the configuration schema each time it
 * saves an object (StorableConfigBase::castValue(),
 * web/core/lib/Drupal/Core/Config/StorableConfigBase.php:263-271). The
 * objects recorded from the 3.1.2 snapshot are not in that order, so the same
 * settings can appear with different key orders. A text comparison reports
 * such an object as changed, and so does StorageComparer, which compares with
 * !==. Sorting every mapping by key removes that difference. A list keeps its
 * order, because the order of a list is part of its value.
 *
 * The uuid and the _core stamp are removed, because core assigns them per
 * object and they never describe the settings of an object.
 */
final class ConfigurationNormaliser {

  /**
   * Returns the normalised form of one configuration object.
   *
   * @param array<string, mixed> $data
   *   The configuration data as recorded.
   *
   * @return array<string, mixed>
   *   The data without the top-level uuid and _core keys, with every mapping
   *   sorted by key. Null values are kept.
   */
  public static function normalise(array $data): array {
    unset($data['uuid'], $data['_core']);

    /** @var array<string, mixed> $normalised */
    $normalised = self::sortMappings($data);
    return $normalised;
  }

  /**
   * Writes the normalised copy of every configuration file of a directory.
   *
   * Each *.yml file of $source is read, normalised and written to $target under
   * the same file name. Existing *.yml files in $target are removed first, and
   * $target is created if it does not exist.
   *
   * @param string $source
   *   The directory of the recorded configuration files.
   * @param string $target
   *   The directory the normalised copies are written to.
   *
   * @throws \RuntimeException
   *   If a file cannot be read or does not hold a configuration object, or if
   *   the target directory cannot be prepared.
   */
  public static function normaliseDirectory(string $source, string $target): void {
    self::prepareTargetDirectory($target);

    foreach (glob($source . '/*.yml') ?: [] as $path) {
      self::normaliseFile($path, $target . '/' . basename($path));
    }
  }

  /**
   * Creates the target directory, and removes the configuration files in it.
   *
   * @param string $target
   *   The directory the normalised copies are written to.
   *
   * @throws \RuntimeException
   *   If the directory cannot be created or a stale file cannot be removed.
   */
  private static function prepareTargetDirectory(string $target): void {
    if (!is_dir($target) && !mkdir($target, 0777, TRUE) && !is_dir($target)) {
      throw new \RuntimeException(sprintf('The target directory %s cannot be created.', $target));
    }

    foreach (glob($target . '/*.yml') ?: [] as $stale) {
      if (!unlink($stale)) {
        throw new \RuntimeException(sprintf('The stale file %s cannot be removed.', $stale));
      }
    }
  }

  /**
   * Reads one configuration file, normalises it and writes the result.
   *
   * @param string $path
   *   The recorded configuration file.
   * @param string $destination
   *   The file the normalised copy is written to.
   *
   * @throws \RuntimeException
   *   If the file cannot be read or does not hold a configuration object, or if
   *   the copy cannot be written.
   */
  private static function normaliseFile(string $path, string $destination): void {
    $contents = file_get_contents($path);
    if ($contents === FALSE) {
      throw new \RuntimeException(sprintf('The file %s cannot be read.', $path));
    }

    try {
      $data = Yaml::decode($contents);
    }
    catch (\InvalidArgumentException $exception) {
      throw new \RuntimeException(sprintf('The file %s is not valid YAML: %s', $path, $exception->getMessage()), 0, $exception);
    }

    if (!is_array($data)) {
      throw new \RuntimeException(sprintf('The file %s does not hold a configuration object.', $path));
    }

    if (file_put_contents($destination, Yaml::encode(self::normalise($data))) === FALSE) {
      throw new \RuntimeException(sprintf('The file %s cannot be written.', $destination));
    }
  }

  /**
   * Sorts every mapping of a value by key, recursively.
   *
   * @param mixed $value
   *   The value to sort.
   *
   * @return mixed
   *   The value with all mappings sorted by key. Lists keep their order, and
   *   their elements are sorted in turn.
   */
  private static function sortMappings(mixed $value): mixed {
    if (!is_array($value)) {
      return $value;
    }

    if (!array_is_list($value)) {
      ksort($value);
    }

    return array_map(self::sortMappings(...), $value);
  }

}
