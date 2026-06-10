---
name: drupal-a11y-dom
description: Accessibility rules for Drupal templates and stylesheets. Use when writing or reviewing *.html.twig, *.css, *.pcss, or theme libraries. Covers semantic HTML over ARIA, hide-content-properly utilities, WCAG 2.2 SC 2.5.8 target size, focus-visible, dark/forced-colours modes, and reduced-motion. Loaded by drupal-accessibility.
---

# Drupal accessibility: DOM, theming, and CSS

## When to use this guidance

You are writing or modifying browser-facing markup or styles for a Drupal
theme or module — `*.html.twig`, `*.css`, `*.pcss`, `*.scss`, library
declarations, or layout/region definitions. This sub-skill owns the
client-side accessibility surface: which element to reach for, how to
hide content correctly, how big a target needs to be, and how to keep
keyboard focus visible. Drupal has explicit utilities for most of this
and they are not interchangeable; pick the wrong one and assistive tech
either announces hidden content or skips visible content.

## Semantic elements come from the Render API; do not re-wrap them

When the server already produced a `<nav>`, `<main>`, `<button>`, `<ul>`,
`<table>`, or `<details>`, the template's job is to place it, not to
re-decorate it with ARIA. The dispatcher's "no redundant ARIA" rule is
strictest in templates because Twig is where AI output most often adds
`role="navigation"` to a `<nav>` or `role="button"` to a `<button>`.

```twig
{# Wrong — redundant role and a fake button #}
<nav role="navigation" aria-label="{{ 'Main'|t }}">
  <div role="button" tabindex="0" onclick="...">{{ 'Open'|t }}</div>
</nav>

{# Right — native semantics, the Render API emits a real button #}
<nav aria-label="{{ 'Main'|t }}">
  {{ button }}
</nav>
```

`aria-label` on landmarks (`<nav>`, `<aside>`, `<section>`) is appropriate
when more than one of the same landmark exists on the page. That is not
the same as `role`. `aria-label` disambiguates; `role` should not be
restated.

When you need an attribute, use Drupal's `Attribute` object so classes,
data attributes, and ARIA states merge correctly across preprocess hooks
and template overrides. Do not rebuild `attributes.toString()` by hand.

```twig
<article{{ attributes.addClass('node', 'node--' ~ node.bundle) }}>
  {{ content }}
</article>
```

## Hiding content: pick the right tool

Drupal's [hide-content-properly](https://www.drupal.org/docs/getting-started/accessibility/hide-content-properly)
contract is the single most misapplied accessibility utility in
AI-generated theme code. Each option has a specific meaning. They are
not interchangeable.

`.visually-hidden` — visible to assistive tech, hidden from sighted
users. Use for skip links, off-screen labels, and screen-reader-only
context. The class is provided by core; do not redefine it locally.

`.visually-hidden.focusable` — the same element becomes visible again
when it receives keyboard focus. Use for skip links that should appear
on-screen when a keyboard user tabs to them (e.g. *"Skip to main
content"*). The pattern is built into core; do not replicate it with
custom CSS or JavaScript.

`.hidden` and the `[hidden]` attribute — hidden from *everyone*
(sighted users and assistive tech). Use when content should not be
perceived at all in this state. `[hidden]` is the HTML attribute and
should be preferred over the class when you can toggle it from a render
array via `'#attributes' => ['hidden' => TRUE]`.

`aria-hidden="true"` — visible to sighted users, hidden from assistive
tech. Almost always the wrong choice. Use only when sighted users
genuinely need to see something that would be confusing or duplicative
for screen-reader users (e.g., a decorative icon next to a text label).
Never put `aria-hidden="true"` on a focusable element — that creates the
infamous "ghost" focus where the keyboard lands on something the screen
reader cannot describe.

`.js-show` and `.js-hide` with the `@media (scripting)` query — the
correct progressive-enhancement pattern. Content that should only appear
when JavaScript runs goes in `.js-show` (hidden by default, revealed
when scripting is available); content that should disappear when JS
runs goes in `.js-hide`.

```css
/* Default: assume no scripting. */
.js-show { display: none; }
.js-hide { display: block; }

@media (scripting: enabled) {
  .js-show { display: block; }
  .js-hide { display: none; }
}
```

Do not toggle these classes from `Drupal.behaviors` by adding a
`.js-enabled` class to `<html>`; the `@media (scripting)` query handles
it without a flash of incorrect content.

## Target Size: every interactive element is at least 24×24 CSS px

WCAG 2.2 SC 2.5.8 is new and frequently missed. Every pointer or touch
target — buttons, links in menus, icon-only controls, tabs, pagination,
form toggles — must occupy at least 24×24 CSS pixels of hit area unless
one of the spec's exceptions applies (inline links inside a paragraph,
default user-agent controls, or a target whose spacing places its centre
≥24 px from any other target).

Small icons inside larger hit areas are fine; what matters is the
clickable region, not the visible glyph. Use padding to expand the hit
area without making the visual element bigger.

```css
.toolbar__icon-button {
  /* The icon is 16×16, but the button hit area is 24×24. */
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  min-height: 24px;
  padding: 4px;
}
```

When stacking targets vertically (a list of menu items, a tag list),
either give each item a 24×24 hit area or ensure 24 px between target
centres. Do not regress to the WCAG 2.1 SC 2.5.5 *Target Size (Enhanced)*
44×44 reasoning — that's a separate, stricter AAA criterion, not a
substitute.

## Colour modes: light, dark, and forced colours

Drupal core themes (Olivero, Claro) increasingly support dark mode and
forced colours. New theme CSS must work in all three contexts: light,
dark, and the user's forced-colours palette. This is a WCAG 1.4.3 and
1.4.11 obligation, not a stylistic preference — contrast must hold in
*both* modes, not just the one you designed in.

Define every colour as a CSS custom property and switch the property
values inside `prefers-color-scheme: dark`. Components reference the
tokens, not the raw values.

```css
:root {
  --color-text:       #1a1a1a;
  --color-background: #ffffff;
  --color-link:       #0066cc;
  --color-focus:      #004499;
}

@media (prefers-color-scheme: dark) {
  :root {
    --color-text:       #e8e8e8;
    --color-background: #1a1a1a;
    --color-link:       #66aaff;
    --color-focus:      #99ccff;
  }
}
```

For forced-colours mode (Windows High Contrast), use the system colour
keywords and let the browser substitute the user's palette. Do not rely
on author colours for borders or focus rings inside this mode.

```css
@media (forced-colors: active) {
  .card        { border: 1px solid CanvasText; }
  :focus-visible { outline: 2px solid Highlight; outline-offset: 2px; }
}
```

SVG icons should use `fill="currentColor"` so they inherit the active
text colour in both modes; do not bake the icon colour into the SVG.

Zebra stripes on tables must be defined relative to the page
background, not as absolute hex values. Use a 5–10% step from the
background and update the tokens together with the rest of the theme:

```css
:root {
  --color-background:     #ffffff;
  --color-table-row-even: #f2f2f2; /* ~5% darker  */
  --color-table-row-odd:  #e5e5e5; /* ~10% darker */
}
@media (prefers-color-scheme: dark) {
  :root {
    --color-background:     #1a1a1a;
    --color-table-row-even: #272727; /* ~5% lighter  */
    --color-table-row-odd:  #343434; /* ~10% lighter */
  }
}
```

Absolute light-mode stripes on a dark page produce excessive luminance
contrast that strains photosensitive users. In forced-colours mode the
stripes vanish; supply a row border (`border-bottom: 1px solid
CanvasText`) so the table remains scannable.

## Keep focus visible

Use `:focus-visible` (not `:focus`) and never strip the outline without
a replacement that meets WCAG 2.2 SC 2.4.7 and SC 2.4.13. The focus
ring must meet 3:1 contrast in both light and dark modes — pull it from
the `--color-focus` token above.

## Honour `prefers-reduced-motion`

Gate transitions, animations, and transforms behind
`@media (prefers-reduced-motion: no-preference)` (or shorten them inside
`(prefers-reduced-motion: reduce)`). Drupal core honours the preference;
theme animations must too.

## Tables in templates: render arrays first

Most Drupal accessibility regressions in tables happen when a template
hand-rolls `<table>` markup instead of rendering a `#type => 'table'`
render element (see `drupal-a11y-fapi`). Prefer `{{ table }}` so the
Render API handles `<caption>`, `<th scope>`, `<thead>`/`<tbody>`, and
the empty-state message for you.

When a Twig template genuinely must emit `<table>` markup (rendering
external data, custom layouts that the Render API cannot model), the
non-negotiables are: `<caption>` as the first child of `<table>`;
`<th>` with explicit `scope` (`col`, `row`, `colgroup`, or `rowgroup`)
on every header cell; `<thead>` and `<tbody>` present.

Wrap wide tables in a keyboard-scrollable region so low-vision users
who zoom can pan horizontally:

```twig
<div role="region"
     aria-labelledby="{{ table_id }}-caption"
     tabindex="0"
     class="table-responsive">
  <table>
    <caption id="{{ table_id }}-caption">{{ caption|t }}</caption>
    {# … #}
  </table>
</div>
```

`role="region"` exposes the wrapper as a landmark; `aria-labelledby`
points at the caption; `tabindex="0"` lets keyboard users focus the
container and arrow-scroll it.

For sortable columns, put `aria-sort` (`ascending`, `descending`,
`none`) on the `<th>` whose data is currently sorted; Drupal Views
output already does this when sorting is configured. Do not implement
column sort with a `<div>` plus a click handler; use a `<button>` inside
the `<th>` so keyboard users can activate it.

Do not use tables for layout. If a legacy template still does, mark
the table with `role="presentation"` so the AT does not announce a
table grid; the content must still linearise correctly when CSS is
disabled.

## Image alt in templates: mirror the FAPI rule

When an image is rendered directly in Twig (rather than via a render
element), the same rule from `drupal-a11y-fapi` applies: every `<img>`
declares `alt`, decorative images use `alt=""`, and meaningful images
get translated alt via `|t`. Prefer `{{ image }}` from a render array
over hand-written `<img>` tags so the FAPI rules apply automatically.

```twig
{# Acceptable when render array is unavailable. #}
<img src="{{ file_url(uri) }}" alt="{{ 'Volunteers planting trees on the riverbank'|t }}">

{# Decorative — alt="" is required, not optional. #}
<img src="{{ file_url(decoration_uri) }}" alt="">
```

## SVG icons adjacent to visible text

When an SVG icon appears immediately next to a visible text label — a
button, a menu link, a tab — the icon is decorative and must be hidden
from assistive tech. Add `aria-hidden="true"` to the `<svg>` element
so the screen reader only announces the text label once, not
"icon-name [pause] label".

```twig
{# Icon button with visible label — icon is decorative #}
<button type="button">
  <svg aria-hidden="true" focusable="false">
    <use href="#icon-cart"/>
  </svg>
  {{ 'View cart'|t }}
</button>
```

Also set `focusable="false"` on the `<svg>` — IE/Edge historically
placed SVGs in the tab order; the attribute removes that behaviour in
older rendering engines still present in some AT browser combinations.

When the icon is the *only* label (an icon-only button with no visible
text), `aria-hidden="true"` is wrong — the icon is not decorative. In
that case, remove `aria-hidden`, add a `.visually-hidden` text label
inside the button, and keep the icon hidden from AT separately if
needed via `aria-hidden` on just the `<svg>`.

```twig
{# Icon-only button — provide a visually-hidden label #}
<button type="button" aria-label="{{ 'View cart'|t }}">
  <svg aria-hidden="true" focusable="false">
    <use href="#icon-cart"/>
  </svg>
</button>
```

AI output frequently adds `aria-hidden="true"` to icon-only buttons,
stripping the only accessible name and creating an unlabelled control.
Check that every icon button has either a visible text label or an
`aria-label` / `.visually-hidden` equivalent.

## ARIA state attributes in templates

Some ARIA states belong in templates because they reflect visual state
visible to sighted users. Two are especially common in Drupal themes.

`aria-current` — marks the "current" item in a navigation set
(current page, current step, current date). Add it to the active
`<a>` or `<li>` in a navigation menu when the server knows which item
is active. The value is typically `page` for the current page link,
`step` for a wizard step, `date` for a calendar cell.

```twig
<a href="{{ url }}"
   {{ item.in_active_trail ? 'aria-current="page"' : '' }}>
  {{ item.title }}
</a>
```

`aria-expanded` — marks whether a widget that toggles open/closed
(accordion, disclosure, mega-menu) is expanded. Pair it with a
`<button>` or `<summary>`; do not put it on the panel itself.

```twig
<button type="button"
        aria-expanded="{{ is_open ? 'true' : 'false' }}"
        aria-controls="{{ panel_id }}">
  {{ label }}
</button>
<div id="{{ panel_id }}" {{ is_open ? '' : 'hidden' }}>
  {{ content }}
</div>
```

Do not use `aria-expanded` without toggling the attribute in JavaScript
when the user interacts — a static `aria-expanded="false"` that never
changes is worse than no attribute at all.

## What not to do

Do not redefine `.visually-hidden`, `.hidden`, `.js-show`, or `.js-hide`
in theme CSS. Core ships them; redefinition causes drift across themes.

Do not put `aria-hidden="true"` on a focusable element. Either remove
focusability (`tabindex="-1"`, or a non-interactive element) or remove
the `aria-hidden`.

Do not toggle visibility with `display: none` and expect a screen reader
to know about the change. Combine with `Drupal.announce()` (see
`drupal-a11y-dynamic`) when the change conveys information.

Do not make a 16×16 icon button. Expand the hit area to 24×24 with
padding even when the visual icon stays small.

Do not strip default focus outlines without providing a high-contrast
replacement, and do not use `outline: 0` on `:focus` without a
`:focus-visible` rule.

Do not animate without `prefers-reduced-motion: no-preference`. Vestibular
disorders are not edge cases.

Do not add `role="navigation"`, `role="main"`, `role="button"`,
`role="list"`, or any role that just restates the element name.

## See also

- [Drupal Accessibility Coding Standards](https://www.drupal.org/docs/getting-started/accessibility/accessibility-coding-standards)
- [Hiding Content Properly](https://www.drupal.org/docs/getting-started/accessibility/hide-content-properly)
- [WCAG 2.2 SC 2.5.8 Target Size (Minimum)](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html)
- [WCAG 2.2 SC 2.4.13 Focus Appearance](https://www.w3.org/WAI/WCAG22/Understanding/focus-appearance.html)
- [WCAG 2.2 SC 1.4.3 Contrast (Minimum)](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html)
- [WCAG 2.2 SC 1.4.11 Non-text Contrast](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html)
- [`prefers-color-scheme` in Media Queries Level 5](https://www.w3.org/TR/mediaqueries-5/#prefers-color-scheme)
- [`forced-colors` in Media Queries Level 5](https://www.w3.org/TR/mediaqueries-5/#forced-colors)
- [Drupal Attribute object reference](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Template%21Attribute.php)
- Dispatcher: `../SKILL.md`
- Sibling sub-skills: `../drupal-a11y-fapi/`, `../drupal-a11y-dynamic/`, `../drupal-a11y-qa/`
- Source material: Mike Gifford (Drupal Core Accessibility Maintainer)
