<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Traits;

use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use weitzman\DrupalTestTraits\Entity\UserCreationTrait;

/**
 * Trait UserTrait.
 */
trait UserTrait {
  use UserCreationTrait;
  use LoginTrait;

  /**
   * Creates a user with the given role.
   *
   * The role will also used as suffix for the username.
   *
   * @param string $role_name
   *
   * @return void
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
    if ($role instanceof RoleInterface) {
      $users = $entity_manager->getStorage('user')->loadByProperties(['name' => 'user_' . $role_name]);
      if ($users === []) {
        $values = ['roles' => [$role->id()]];
        $account = $this->createUser([], 'user_' . $role_name, $values);
      }
      else {
        $account = reset($users);
      }
      $this->drupalLogin($account);
    }
    else {
      throw new \InvalidArgumentException(sprintf('A role %s does not exists.', $role_name));
    }
  }

  /**
   * To login existing user we need set a new password.
   *
   * @param \Drupal\user\UserInterface $user
   *
   * @see \Drupal\Tests\openculturas\Traits\UserTrait::drupalLogin
   *
   * @return void
   */
  protected function setNewPassword(UserInterface $user) {
    /** @var \Drupal\user\UserStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('user');
    $pass_raw = $this->container->get('password_generator')->generate();
    $user->setPassword($pass_raw);
    $storage->save($user);
    // Add the raw password so that we can log in as this user.
    $user->pass_raw = $pass_raw;
    // Support BrowserTestBase as well.
    $user->passRaw = $user->pass_raw;
  }

}
