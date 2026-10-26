<?php

declare(strict_types=1);

namespace Drupal\entity_popover\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A boolean toggle that opens the host entity in a popover dialog.
 *
 * A dedicated field type (rather than reusing the plain "boolean" type
 * directly) so it appears as its own, clearly labelled entry in Field UI's
 * "Add field" list. Behaviourally identical to "boolean" — only the field
 * type identity and its default formatter differ.
 */
#[FieldType(
  id: 'entity_popover',
  label: new TranslatableMarkup('Entity popover'),
  description: new TranslatableMarkup('A toggle that opens the host entity in an alternate view mode inside a native dialog popover.'),
  default_widget: 'entity_popover_checkbox',
  default_formatter: 'entity_popover',
)]
final class EntityPopoverItem extends BooleanItem {
}
