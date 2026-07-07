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

    $finder = new Finder()
      ->in($working_dir)
      ->files()
      ->name(sprintf('*%s*', $hyphenated_old));

    $fs = new Filesystem();
    foreach ($finder as $file) {
      $new_filename = str_replace($hyphenated_old, $hyphenated_new, $file->getFilename());
      $fs->rename($file->getRealPath(), $file->getPath() . '/' . $new_filename);
    }

    $opcult_sass_path = '../opcult/sass';
    $candidates = [
      '/profiles/contrib/openculturas-profile/themes/opcult/sass' => '../../../profiles/contrib/openculturas-profile/themes/opcult/sass',
      '/profiles/contrib/openculturas-distribution/profile/themes/opcult/sass' => '../../../profiles/contrib/openculturas-distribution/profile/themes/opcult/sass',
    ];
    foreach ($candidates as $candidate => $relative_path) {
      if (is_dir(DRUPAL_ROOT . '/' . $candidate)) {
        $opcult_sass_path = $relative_path;
        break;
      }
    }

    $gulpfile = $working_dir . '/gulpfile.mjs';
    if (file_exists($gulpfile)) {
      $content = file_get_contents($gulpfile);
      if ($content === FALSE) {
        return;
      }

      $content = preg_replace(
        "/const opcultSassPath = '[^']*';/",
        "const opcultSassPath = '" . $opcult_sass_path . "';",
        $content
      );
      if ($content === NULL) {
        return;
      }

      $fs->dumpFile($gulpfile, $content);
    }
  }

}
