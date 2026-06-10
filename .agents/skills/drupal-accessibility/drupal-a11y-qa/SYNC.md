---
skill: drupal-a11y-qa
canonical_source:
  - title: Accessibility Bug Reporting Best Practices (upstream)
    url: https://mgifford.github.io/ACCESSIBILITY.md/examples/ACCESSIBILITY_BUG_REPORTING_BEST_PRACTICES.html
    repo: https://github.com/mgifford/ACCESSIBILITY.md
    pulls:
      - The structured field block (Bug ID instance/pattern, URL,
        simplified and full-DOM XPath, WCAG SC, Rule, Severity,
        Frequency, Screen type, Colour mode, HTML snippet).
      - The two-level Bug ID hash inputs (instance_id = page path +
        CSS selector + rule ID + screen type; pattern_id = CSS selector
        + rule ID + screen type).
      - The "use CSS selector — not XPath — as the hash input" rule.
      - The severity taxonomy (Critical / High / Medium / Low) and the
        frequency-amplifies-severity adjustment.
      - The "deduplicate before filing" rule.
      - The 768 px MOBILE_BREAKPOINT for screen_type detection.
  - title: Creating or updating an issue report (Drupal contributor guide)
    url: https://www.drupal.org/community/contributor-guide/reference-information/quick-info/creating-or-updating-an-issue-report
    pulls:
      - The required sections every drupal.org issue carries
        (Problem/Motivation, Steps to reproduce, Proposed resolution,
        Remaining tasks, UI/API/Data model changes, Related issues).
      - The expectation that issues are reproducible on a clean install.
  - title: Issue #3587661 — reference reporting style
    url: https://www.drupal.org/project/drupal/issues/3587661
    pulls:
      - The reporting style used by Mike Gifford on accessibility issues:
        explicit WCAG success-criterion mapping, AT/browser pair declared,
        verbatim tool output, environment block, and clear separation of
        automated vs manual findings.
  - title: WCAG 2.2 quick reference
    url: https://www.w3.org/WAI/WCAG22/quickref/
    pulls:
      - The canonical SC numbers and names used in titles and the
        "WCAG success criterion" line.
  - title: drupal-a11y-fapi (sibling sub-skill)
    url: ../drupal-a11y-fapi/SKILL.md
    pulls:
      - Cross-reference for "Sub-skill that applies" in the Proposed
        resolution section, when the fix is server-side.
  - title: drupal-a11y-dom (sibling sub-skill)
    url: ../drupal-a11y-dom/SKILL.md
    pulls:
      - Cross-reference for "Sub-skill that applies" when the fix is
        in templates, CSS, or layout.
  - title: drupal-a11y-dynamic (sibling sub-skill)
    url: ../drupal-a11y-dynamic/SKILL.md
    pulls:
      - Cross-reference for "Sub-skill that applies" when the fix is in
        JavaScript, AJAX, or test layers.
last_synced: 2026-05-04
---

# Sync sources for drupal-a11y-qa

This file lists the upstream documentation that drupal-a11y-qa/SKILL.md
and REFERENCE_BUG_REPORT.md distil. The reporting style (one issue per
SC, AT/browser pair declared, verbatim tool output, AI disclosure block)
is informed by issue #3587661 and Mike Gifford's wider accessibility
issue queue.

When the Drupal community AI policy publishes a canonical disclosure
template, replace the wording in REFERENCE_BUG_REPORT.md with the
official block and bump `last_synced`.
