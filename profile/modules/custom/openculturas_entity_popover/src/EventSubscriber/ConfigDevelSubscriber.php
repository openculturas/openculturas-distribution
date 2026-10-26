<?php

declare(strict_types=1);

namespace Drupal\openculturas_entity_popover\EventSubscriber;

use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\config_devel\Event\ConfigDevelEvents;
use Drupal\config_devel\Event\ConfigDevelSaveEvent;
use Drupal\openculturas_entity_popover\DisplayUnwirer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Exports the profile's displays as they are on a site without this module.
 *
 * The profile's config/install is imported before this module is installed,
 * and update hooks import from it on sites that never install this module.
 * It must not reference field_entity_popover or the entity_popover modules,
 * so config_devel exports of the profile leave out the popover wiring.
 */
final readonly class ConfigDevelSubscriber implements EventSubscriberInterface {

  public function __construct(
    private ProfileExtensionList $profileExtensionList,
    #[Autowire(param: 'install_profile')]
    private string $installProfile,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    if (!class_exists(ConfigDevelEvents::class)) {
      return [];
    }

    return [
      ConfigDevelEvents::SAVE => ['onConfigDevelSave'],
    ];
  }

  /**
   * Removes the popover wiring from profile displays before they are written.
   */
  public function onConfigDevelSave(ConfigDevelSaveEvent $event): void {
    $fileNames = $event->getFileNames();
    if (!$this->writesToProfile($fileNames)) {
      return;
    }

    $configName = pathinfo((string) reset($fileNames), PATHINFO_FILENAME);
    if (!str_starts_with($configName, 'core.entity_view_display.') && !str_starts_with($configName, 'core.entity_form_display.')) {
      return;
    }

    $event->setData(DisplayUnwirer::unwire($event->getData()));
  }

  /**
   * Whether config_devel writes into the profile's config directories.
   *
   * @param array<int, string> $fileNames
   *   The files config_devel writes to.
   */
  private function writesToProfile(array $fileNames): bool {
    $profileConfigPath = $this->profileExtensionList->getPath($this->installProfile) . '/config/';
    return array_any($fileNames, fn(string $fileName): bool => str_starts_with($fileName, $profileConfigPath));
  }

}
