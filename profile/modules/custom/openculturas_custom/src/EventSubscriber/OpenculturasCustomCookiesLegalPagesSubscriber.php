<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\block\BlockInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds the cookies.texts legal links to the Cookies UI block's ignore list.
 */
final readonly class OpenculturasCustomCookiesLegalPagesSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigInstallerInterface $configInstaller,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ConfigEvents::SAVE => 'onConfigSave',
    ];
  }

  /**
   * Keeps the privacy policy and imprint pages out of the cookie banner.
   *
   * Cookies.texts stores each link as an internal path or an external URL;
   * only internal ones can ever match the block's request_path visibility
   * condition, so external ones are skipped. Existing entries (manual, or
   * from a previously configured URL) are kept as-is: changing a URL later
   * just adds the new path alongside the stale one, which is easier to spot
   * and fix than silently losing a manual addition.
   */
  public function onConfigSave(ConfigCrudEvent $event): void {
    $config = $event->getConfig();
    if ($this->configInstaller->isSyncing() || $config->getName() !== 'cookies.texts') {
      return;
    }

    $paths = self::getLegalPaths($config);
    if ($paths === []) {
      return;
    }

    self::addPathsToCookiesUiBlocks($this->entityTypeManager, $paths);
  }

  /**
   * Adds paths to the request_path ignore list of every Cookies UI block.
   *
   * Used both here and by openculturas_install_content(), which resolves
   * the default privacy/imprint content nodes by UUID for fresh installs
   * (cookies.texts ships with empty URIs by default, so this subscriber's
   * own onConfigSave() never fires for them).
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param string[] $paths
   *   Internal paths to add.
   */
  public static function addPathsToCookiesUiBlocks(EntityTypeManagerInterface $entityTypeManager, array $paths): void {
    /** @var \Drupal\block\BlockInterface[] $blocks */
    $blocks = $entityTypeManager->getStorage('block')->loadByProperties(['plugin' => 'cookies_ui_block']);
    foreach ($blocks as $block) {
      self::addToIgnoreList($block, $paths);
    }
  }

  /**
   * Reads the internal legal-page paths out of a cookies.texts config.
   *
   * Shared with OpenculturasCustomConfigDevelSubscriber, which strips these
   * same paths back out again before the Cookies UI block's visibility
   * settings are exported as this profile's default config.
   *
   * @return string[]
   *   The internal paths, if any.
   */
  public static function getLegalPaths(StorableConfigBase $cookiesTexts): array {
    return array_filter([
      self::toInternalPath($cookiesTexts->get('privacyUri')),
      self::toInternalPath($cookiesTexts->get('imprintUri')),
    ]);
  }

  private static function toInternalPath(mixed $uri): ?string {
    if (!is_string($uri) || $uri === '' || preg_match('#^https?://#', $uri)) {
      return NULL;
    }

    return $uri;
  }

  /**
   * @param \Drupal\block\BlockInterface $block
   *   The Cookies UI block instance.
   * @param string[] $paths
   *   Internal paths to add to the block's request_path ignore list.
   */
  private static function addToIgnoreList(BlockInterface $block, array $paths): void {
    $conditions = $block->getVisibilityConditions();
    $configuration = $conditions->has('request_path')
      ? $conditions->get('request_path')->getConfiguration()
      : ['negate' => TRUE, 'pages' => ''];

    $pagesValue = $configuration['pages'] ?? '';
    $pages = array_filter(array_map(trim(...), explode("\n", is_string($pagesValue) ? $pagesValue : '')));
    $missing = array_diff($paths, $pages);
    if ($missing === []) {
      return;
    }

    $configuration['pages'] = implode("\n", [...$pages, ...$missing]);
    $block->setVisibilityConfig('request_path', $configuration);
    $block->save();
  }

}
