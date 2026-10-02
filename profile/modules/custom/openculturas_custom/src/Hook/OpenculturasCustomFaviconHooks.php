<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use Drupal\Core\Extension\ThemeSettingsProvider;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\openculturas_custom\GetThemeFaviconsPath;

use function is_array;
use function is_string;
use function parse_url;
use function str_ends_with;
use function strtolower;

/**
 * Hook implementations for favicon tokens and links in openculturas_custom.
 */
class OpenculturasCustomFaviconHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new OpenculturasCustomFaviconHooks.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Extension\ThemeSettingsProvider $themeSettingsProvider
   *   The theme settings provider.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator.
   */
  public function __construct(
    protected ThemeManagerInterface $themeManager,
    protected ThemeSettingsProvider $themeSettingsProvider,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
  }

  /**
   * Implements hook_preprocess_HOOK() for html.html.twig.
   *
   * Provides "favicon_directory" for favicon assets that html.html.twig links
   * itself. Unlike "directory", it falls back to the nearest base theme when
   * the active theme has no favicons directory.
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(array &$variables): void {
    $variables['favicon_directory'] = GetThemeFaviconsPath::getPathForActiveTheme($this->themeManager->getActiveTheme());
  }

  /**
   * Implements hook_metatags_attachments_alter().
   *
   * Adds sizes="32x32" to an .ico shortcut icon. Without it, Chrome downloads
   * both the ICO and the SVG favicon instead of only the SVG.
   */
  #[Hook('metatags_attachments_alter')]
  public function metatagsAttachmentsAlter(array &$metatag_attachments): void {
    $attached = $metatag_attachments['#attached'] ?? NULL;
    if (!is_array($attached) || !is_array($attached['html_head'] ?? NULL)) {
      return;
    }

    foreach ($attached['html_head'] as $index => $attachment) {
      if (!is_array($attachment)) {
        continue;
      }

      if (($attachment[1] ?? NULL) !== 'shortcut_icon') {
        continue;
      }

      if (!is_array($attachment[0] ?? NULL)) {
        continue;
      }

      $attributes = $attachment[0]['#attributes'] ?? NULL;
      if (!is_array($attributes)) {
        continue;
      }

      if (!is_string($attributes['href'] ?? NULL)) {
        continue;
      }

      $path = parse_url($attributes['href'], PHP_URL_PATH);
      if (is_string($path) && str_ends_with(strtolower($path), '.ico')) {
        $attributes['sizes'] = '32x32';
        $attachment[0]['#attributes'] = $attributes;
        $attached['html_head'][$index] = $attachment;
      }
    }

    $metatag_attachments['#attached'] = $attached;
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    $site['favicon-path'] = [
      'name' => $this->t('Favicon path'),
      'description' => $this->t('The favicons directory of the active theme, falling back to its nearest base theme and then to opcult.'),
    ];
    $site['favicon-shortcut-icon'] = [
      'name' => $this->t('Favicon: shortcut icon'),
      'description' => $this->t("The active theme's shortcut icon: the site's custom-uploaded favicon (theme settings) if configured, otherwise the theme default."),
    ];
    return ['tokens' => ['site' => $site]];
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    $replacements = [];
    if ($type !== 'site') {
      return $replacements;
    }

    foreach ($tokens as $name => $original) {
      if ($name === 'favicon-path') {
        $bubbleable_metadata->addCacheContexts(['theme']);
        $path = GetThemeFaviconsPath::getPathForActiveTheme($this->themeManager->getActiveTheme());
        $replacements[$original] = $path !== NULL ? $this->fileUrlGenerator->generateString($path) : '';
      }
      elseif ($name === 'favicon-shortcut-icon') {
        $bubbleable_metadata->addCacheContexts(['theme']);
        $bubbleable_metadata->addCacheTags(['config:' . $this->themeManager->getActiveTheme()->getName() . '.settings']);
        $replacements[$original] = $this->resolveShortcutIcon();
      }
    }

    return $replacements;
  }

  /**
   * Resolves the shortcut icon, preferring a site-uploaded custom favicon.
   *
   * Reuses core's own theme settings favicon resolution
   * (\Drupal\Core\Extension\ThemeSettingsProvider::buildThemeSettings()),
   * which already computes "favicon.url" as the uploaded/custom file's
   * public URL when favicon.use_default is off. Only falls back to the
   * theme's own bundled favicon.ico when no custom favicon is configured.
   */
  private function resolveShortcutIcon(): string {
    if (!$this->themeSettingsProvider->getSetting('favicon.use_default')) {
      $customUrl = $this->themeSettingsProvider->getSetting('favicon.url');
      if (is_string($customUrl) && $customUrl !== '') {
        return $customUrl;
      }
    }

    $path = GetThemeFaviconsPath::getPathForActiveTheme($this->themeManager->getActiveTheme());
    return $path !== NULL ? $this->fileUrlGenerator->generateString($path . '/favicon.ico') : '';
  }

}
