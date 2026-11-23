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

    // Reload to get the latest login timestamp. Keep $account, so the caller's
    // object gets the session ID for ::drupalUserIsLoggedIn().
    $unchanged_account = \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($account->id());
    if (!$unchanged_account instanceof UserInterface) {
      throw new \InvalidArgumentException('The account to log in does not exist.');
    }

    $login = \Drupal::service(OneTimeAuthentication::class)
      ->generateOneTimeLoginUrl($unchanged_account, immediate: TRUE)
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
    $session_id = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    // @phpstan-ignore property.notFound
    $account->sessionId = $session_id;
    // @phpstan-ignore assign.propertyType
    $unchanged_account->sessionId = $session_id;
    $this->assertTrue($this->drupalUserIsLoggedIn($account), sprintf('User %s successfully logged in.', $account->getAccountName()));

    $this->loggedInUser = $unchanged_account;
    $this->container->get('current_user')->setAccount($unchanged_account);
  }

}
