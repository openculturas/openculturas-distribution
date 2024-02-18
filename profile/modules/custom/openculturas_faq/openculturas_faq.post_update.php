<?php

declare(strict_types=1);

/**
 * Re-order of interact field_group fields.
 */
function openculturas_faq_post_update_interaction_button_section(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('openculturas_faq', 'openculturas_faq_post_update_interaction_button_section');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}
