---
skill: drupal-a11y-dynamic
canonical_source:
  - title: Drupal.announce JavaScript API
    url: https://api.drupal.org/api/drupal/core%21misc%21announce.es6.js
    pulls:
      - The polite/assertive contract for the shared core live region.
      - The "do not hand-roll aria-live" rule.
  - title: Drupal AJAX commands reference
    url: https://www.drupal.org/docs/develop/drupal-apis/ajax-api/ajax-commands
    pulls:
      - The FocusFirstCommand pattern for returning focus after AJAX.
      - General rules about command-queue ordering and focus timing.
  - title: Issue #3553673 — Adopt Playwright for browser-level testing
    url: https://www.drupal.org/project/drupal/issues/3553673
    pulls:
      - The deprecation of Nightwatch and the move to Playwright +
        @axe-core/playwright for new browser-level accessibility coverage.
  - title: Issue #3338664 — Axe-core scans in PHPUnit
    url: https://www.drupal.org/project/drupal/issues/3338664
    pulls:
      - The render-array scanning approach and the proposed
        AxeRenderArrayTrait::assertAxeClean() shape.
      - Why render-array Axe scans belong in Kernel/Functional layers.
  - title: Light/Dark Mode Accessibility Best Practices (upstream)
    url: https://mgifford.github.io/ACCESSIBILITY.md/examples/LIGHT_DARK_MODE_ACCESSIBILITY_BEST_PRACTICES.html
    repo: https://github.com/mgifford/ACCESSIBILITY.md
    pulls:
      - The "test in both modes" requirement; pairs Playwright's
        `emulateMedia({ colorScheme })` with the AxeBuilder scan.
      - The forced-colours pass and the focus-visible-in-forced-colours
        check.
  - title: Drupal automated testing skill (sibling)
    url: ../../drupal-automated-testing/SKILL.md
    pulls:
      - The #[RunTestsInSeparateProcesses] attribute requirement.
      - Test type selection (Functional vs Kernel) for accessibility scans.
last_synced: 2026-05-04
---

# Sync sources for drupal-a11y-dynamic

This file lists the upstream documentation and core issues that
drupal-a11y-dynamic/SKILL.md distils.

The two referenced core issues (#3553673 Playwright adoption, #3338664
Axe in PHPUnit) are still in flight at the time of this skill's last
review; rerun this sync when either lands so the snippets and trait
references reflect the committed API. The skill currently uses the
proposed `AxeRenderArrayTrait::assertAxeClean()` shape from #3338664 —
update if the committed name differs.
