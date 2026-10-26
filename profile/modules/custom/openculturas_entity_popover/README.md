# OpenCulturas Entity Popover

Project-specific wiring for [entity_popover](../entity_popover/README.md) and
[entity_popover_swiffy_slider](../entity_popover/modules/entity_popover_swiffy_slider/README.md),
kept separate from those modules so they stay generic and reusable in other
projects — matching the same "add-on module depends on the generic one"
pattern as `openculturas_responsive_images`.

## What it wires up

- Adds `field_entity_popover` to the `gallery` and `media` paragraph
  bundles, displayed on `paragraph.gallery.grid`/`slider_duo`/`slider_multiple`
  and `paragraph.media.default`.
- Adds `entity_popover_swiffy_slider`'s third-party setting to
  `field_gallery` on the bare node bundles `event`, `location`, `profile`
  (both their `full` and `full_lb` view modes, as a Layout Builder field
  block or a regular display component), reusing whatever formatter
  is currently live on that field.
- Uses the `gallery_big` media view mode as the popover's target for the
  node bundles above. It is part of the profile's baseline config and
  switched by `openculturas_responsive_images` like any other image display.

## How it works

`hook_install()` wires everything up (`DisplayWirer::wireAll()`), so a site
using only `openculturas_base` (without this module) is untouched. Every
wiring step checks whether it has already happened before doing anything,
so running it again is safe. `hook_uninstall()` takes back exactly what it
wired - the `entity_popover_swiffy_slider` setting on `field_gallery` of the
node displays above (`NodeDisplayUnwirer`) and the paragraph field
(`FieldUnwirer`). Popovers a site builder set up on their own keep both their
setting and the display's dependency on `entity_popover_swiffy_slider`, which
stays installed when this module is uninstalled.

The profile's `config/install` must not reference the popover: it is
imported before this module is installed, and update hooks import from it
on sites without this module. `ConfigDevelSubscriber` strips the wiring
(`DisplayUnwirer`) from profile displays exported by config_devel
(`composer run cde`).

## Dependencies

- `entity_popover`, `entity_popover_swiffy_slider` — hard dependencies.
