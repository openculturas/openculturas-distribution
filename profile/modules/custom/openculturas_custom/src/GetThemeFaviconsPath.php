<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

use Drupal\Core\Theme\ActiveTheme;

use function array_keys;
use function is_dir;

class GetThemeFaviconsPath {

  public static function getPath(string $themeName): ?string {
    $themeList = \Drupal::service('extension.list.theme');
    // The active theme is the "core" pseudo theme when no installed theme
    // could be negotiated, which has no extension to look up.
    if (!$themeList->exists($themeName)) {
      return NULL;
    }

    $path = $themeList->getPath($themeName) . '/favicons';
    return is_dir($path) ? $path : NULL;
  }

  /**
   * Returns the favicons path of the active theme or its nearest base theme.
   *
   * Sub-themes without their own favicons directory inherit the one of their
   * closest base theme. Falls back to opcult when no theme in the chain ships
   * a favicons directory, e.g. for admin themes.
   */
  public static function getPathForActiveTheme(ActiveTheme $activeTheme): ?string {
    $themeNames = [$activeTheme->getName(), ...array_keys($activeTheme->getBaseThemeExtensions())];
    foreach ($themeNames as $themeName) {
      $path = self::getPath($themeName);
      if ($path !== NULL) {
        return $path;
      }
    }

    return self::getPath('opcult');
  }

}
