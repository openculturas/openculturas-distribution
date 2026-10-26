# OpenCulturas Responsive images

Ships responsive images as a self-contained, **opt-in** module: nothing on an
existing site changes until a site builder installs it (`/admin/modules`) and
switches the image displays on its settings page. Fresh installs get it enabled
and switched by default, because `profile/openculturas.install` installs the
module and calls `SharedConfigSwitcher::switchToResponsive()` in its install
task.

## What it ships

- **6 crop types and a ratio-based image style family** (`oc_16_9`, `oc_2_1`,
  `oc_1_1`, `oc_4_3`, `oc_3_4`, `oc_9_16`), each with 4 to 5 pre-sized
  derivatives and a `responsive_image_style` using `sizes="auto, ..."`, so the
  browser picks the derivative that matches the image's *actual* rendered size
  instead of one fixed style. Every derivative crops to the ratio, converts to
  WebP and uses the matching `oc_*` crop type, for example
  `config/install/image.style.oc_16_9_400.yml`.
- **A free-format "bounds" family** (`oc_bounds_<width>_<height>`) for contexts
  without a fixed crop ratio: `free_format` crop plus an `image_scale` effect
  with both width and height set, so the image is scaled into a maximum box
  and only cropped where an editor drew a free-format crop. Three responsive styles (`oc_bounds_600`, `oc_bounds_800`,
  `oc_bounds_1600`) map those derivatives.
- **Teaser variants** on top of that: `oc_16_9_teaser`, `oc_16_9_teaser_big`,
  `oc_4_3_teaser`, `oc_4_3_teaser_big`, plus `oc_1_1_profile` for profile
  pictures.
- **Switching of the shared image displays** via `SharedConfigSwitcher`:
  `media.image.header_image` (`oc_2_1`), `media.image.teaser_image`
  (`oc_16_9_teaser`), `media.image.teaser_image_big` (`oc_16_9_teaser_big`),
  `media.user_profile_picture.default` and `.profile_image`
  (`oc_1_1_profile`), `media.image.gallery` (`oc_bounds_600`),
  `media.image.gallery_big` (`oc_bounds_1600`) and
  `media.image.content_asset` (`oc_bounds_800`). On top of that it switches
  the media image widget crop lists, the `media.image.token` display, the
  `related_dates_export` view, the `:header_image:` metatag token on the
  `node`, `node__date` and `taxonomy_term` defaults, and the labels of the
  `16_9`, `1_1` and `image_crop` crop types.
- **The gallery paragraph's single-item displays**
  (`paragraph.gallery.default` and `paragraph.gallery.slider`) switch their
  `field_gallery` view mode from `gallery` to `gallery_big`. The view mode
  itself is profile baseline config; switching to it is what the module does,
  because `gallery` is sized for multi-item grid cells and gets upscaled there.
  `grid`, `slider_duo` and `slider_multiple` keep using `gallery`.
- **A crop migration tool** on the settings page
  (`/admin/config/media/openculturas_responsive_images`,
  `src/Form/SettingsForm.php`), plus
  `drush openculturas-responsive-images:migrate-crops`
  (`src/Drush/Commands/MigrateCropsCommand.php`) for the non-interactive case.

## How it works

`CropMigrator` finds every non-`oc_*` crop type that still has crops whose file
has no crop of a matching type, and the settings form offers per row a target
crop type, grouped into "same aspect ratio" and "different aspect ratio".
`migrate()` duplicates the crop, keeping the coordinates when the ratios match
and trimming the rectangle around its center to the largest rectangle with the
target ratio that fits inside the original otherwise, so images are never
distorted. Crops that already have a duplicate on the same file are skipped and
the source to target pair is remembered in state, so a migration can run again
safely and only picks up new crops. The Drush command does the same without a
form, but only for crop types with exactly one aspect-ratio-matching target;
the others are reported as skipped. `scripts/post_tasks.sh` runs it after every
deploy when the module is enabled.

The settings form has a second element that switches the shared config. Only
formatter type and settings are touched, so label, weight, region and third
party settings stay as the site configured them, and reverting only touches
components in the switched state (plus this module's metatag token and crop
type label suffix, which have no state). "Switch to responsive images" is
shown while any component is in the standard or deviating state, "Revert to
fixed image styles" while `hasRevertibleValues()` says a revert would change
anything, so a partly switched site shows both. Both are driven by
`SharedConfigSwitcher`, whose `componentStates()` returns the state of every
switched component.

Switching to responsive leaves components alone that the site changed itself.
A component is in the standard state while it matches the legacy values the
profile ships (for media image formatters ignoring `image_loading`, for crop
lists ignoring the order), and switched while it holds this module's values.
Anything else is a deviation, listed by `SharedConfigSwitcher::deviations()`
under a key like `entity_view_display:media.image.content_asset`. Only keys
passed to `switchToResponsive()` are overwritten. The settings form lists the
deviations in a table with an "Overwrite" checkbox per component and passes the
checked keys. Reverting an overwritten component restores the OpenCulturas
standard, not the site's earlier value; the form warns about this next to the
table.

`hook_install()` switches nothing, it only points the site builder at the
settings page (and mentions the crop migration if any crops are pending).
`hook_uninstall()` reverts the shared config, deletes the migration state and
deletes all crop content entities of this module's crop types; core's config
dependency system removes the crop type config objects itself.

**No theme dependency at all.** The one non-obvious piece: these config objects
need a `responsive_image` breakpoint group, which could have lived in the
`opcult` theme (that's where it started), but a breakpoint group is just as
valid living in a module, so it lives in
`openculturas_responsive_images.breakpoints.yml` and the module installs on any
theme. The `opcult` theme reacts to the module rather than depending on it:
`profile/themes/opcult/src/Hook/OpcultHooks.php` checks
`moduleExists('openculturas_responsive_images')` and serves the page
background from `oc_16_9_1200` or `oc_16_9_2000` (depending on the theme
setting) instead of `header_image`. This follows the installed module, also
before the switch and after a revert; until the crops are migrated, the
styles render a centered 16:9 crop. The theme names no class of this module,
so it keeps working without it.

**The module ships no shared config.** Everything this module owns ships only
in its own `config/install/`; anything it merely *rewires* stays in the
profile's `config/install/` in its legacy state and is changed imperatively. The profile's install task switches on fresh
installs, the settings form switches on existing sites, and `hook_uninstall()`
reverts. `ConfigDevelSubscriber` reverts the switched config again before
config_devel writes profile config, so `composer run cde` keeps the profile's
config in the state of a site without this module. Installing the module alone
changes nothing.

## Dependencies

- `crop:crop`
- `drupal:responsive_image`
- `svg_image:svg_image_responsive`

## Also provides

`preprocess_responsive_image()` in
`src/Hook/OpenculturasResponsiveImagesHooks.php` strips the `auto` entry from
`sizes` on images that are not lazy-loaded, since `auto` is only valid there.