<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an eca notification recipient entity type.
 */
interface EcaNotificationRecipientInterface extends ConfigEntityInterface {
  public function isECAModelEnabledForRecipient(string $model): bool;

  public function getPreferredLangcode(): string;
}
