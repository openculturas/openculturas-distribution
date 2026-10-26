<?php

declare(strict_types=1);

namespace Drupal\openculturas_responsive_images\EventSubscriber;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\config_devel\Event\ConfigDevelEvents;
use Drupal\config_devel\Event\ConfigDevelSaveEvent;
use Drupal\openculturas_responsive_images\SharedConfigSwitcher;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Exports the profile's shared config as it is on a site without this module.
 *
 * The profile's config/install is imported before this module is installed,
 * and update hooks import from it on sites that never install this module.
 * It must not reference this module's image styles, so config_devel exports
 * of the profile get the legacy state, even on a site that switched.
 */
final readonly class ConfigDevelSubscriber implements EventSubscriberInterface {

  public function __construct(
    private SharedConfigSwitcher $sharedConfigSwitcher,
    private ConfigManagerInterface $configManager,
    private EntityTypeManagerInterface $entityTypeManager,
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
   * Reverts switched config to the legacy state before it is written.
   */
  public function onConfigDevelSave(ConfigDevelSaveEvent $event): void {
    if (!$this->writesToProfile($event->getFileNames())) {
      return;
    }

    $data = $event->getData();
    $fileNames = $event->getFileNames();
    $configName = pathinfo((string) reset($fileNames), PATHINFO_FILENAME);
    $entityTypeId = $this->configManager->getEntityTypeIdByName($configName);
    if ($entityTypeId === NULL || !isset(SharedConfigSwitcher::SWITCHED_PROPERTIES[$entityTypeId])) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage($entityTypeId);
    if (!$storage instanceof ConfigEntityStorageInterface) {
      return;
    }

    $original = $storage->createFromStorageRecord($data);
    $entity = $storage->createFromStorageRecord($data);
    if (!$this->sharedConfigSwitcher->switchEntity($entity, FALSE)) {
      return;
    }

    $property = SharedConfigSwitcher::SWITCHED_PROPERTIES[$entityTypeId];
    $data[$property] = $this->changedValues($data[$property] ?? NULL, $original->get($property), $entity->get($property));
    $data['dependencies'] = $this->changedDependencies(
      $data['dependencies'] ?? [],
      $original->calculateDependencies()->getDependencies(),
      $entity->calculateDependencies()->getDependencies(),
    );
    $event->setData($data);
  }

  /**
   * Applies only what the switch changed to the exported value.
   *
   * Entity displays fill in default components for fields that are neither
   * shown nor hidden when they are constructed. Comparing the switched entity
   * with an unswitched one built from the same data leaves those defaults
   * out of the export. Changed entries (a display component, a view display,
   * a metatag) are replaced as a whole, unchanged ones keep their exported
   * form.
   */
  private function changedValues(mixed $exported, mixed $original, mixed $switched): mixed {
    if (!is_array($exported) || !is_array($original) || !is_array($switched)) {
      return $switched;
    }

    foreach ($switched as $key => $value) {
      if (!array_key_exists($key, $original) || $original[$key] !== $value) {
        $exported[$key] = $value;
      }
    }

    foreach (array_diff_key($original, $switched) as $key => $value) {
      unset($exported[$key]);
    }

    return $exported;
  }

  /**
   * Applies only the dependency changes caused by the switch.
   *
   * @param array<string, mixed> $exported
   *   The exported dependencies.
   * @param array<string, mixed> $original
   *   The dependencies calculated before the switch.
   * @param array<string, mixed> $switched
   *   The dependencies calculated after the switch.
   *
   * @return array<string, mixed>
   *   The exported dependencies with the switch's changes applied.
   */
  private function changedDependencies(array $exported, array $original, array $switched): array {
    foreach (['config', 'content', 'module', 'theme'] as $type) {
      $before = (array) ($original[$type] ?? []);
      $after = (array) ($switched[$type] ?? []);
      $names = array_diff((array) ($exported[$type] ?? []), array_diff($before, $after));
      $names = array_unique([...$names, ...array_diff($after, $before)]);
      sort($names);

      if ($names === []) {
        unset($exported[$type]);
      }
      else {
        $exported[$type] = $names;
      }
    }

    return $exported;
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
