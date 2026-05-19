# OpenCulturas Sub (opcult_starterkit)

A starterkit sub-theme for **OpenCulturas Next** (opcult).

For full documentation of the base theme, see the [opcult README](../../profiles/contrib/openculturas-distribution/profile/themes/opcult/README.md).

## How it works

- **CSS inheritance**: opcult's compiled `css/opcult.css` and `js/opcult.js` are
  loaded automatically by Drupal's theme system. The sub-theme only adds its own
  `css/opcult_starterkit.css` on top.
- **SASS access**: The gulpfile configures SASS `loadPaths` pointing to opcult's
  `sass/` directory. This lets you `@use` opcult's abstracts (variables, mixins,
  placeholders) and utility grids in your own SCSS.
- **Template inheritance**: All opcult Twig templates are inherited. Override
  individual templates by placing them in this theme's `templates/` directory.
- **Theme settings**: Background image, hero layout, and layout builder settings
  are inherited from opcult's `theme-settings.php`.

## Setup

Now that you have copied your custom theme files from the starterkit, some last manual adjustments are required.

### TL;DR

1. Update `opcultSassPath` in `gulpfile.mjs`
2. Run `npm install` in your theme's root folder
3. Compile SCC with `npm run build`
4. Enable your theme and set as default
5. Verify block placements + templates
6. Check theme settings in the UI and adjust to your liking
7. Provide your site logo
8. Provide your custom favicon variants


### 1. Adjust your gulpfile.mjs

Update the `opcultSassPath` variable in `gulpfile.mjs` to point to opcult's
`sass/` directory relative to the new location. This would normally be:
`../../profiles/contrib/openculturas-distribution/profile/themes/opcult/sass`
but please verify.

### 2. Install Node.js dependencies

```bash
cd profile/themes/opcult_starterkit
npm install
```

### 3. Compile CSS

```bash
npm run build
```

For development with auto-rebuild on changes:

```bash
npm run watch
```

### 4. Enable the theme

Navigate to **Admin > Appearance** (`/admin/appearance`) and set
*OpenCulturas Sub* as the default theme.

### 5. Adjust blocks, check block placements

- Rename the templates in templates/block. Replace `opcult-starterkit` in the file names with your chosen machine name
(replace underscores with dashes in this case).
- Clear caches (run `drush cr`)

Drupal might not have automatically copied all block placements when you switched themes. Especially when your
custom theme does not use the exact same theme regions as its base theme (regions defined in opcult-starterkit.info.yml).
In that case you must reassign blocks from opcult to the sub-theme:

- **Via admin UI**: Go to **Admin > Structure > Block layout**
  (`/admin/structure/block`) and place blocks in the appropriate regions.
- **Via Drush** (if available): Export opcult's block config, rename the theme
  references, and reimport.
- **Via config export**: If you have access to the site's configuration,
  export the `block.block.*` config entities, change the `theme:` key from
  `opcult` to `opcult_starterkit`, and reimport.

### 6. Verify theme settings

Visit **Admin > Appearance > Settings > OpenCulturas Sub**
(`/admin/appearance/settings/opcult_starterkit`) to verify that background
image, hero layout, and layout builder settings are available.

### 7. Provide your logo

Plase a logo.svg and logo_negative.svg in your theme's root folder with your logo. If your logo does not need a "negative" version for dark mode,
add a copy with the "negative" name nevertheless. That way the base theme inheritance handles everything.

### 8. Provide your favicons

Overwrite/edit the files in favicons/ folder and check the site.webmanifest. See [favicons README](favicons/README.md).

## Customization

### Overriding CSS custom properties

The fastest way to customize the look and feel is to override opcult's CSS
custom properties. All variables are prefixed with `--oc`.

In `sass/opcult_starterkit.scss`:

```scss
:root {
  --oc-color-primary: #your-brand-color;
  --oc-color-secondary: #your-secondary-color;
  --oc-color-link: #your-link-color;
  --oc-font-family-default: "YourFont", sans-serif;
  --oc-font-family-head: "YourFont", sans-serif;
}
```

See opcult's `sass/abstracts/_colors.scss` for the full list of color variables
and `sass/abstracts/_typography.scss` for typography variables.

**Please note:** Always copy anything you want to change to files in your theme folder. Otherwise any
software updates will overwrite your customizations.

### Using opcult's SASS features

In any SCSS file, you can access opcult's SASS abstracts:

```scss
@use 'abstracts' as opcult;

.my-component {
  @extend %button;

  @include opcult.breakpoint(m) {
    padding: 2rem;
  }
}
```

For grid utilities:

```scss
@use 'base/utility-grids' as grids;

.my-grid {
  @extend %grid;
  @extend %grid-3;
}
```

See <a href="#how-to">How-to</a> section below with more examples.

### Using a custom font

See [`fonts/README.md`](fonts/README.md) for detailed instructions on adding
custom fonts and changing the appropriate custom properties.

### Overriding templates

Place Twig template overrides in the `templates/` directory, mirroring opcult's
template structure. For example, to override the page template:

```
templates/layout/page.html.twig
```

### Adding custom JavaScript

1. Create your JS file in `js/opcult_starterkit.js`
2. Uncomment the JS section in `opcult_starterkit.libraries.yml`
3. Clear Drupal's cache

### CKEditor styles

The sub-theme compiles its own `css/ckeditor.css` for the WYSIWYG editor.
opcult's base CKEditor styles are included automatically.

To add sub-theme specific CKEditor styles, edit
`sass/_ckeditor-opcult_starterkit.scss`.

## Known considerations

- **Duplicate CSS custom properties**: Because the sub-theme's SCSS uses opcult's
  abstracts via `@use`, CSS custom property declarations (`:root {}`, `body {}`)
  and `@font-face` rules from opcult are duplicated in the sub-theme's compiled
  CSS. This is harmless -- the base theme's CSS provides the canonical values and
  correct font paths. The sub-theme's declarations simply reinforce them (and can
  be overridden with your custom values).

- **SVG icon sprite**: The sub-theme inherits opcult's SVG sprite and icon mixin.
  If you need custom icons, you'll find everything prepared to run an SVG sprite generator. See
[dedicated README file](sprite/README.md). OpenCulturas is using custom properties to address the icons.
Your custom sprite will produce identical custom properties (as long as your SVG files have matching file names),
thus overriding the icons. If you prefer to use your own icon (font) system, check opcult/abstracts/_mixins.scss
for the icon mixin and opcult/base/_icons.scss for the given mixin usage.

<!-- @todo Instructions how to _exclude_ OC icons custom properties? -->

## Folder structure

```
(profile/themes/opcult_starterkit/)
├── config/
│   ├── install/
│   │   └── opcult_starterkit.settings.yml  # Default theme settings
│   └── schema/
│       └── opcult_starterkit.schema.yml    # Settings schema
├── css/                                     # Compiled CSS (gitignored)
├── fonts/                                   # Custom web fonts
│   └── README.md                            # Font usage instructions
├── sass/                                    # SCSS source files
│   ├── opcult_starterkit.scss               # Main SCSS entry point
│   ├── ckeditor.scss                        # CKEditor SCSS entry point
│   └── _ckeditor-opcult_starterkit.scss     # CKEditor sub-theme additions
├── templates/                               # Twig template overrides
├── .gitignore
├── gulpfile.mjs                             # Gulp build configuration
├── opcult_starterkit.info.yml               # Theme definition
├── opcult_starterkit.libraries.yml          # Asset libraries
├── opcult_starterkit.theme                  # Theme preprocess functions
├── package.json                             # Node.js dependencies
└── README.md                                # This file
```

<a id="how-to"></a>
## How-to

### Add an icon

```
button.rss {
  @include icon(rss);
}
```

```
a.child-care-link {
  @include icon(baby-carriage);
}
```

Default parameters: iconset "light", position "before". Parameter order:
icon, iconset, position. You can change the defaults as follows:

```
.bookmark--active {
  @include icon(bookmark, fill);
}

.element_with_arrow_after {
  @include icon(arrow-down, light, after);
}
```

### Visually replace a link or button with an icon

The given labels are preserved for screen readers, the replacement is merely visual.
For custom elements, make sure to provide a text alternative and provide translations
in a multi-language setup.

```
.button--edit a {
  display: inline-block;
  @include icon(pencil-simple);
  @extend %icon-only;
}
```

Please note that %icon-only increases the font size (--oc-font-size-icon) for
standards conform touch screen usability. Only reduce that size when it's really required
- like this:

```
table .button--edit a {
  display: inline-block;
  @include icon(pencil-simple);
  @extend %icon-only;
  --oc-font-size-icon: var(--oc-font-size-icon-default);
}
```

### Control the number of grid columns

For a custom view, simply use the prepared grid placeholders:

```
.my-custom-view .view-content {
  @extend %grid;
  @extend %grid-3;
}
```

Please note that the load more-pager adds an extra div inside `.view-content`,
in this case you'd use the dedicated class:

```
.my-custom-view .views-infinite-scroll-content-wrapper {
  @extend %grid;
  @extend %grid-3;
}
```

The grid placeholders (e.g. `%grid-4`) already contain breakpoints (which is basically all they do).
If you want to change the number of grid columns in a certain view for a certain breakpoint
simply change the custom property per breakpoint:

```
.terms .view-content {
  @include opcult.breakpoint(xxl) {
    --oc-grid-item-count: 6;
  }
}
```


