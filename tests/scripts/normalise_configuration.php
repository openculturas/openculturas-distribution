#!/usr/bin/env php
<?php

/**
 * @file
 * Writes normalised copies of a directory of recorded configuration files.
 *
 * Usage: normalise_configuration.php <source-directory> <target-directory>
 *
 * The normalisation itself is ConfigurationNormaliser's. This script only
 * checks its arguments and reports failures on STDERR.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Drupal\Tests\openculturas\Lifecycle\ConfigurationNormaliser;

function main(array $arguments): int {
  if (count($arguments) !== 3) {
    fwrite(STDERR, sprintf("usage: %s <source-directory> <target-directory>\n", $arguments[0] ?? 'normalise_configuration.php'));
    return 1;
  }

  [, $source, $target] = $arguments;
  if (!is_dir($source)) {
    fwrite(STDERR, sprintf("The source directory %s does not exist.\n", $source));
    return 1;
  }

  try {
    ConfigurationNormaliser::normaliseDirectory($source, $target);
  }
  catch (\RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    return 1;
  }

  return 0;
}

exit(main($argv));
