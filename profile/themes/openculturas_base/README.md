# OpenCulturas Base Theme

This is the base theme for the openculturas distribution.

You can use another theme or craft your own one, but it is strongly recommended that you use this theme or at least
build a subtheme that depends on it.

For creating your subtheme there is a starterkit you can copy (starterkits/THEMENAME), rename and change to your liking.

## Creating a subtheme

To use the subtheme starterkit, follow these steps:

1. Copy the whole `starterkits/THEMENAME` directory and paste it to your $PROJECT_ROOT/web/themes/custom directory.
2. Replace all occurrences of `THEMENAME` with the machine name you'd like for your custom theme.
3. Replace all occurrences of `THEMETITLE` with the human readable representation of your custom theme' name.
4. Optionally you can replace the `favicon.ico`, `logo.svg` and `screenshot.png` files with graphics of your liking.

When all the above steps are completed, you should already be able to enable the theme in drupal's backend.

## Overriding default colors

This theme comes with a system for overriding the default colors without the need for any code changes
(except the creation of the subtheme, but you could theoretically simply use the basetheme as default theme, if you do
not need to override any templates / stylings and just want to use different colors).

To do so, visit the following drupal backend sites:

* `/admin/appearance/settings/openculturas_base`: If you do not plan to use a subtheme and want to use the basetheme as-is.
* `/admin/appearance/settings/[THEMENAME]`: If you are using this subtheme starterkit. Replace `[THEMENAME]` with your
  subtheme's machine name.

There you should see some color fields. With those, you are able to change the colors of the whole theme to your liking,
if you want to have some personalization, but do not want to write any code or build the frontend css for it.

## Creating own templates

If you want to overwrite any template via this theme, you can simply add the needed files to the
`templates` directory. It is recommended, to wrap them in directories which describe what kind
of element they are overriding, so they will be easier to manage, when they start adding up.

F.e. a block template like `block--custom.html.twig` would go to the `templates/block/` or, if you want to be more
specific, `templates/block/block--custom` the directory.

Also you can create .SCSS and .JS files for your custom template directly where the template lies.

**BEWARE: The created .scss and .js files will only be picked up if you are using the compilers in the next chapter.**

So f.e. you could have a directoy like this:

```
+ templates
|-+ block
  |-+ block--custom
    |- block--custom.html.twig
    |- block--custom.scss
    |- block--custom.js
```

## Compiling frontend

For any of the following steps to work, you need to have npm installed.
Please install npm the way your OS needs it. We recommend using of a version manager like NVM:

https://docs.npmjs.com/downloading-and-installing-node-js-and-npm#using-a-node-version-manager-to-install-nodejs-and-npm

For running the gulp tasks you also need to have the gulp-cli installed globally: `npm install --global gulp-cli`

As a next step, you have to install all dependencies via npm: `npm install`

Also for using the `watch` task (BrowserSync) you have to copy the `config.example.js` to `config.js` and insert your local host of the setup.
Otherwise it will default to `openculturas.ddev.site` which is the host of the distro's default ddev configuration.

After that you can run tasks via npm or gulp:

* `npm run build` | `gulp build` Build CSS / JS for production.
* `npm run watch` | `gulp watch` Build CSS / JS for development and start a BrowserSync instance for fast theming.
* `npm run sass` | `gulp sass` Build CSS for production.
* `npm run js` | `gulp js` Collect JS for production.
* `npm run dev` | `gulp dev` Build CSS / JS for development.

Please note, that aside from the base config, all SCSS / JS Files are in the `templates` directory, on the same level as
their respective component, to keep all elements of a component together.

On Building they will be picked up and compiled into the `css` and `js`directories.

## Utility class system

There is an utility class system in place for setting grid layouts on fields and views.

To use it, you can add the classes below to a field group, views display or a field display formatter
(via `field_formatter_class` module) in the drupal backend.

### Defining a grid

* `grid-1`: 1 Column Grid
* `grid-2`: 2 Column Grid
* `grid-3`: 3 Column Grid
* `grid-4`: 4 Column Grid

You can also define different Grids per Breakpoint. The used Breakpoints are:

* `xs`
* `s`
* `m`
* `l`
* `xl`

To combine them with any utility-class of the grid-system just append `-[BREAKPOINT]`.

So f.e. the class list `grid-1 grid-2-m grid-3-l` would define, that per default the grid has only one column,
but for medium sized screens it should have 2 columns and for large screens it should have 3 columns.

Also you can define how large the column- and row-gaps should be.

### Defining column- and row-gaps

* `grid-gap-0`: No grid gap
* `grid-gap-1`: 1x Grid Gutter
* `grid-gap-2`: 2x Grid Gutter
* `grid-gap-3`: 3x Grid Gutter

You can also define only the column-gap via:

* `grid-column-gap-0`: No grid column gap
* `grid-column-gap-1`: 1x Grid Gutter
* `grid-column-gap-2`: 2x Grid Gutter
* `grid-column-gap-3`: 3x Grid Gutter

You can also define only the row-gap via:

* `grid-row-gap-0`: No grid row gap
* `grid-row-gap-1`: 1x Grid Gutter
* `grid-row-gap-2`: 2x Grid Gutter
* `grid-row-gap-3`: 3x Grid Gutter

Also you can append the breakpoints to define a gap only for certain breakpoints, just like with the grid definition.

F.e. `grid-gap-0 grid-gap-1-m grid-gap-2-l` would define that on mobile there is no gap, on medium sized screens it is
as big as the normal grid-gutter and on large screens it has a grid-gap of 2x the normal grid-gutter.

### Breaking out of usual content area

Usually the content is limited to a small area in the middle of the page. Some elements, like galleries or big images
should break out of the area to take the full width of the layout.

You can set elements (BEWARE: they have to be an direct child of the grid container) to take the full width of the grid
via giving them the class `grid-child-fullwidth`. Elements with this class will always take the whole available grid space.
They can also, in addition, define their own grid for child elements of themself via the classes above.

### Other layout utility classes

When defining views or field formatters in the displays on the backend, you can also add the classes `grid-inline-block`
or `grid-flex` to define inline-block displays for the children of the grid or flexbox behavior for the grid parent.

* `grid-inline-block` defines, that children of the grid will behave like inline-block elements. This is useful f.e. for a
 list of buttons or links that should not calculate their width depending on a grid, but depending on their text content.
* `grid-flex` gives the parent of the children the `flex` display. So without any other definition, this will try to push
 all children of the container in one row.

### Other utility classes

#### Links

Since links in the main content, the offcanvas menu and footer behave differently because of different
context / background, you can force the styling of any of those styles on any link by adding the respective following
classes to it. Usually this should not be needed, because the context defines which link styling is appropriate.

* `link-default` The default link style.
* `link-offcanvas` The offcanvas menu link style.
* `link-inverted` The inverted link style for footer elements.

#### Text alignment

For aligning elements, f.e. in conjunction with the `grid-inline-block` class, you can add the following classes to
containers.

* `align-left` Align the text to left.
* `align-center` Align the text to center.
* `align-right` Align the text to right.
