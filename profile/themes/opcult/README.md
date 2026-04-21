# OpCult - the second OpenCulturas base theme

This is a **completely new base theme** for the OpenCulturas distribution (OC).

Feel free to craft your own one, but it is strongly recommended that you use this theme as a dependency or
build a sub-theme that depends on OpCult. OpenCulturas is designed to include new features regularly and
those will derive their initial styling from this theme.

If your site is still using or depending on the old *openculturas_base* theme: nevermind. It will still be
around for a while and include new features styling, if necessary. Please note that it incorporates
deprecated SASS functions that cannot easily be resolved.

However, the OC theming principle has always been to **style things as generic as possible** and this is still
true for OpCult.

## Apply your custom look + feel

OpCult comes with many **CSS variables** (custom properties). The fastest way to make OpenCulturas resemble your
brand design is to copy all variables (abstract/_variables.scss) to a custom CSS injector
(/admin/config/development/asset-injector/css). You can change the definitions (colors, font sizes etc.) there
without ever touching any theme files. You can even use a custom font (recommended: store a copy of the webfont
on your web server to avoid regulatory privacy issues).

**Please note**: Do not change CSS variable names in OpCult theme itself. Your changes will be reset with the next software
update.

With advanced CSS skills, you can even change the positions of elements in the layout grid.

In any case please make sure to have a separate development/staging setup for experiments
and better export your CSS injector configuration into a code versioning system like Git.

All OpenCulturas variables are prefixed with `--oc`. The only exception is `--icon-url`. If you notice any deviations,
please notify us via an [issue on Drupal.org](https://www.drupal.org/project/issues/openculturas).

## Creating a sub-theme

If you want to adjust more than just CSS, say, you want to override given templates, use a custom icon font etc.
you would want to create a sub-theme (based on OpCult).

<!-- @todo: Explain how to use a future STARTERKIT, what to do with the mixins etc. -->

Please keep in mind that a custom sub-theme might raise the maintenance costs a bit. For example, when OpenCulturas
releases new features, you might need to include a new icon from your custom icon font.

You will find some commented CSS lines in scss files that are deliberately left here as conceptual ideas.
Feel free to copy these ideas to your sub-theme.

### What about SASS variables?

If you prefer to prevent CSS custom properties failing silently,
copy (not replace!) the variables and define your SASS variables like this:
<code>$color-primary: var(--oc-color-primary);</code>

## Theming principles

* Follow the site-builder-friendly approach. Only use template overrides when you cannot change fields easily in the
display UI (e.g. changing field order or disabling fields)
* Accessibility by default:
  * Prefer rem/em units over pixel units wherever possible (inherit from user's font size preference, think inclusive!)
  * Use accessible color contrasts
  * Theme functions/templates must provide accessible markup
  * All strings must be translatable, even (especially) when they are visually hidden
* No proprietary 3rd-party dependencies for privacy reasons

### A note on units

Besides preferring font-related units (e.g. 0.125rem instead of 2px) we are aiming at full inclusion by design.
As an example: right-to-left layouts should inherit suitable indentations. Therefore, we are moving
towards a writing-mode-agnostic layout with logical properties and values.
https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_logical_properties_and_values
Examples (related to standard english reading direction):

| Physical property       | Logical property                                           |
|-------------------------|------------------------------------------------------------|
| margin-top              | margin-block-start                                         |
| margin-left             | margin-inline-start                                        |
| padding-bottom          | padding-block-end                                          |
| padding-right           | padding-inline-end                                         |
| margin                  | margin (this shorthand is considered as "already logical") |
| max-width               | max-inline-size                                            |
| height                  | block-size                                                 |
| (position:)top          | inset-block-start                                          |
| right                   | inset-inline-end                                           |
| border-bottom-color     | border-block-end-color                                     |
| border-top-right-radius | border-start-end-radius                                    |

Breakpoints remain untouched because there's always a physical "width" of your device.

## Icons

We are no longer using any icon font. Instead, an SVG sprite is rendered from a local folder
and via included IDs used as mask images in CSS.
- see [separate README file](sprite/README.md).

## Layout utility classes and their functions

These utility classes are primarily defined to use with the layout builder.

Layout classes are configured in the layout section.
Block classes are configured via "Manage attributes" for single blocks. Both only apply when the respective container
is rendered.

<dl>
  <dt><code>address-region</code></dt>
  <dd>Layout section class for 2 column sections to modify breakpoint-related wrapping of the "sidebar"</dd>
  <dt><code>no-whitespace-below</code></dt>
  <dd>Layout class attribute for top (5 column) area to avoid whitespace between body + content elements fields</dd>
  <dt><code>block-field-mood-image</code></dt>
  <dd>Attribute attached to the media asset in the hero layout grid.</dd>
  <dt><code>copy-area</code></dt>
  <dd>Because lines of text are limited to a readable length, you can use this class to indent copy text in wider viewports.</dd>
  <dt><code>details</code></dt>
  <dd>Similar to <code>copy-area</code>, dedicated to lists in details components (e.g. on FAQ category pages)</dd>
  <dt><code>teaser-section</code></dt>
  <dd>Layout section class for a dedicated background. Used e.g. for magazine article "mentions".</dd>
  <dd>Block class attribute for the "Main image" field block. Required to properly handle the mood image's
      (non-)presence in some of the header layout variants.
  </dd>
</dl>

## Container queries

We are introducing container queries, meaning that instead of viewport width (breakpoints) the parent container's
width can provide a condition for styling. For example, a profile teaser's image is stacked above the teaser title
unless the parent container is wide enough to place both side by side.

We might introduce a mixin in a future iteration, for the time being the following sizes give an orientation:

- 20rem (profile teasers in "sidebar")
- 32rem (forms; teasers in profile/location views)
- 48rem
- 80ch (copy text)

## Folder structure

```
(/profile/themes/opcult/)
├── favicon.ico                    # OpenCulturas favicon (default theme favicon)
├── gulpfile.js                    # Gulp configuration for compiling SASS + SVG sprite
├── logo.svg                       # OpenCulturas logo (default theme logo)
├── logo_negative.svg              # OpenCulturas logo for dark mode workaround
├── opcult.breakpoints.yml         # Classic breakpoints definitions for Drupal (see also: sass/abstracts/breakpoints)
├── opcult.info.yml                # Theme information and layout regions definition
├── opcult.layout_options          # Layout Builder options configuration (module "Layout Options")
├── opcult.layouts.yml             # OpenCulturas layouts for Layout Builder
├── opcult.libraries.yml           # Asset libraries (CSS + JS files re-/used by this theme)
├── opcult.theme                   # Theme hooks and functions (PHP)
├── package.json                   # Node.js dependencies
├── README.md                      # Documentation (this one)
├── screenshot.png                 # Representative theme image for the "Appearance" overview page
├── theme-settings.php             # Functions for the theme settings UI
│
├── config/                        # Files for the theme settings function
├── css/                           # Compiled CSS files (do not add custom files here)
├── favicons/                      # Favicons for many devices
├── fonts/                         # Font files stored on your webserver
├── images/                        # A few images used by the theme
│   ├── settings/                  # Images for the theme settings page
├── js/                            # Javascript files (_not_ compiled from templates folder in case you're wondering)
├── sass/                          # SASS files for compilation (SCSS)
│   ├── _ckeditor.scss             # Selected files to preprocess for (back-end) CKEditor
│   ├── _print.scss                # Print optimizations
│   ├── _templates.scss            # Forwards all scss files in the templates folder (and sub-folders)
│   ├── ckeditor.scss              # Compile selection (_ckeditor) plus some extras into a standalone CSS file
│   ├── opcult.scss                # Compile all front-end styles into a standalone CSS file
│   ├── theme.settings.scss        # Compile CSS additions for the theme settings page
│   │── fonts.scss                 # Loading the fonts
│   ├── abstracts/                 # SASS functional files
│   │   ├── _index.scss            # Defines the order for folder content compilation
│   │   ├── _buttons.scss          # Variables + placeholder definitions for buttons + similar elements
│   │   ├── _colors.scss           # Variables for all colors (custom properties)
│   │   ├── _effects.scss          # Variables for box shadows, border radiuses, dividers, transitions
│   │   ├── _forms.scss            # Variables for form elements (other than buttons)
│   │   ├── _icons.scss            # Variables + placeholder definitions for icon types (added to or instead of links/button)
│   │   ├── _layout.scss           # Some layout-related placeholders, more relevant: base/_layout.scss
│   │   ├── _mixins.scss           # SASS mixins (breakpoints + icon mixins + some helpers)
│   │   ├── _modal-dialog.scss     # Variables + placeholders for modal dialogs
│   │   ├── _teasers.scss          # Variables + placeholders for teasers, incl. fallback image CSS solution
│   │   ├── _typography.scss       # Variables for fonts (families, sizes) + placeholder definitions for typography
│   ├── base/                      # Basic generic style definitions
│   │   ├── _index.scss            # Defines the order for folder content compilation
│   │   ├── _buttons.scss          # Buttons, tags, and flags
│   │   ├── _details.scss          # Details (collapsible containers, widely used in OC)
│   │   ├── _layout.scss           # Variables page layout calculations and global spacings
│   │   ├── _modal-dialog.scss     # Modals layers/dialogs
│   │   ├── _forms.scss            # Form elements (except buttons)
│   │   ├── _html-tags.scss        # Pure tags, no dependencies (including basic typography)
│   │   ├── _icons.scss            # Diverse (re-usable) class-based icons
│   │   ├── _messages.scss         # Adjust Drupal messages (feedback) in front-end theme
│   │   ├── _reusables.scss        # Generic utility classes for all-purpose usage
│   │   ├── _tables.scss           # Generic tables incl. Drupal-specific utility classes in tables
│   │   ├── _utility-grids.scss    # Grid placeholders (used e. g. in views) - @use required
│   ├── dark-mode                  # Additional files to preprocess for dark mode
│   │   ├── _index.scss            # Defines the order for folder content compilation
│   │   ├── _mixc.scss             # Collected overrides for styles that cannot be handled with dark mode overrides of custom properties
│   ├── sprite                     # Files to generate icon SVG sprite, see dedicated [README](sprite/README.md)
│   ├── templates/                 # Holds per-type subfolders to bundle templates, related SCSS + JS files
│   │   ├── block/                 # Block-specific templates + SCSS files
│   │   ├── components/            # Overrides (templates, SCSS) generic components that fit in no other category
│   │   ├── contrib/               # Overrides (templates, SCSS) of replaceable contributed modules' defaults
│   │   ├── field/                 # Field-specific templates + SCSS files
│   │   ├── flag/                  # Flag-specific templates + SCSS files (Flag examples: bookmark, abuse)
│   │   ├── layout/                # Layout-specific templates + SCSS files (HTML, header, node pages etc.)
│   │   ├── layouts/               # Layout Builder templates + occasional Layout builder CSS fixes
│   │   ├── media/                 # Media item templates + SCSS files (images, videos etc.)
│   │   ├── navigation/            # Menus + related navigation elements templates + SCSS files
│   │   ├── node/                  # Templates + SCSS files for content types ("nodes") including full pages
│   │   ├── paragraph/             # Templates + SCSS files for content building blocks ("paragraphs")
│   │   ├── taxonomy-term/         # Templates + SCSS files for tags ("taxonomy terms") including full pages
│   │   ├── views/                 # Styling for diverse aggregations (teaser lists) built with Views

```
