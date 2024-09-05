<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\EventSubscriber;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\config_devel\Event\ConfigDevelEvents;
use Drupal\config_devel\Event\ConfigDevelSaveEvent;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function array_search;
use function array_unique;
use function array_values;
use function basename;
use function class_exists;
use function dirname;
use function pathinfo;
use function preg_match;
use function reset;
use function str_contains;
use function str_ends_with;
use function str_starts_with;

/**
 * OpenCulturas Customizing event subscriber.
 */
final class OpenculturasCustomConfigDevelSubscriber implements EventSubscriberInterface {

  /**
   * Creates OpenculturasCustomConfigDevelSubscriber objects.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   Config manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   */
  public function __construct(protected ConfigManagerInterface $configManager, protected ModuleHandlerInterface $moduleHandler) {
  }

  /**
   * Adds enforced dependency and adds back the uuid for view config entities.
   *
   * @param \Drupal\config_devel\Event\ConfigDevelSaveEvent $event
   *   The ConfigDevelSaveEvent event object.
   */
  public function onConfigDevelSave(ConfigDevelSaveEvent $event): void {
    $data = $event->getData();
    $file_names = $event->getFileNames();
    $file_path = reset($file_names);
    $config_name = pathinfo((string) $file_path, PATHINFO_FILENAME);
    $entity_type_id = $this->configManager->getEntityTypeIdByName($config_name);
    $extension = basename(dirname((string) $file_path, 3));
    if ($entity_type_id === NULL) {
      return;
    }

    if ($entity_type_id === 'view') {
      /** @var \Drupal\views\ViewEntityInterface|null $configEntity */
      $configEntity = $this->configManager->loadConfigEntityByName($config_name);
      if ($configEntity instanceof ViewEntityInterface && $configEntity->uuid() !== NULL) {
        $data = ['uuid' => $configEntity->uuid()] + $data;
        $event->setData($data);
      }
    }

    if ($extension === 'openculturas-profile' && $config_name === 'user.role.oc_admin') {
      $this->excludeOpenCulturasFaq($event);
      $this->excludeOpenCulturasMap($event);
      $this->excludeRoleDelegation($event);
    }

    if ($extension === 'openculturas-profile' && (
      $config_name === 'field.field.paragraph.view.field_view' ||
      $config_name === 'views.view.moderated_content' ||
      str_starts_with($config_name, 'core.entity_form_display.node') ||
      str_starts_with($config_name, 'core.entity_view_display.node') ||
      str_starts_with($config_name, 'user.role.')
    )) {
      $this->excludeOpenCulturasDiscussions($event);
      $this->excludeOpenCulturasSection($event);
    }

    // // @phpstan-ignore-next-line
    if ($extension === \Drupal::installProfile()) {
      return;
    }

    if (!$this->moduleHandler->moduleExists($extension)) {
      return;
    }

    if (str_ends_with(dirname((string) $file_path), InstallStorage::CONFIG_INSTALL_DIRECTORY) === FALSE) {
      return;
    }

    $data['dependencies']['enforced']['module'][] = $extension;
    $data['dependencies']['enforced']['module'] = array_unique($data['dependencies']['enforced']['module']);

    $event->setData($data);
  }

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
   * OpenCulturas-Section is an optional module.
   */
  private function excludeOpenCulturasSection(ConfigDevelSaveEvent $configDevelSaveEvent): void {
    $data = $configDevelSaveEvent->getData();
    unset($data['content']['field_section'], $data['hidden']['field_section']);
    if (isset($data['dependencies']['config'])) {
      foreach ($data['dependencies']['config'] as $index => $config_name) {
        if (str_ends_with($config_name, 'field_section')) {
          unset($data['dependencies']['config'][$index]);
        }
      }

      $data['dependencies']['config'] = array_values($data['dependencies']['config']);
    }

    if (isset($data['third_party_settings']['field_group'])) {
      foreach ($data['third_party_settings']['field_group'] as $field_id => $field_group_data) {
        if (isset($field_group_data['children'])) {
          $index = array_search('field_section', $field_group_data['children'], TRUE);
          if ($index !== FALSE) {
            unset($field_group_data['children'][$index]);
          }

          $field_group_data['children'] = array_values($field_group_data['children']);
          $data['third_party_settings']['field_group'][$field_id] = $field_group_data;
        }

      }
    }

    $configDevelSaveEvent->setData($data);
  }

  /**
   * OpenCulturas FAQ is an optional module.
   */
  private function excludeOpenCulturasFaq(ConfigDevelSaveEvent $configDevelSaveEvent): void {
    $data = $configDevelSaveEvent->getData();
    $config_names = ['node.type.faq', 'taxonomy.vocabulary.faq_category'];
    foreach ($config_names as $config_name) {
      $index = array_search($config_name, $data['dependencies']['config'], TRUE);
      if ($index !== FALSE) {
        unset($data['dependencies']['config'][$index]);
      }
    }

    $data['dependencies']['config'] = array_values($data['dependencies']['config']);
    $configDevelSaveEvent->setData($data);
    foreach ($data['permissions'] as $index => $permission) {
      if (str_contains($permission, 'faq')) {
        unset($data['permissions'][$index]);
      }
    }

    $data['permissions'] = array_values($data['permissions']);
    $configDevelSaveEvent->setData($data);
  }

  /**
   * OpenCulturas Map will be installed after the profile is installed.
   */
  private function excludeOpenCulturasMap(ConfigDevelSaveEvent $configDevelSaveEvent): void {
    $data = $configDevelSaveEvent->getData();
    foreach ($data['dependencies']['module'] as $index => $dependency) {
      if ($dependency === 'openculturas_map') {
        unset($data['dependencies']['module'][$index]);
      }
    }

    $data['dependencies']['module'] = array_values($data['dependencies']['module']);

    foreach ($data['permissions'] as $index => $permission) {
      if ($permission === 'administer openculturas_map configuration') {
        unset($data['permissions'][$index]);
      }
    }

    $data['permissions'] = array_values($data['permissions']);
    $configDevelSaveEvent->setData($data);
  }

  /**
   * Removes role_delegation permissions.
   *
   * The permissions of this module needs to be installed after openculturas because
   * it depends on roles which are installed with this profile.
   *
   * @see openculturas_install_role_missing_permission()
   */
  private function excludeRoleDelegation(ConfigDevelSaveEvent $configDevelSaveEvent): void {
    $data = $configDevelSaveEvent->getData();
    foreach ($data['permissions'] as $index => $permission) {
      if (preg_match('/assign \w+ role/', $permission)) {
        unset($data['permissions'][$index]);
      }
    }

    $data['permissions'] = array_values($data['permissions']);
    $configDevelSaveEvent->setData($data);
  }

  /**
   * OpenCulturas Discussions is an optional module.
   */
  private function excludeOpenCulturasDiscussions(ConfigDevelSaveEvent $configDevelSaveEvent): void {
    $data = $configDevelSaveEvent->getData();
    if (isset($data['dependencies']['config'])) {
      foreach ($data['dependencies']['config'] as $index => $config_name) {
        if (
          $config_name === 'filter.format.comment_html' ||
          $config_name === 'node.type.comment' ||
          $config_name === 'workflows.workflow.comment' ||
          str_ends_with($config_name, 'field_comments') || str_ends_with($config_name, 'field_comments_mode')
        ) {
          unset($data['dependencies']['config'][$index]);
        }
      }

      $data['dependencies']['config'] = array_values($data['dependencies']['config']);
    }

    if (isset($data['third_party_settings']['field_group']['group_administrative']['children'])) {
      foreach ($data['third_party_settings']['field_group']['group_administrative']['children'] as $index => $config_name) {
        if ($config_name === 'field_comments_mode') {
          unset($data['third_party_settings']['field_group']['group_administrative']['children'][$index]);
        }
      }

      $data['third_party_settings']['field_group']['group_administrative']['children'] = array_values($data['third_party_settings']['field_group']['group_administrative']['children']);
    }

    if ($data['id'] === 'moderated_content' && isset($data['display']['default']['display_options']['filters']['type'])) {
      $data['display']['default']['display_options']['filters']['type']['exposed'] = TRUE;
      $data['display']['default']['display_options']['filters']['type']['operator'] = 'in';
      $data['display']['default']['display_options']['filters']['type']['value'] = [];
    }

    if (isset($data['permissions'])) {
      foreach ($data['permissions'] as $index => $permission) {
        if (str_ends_with($permission, 'comment content') ||
          str_starts_with($permission, 'use comment transition') ||
          str_contains($permission, 'comment_html') ||
          str_ends_with($permission, 'comment revisions')) {
          unset($data['permissions'][$index]);
        }
      }

      $data['permissions'] = array_values($data['permissions']);
    }

    unset(
      $data['content']['field_comments_mode'],
      $data['content']['field_comments'],
      $data['hidden']['field_comments'],
      $data['hidden']['field_comments_mode'],
      $data['third_party_settings']['field_group']['group_comments'],
      $data['settings']['allowed_views']['related_comments'],
    );
    $configDevelSaveEvent->setData($data);
  }

}
