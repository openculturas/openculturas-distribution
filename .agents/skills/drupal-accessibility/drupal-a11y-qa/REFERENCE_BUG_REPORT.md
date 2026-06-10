<!--
  REFERENCE_BUG_REPORT.md
  Fillable Markdown template for Drupal accessibility issues. Copy this body
  into your drupal.org issue and replace each placeholder. If a section does
  not apply, write "N/A — <reason>".

  Companion skill: drupal-a11y-qa/SKILL.md
  Upstream best-practices guide:
    https://mgifford.github.io/ACCESSIBILITY.md/examples/ACCESSIBILITY_BUG_REPORTING_BEST_PRACTICES.html
  Drupal issue guide:
    https://www.drupal.org/community/contributor-guide/reference-information/quick-info/creating-or-updating-an-issue-report
-->

## Accessibility Issue: [Brief Description]

**Bug ID:** `[PREFIX-xxxxxxxx]` (instance) / `[PREFIX-xxxxxxxx]` (pattern)
**URL:** [Full URL where issue was found, including query string and fragment if relevant]
**XPath:** `[Shortest unique XPath]`
**Full DOM path:** `[Full ancestor chain XPath]`
**WCAG SC:** [SC number] — [SC name] (Level [A/AA/AAA])
**Rule:** [Tool name and version] — [Rule ID]
**Severity:** [Critical / High / Medium / Low]
**Frequency:** [N instances on this page; M of P pages affected]
**Screen type:** [desktop / mobile] | **Colour mode:** [light / dark]

### HTML Snippet

```html
[Minimal failing HTML fragment, captured at scan time]
```

### Description

[One paragraph: what is wrong and why it is a barrier for users with
disabilities. Name the affected component in Drupal terms (block, field,
view, region, theme template) so reviewers can locate it.]

### Steps to Reproduce

1. [Step 1]
2. [Step 2]
3. [Step 3]

### Expected Behaviour

[What an accessible implementation should do, with reference to the
relevant WCAG SC.]

### Actual Behaviour

[What happens today. Quote screen-reader output verbatim where relevant.]

### Testing Environment

| Item | Value |
|------|-------|
| Drupal core version | [e.g. 11.1.0] |
| Theme | [e.g. Olivero (default)] |
| Browser + version | [e.g. Firefox 142] |
| Operating system | [e.g. Windows 11 / macOS 15 / Ubuntu 24.04] |
| Assistive technology | [e.g. NVDA 2025.1 / VoiceOver / JAWS / "keyboard only — no AT"] |
| Viewport | [e.g. 1280×800 px] |
| Zoom level | [e.g. 100% / 200% / 400%] |
| Forced colours / contrast mode | [e.g. Inactive / Windows High Contrast / macOS Increased Contrast] |
| `prefers-reduced-motion` | [no-preference / reduce] |
| Tool that detected the issue | [e.g. @axe-core/playwright 4.10 / manual] |

### Impact

[Which disability groups are affected and how. Be specific:
"keyboard-only users cannot dismiss the modal", "NVDA announces only
'button'", "low-vision users at 200% zoom lose the focus indicator
against the background gradient". Generic "users with disabilities"
is not enough.]

### Suggested Fix

**Sub-skill that applies:** [drupal-a11y-fapi | drupal-a11y-dom | drupal-a11y-dynamic]

[Code or prose. When citing a sub-skill rule, name it explicitly so
reviewers can verify the fix follows project conventions.]

```html
<!-- Current (broken) -->
[snippet]

<!-- Proposed -->
[snippet]
```

### Related Issues

- #[id] — [short title]
- #3553673 — Adopt Playwright for browser-level testing (reference if test changes apply)
- #3338664 — Axe-core scans in PHPUnit (reference if a PHPUnit assertion is added)

### Remaining Tasks

<!--
  Tests pass AND manual checks pass before RTBC. Manual checks are not optional.
-->

- [ ] Patch implemented per sub-skill guidance.
- [ ] Playwright a11y scan added or updated (drupal-a11y-dynamic).
- [ ] PHPUnit Axe assertion added or updated (drupal-a11y-dynamic).
- [ ] Manual keyboard-only walk passes.
- [ ] Manual screen-reader pass with [NVDA / VoiceOver / JAWS].
- [ ] 200% zoom check passes; 400% zoom check passes if SC 1.4.10 applies.
- [ ] Light **and** dark mode pass (when the theme supports `prefers-color-scheme`).
- [ ] Forced-colours / Windows High Contrast pass (if visual change).
- [ ] Change record drafted (if user-facing).
- [ ] Reviewed by an accessibility maintainer.

### User Interface Changes

[Describe any visible change. For SC 2.5.8 fixes, note that the visible
glyph stays the same and only the hit area changes (or describe the
visible change if intentional). Include before/after screenshots when
the change is visual.]

### API Changes

N/A
<!-- Or describe added/changed/removed APIs. -->

### Data Model Changes

N/A

### AI Disclosure

<!--
  Required if any part of the patch, tests, or issue text was generated
  or substantively edited by an AI tool. Required even when a human
  reviewed the output.
-->

**AI disclosure**

This contribution was prepared with assistance from an AI coding tool.
- Tool: [tool name and version, e.g. Claude Code]
- Used for: [specific tasks, e.g. drafting the patch, generating tests, drafting this issue]
- Reviewed by: [human reviewer's drupal.org username]
- Skills loaded: drupal-accessibility (sub-skills: [comma-separated list])
