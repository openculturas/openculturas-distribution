---
skill: drupal-a11y-dom
canonical_source:
  - title: Drupal Accessibility Coding Standards
    url: https://www.drupal.org/docs/getting-started/accessibility/accessibility-coding-standards
    pulls:
      - Semantic HTML before ARIA in templates.
      - The Attribute-object pattern for class and ARIA state merging.
      - Focus-visible expectations.
  - title: Hiding Content Properly
    url: https://www.drupal.org/docs/getting-started/accessibility/hide-content-properly
    pulls:
      - The `.visually-hidden` / `.hidden` / `[hidden]` / `aria-hidden` decision tree.
      - The `@media (scripting)` `.js-show` / `.js-hide` progressive-enhancement
        pattern (replaces the older `.js-enabled` approach on `<html>`).
  - title: WCAG 2.2 SC 2.5.8 Target Size (Minimum)
    url: https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html
    pulls:
      - The 24×24 CSS-pixel minimum hit area and the spec exceptions
        (inline links, user-agent controls, spacing-based exception).
  - title: WCAG 2.2 SC 2.4.13 Focus Appearance
    url: https://www.w3.org/WAI/WCAG22/Understanding/focus-appearance.html
    pulls:
      - The contrast and size requirements for custom focus indicators.
  - title: Drupal Attribute object reference
    url: https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Template%21Attribute.php
    pulls:
      - The Attribute API used in Twig (`addClass`, `setAttribute`,
        `removeClass`) for safe attribute composition.
  - title: Drupal user-interface standards — tables
    url: https://www.drupal.org/docs/develop/user-interface-standards/table
    pulls:
      - The "render arrays first" stance; templates should defer to
        `#type => 'table'` rather than hand-roll markup.
  - title: Light/Dark Mode Accessibility Best Practices (upstream)
    url: https://mgifford.github.io/ACCESSIBILITY.md/examples/LIGHT_DARK_MODE_ACCESSIBILITY_BEST_PRACTICES.html
    repo: https://github.com/mgifford/ACCESSIBILITY.md
    pulls:
      - The CSS-custom-property + `prefers-color-scheme` pattern for
        switching tokens between modes.
      - The forced-colors mode rules (`Canvas`, `CanvasText`, `Highlight`,
        `ButtonFace` system colours).
      - The relative-luminance zebra-stripe rule (5–10% from background,
        not absolute hex).
      - SVG `fill="currentColor"` so icons follow the active text colour.
  - title: Tables Accessibility Best Practices (upstream)
    url: https://mgifford.github.io/ACCESSIBILITY.md/examples/TABLES_ACCESSIBILITY_BEST_PRACTICES.html
    repo: https://github.com/mgifford/ACCESSIBILITY.md
    pulls:
      - `<caption>` first-child requirement; `<th>` with explicit `scope`
        (`col`, `row`, `colgroup`, `rowgroup`).
      - The responsive-wrapper pattern (`role="region"` +
        `aria-labelledby` + `tabindex="0"` + `overflow-x: auto`).
      - The `aria-sort` placement on `<th>` for sortable columns.
      - The "no layout tables; mark legacy ones with role='presentation'"
        rule.
last_synced: 2026-05-04
---

# Sync sources for drupal-a11y-dom

This file lists the upstream documentation that drupal-a11y-dom/SKILL.md
distils. Reviewers should re-read each source on a Drupal version bump and
update the skill (and `last_synced`) when guidance changes. Per
CONTRIBUTING.md, we link to canonical docs rather than copying their text
into this skill.
