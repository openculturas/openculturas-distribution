---
name: drupal-a11y-fapi
description: Accessibility rules for Drupal Form API and render arrays. Use when writing or reviewing *.module, *.inc, *Form.php, or any PHP that builds a render array. Covers #title / #description, fieldset grouping, #markup prohibition on interactive HTML, #alt on images, and #type over raw HTML. Loaded by drupal-accessibility.
---

# Drupal accessibility: Form API and render arrays

## When to use this guidance

You are building or modifying a Drupal render array — anywhere that
`#type`, `#title`, `#theme`, or `#markup` appears in PHP. That includes
`*Form.php` classes under `src/Form/`, hook implementations in `*.module`
or `*.inc`, controllers that return render arrays, and Twig preprocessing
that hands a render array back to a template. Most accessibility regressions
in Drupal originate here, because agents reach for hand-written HTML and
ARIA before checking whether Form API would have produced the right
markup automatically.

## Form labels: every element gets `#title` and, where useful, `#description`

Drupal's Form API generates the `<label for="…">` association,
`aria-describedby`, and required-field indicators for you when you supply
the right keys. You do not write those attributes by hand.

```php
$form['email'] = [
  '#type' => 'email',
  '#title' => $this->t('Your email address'),
  '#description' => $this->t('We will only use this to send order receipts.'),
  '#required' => TRUE,
];
```

This single render array produces a labelled input, a description element
linked via `aria-describedby`, and a required indicator that screen readers
announce. Hand-rolling any of those attributes — `aria-label`, `aria-required`,
a separate `<span class="description">`, a manual `<label for>` — duplicates
or conflicts with Form API output.

When the visible label would be redundant in context (a search field next
to a "Search" button, for example), still set `#title` and use
`#title_display => 'invisible'`. That keeps the label in the accessibility
tree while hiding it visually. Never replace `#title` with `#attributes['aria-label']`.

`#description_display` defaults to `'after'`. Use `'before'` when the
description is genuinely instruction the user needs *before* answering
(format requirements, password rules). Do not move the description into
`#title` to make it more prominent — that breaks the label/description
distinction in the accessibility tree.

## Group related controls with `fieldset` or `details`

Related radios and checkboxes need a programmatic group with a legend.
Form API gives you two `#type` values for this — pick by intent, not by
visual style.

```php
$form['shipping'] = [
  '#type' => 'fieldset',
  '#title' => $this->t('Shipping speed'),
  'speed' => [
    '#type' => 'radios',
    '#title' => $this->t('Choose a shipping speed'),
    '#title_display' => 'invisible',
    '#options' => [
      'standard' => $this->t('Standard (5–7 days)'),
      'express' => $this->t('Express (2 days)'),
    ],
  ],
];
```

Use `#type => 'details'` instead of `'fieldset'` when the group should be
collapsible. `details` renders as a native `<details><summary>` element,
which has correct keyboard and screen-reader behaviour out of the box.
Do not build a collapsible group with a `<div>` plus `aria-expanded`
unless you have a Drupal-specific reason that the issue queue has
already accepted.

A standalone `'checkboxes'` or `'radios'` element without an enclosing
`fieldset` or `details` is a violation: the individual `<label>`s do not
substitute for a group label.

## Never put interactive elements inside `#markup`

`#markup` is for inert content. The moment your string contains a
`<button>`, `<a href>`, `<input>`, `<select>`, `<details>`, or any other
focusable element, switch to a render array. Interactive HTML inside
`#markup` bypasses Drupal's attribute system, the theme layer, and the
escaper, and it is the single most common source of inaccessible
buttons-that-are-actually-divs in agent-generated code.

```php
// Wrong — interactive markup as a string.
$build['cta'] = [
  '#markup' => '<a href="/cart" class="button">' . $this->t('Go to cart') . '</a>',
];

// Right — Render API generates the link.
$build['cta'] = [
  '#type' => 'link',
  '#title' => $this->t('Go to cart'),
  '#url' => Url::fromRoute('commerce_cart.page'),
  '#attributes' => ['class' => ['button']],
];
```

The same rule applies to `#prefix` and `#suffix`: they are for inert
wrapper markup only.

## Image render elements: `#alt` is required, decorative is `''`

Every image render element must declare `#alt`. There is no implicit
default. For images that carry meaning, write the alt text the user
needs, translated through `$this->t()`. For purely decorative images
that already have a text equivalent nearby (or genuinely add nothing),
set `#alt => ''` so assistive tech skips them.

```php
$build['hero'] = [
  '#theme' => 'image',
  '#uri' => 'public://campaign/banner.jpg',
  '#alt' => $this->t('Volunteers planting trees on the riverbank.'),
];

$build['decoration'] = [
  '#theme' => 'image',
  '#uri' => 'public://decor/swirl.svg',
  '#alt' => '',
];
```

A missing `#alt` key, `#alt => NULL`, or an alt value that just repeats
the filename ("banner.jpg") is a violation. So is alt text generated by a
model without a human-in-the-loop check on a public-facing image — see
the `drupal-a11y-qa` sub-skill for the disclosure requirement.

## Tables: use `#type => 'table'`

Drupal's table render element handles the accessibility scaffolding for
you. It generates `<th scope="col">` for header strings, wraps rows in
`<thead>` and `<tbody>`, renders `#caption` as `<caption>`, and shows
`#empty` when there are no rows. Reach for it before any other approach.

```php
$build['orders'] = [
  '#type' => 'table',
  '#caption' => $this->t('Open orders for @user', ['@user' => $account->getDisplayName()]),
  '#header' => [
    $this->t('Order'),
    $this->t('Placed'),
    $this->t('Status'),
  ],
  '#rows' => $rows,
  '#empty' => $this->t('You have no open orders.'),
];
```

Set `#caption` on every data table. When the first column is a row
header, mark each cell as a header in the row array
(`['data' => $label, 'header' => TRUE]`) so it renders as
`<th scope="row">`. For sortable rows (drag-and-drop reorder), use
`#tabledrag`; do not hand-build sort handles. Do not build tables with
`#markup` strings or `#theme => 'table'` workarounds when `#type =>
'table'` would do — every workaround risks losing `<caption>`, `scope`,
or the empty-state message.

## Generate semantic elements via `#type`, not raw HTML

When you need a list, a heading, a navigation block, a table, or any
element with semantic meaning, find the Drupal `#type` or `#theme` first.
`#type => 'item_list'` produces a real `<ul>` with the right wrappers.
`#type => 'table'` produces a `<table>` with `<caption>`, `<th scope>`,
and Drupal's responsive helpers. `#type => 'link'` produces an `<a>` with
`#url` validation. Hand-rolling these as `#markup` strings loses the
semantics every time and frequently loses the translation, sanitisation,
and cache metadata too.

The redundant-ARIA rule from the dispatcher applies here as well: do not
add `role="navigation"`, `role="button"`, `role="list"`, or `role="main"`
to the elements Drupal already generates from `#type`.

## Hiding form elements: `#access`, not CSS

When a form element should not be shown to the current user, set
`#access => FALSE` on the render element. Do not reach for CSS
(`display:none`, `.hidden`) or `#attributes['style']` to hide it —
those approaches remove the element visually but leave it in the
accessibility tree, where assistive tech will still encounter it. `#access
=> FALSE` removes the element from both the rendered output and the
accessibility tree, and also prevents the value from being submitted.

```php
$form['internal_id'] = [
  '#type'   => 'hidden',
  '#value'  => $entity->id(),
  '#access' => $this->currentUser()->hasPermission('administer content'),
];
```

The same rule applies to entire fieldsets and fieldgroups. If an
entire `#type => 'fieldset'` should be hidden, set `#access` on the
container, not on each child individually — one flag hides the group
and all its children correctly.

## Progressive disclosure with `#states`: visible ≠ accessible

`#states` wires show/hide logic between form fields — a checkbox that
reveals an extra fieldset, a select that disables a text field. It is
the correct way to implement progressive disclosure in Drupal because
it keeps the logic in the render array rather than custom JavaScript.

```php
$form['opt_in'] = [
  '#type' => 'checkbox',
  '#title' => $this->t('I want email updates'),
];

$form['email_frequency'] = [
  '#type' => 'select',
  '#title' => $this->t('How often?'),
  '#options' => [
    'daily' => $this->t('Daily'),
    'weekly' => $this->t('Weekly'),
  ],
  '#states' => [
    'visible' => [':input[name="opt_in"]' => ['checked' => TRUE]],
  ],
];
```

**Critical caveat:** `#states` hides elements with CSS (`display:none`).
Hidden elements *remain in the accessibility tree* and can receive focus
when their container is scrolled or navigated programmatically. If a
field must be unconditionally inaccessible (permissions-based hiding,
not user-driven disclosure), use `#access => FALSE` instead — it removes
the element from both the DOM and the accessibility tree.

Do not mix `#states` and `#access` on the same element. Use `#states`
for toggling in response to user input; use `#access` for server-side
conditional rendering. If both conditions must hold, compute `#access`
server-side and let `#states` handle the client-side disclosure.

## What not to do

Do not replace `#title` with `aria-label`. The label/aria-label split
hides the visible label from sighted keyboard users while keeping it for
screen readers — that is exactly the opposite of what you want.

Do not synthesise `aria-describedby` by hand. `#description` does it,
correctly, with the matching `id` and translation handling.

Do not group radios or checkboxes with a `<div>` and a heading. Use
`fieldset` or `details`. The `<legend>` element is the only thing assistive
tech will reliably treat as the group label.

Do not put a `<button>`, `<a>`, or any focusable element inside `#markup`,
`#prefix`, or `#suffix`. Switch to a render array.

Do not generate alt text from a model and ship it without review on a
public-facing image. AI-generated alt is acceptable as a starting point;
shipping it unreviewed is the kind of regression the issue queue is
filling up with.

Do not add `role="button"` to a `<button>`, `role="navigation"` to a
`<nav>`, or `role="list"` to a `<ul>` — including when those elements are
emitted by `#type`.

## See also

- [Drupal Accessibility Coding Standards](https://www.drupal.org/docs/getting-started/accessibility/accessibility-coding-standards)
- [Form API reference](https://api.drupal.org/api/drupal/elements/11.x)
- [Render API overview](https://www.drupal.org/docs/drupal-apis/render-api)
- Dispatcher: `../SKILL.md`
- Sibling sub-skills: `../drupal-a11y-dom/`, `../drupal-a11y-dynamic/`, `../drupal-a11y-qa/`
- Source material: Mike Gifford (Drupal Core Accessibility Maintainer)
