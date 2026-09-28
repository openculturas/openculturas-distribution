<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Traits;

use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use weitzman\DrupalTestTraits\Entity\UserCreationTrait;

/**
 * Trait for user related tasks.
 */
trait UserTrait {
  use UserCreationTrait;
  use LoginTrait;

  /**
   * Creates a user with the given role.
   *
   * The role will also used as suffix for the username.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function loginWithRole(string $role_name): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager */
    $entity_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\user\RoleStorageInterface $role_storage */
    $role_storage = $entity_manager->getStorage('user_role');
    $role = $role_storage->load($role_name);
    $user_name = 'user_' . $role_name;
    if ($role instanceof RoleInterface) {
      $users = $entity_manager->getStorage('user')->loadByProperties(
        [
          'name' => $user_name,
        ]
      );
      if ($users === []) {
        $values = ['roles' => [$role->id()]];
        $account = $this->createUser([], $user_name, FALSE, $values);
      }
      else {
        $account = reset($users);
      }

      if (!$account instanceof UserInterface) {
        throw new \RuntimeException(sprintf('The user %s could not be loaded or created.', $user_name));
      }

      $this->drupalLogin($account);
    }
    else {
      throw new \InvalidArgumentException(sprintf('A role %s does not exists.', $role_name));
    }
  }

}
