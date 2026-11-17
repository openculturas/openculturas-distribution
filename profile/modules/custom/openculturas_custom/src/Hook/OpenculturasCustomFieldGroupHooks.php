<?php

declare(strict_types=1);

namespace Drupal\openculturas_custom\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Field group UX hook implementations for openculturas_custom.
 */
class OpenculturasCustomFieldGroupHooks {

  /**
   * Field names inside group_editorial whose own AJAX reloads the subform.
   *
   * Adding a field here only requires its machine name: the match is on the
   * array_parents segment right after the subform, not the field's own
   * internal widget structure (which can differ per field/column).
   */
  private const array AJAX_RELOAD_FIELDS = ['paragraph_view_mode'];

  /**
   * Implements hook_field_group_form_process_build_alter().
   *
   * The "Display type" field (paragraph_view_mode) lives inside the
   * "Editorial + layout" details on wrapper_section and teaser_wrapper and
   * reloads the whole paragraph subform via AJAX on change (see
   * ParagraphViewModeWidget). The rebuilt subform re-derives #open from the
   * static format_settings default (closed), so without this, the details
   * collapses every time that field - only reachable while the details is
   * open - is used. Since the field can only trigger this while the group
   * is already open, re-opening it here is always correct.
   */
  #[Hook('field_group_form_process_build_alter')]
  public function fieldGroupFormProcessBuildAlter(array &$element, ?FormStateInterface $form_state, array &$complete_form): void {
    if (!$form_state instanceof FormStateInterface || empty($element['#fieldgroups']['group_editorial']) || !isset($element['group_editorial'])) {
      return;
    }

    $triggeringElement = $form_state->getTriggeringElement();
    if ($triggeringElement === NULL) {
      return;
    }

    $subformParents = $element['#array_parents'] ?? [];
    $subformDepth = count($subformParents);
    $triggeringParents = $triggeringElement['#array_parents'] ?? [];
    $fieldName = $triggeringParents[$subformDepth] ?? NULL;

    if ($fieldName !== NULL
      && array_slice($triggeringParents, 0, $subformDepth) === $subformParents
      && in_array($fieldName, self::AJAX_RELOAD_FIELDS, TRUE)
    ) {
      $element['group_editorial']['#open'] = TRUE;
    }
  }

}
