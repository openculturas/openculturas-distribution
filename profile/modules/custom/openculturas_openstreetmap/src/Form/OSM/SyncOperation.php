<?php

declare(strict_types=1);

namespace Drupal\openculturas_openstreetmap\Form\OSM;

use function t;

enum SyncOperation: string {
  case Pull = 'pull';
  case Push = 'push';
  case None = 'none';

  public static function asOptionList(bool $is_new): array {
    $options[self::Pull->value] = t('Pull');
    $options[self::None->value] = t('None');
    if (!$is_new && self::canPush()) {
      $options[self::Push->value] = t('Push');
    }

    return $options;
  }

  public static function canPush(): bool {
    static $can_push;
    if (isset($can_push)) {
      return (bool) $can_push;
    }

    $state = \Drupal::state();
    $push_enabled = $state->get('openculturas_openstreetmap.settings.push_enabled', FALSE);
    if ($push_enabled && \Drupal::currentUser()->hasPermission('access openstreetmap push operation')) {
      $can_push = TRUE;
      return TRUE;
    }

    $can_push = FALSE;
    return FALSE;
  }

}
