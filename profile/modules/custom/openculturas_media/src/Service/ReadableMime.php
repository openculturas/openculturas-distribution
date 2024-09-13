<?php

declare(strict_types=1);

namespace Drupal\openculturas_media\Service;

use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\Yaml\Yaml;
use function is_array;

/**
 * Service class for readable mimetypes.
 *
 * Shamelessly stolen from https://www.drupal.org/project/nice_filemime.
 * This module handles files, we are working with media.
 */
final class ReadableMime {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   *   Custom media configuration.
   */
  public $config;

  /**
   * Create the Service.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->config = $configFactory->get('openculturas_media.settings');
  }

  /**
   * Gets a readable name of a mimetype.
   *
   * @param string $filemime
   *   The mime type.
   *
   * @return string
   *   The readable name.
   */
  public function getReadableMime(string $filemime): string {
    // All our nice mimes.
    $niceFileMimes = $this->mapNiceFileMime();

    // Return a nice filemime is one exists.
    if (array_key_exists($filemime, $niceFileMimes)) {
      return $niceFileMimes[$filemime];
    }

    // No result do a pass through.
    return $filemime;
  }

  /**
   * @return array
   *   List of all available mime names.
   */
  private function mapNiceFileMime(): array {
    $niceFileMimesTemp = $this->config->get('map_mimetypes');

    // @todo Find a nicer way to str_replace all keys containing _@_ to .
    // See https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Config%21ConfigBase.php/function/ConfigBase%3A%3AvalidateKeys/8.2.x
    $niceFileMimesReplaced = str_replace('_@_', '.', Yaml::dump($niceFileMimesTemp));
    $mimeTypes = Yaml::parse($niceFileMimesReplaced);
    return is_array($mimeTypes) ? $mimeTypes : [];
  }

}
