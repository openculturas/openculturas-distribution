# Entity Popover: Swiffy Slider

Adds a popover to an entity reference field's *existing* formatter, for
bundles where `entity_popover`'s own field can't be added because there's no
wrapping paragraph entity to attach it to (e.g. `field_gallery` directly on
node bundles like `event`, `location`, `profile`).

## Dependencies

- [entity_popover](../../README.md) — provides the shared dialog rendering,
  Twig template and CSS this module reuses.
- [Swiffy Slider](https://www.drupal.org/project/swiffy_slider) — the module
  this is packaged alongside, since it's typically (though not necessarily)
  the field's own live formatter in the cases this targets.
- Layout Builder (core) — optional: not declared as a module dependency,
  since Layout Builder renders each field as its own block plugin, bypassing
  classic entity rendering entirely (see "How it works" below), and not
  every site needs that. When Layout Builder is installed,
  `EntityPopoverSwiffySliderServiceProvider` registers this module's own
  event subscriber for Layout Builder's field blocks; otherwise that
  subscriber is removed from the container and only classic entity
  rendering is covered.

## Adding a popover to a field

1. On the field's display (Manage display, or a Layout Builder field
   block's own configuration), open the field's formatter settings.
2. Check "Enable popover".
3. Choose the "Popover formatter" — any formatter valid for the field, not
   necessarily the same one used for the field's normal display. For
   example, a field displayed as a plain grid can still open a Swiffy
   Slider-powered popover, or vice versa.
4. Save the display, then reopen the formatter settings: the chosen
   formatter's own settings now appear in a nested "Popover formatter
   settings" section. This two-step flow (rather than instantly revealing
   the nested settings) avoids stacking a second layer of AJAX inside
   Field UI's own settings-edit subform.

## How it works

Adds a third-party setting to *any* `entity_reference` field formatter
(`hook_field_formatter_third_party_settings_form()`), independent of which
formatter plugin is currently active for the field. At render time, when
that setting is enabled, a second, independent instance of the chosen
formatter renders the same field items again, and the result is added as a
sibling render array next to the field, wrapped in `entity_popover`'s shared
dialog markup.

Two separate mechanisms cover the two different rendering pipelines fields
can go through:

- `hook_entity_view_alter()` for classic entity rendering, where a field is
  still a sibling within the entity's own render array.
- For Layout Builder: **not** `hook_block_view_alter()` — Layout Builder's
  own field blocks bypass classic entity rendering entirely, and are built
  directly by `\Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray`,
  which never invokes that hook. Reaching the finished build instead means
  subscribing to the same underlying Symfony event
  (`LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY`) at a lower
  priority, so this module's own subscriber runs after Layout Builder's has
  already set the build.

Code that renders a field itself by calling `FieldItemList::view()`, e.g. to
show a field of a referenced entity on the referencing one, goes through
neither pipeline. The `InheritedFieldRenderer` service renders such a field
and adds the popover if the formatter configuration passed in enables it.
Callers that must work without this module can check for the service with
`$container->has(InheritedFieldRenderer::class)`.

## Scope

- Deliberately excludes `entity_reference_revisions` (paragraph reference)
  fields: that's `entity_popover`'s own domain (a dedicated field added
  directly to the paragraph bundle), and allowing both mechanisms on the
  same kind of field would be a confusing, overlapping way to configure the
  same outcome.
- The nested formatter's own third-party settings (e.g. `field_formatter_class`'s
  CSS class field) are intentionally not included in the "Popover formatter
  settings" section — they don't apply to a formatter instance that only
  ever renders inside this dialog.
- This module's setting is suppressed entirely (`FieldPopoverBuilder::hasNativePopoverField()`)
  on any bundle that already has a native `entity_popover`-typed field —
  `entity_popover`'s own field is meant to be the default mechanism, this
  formatter setting only the fallback where no wrapping entity exists to
  attach that field to. This is a per-*bundle* check, not a per-*purpose*
  one: it assumes a bundle only ever wants a single popover. A bundle that
  legitimately wants both — e.g. a native `field_entity_popover` toggle that
  opens the whole node in a "preview" view mode, *and* a separate,
  independently configured popover just for one of its entity reference
  fields (a "gallery lightbox" opening a different view mode) — cannot
  currently have both; the native field wins bundle-wide and the second
  toggle becomes unreachable. Believed to be a rare combination in practice
  (so not solved here), but noted so it isn't lost: if it becomes a real
  need, the check would have to move from "does this bundle have a native
  field" to something field/purpose-specific.

## @todo

- Folding this into the `swiffy_slider` contrib project proper, rather than
  staying a standalone module depending on it, is a later question — not
  blocking this PoC.
