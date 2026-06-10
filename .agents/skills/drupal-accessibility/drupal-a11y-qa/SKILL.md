---
name: drupal-a11y-qa
description: Issue-authoring rules for Drupal accessibility work. Use when drafting or reviewing an accessibility issue, MR description, or change record. Enforces structured Bug ID / WCAG SC / severity field block, one-issue-per-criterion scoping, AI disclosure, and manual checks before RTBC. Pair with REFERENCE_BUG_REPORT.md. Loaded by drupal-accessibility.
---

# Drupal accessibility: QA and issue authoring

## When to use this guidance

You are about to file an accessibility issue on drupal.org, write a
merge-request description for an accessibility change, draft a change
record, or triage someone else's accessibility report. This sub-skill
is the contract every such artefact must meet before it leaves the
agent. The companion file `REFERENCE_BUG_REPORT.md` is the fillable
template; this SKILL.md explains the rules behind it.

The fields below are non-negotiable. The format aligns with the
upstream [Accessibility Bug Reporting Best Practices](https://mgifford.github.io/ACCESSIBILITY.md/examples/ACCESSIBILITY_BUG_REPORTING_BEST_PRACTICES.html)
guide so reports are interoperable across projects, with Drupal-specific
additions for theme, AT pair, and sub-skill citation.

## Tool hierarchy: automated first, manual second, AI-assisted last

Accessibility work follows a strict detection order. Each tier covers
what the previous tier cannot; none replaces the one before it.

**1. Automated tools — run these first, every time.**
`@axe-core/playwright`, Lighthouse CI, and pa11y-ci are battle-tested
by the industry, produce stable rule IDs and CSS selectors, run in
seconds, and catch the same class of issue reproducibly across runs.
They are the authoritative source of truth for whether a fix worked.
Do not open source files, do not ask an AI to review markup, and do
not form opinions about the code until the automated scan has run and
its output is in front of you.

**2. Manual AT testing — after automated tools, for what they miss.**
Automated tools catch roughly a third of WCAG issues. Manual
keyboard-only walks, screen-reader passes, zoom checks, and
forced-colours checks cover the rest — focus order, announcement
quality, cognitive load, reflow. Manual checks belong in the issue
*after* automated findings are filed, not instead of them.

**3. AI-assisted review — last, for root cause and patch drafting.**
AI code review and analysis can help identify *why* an automated
finding exists and suggest a fix. It is not a detection method. An
AI that reads template markup and reports "I see a potential
accessibility issue" without a corresponding automated tool finding is
producing a hypothesis, not a confirmed bug. Do not file issues based
solely on AI code inspection.

This order is not a preference — it is a rule. Industry-trusted tools
like axe-core have been validated against real assistive technology
and real users. Manual review and AI-assisted review have not been
validated at that scale and introduce inconsistency. Default to the
tools.

## Automated scan first — always

Before reading source code or writing issue text, run an automated
scan against the live (or locally running) site. Automated tools
produce the CSS selectors, HTML snippets, and WCAG rule IDs the
structured field block requires; manual code reading cannot substitute
for them because it misses runtime-rendered state (colour values,
computed ARIA, dynamic content).

**Minimum scan setup** (run once per project, commit the config):

```bash
npm install -D @playwright/test @axe-core/playwright
npx playwright install chromium
```

```javascript
// a11y-scan.spec.js  — run with: npx playwright test a11y-scan.spec.js
import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const BASE_URL = process.env.BASE_URL ?? 'http://localhost';
const PAGES = [
  { name: 'home',    path: '/' },
  { name: 'content', path: '/blog' },
];

for (const colorScheme of ['light', 'dark']) {
  for (const pg of PAGES) {
    test(`${pg.name} — WCAG 2.2 AA (${colorScheme})`, async ({ page }) => {
      await page.emulateMedia({ colorScheme });
      await page.goto(BASE_URL + pg.path, { waitUntil: 'networkidle' });
      const results = await new AxeBuilder({ page })
        .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
        .analyze();
      expect(results.violations).toEqual([]);
    });
  }
}

test('home — forced colours', async ({ page }) => {
  await page.emulateMedia({ forcedColors: 'active' });
  await page.goto(BASE_URL + '/');
  const results = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag22aa'])
    .analyze();
  expect(results.violations).toEqual([]);
});
```

Run against light, dark, and forced-colours modes in a single spec file
(see `drupal-a11y-dynamic` for the full pattern). Export results to JSON
(`--reporter=json`) so violations can be parsed for issue filing.

**Scan, then read source.** Use axe output as the authoritative issue
list. Only open template or JS files after you have a confirmed
violation with a CSS selector, HTML snippet, and rule ID from the scan.
Manual code review supplements the scan; it does not replace it.

**Confidence scores.** Axe flags some violations as `incomplete`
(needs-review) rather than confirmed. Mark those findings
`Confidence: Needs manual confirmation` in the issue until a human
verifies them. Do not file `incomplete` results as confirmed bugs.

**Deduplicate before filing.** Group identical rule + CSS selector
combinations across pages into a single issue with a frequency count
("5 instances across 3 pages"). Filing 200 `image-alt` issues when
one issue with `frequency: 200` would do creates noise and slows
triage. The `pattern_id` hash (see below) is the deduplication key.

**Alert-fatigue guard.** If a scheduled or CI scan runs and there are
already open issues for the same `pattern_id`, do not refile. Update
the existing issue's frequency count instead. Automated scans that
flood the queue with duplicates lose reviewer trust faster than the
bugs themselves.

## Structured field block (required at the top of every issue)

Every report opens with the block in `REFERENCE_BUG_REPORT.md`. Fields
are required unless explicitly marked optional.

**Bug ID — two levels.** Every violation gets a stable identifier so
pipelines can deduplicate, track regressions, and group recurring
patterns across pages.

| Level         | Inputs to hash                                                  | Purpose                                                                                                                       |
|---------------|-----------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------|
| `instance_id` | page path + CSS selector + rule ID + screen type                 | Uniquely identifies one occurrence on one specific page. Same element, same rule, same page, same viewport class = same ID.   |
| `pattern_id`  | CSS selector + rule ID + screen type                             | Identifies the recurring template-level pattern across pages. Multiple pages sharing the same component share the pattern_id. |

Format: `[PREFIX]-[8-char hex]`. Use `DRP-` for Drupal core,
`DRPC-<short>` for contrib (e.g. `DRPC-WEBFORM-a3f1c2d4`), and a
project-defined prefix for client work. The hex is the first 8
lowercase hexadecimal characters of a SHA-256 of the inputs joined
with `|` (e.g. `sha256("path|selector|rule|screen_type")[:8]`).
Hash inputs use the **CSS selector** from
`axe-core` `node.target`, not the XPath, because the CSS selector is
what the scanner emits natively and is stable across XPath generation
changes. Include both the simplified XPath and the full DOM-path XPath
in the report regardless.

**URL.** Full URL including query string and fragment. If the same
violation appears on multiple URLs, list them or describe the pattern
("all `/node/*/edit` pages").

**XPath (simplified) and Full DOM path.** Both forms. Simplified XPath
is human-readable, the full path is for deterministic replay when IDs
change dynamically.

**WCAG SC.** Number, name, and level. Format: *"WCAG 2.2 SC 2.5.8 Target
Size (Minimum) — Level AA"* with a link to the W3C Understanding
document. One issue, one criterion (see below).

**Rule.** Tool name and version (e.g. `@axe-core/playwright 4.10`,
Pa11y, manual NVDA pass) and the rule ID it flagged (`target-size`,
`button-name`). When multiple tools flag the same issue, list each.

**Severity.** Use the upstream taxonomy consistently:

| Level     | Definition                                                            |
|-----------|-----------------------------------------------------------------------|
| Critical  | Users cannot complete a core task at all.                             |
| High      | Significant barrier that degrades or blocks a key workflow.           |
| Medium    | Noticeable barrier with a workaround available.                       |
| Low       | Minor issue with minimal real-world impact.                           |

Frequency amplifies severity: a Low issue on every page or on a
high-traffic flow is treated as Medium. Document the adjustment in the
issue when applied.

**Frequency.** *"N instances on this page; M of P pages affected"*. For
automated scans, include aggregate counts. Deduplicate before filing —
do not file 200 identical `image-alt` issues; file one with the count.

**Screen type and colour mode.** `desktop` / `mobile` (inferred from
viewport width — < 768 px = mobile, ≥ 768 px = desktop) and
`light` / `dark`. Both are part of the hash inputs above.

**HTML snippet.** Minimal failing fragment. Capture at scan time so
the snippet survives later DOM changes.

## One issue, one criterion

A pull request that fixes three SCs across two components is three
issues. AI tools tend to bundle because the diff comes out as one patch.
Resist it. Reviewers triage by SC, the release notes group by SC, and
the fix history needs to be traceable per-criterion. Link the three
issues with *Related issues* and a brief note in each that this fix
coordinates with the others.

## Steps to reproduce, environment, impact

An issue is only worth filing if a developer who has never seen it
before can reproduce the failure *and* confirm it is fixed after
applying a patch — using the same tool that found it, on the same
page, with the same scan command. If that loop cannot be closed, the
issue is incomplete.

**Reproducible scan command.** Every automated finding must include
the exact command used to detect it, so it can be re-run after a patch:

```bash
# Example — save this in the issue as the verification command.
BASE_URL=https://my-site.ddev.site \
  npx playwright test a11y-scan.spec.js \
  --grep "home — WCAG 2.2 AA (light)"
```

Include `@axe-core/playwright` version, `@playwright/test` version, and
the `withTags` array used. A scan with different tags may not reproduce
the violation. A scan against a different URL or page state may not
either.

**Environment — required fields.** All of these must be in the issue:

| Field | Example |
|---|---|
| Drupal core version | 11.3.9 |
| Theme + version | haven_theme 1.0.0 |
| Module/recipe | Haven recipe 1.x |
| axe-core/playwright version | @axe-core/playwright 4.11, @playwright/test 1.44 |
| Browser (Playwright) | Chromium 124 (headless) |
| Colour mode | light / dark / forced-colours |
| Viewport | desktop (1280×720) / mobile (375×667) |
| Page URL and path | `https://my-site.ddev.site/` → `/` |
| AT + browser (if manual) | NVDA 2024.1 + Chrome 124 |

**Steps to reproduce.** Numbered, on a clean install, ending with the
failing state. The last step must be "Run the scan command above and
observe the violation in the output" (for automated findings) or the
exact AT interaction that produces the failure (for manual findings).

Impact must name the disability groups affected ("keyboard-only users",
"NVDA users", "low-vision users at 200% zoom"). Generic "users with
disabilities" is not enough.

**Definition of Done.** Include this block explicitly in every issue's
Remaining Tasks checklist:

```
- [ ] Run the verification scan command above against the patched site
- [ ] Confirm the rule ID no longer appears in axe output for this page
- [ ] Confirm no new violations introduced on the same page
- [ ] Manual AT check completed (if the issue was originally manual)
```

A patch is not done when the code looks correct. It is done when the
same tool that found the issue no longer finds it.

**AT/browser pairs** are required when the issue depends on assistive
technology behaviour. Different AT/browser combinations have different
rendering and different bugs; the pair determines the fix. Preferred
pairs for Drupal issues:

| AT | Browser | Use for |
|---|---|---|
| NVDA (latest) | Chrome | Windows screen-reader baseline |
| JAWS (latest) | Edge | Enterprise screen-reader coverage |
| VoiceOver | Safari | macOS / iOS baseline |
| TalkBack | Chrome (Android) | Mobile coverage |
| Keyboard only | Chrome or Firefox | Focus management, tab order |

When the issue is keyboard-only and does not involve AT, write
`AT: n/a (keyboard only)` rather than leaving the field blank.

**ATAG 2.0 scope.** Drupal's administrative interface and content-editor
tools are *authoring tools* and must meet
[ATAG 2.0](https://www.w3.org/TR/ATAG20/) in addition to WCAG 2.2 AA.
Part A of ATAG requires the authoring UI itself to be accessible; Part B
requires the content it generates to be accessible by default. When
filing issues against the admin toolbar, node edit forms, CKEditor, or
the Layout Builder, note whether the issue is a Part A failure
(inaccessible authoring UI) or a Part B failure (inaccessible content
output) — the fix strategy differs.

## Suggested fix cites a sub-skill

Every Suggested Fix names the sub-skill whose rule applies:
`drupal-a11y-fapi` (server-side render arrays), `drupal-a11y-dom`
(templates and styles), or `drupal-a11y-dynamic` (JavaScript and tests).
Cite the specific rule in the prose ("per drupal-a11y-dom, expand the
hit area to 24×24 with padding") so reviewers can verify the fix
follows project conventions without re-reading the whole sub-skill.

## Manual checks before RTBC

The Remaining Tasks block in the template is a checklist. Tests pass
*and* manual checks pass before RTBC. Axe alone catches roughly a third
of WCAG issues; an automated green is not a fix. The mandatory manual
checks are: keyboard-only walk, screen-reader pass with the relevant
AT, 200% zoom (and 400% for SC 1.4.10), and forced-colours when the
change is visual.

## AI disclosure: exact wording

Use this block verbatim in any issue, MR, or change record where AI
contributed:

```
**AI disclosure**

This contribution was prepared with assistance from an AI coding tool.
- Tool: <tool name and version, e.g. Claude Code>
- Used for: <specific tasks, e.g. drafting the patch, generating tests, drafting this issue>
- Reviewed by: <human reviewer's drupal.org username>
- Skills loaded: drupal-accessibility (sub-skills: <comma-separated list>)
```

The reviewed-by line is required before the issue is posted — an
unreviewed AI contribution does not qualify for the project. When
drafting before a reviewer is known, write `- Reviewed by: [pending —
assign before posting]` as a placeholder; do not post the issue until
the line is filled in with a real drupal.org username. *Skills loaded*
is the dispatcher and the sub-skills that informed the work; this lets
reviewers spot when a relevant sub-skill was missed.

## What not to do

Do not file an issue based on AI code inspection alone or manual
template reading alone. Every confirmed bug must have a tool finding
— a rule ID, a CSS selector, and an HTML snippet from an automated
scan — before it is filed. "The code looks wrong to me" is not a bug
report.

Do not file an issue without the reproducible scan command and
Definition of Done checklist. An issue that cannot be verified after
patching wastes reviewer time and creates false confidence.

Do not file an issue without the structured field block. Free-form
prose without Bug ID, URL, XPath, WCAG SC, Rule, Severity, Frequency,
Screen type, and Colour mode is rejected.

Do not omit the Bug ID. Pipelines need stable identifiers; without
them, the same regression is filed twice and fixes cannot be tracked.

Do not bundle multiple SCs in a single issue. File one per criterion
and link them.

Do not paste raw Axe JSON without the rule names and the affected
selectors highlighted. Reviewers should be able to find the failing
element from the issue text without re-running the tool.

Do not file `incomplete` (needs-review) axe results as confirmed bugs.
Mark them "Confidence: Needs manual confirmation" until a human
verifies the finding.

Do not run a scan and immediately file every violation as a separate
issue. Deduplicate by `pattern_id` first, aggregate frequency counts,
and check whether an open issue already covers the same pattern.

Do not omit the AT/browser pair. A keyboard-only finding and a
JAWS-on-Edge finding can have different fixes.

Do not generate the issue text with AI and post it without review.
Disclosure is required even with review; posting without review is
not acceptable.

Do not rely on Axe alone. Pair the automated finding with at least
one manual check and document both.

Do not assume a passing automated scan means the issue is resolved.
Manual keyboard, screen-reader, and zoom checks are required before
RTBC.

## See also

**Primary upstream references (read these first):**
- [Accessibility Bug Reporting Best Practices](https://mgifford.github.io/ACCESSIBILITY.md/examples/ACCESSIBILITY_BUG_REPORTING_BEST_PRACTICES.html) — field definitions, stable ID generation, severity taxonomy, deduplication rules, and the JSON schema this skill's structured block is based on
- [CI/CD Accessibility Best Practices](https://mgifford.github.io/ACCESSIBILITY.md/examples/CI_CD_ACCESSIBILITY_BEST_PRACTICES.html) — scanning pipeline stages (local → pre-commit → MR gate → scheduled), Lighthouse CI configuration, alert-fatigue guard, and the detect → propose → review loop for agent-driven remediation

**Drupal-specific:**
- [Creating or updating an issue report (Drupal contributor guide)](https://www.drupal.org/community/contributor-guide/reference-information/quick-info/creating-or-updating-an-issue-report)
- [Issue #3587661 — reference reporting style](https://www.drupal.org/project/drupal/issues/3587661)

**Standards:**
- [WCAG 2.2 quick reference](https://www.w3.org/WAI/WCAG22/quickref/)
- [ATAG 2.0](https://www.w3.org/TR/ATAG20/)

**Tools:**
- [@axe-core/playwright](https://www.npmjs.com/package/@axe-core/playwright)
- [Lighthouse CI](https://github.com/GoogleChrome/lighthouse-ci)
- [pa11y-ci](https://github.com/pa11y/pa11y-ci)

- Template: `REFERENCE_BUG_REPORT.md` in this directory.
- Dispatcher: `../SKILL.md`
- Sibling sub-skills: `../drupal-a11y-fapi/`, `../drupal-a11y-dom/`, `../drupal-a11y-dynamic/`
- Source material: Mike Gifford (Drupal Core Accessibility Maintainer)
