<?php

namespace Drupal\Tests\openculturas\Traits;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * An alternative login/logout for sites that use TFA, SAML, etc.
 */
trait LoginTrait {

  /**
   * Login via a one time password URL.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account interface.
   */
  protected function drupalLogin(AccountInterface $account) {
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    // These 3 lines are the only lines changed from parent::drupalLogin().
    // Reload to get latest login timestamp.
    $account = User::load($account->id());
    $login = $this->user_pass_reset_url($account) . '/login';
    $this->getSession()->visit($login);
    try {
      $this->assertSession()->fieldExists('legal_accept');
      $this->submitForm(['legal_accept' => TRUE], 'Confirm');
    } catch (ElementNotFoundException $elementNotFoundException) {
      // Need to check this only once.
    }
    // @see ::drupalUserIsLoggedIn()
    $account->sessionId = $this->getSession()->getCookie(\Drupal::service('session_configuration')->getOptions(\Drupal::request())['name']);
    $this->assertTrue($this->drupalUserIsLoggedIn($account), (string) new FormattableMarkup('User %name successfully logged in.', ['%name' => $account->getAccountName()]));

    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Logout without asserting user/pass form fields.
   */
  protected function drupalLogout() {
    $this->drupalGet('user/logout');
    // Omit checking for user/pass fields.
    // Clear entity cache entry so drupalLogin() goes to DB.
    \Drupal::entityTypeManager()
      ->getStorage('user')
      ->resetCache([$this->loggedInUser->id()]);

    // @see BrowserTestBase::drupalUserIsLoggedIn()
    unset($this->loggedInUser->sessionId);
    $this->loggedInUser = FALSE;
    \Drupal::currentUser()->setAccount(new AnonymousUserSession());
  }

  /**
   * Generates a unique URL for a user to log in and reset their password.
   *
   * The only change here is use of time() instead of REQUEST_TIME.
   *
   * @param \Drupal\user\UserInterface $account
   *   An object containing the user account.
   * @param array $options
   *   (optional) A keyed array of settings. Supported options are:
   *   - langcode: A language code to be used when generating locale-sensitive
   *    URLs. If langcode is NULL the users preferred language is used.
   *
   * @return string
   *   A unique URL that provides a one-time log in for the user, from which
   *   they can change their password.
   */
  public function user_pass_reset_url(UserInterface $account, array $options = []) {
    $timestamp = time();
    $langcode = $options['langcode'] ?? $account->getPreferredLangcode();

    return Url::fromRoute(
          'user.reset',
          [
            'uid'       => $account->id(),
            'timestamp' => $timestamp,
            'hash'      => user_pass_rehash($account, $timestamp),
          ],
          [
            'absolute' => TRUE,
            'language' => \Drupal::languageManager()->getLanguage($langcode),
          ]
      )->toString();
  }

}
