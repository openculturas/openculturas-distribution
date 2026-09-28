<?php

declare(strict_types=1);

namespace Drupal\Tests\openculturas\Traits;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\OneTimeAuthentication;
use Drupal\user\UserInterface;

/**
 * Logs in via a one-time login link and accepts the legal module's terms.
 *
 * Core's UiHelperTrait::drupalLogin() also uses one-time login links, but the
 * legal module closes the session until the terms are accepted, so core's
 * logged-in check fails. Logout is handled by
 * weitzman\DrupalTestTraits\AuthTrait.
 */
trait LoginTrait {

  /**
   * Logs in via a one-time login link and accepts the terms if asked.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to log in.
   */
  protected function drupalLogin(AccountInterface $account): void {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    // Reload to get the latest login timestamp.
    $account = \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($account->id());
    if (!$account instanceof UserInterface) {
      throw new \InvalidArgumentException('The account to log in does not exist.');
    }

    $login = \Drupal::service(OneTimeAuthentication::class)
      ->generateOneTimeLoginUrl($account, immediate: TRUE)
      ->toString();
    $this->drupalGet($login);
    try {
      $this->assertSession()->fieldExists('legal_accept');
      $this->submitForm(['legal_accept' => TRUE], 'Confirm');
    }
    catch (ElementNotFoundException) {
      // The account already accepted the current terms.
    }

    // @see ::drupalUserIsLoggedIn()
    // A runtime property the test framework reads, like in core's
    // UiHelperTrait::drupalLogin(); it is not the entity field.
    // @phpstan-ignore assign.propertyType
    $account->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->assertTrue($this->drupalUserIsLoggedIn($account), sprintf('User %s successfully logged in.', $account->getAccountName()));

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

}
