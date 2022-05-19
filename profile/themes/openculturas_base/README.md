# OpenCulturas Base Theme

Base theme for the openculturas distribution.

You can use any theme, but we recommend to build a subtheme.

## Creating a subtheme

To use the subtheme starterkit, follow these steps:

1. Copy the whole `STARTERKIT` directory and paste it to your $PROJECT_ROOT/web/themes/custom directory (if the directory does not exist yet, you can simply create it).
2. Rename the copied directory from `STARTERKIT`  to the machine name you'd like for your custom theme.
3. Replace all occurrences of `STARTERKIT` with the machine name from step 2.
4. Replace all occurrences of `STARTERKIT_TITLE` with the human readable representation of your custom theme's name.
5. Rename `STARTERKIT.info.yml`, `STARTERKIT.libraries.yml` and `STARTERKIT.theme`. Replace `STARTERKIT` with the machine name from step 2.
6. Optionally you can replace the `favicon.ico`, `logo.svg` and `screenshot.png` files with graphics of your liking.

Alternatively you can use a terminal:

```
export THEME_MACHINE_NAME="my_theme"
export THEME_TITLE="My Theme"
mkdir -p web/themes/custom
cp -R web/profiles/contrib/openculturas-profile/themes/openculturas_base/STARTERKIT/ "web/themes/custom/$THEME_MACHINE_NAME"
for f in web/themes/custom/$THEME_MACHINE_NAME/STARTERKIT.*; do mv -i -- "$f" "${f//STARTERKIT/$THEME_MACHINE_NAME}"; done
sed -i "" "s/STARTERKIT_TITLE/$THEME_TITLE/" web/themes/custom/$THEME_MACHINE_NAME/*.yml
sed -i "" "s/STARTERKIT/$THEME_MACHINE_NAME/" web/themes/custom/$THEME_MACHINE_NAME/*.yml
```

When all the above steps are completed, you should already be able to enable the theme in drupal's backend.

## Changing colors

This theme comes with a system for overriding the default colors without the need for any code changes. Search for "Color Schema UI" at any *tab menu*. It will open an UI. If your screen is small you may need decrease font-size with STRG/Command + "-" in browser till you see the "Save" button.


## Chose your workflow

There a three approaches how to create and maintain and update openculturas your subtheme. All three have benefits and downsides, which depend on your design concept. Please read trough all options to decide which one you want to use. 

### Plain CSS

You will mainly use base theme delivered CSS and add your changes in plain CSS.

**Pro**

* very easy
* Quick override of our CSS variables possible 
    * Color variables (if you dont want to use css_color_variables_ui)
    * Font-Sizes, but with a headache as we have some responsive font-size variables
    * Font changes via variable: --font-base, --font-head
    * Outline style: --outline-active
* No install of node / compiling of any kind required
* Easy upgrades: If base theme gets an update most likely all your custom styles will survive as we don't remove css classes if we can avoid it in upgrades


**Contra**
* Limited to only CSS variable changes 
* No GRID variables available to change. No Layout changes
* No Access to pre defined breakpoints (which can make overriding existing Breakpoints very hard.
* Not really suitable for serious redesigning. If you do more than minimal changes you will most probably soon end up with a messy css file, whre you don't remember which line was meant for what. 
* No browsersync for local development
* No autoprefixer, Minification etc: You need to write your own browser-prefixed CSS. 


**Howto**

If you only want to use plain CSS/Javascript you may directly edit `css/custom_style.css`, `css/custom_wysiwyg.css` and `js/custom_scripts.js`. 
That's it. 

### SASS to compile your theme "addons"

The geeneral question about two SASS approaches is, wheather you use **the compiled  culturas_base/css and Javascript**  and add some more in your subtheme. Or you recompile the whole **base theme code and your subtheme code** into your subtheme. 

*SASS to compile your theme "addons"* would mean using the base theme defined variable set, which means you could still change all css variables like with plain CSS, but also use all sass variables to create new features or do overrides  to the basetheme.

Main argument for this solution is that you don't need to recompile the subtheme if base Theme upgrades and will get every new feature themed immediately. 

**pro**

* browsersync for local development, autoprefixer, minification etc.
* Easier to Upgrade: Base theme css is fully independend from your code. You don't need to recompile subtheme if base theme code changes.
* Create your components and even rewrite base styles at your needs on top of base thme
* No need to understand all of our SCSS code.
*  To change a component you might just copy the base themes scss file to your subtheme and adopt it to your needs. 

**contra**

* Limited to only CSS variable changes 
* No GRID variables available to change. Layout changes might be difficult
* Overriding is alwas a little more challenging ad you may need to "undo" things set before. This also comes with a little browser performamce impact.

**Howto**

TODO 
Use libraries-extend in youttheme.info.yml


Chosing a SASS workflow requires you to have node installed. More see --> Compiling frontend.

### SASS compile base- and subtheme together

**pro**

* you are able to change all sass Variables which allows you also to change the grid variables or use your own grid (and exclude ours)
* You can Exclude base them components
* browsersync for local development, autoprefixer, minification etc.
*  To change a component you might just copy the base themes scss file to your subtheme and adopt it to your needs.
* You may adapt the imports to exclude components. In this case you might require to replace the `@import "../templates/**/*.scss";` with your custom selective base theme imports
* Better frontend performance and cleaner CSS because you can improve our components instead of overriding their CSS to your needs.


**contra**
* You are responsible for Upgrades. 
* A base theme style update will require you to recompile your theme
* Especially excluding base theme files from glob imports (templates/**/*.scss) will require you to review base theme SASS changes. 


**Howto**

TODO

Add Exclude base theme `stylesheets-remove`

Chosing a SASS workflow requires you to have node installed. More see --> Compiling frontend.


### Recommend: 

Make CSS VARS
    * $grid-gutter
    * $grid-layout-max-width
    * $grid-column-count macht keinen Sinn, weil man den nicht ändern kann ohne dass alles kaputt aussieht.

Consider removing Globs entirely:
Das würde selektiven import erleichtern. Man sieht dann zb im code, wenn eine neue SASS Datei dazu gekommen ist und kann das im Subtheme leichter nachbauen. Zudem wird der Compiler dadurch schneller und es verhindert, dass ungeschickter SASS code durch File umbenennungen seinen Platz in der Hierarchie ändert.
Die Alternative sind index files (z.B: penculturas_base/scss_config/base/_index.scss)

Consider changing the Grid in Content Area so that Max-Width actually limits Max Width

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

Important: to use the the same node version we use (defined in .nvmrc) you must type `nvm use` in theme dir once, before runnning npm commands.


For running the gulp tasks you also need to have the gulp-cli installed globally: `npm install --global gulp-cli`

As a next step, you have to install all dependencies via npm: `npm install`

Also for using the `watch` task (BrowserSync) you have to copy the `config.js.example` to `config.js` and insert your local host of the setup.
Otherwise it will default to `openculturas.ddev.site` which is the host of the distro's default ddev configuration.

After that you can run tasks via npm or gulp:

* `npm run build` Build CSS / JS for production. Always do this in the end, as 'production' task adds autoprefixes and minification.
* `npm run serve` | `gulp watch` Build CSS / JS for development and start a BrowserSync instance for fast theming.

Please note, that aside from the base config, all SCSS / JS Files are in the `templates` directory, on the same level as their respective component, to keep all elements of a component together.

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
