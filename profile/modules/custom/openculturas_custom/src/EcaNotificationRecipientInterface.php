<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an eca notification recipient entity type.
 */
interface EcaNotificationRecipientInterface extends ConfigEntityInterface {

  /**
   * Returns TRUE when the recipient has enabled notifications for the model.
   *
   * @param string $model
   *   ECA model.
   *
   * @return bool
   *   Recipient has enabled notifications for the model.
   */
  public function isEcaModelEnabledForRecipient(string $model): bool;

  /**
   * Returns the configured preferred language of the notification recipient.
   *
   * @return string
   *   Preferred langcode of the notification recipient.
   */
  public function getPreferredLangcode(): string;

}
