# Entity Popover

Adds a boolean field type and formatter that opens the host entity in an alternate,
site-builder-chosen view mode inside a native `<dialog popover>` — without a
page reload and without any custom JavaScript.

## Dependencies

None. The field extends Drupal core's boolean field type and works on any fieldable entity
type.

## Adding a popover toggle to a bundle

1. Add an "Entity popover" field to the entity bundle (e.g. "Enable zoom").
2. Depending on your use case, set the default value.
3. If you want to allow individual decisions, make sure the field is accessible in Manage form display
4. Go to Manage display, identify the view modes where you want a popover toogle, enable the entity popover field there.
   Under settings, choose the view mode to render inside the dialog. Any view mode enabled
   for the bundle is available.
4. Enable the field's value on the entities that should show the toggle.

## Filling existing paragraphs

Paragraphs created before the field existed keep the toggle empty. Set them
to the field's default value at `/admin/config/content/entity_popover`, or
for every paragraph bundle at once with
`drush entity-popover:backfill-paragraphs`. Toggles that already have a
value are kept.

## How it works

The formatter renders a trigger button and a `<dialog popover>` sharing a
unique ID (`popovertarget`). Opening and closing is handled entirely by the
browser's native Popover API — no JavaScript ships with this module. The
dialog's content is the host entity, rendered through Drupal's normal entity
view builder in the configured view mode.

A render-context guard (`entity_popover.render_context` service) prevents
infinite recursion if the target view mode itself displays the same
popover-enabled field on the same entity — in that case the nested toggle is
silently skipped.

## Scope

- CKEditor-embedded images are out of scope: there's no field formatter to
  hang a setting on for content embedded directly in rich text. Attaching
  this formatter to the referenced media bundle's own display closes that
  gap without additional code, whenever it's wanted.
- Multi-value fields get one toggle per item, not a single container-level
  toggle. A single toggle for a whole container (e.g. a gallery paragraph)
  is a matter of *where* the field is added — directly on the paragraph
  rather than on each referenced item — not a code difference.

## Roadmap

- There is a `swiffy_slider` integration in the OpenCulturas distribution.
  Contact the maintainers when interested.
