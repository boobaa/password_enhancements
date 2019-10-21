<?php

namespace Drupal\password_enhancements;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;

/**
 * Defines a password checker service.
 *
 * @internal
 */
final class PasswordChecker {

  /**
   * The current user proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $account;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  private $time;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  private $userStorage;

  /**
   * Constructs the password checker service.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user proxy.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(AccountProxyInterface $account, TimeInterface $time, EntityTypeManagerInterface $entity_type_manager) {
    $this->account = $account;
    $this->time = $time;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * Checks whether the password is expired for the current user or not.
   *
   * @param \Drupal\password_enhancements\PasswordPolicyInterface $policy
   *   Policy config entity.
   * @param \Drupal\user\UserInterface|null $user
   *   A user for which the password expiry should be checked, if not set it
   *   will use the currently logged in user.
   *
   * @return bool
   *   TRUE if expired, FALSE otherwise.
   */
  public function isExpired(PasswordPolicyInterface $policy, ?UserInterface $user = NULL): bool {
    // Load user if not given.
    if ($user === NULL) {
      $user = $this->userStorage->load($this->account->id());
    }

    // Check password expiry.
    $expire_seconds = $policy->getExpireSeconds();
    $is_password_change_required = $user->get('password_enhancements_password_change_required')->getValue() ? (bool) $user->get('password_enhancements_password_change_required')->getValue()[0]['value'] : FALSE;
    return $is_password_change_required || ($expire_seconds !== PasswordPolicyInterface::PASSWORD_NO_EXPIRY && (int) $user->get('password_enhancements_password_changed_date')->getValue()[0]['value'] < $this->time->getRequestTime() - $expire_seconds);
  }

  /**
   * Checks whether the password expiry warning message should be shown or not.
   *
   * @param \Drupal\password_enhancements\PasswordPolicyInterface $policy
   *   Policy config entity.
   * @param \Drupal\user\UserInterface|null $user
   *   A user for which the warning message should be shown or not.
   *
   * @return bool
   *   TRUE to show the warning message, FALSE otherwise.
   */
  public function isWarningMessageNeeded(PasswordPolicyInterface $policy, ?UserInterface $user = NULL): bool {
    // Load user if not given.
    if ($user === NULL) {
      $user = $this->userStorage->load($this->account->id());
    }
    $expire_warn_seconds = $policy->getExpireWarnSeconds();
    return $expire_warn_seconds !== PasswordPolicyInterface::PASSWORD_NO_WARNING && (int) $user->get('password_enhancements_password_changed_date')->getValue()[0]['value'] + $policy->getExpireSeconds() - $expire_warn_seconds < $this->time->getRequestTime();
  }

}
