<?php

declare(strict_types=1);

/**
 * Implements hook_removed_post_updates().
 */
function openculturas_custom_removed_post_updates(): array {
  return [
    'openculturas_custom_post_update_set_allowed_values_function_for_field_status' => '2.2.0',
    'openculturas_custom_post_update_set_allowed_values_function_for_field_premiere' => '2.2.0',
    'openculturas_custom_post_update_grant_administer_openculturas_custom_configuration' => '2.2.0',
  ];
}
