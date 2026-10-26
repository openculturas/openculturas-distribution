<?php

declare(strict_types=1);

namespace Drupal\entity_popover_swiffy_slider;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Renders a field directly, wrapped with entity_popover's dialog if enabled.
 *
 * The dialog is only added if the formatter configuration passed in has this
 * module's third-party setting enabled. For code that renders a field
 * outside classic entity rendering and Layout Builder by calling
 * FieldItemList::view() itself, e.g. to show a field of a referenced entity
 * on the referencing one. Neither of this module's own hooks sees that
 * rendering path, so without this service the popover is missing there.
 */
final readonly class InheritedFieldRenderer {

  public function __construct(
    private RendererInterface $renderer,
    private PopoverFormatterSettings $popoverFormatterSettings,
  ) {}

  /**
   * Renders $fieldName on $entity, wrapped with the popover if enabled.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to render the field from.
   * @param string $fieldName
   *   The field name on $entity to render.
   * @param array $displaySettings
   *   Formatter configuration to render $fieldName with.
   */
  public function render(FieldableEntityInterface $entity, string $fieldName, array $displaySettings): string|MarkupInterface {
    $wrapper = [
      'field' => $entity->get($fieldName)->view($displaySettings),
    ];

    FieldPopoverBuilder::wrapFieldWithPopover($wrapper, 'field', $this->popoverFormatterSettings);

    // Not casting to string: render() returns a MarkupInterface marked
    // safe, and casting would call __toString(), losing that status and
    // causing this markup to be re-filtered wherever it ends up (e.g. as
    // #markup), which strips tags like <dialog> and attributes like
    // popovertarget that aren't on the default allowed list.
    return $this->renderer->render($wrapper);
  }

}
