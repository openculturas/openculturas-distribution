<?php

declare(strict_types=1);

/**
 * Implements hook_removed_post_updates().
 */
function openculturas_faq_removed_post_updates(): array {
  return [
    'openculturas_faq_post_update_interaction_button_section' => '2.2.0',
    'openculturas_faq_post_update_enable_media_edit' => '2.2.0',
    'openculturas_faq_post_update_enable_media_edit_2' => '2.2.0',
    'openculturas_faq_post_update_source_string_spell_corrections' => '2.2.0',
  ];
}
