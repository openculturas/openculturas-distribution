<?php

declare(strict_types=1);

namespace Drupal\opcult_starterkit;

use Drupal\Core\Theme\StarterKitInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class StarterKit implements StarterKitInterface {

  public static function postProcess(string $working_dir, string $machine_name, string $theme_name): void {
    $hyphenated_new = str_replace('_', '-', $machine_name);
    $hyphenated_old = 'opcult-starterkit';

    $finder = (new Finder())
      ->in($working_dir)
      ->files()
      ->name(sprintf('*%s*', $hyphenated_old));

    $fs = new Filesystem();
    foreach ($finder as $file) {
      $new_filename = str_replace($hyphenated_old, $hyphenated_new, $file->getFilename());
      $fs->rename($file->getRealPath(), $file->getPath() . '/' . $new_filename);
    }
  }

}
