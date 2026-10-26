<?php

declare(strict_types=1);

namespace Drupal\entity_popover\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\Plugin\Field\FieldWidget\BooleanCheckboxWidget;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A single on/off checkbox widget for the entity_popover field type.
 *
 * Core's own "boolean_checkbox" widget only declares itself applicable to
 * the "boolean" field type, not "entity_popover" — Field UI's widget-plugin
 * select would otherwise have zero valid options for this field type, and
 * fall back to submitting its internal "hidden" placeholder value as the
 * plugin ID on save, which is not a real widget plugin. A dedicated
 * subclass (identical behaviour, its own applicability) is the fix.
 */
#[FieldWidget(
  id: 'entity_popover_checkbox',
  label: new TranslatableMarkup('Single on/off checkbox'),
  field_types: ['entity_popover'],
)]
final class EntityPopoverCheckboxWidget extends BooleanCheckboxWidget {
}
