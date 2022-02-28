# OpenCulturas Base Theme

@Todo: Fill readme.


### Compiling frontend

For running the gulp tasks you need to have the gulp-cli installed globally: `npm install --global gulp-cli`


### Utility class system (WIP)

@TODO: Review documentation.

There is an utility class system in place for setting grid layouts on fields and views.

To use it, you can add the classes below to a field group, views display or a field display formatter (via field_formatter_class).

#### Defining a grid

`grid-1`: 1 Column Grid

`grid-2`: 2 Column Grid

`grid-3`: 3 Column Grid

`grid-4`: 4 Column Grid

You can also define different Grids per Breakpoint. The used Breakpoints are:

* xs
* s
* m
* l
* xl

To combine them with any utility-class of the grid-system just append `-[BREAKPOINT]`.

So f.e. the class list `grid-1 grid-2-m grid-3-l` would define, that per default the grid has only one column,
but for medium sized screens it should have 2 columns and for large screens it should have 3 columns.

Also you can define how large the column- and row-gaps should be.

#### Defining column- and row-gaps

`grid-gap-0`: No grid gap

`grid-gap-1`: 1x Grid Gutter

`grid-gap-2`: 2x Grid Gutter

`grid-gap-3`: 3x Grid Gutter

You can also define only the column-gap via:

`grid-column-gap-0`: No grid column gap

`grid-column-gap-1`: 1x Grid Gutter

`grid-column-gap-2`: 2x Grid Gutter

`grid-column-gap-3`: 3x Grid Gutter

You can also define only the row-gap via:

`grid-row-gap-0`: No grid row gap

`grid-row-gap-1`: 1x Grid Gutter

`grid-row-gap-2`: 2x Grid Gutter

`grid-row-gap-3`: 3x Grid Gutter

Also you can append the breakpoints to define a gap only for certain breakpoints, just like with the grid definition.

F.e. `grid-gap-0 grid-gap-1-m grid-gap-2-l` would define that on mobile there is no gap, on medium sized screens it is
as big as the normal grid-gutter and on large screens it has a grid-gap of 2x the normal grid-gutter.

#### Breaking out of usual content area

Usually the content is limited to a small area in the middle of the page. Some elements, like galleries or big images
should break out of the area to take the full width of the layout.

You can set elements (beware, they have to be an direct child of the grid container) to take the full width of the grid
via giving them the class `grid-child-fullwidth`. Elements with this class will always take the whole available grid space.
They can also, in addition, define their own grid for child elements via the classes above.
