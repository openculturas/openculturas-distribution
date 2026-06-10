---
skill: drupal-a11y-fapi
canonical_source:
  - title: Drupal Accessibility Coding Standards
    url: https://www.drupal.org/docs/getting-started/accessibility/accessibility-coding-standards
    pulls:
      - The semantic-HTML-first stance (section "Use Semantic HTML").
      - The prohibition on redundant ARIA roles on native elements.
      - The Render API guidance preferring `#type` over raw HTML.
  - title: Hiding Content Properly
    url: https://www.drupal.org/docs/getting-started/accessibility/hide-content-properly
    pulls:
      - Cross-reference for `#title_display => 'invisible'` (the FAPI side
        of the visually-hidden contract; CSS side lives in drupal-a11y-dom).
  - title: Form API reference
    url: https://api.drupal.org/api/drupal/elements/11.x
    pulls:
      - `#title`, `#description`, `#description_display`, `#title_display`,
        `#required` semantics.
      - `fieldset`, `details`, `radios`, `checkboxes` element definitions.
      - `image`, `link`, `item_list`, `table` element definitions.
  - title: Render API overview
    url: https://www.drupal.org/docs/drupal-apis/render-api
    pulls:
      - The `#markup` vs render-array distinction; cache metadata propagation.
  - title: Drupal user-interface standards — tables
    url: https://www.drupal.org/docs/develop/user-interface-standards/table
    pulls:
      - The `#type => 'table'` element shape (header / rows / caption /
        empty / sticky / tabledrag) and when to mark a row cell as a
        header via `'header' => TRUE`.
  - title: Tables Accessibility Best Practices (upstream)
    url: https://mgifford.github.io/ACCESSIBILITY.md/examples/TABLES_ACCESSIBILITY_BEST_PRACTICES.html
    repo: https://github.com/mgifford/ACCESSIBILITY.md
    pulls:
      - The "every data table needs caption + scope" baseline that
        `#type => 'table'` satisfies automatically.
      - The "do not use tables for layout" rule.
last_synced: 2026-05-04
---

# Sync sources for drupal-a11y-fapi

This file lists the upstream Drupal documentation that drupal-a11y-fapi/SKILL.md
distils. It is not a substitute for reading the canonical pages — it is an
audit trail so reviewers can check that the skill has not drifted from the
sources it is supposed to mirror.

When Drupal updates any of these pages, re-read them, update the skill if the
guidance changed, and bump `last_synced`. Do not copy long passages from these
sources into the skill file (per CONTRIBUTING.md, skills are procedural
guidance, not parallel documentation).
