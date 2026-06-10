---
name: drupal-a11y-dynamic
description: Accessibility rules for Drupal JavaScript, AJAX, modals, and test layers. Use when writing or reviewing *.js, Drupal.behaviors, dialog/modal code, or *Test.php that exercises the UI. Mandates Drupal.announce(), FocusCommand for AJAX, Playwright over Nightwatch, and Axe-core in PHPUnit. Loaded by drupal-accessibility.
---

# Drupal accessibility: interaction and testing

## When to use this guidance

You are writing or modifying JavaScript that ships to Drupal pages
(`*.js`, library declarations that attach behaviours, AJAX commands,
modal/dialog code) or test code that exercises UI behaviour
(`*Test.php` extending `WebDriverTestBase`, Playwright specs, PHPUnit
tests that render templates and want an accessibility assertion). This
sub-skill covers the runtime announcements, focus management, and the
test layers that catch regressions in both.

## Announcements: `Drupal.announce()`, never hand-rolled `aria-live`

Drupal core ships a single shared, polite live region with the
`Drupal.announce()` API. Use it for any state change a sighted user
sees but a screen-reader user would otherwise miss — AJAX content
loaded, filters applied, validation errors revealed, items added to a
cart.

```javascript
((Drupal, once) => {
  Drupal.behaviors.cartUpdate = {
    attach(context) {
      once('cart-update', '[data-cart-add]', context).forEach((el) => {
        el.addEventListener('click', () => {
          // Polite: queues behind anything the user is currently reading.
          Drupal.announce(Drupal.t('Item added to your cart.'));
        });
      });
    },
  };
})(Drupal, once);
```

Use the second argument for assertive announcements only when the
information is genuinely time-critical (a session-expiry warning, a
form validation error that blocks submission):

```javascript
Drupal.announce(Drupal.t('Your session will expire in 1 minute.'), 'assertive');
```

A hand-rolled `<div aria-live="polite">` somewhere in the DOM is a
violation. It conflicts with the core live region, races the polite
queue, and frequently announces the wrong thing because the contents
are inserted before the region is registered with the AT.

## Focus management

Two rules cover the vast majority of dynamic-focus bugs in Drupal code.

**Modals trap focus.** Use `Drupal.dialog` / `Drupal.dialog.ajax`
(jQuery UI dialog under the hood, with Drupal's accessibility wrappers)
for modal content. It traps Tab/Shift+Tab inside the dialog, sets
`aria-modal="true"`, moves focus to the first focusable element on
open, and returns focus to the trigger on close. Do not build a modal
from a `<div>` plus your own `keydown` handler unless the issue queue
has already accepted that you cannot use core dialog. If you must
hand-build, the focus contract is: trap Tab cycle inside the dialog,
restore focus to the element that opened it, and close on Escape.

**AJAX triggers return focus.** When an AJAX command replaces the
element the user just interacted with (a "Load more" button replaced
by the loaded content, an inline edit form swapped for the saved
value), the rebuilt DOM must place focus somewhere meaningful — usually
the new equivalent of the trigger, otherwise the page heading or the
landmark containing the new content. Drupal core ships `FocusCommand`
(in `\Drupal\Core\Ajax\FocusCommand`) for exactly this; use it over
manual `element.focus()` so refocusing happens after the command
queue settles.

```php
use Drupal\Core\Ajax\FocusCommand;

// In an AJAX response, move focus to the rebuilt element.
$response->addCommand(new FocusCommand('.commerce-cart-form'));
```

When server-side `FocusCommand` is unavailable (client-side-only AJAX
handlers), use the `drupalAjaxSuccess` event that Drupal fires on the
document after a successful AJAX response:

```javascript
once('load-more', '[data-load-more]', context).forEach((trigger) => {
  trigger.addEventListener('click', () => {
    // After Drupal's AJAX finishes, move focus to the new content.
    document.addEventListener('drupalAjaxSuccess', function onSuccess(e) {
      document.removeEventListener('drupalAjaxSuccess', onSuccess);
      const newItem = document.querySelector('[data-new-item]');
      if (newItem) { newItem.focus(); }
    });
  });
});
```

For AJAX form submissions that surface inline validation errors, move
focus to the first error element so screen-reader users know validation
failed without having to search the page:

```php
// Server side — AJAX form error response.
$response->addCommand(new FocusCommand('[data-drupal-selector="edit-email"].error'));
```

A "Load more" button that disappears with no focus return strands
keyboard users on `<body>`. That is the single most common AJAX a11y
regression and shows up immediately in the Playwright scan below.

**Complex widget focus management: `Drupal.tabbingManager`.** When a
complex widget (a mega-menu, a date picker, an off-canvas tray) needs to
constrain Tab focus to a sub-tree *without* being a modal dialog, use
`Drupal.tabbingManager`. It manages a stack of tabbable sets so multiple
widgets can co-exist without clobbering each other's focus contract.

```javascript
// Restrict Tab to the off-canvas tray while it is open.
const tabbingContext = Drupal.tabbingManager.constrain($('.off-canvas-tray'));

// Release the constraint when the tray closes.
tabbingContext.release();
```

Do not hand-roll Tab-key trapping with `keydown` handlers and manual
tabIndex management when `Drupal.tabbingManager` is available — the
manual approach breaks when multiple widgets are open simultaneously.

## Choosing the right testing layer

No single tool catches everything. Use this table to pick the right
tool for each type of assertion:

| Tool | WCAG rule checks | Announcement quality | SVG / icon support | CI-ready | Notes |
|---|---|---|---|---|---|
| `@axe-core/playwright` | ✅ ~30% of WCAG | ❌ | Limited | ✅ | Start here; catches structural violations |
| Lighthouse CI | ✅ (subset of axe) | ❌ | ❌ | ✅ | Quality gate; use `minScore: 1` to block on regression |
| Playwright ARIA snapshots | Partial | ✅ | ✅ | ✅ | Validates exact screen-reader announcement text |
| Guidepup virtual SR | ❌ | ✅ exact | ✅ | ✅ | Automates a real screen reader in CI without hardware |

The correct layering is: axe/Lighthouse for WCAG rule coverage →
Playwright ARIA snapshots for announcement quality → Guidepup when
exact spoken output must be verified in CI. Each layer catches what the
others miss; none is sufficient on its own.

## Browser-level testing: Playwright, not Nightwatch

Per core issue #3553673, new browser-level accessibility coverage uses
Playwright with `@axe-core/playwright`. Nightwatch is being removed;
do not extend Nightwatch tests for new accessibility coverage.

```javascript
// tests/playwright/a11y/cart.spec.js
import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

test('cart page has no detectable WCAG 2.2 AA violations', async ({ page }) => {
  await page.goto('/cart');
  const results = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
    .analyze();
  expect(results.violations).toEqual([]);
});

test('Load more returns focus to the rebuilt trigger', async ({ page }) => {
  await page.goto('/news');
  await page.getByRole('button', { name: /load more/i }).click();
  await expect(page.getByRole('button', { name: /load more/i })).toBeFocused();
});
```

Tag `wcag22aa` so SC 2.5.8 (target size) and SC 2.4.13 (focus
appearance) are exercised. Axe catches roughly a third of WCAG issues;
pair every scan with at least one keyboard-flow assertion of the kind
shown above.

## Run scans in both colour modes

Axe rules that depend on colour (most importantly `color-contrast`)
only inspect what the browser is rendering. A theme that supports
`prefers-color-scheme: dark` therefore needs Axe scans in *both* modes,
plus a forced-colours pass when the visual change is in scope. Use
Playwright's `emulateMedia` to switch modes inside the test; do not
write three separate test files.

```javascript
import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

for (const colorScheme of ['light', 'dark']) {
  test(`cart page passes WCAG 2.2 AA (${colorScheme})`, async ({ page }) => {
    await page.emulateMedia({ colorScheme });
    await page.goto('/cart');
    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag22aa'])
      .analyze();
    expect(results.violations).toEqual([]);
  });
}

test('cart page survives forced-colours mode', async ({ page }) => {
  await page.emulateMedia({ forcedColors: 'active' });
  await page.goto('/cart');
  // Focus indicator must remain visible against the system palette.
  await page.getByRole('button', { name: /checkout/i }).focus();
  await expect(page.getByRole('button', { name: /checkout/i }))
    .toHaveCSS('outline-style', /(?!none).+/);
});
```

When the theme does not yet support dark mode, write the dark-mode
test anyway and mark it `test.fixme()` against the issue tracking the
work — that way the gap is visible in the test output rather than
silently skipped.

## Playwright ARIA snapshots: validate announcements, not just rules

Axe confirms that ARIA attributes are structurally present; it cannot
verify what a screen reader will actually *say*. Playwright's
`toMatchAriaSnapshot()` (added in v1.46) queries the browser's
accessibility tree and asserts the exact announced structure.

```javascript
test('accordion announces expanded state', async ({ page }) => {
  await page.goto('/faq');
  const accordion = page.getByRole('button', { name: /What is Haven/ });

  // Before click: collapsed.
  await expect(accordion).toMatchAriaSnapshot(`
    - button "What is Haven" [expanded=false]
  `);

  await accordion.click();

  // After click: expanded, region visible.
  await expect(accordion).toMatchAriaSnapshot(`
    - button "What is Haven" [expanded=true]
  `);
});
```

Use `getByRole` semantic queries rather than CSS selectors — they
mirror how assistive technology navigates the page and fail loudly when
semantics break, not just when CSS classes change.

```javascript
// Prefer semantic queries in all Playwright accessibility tests.
page.getByRole('navigation', { name: 'Main' })
page.getByRole('textbox', { name: 'Email address' })
page.getByRole('button', { name: 'Submit' })
```

ARIA snapshots are the right tool when a ticket says "the screen reader
announces the wrong text" — axe will not catch that.

## Guidepup: virtual screen reader for CI

When exact spoken output must be verified without hardware (useful for
regression-testing navigation announcements, live region content, or
widget state changes), use Guidepup's virtual screen reader:

```bash
npm install -D @guidepup/virtual-screen-reader @guidepup/jest
```

```javascript
import { virtual } from '@guidepup/virtual-screen-reader';

test('status message is announced politely', async () => {
  document.body.innerHTML = `<div role="status">Item added to cart.</div>`;
  await virtual.start({ container: document.body });
  const spoken = await virtual.spokenPhraseLog();
  expect(spoken).toContain('Item added to cart.');
  await virtual.stop();
});
```

Guidepup runs entirely in Node — no screen reader installation needed
on the CI runner. Use it for unit-level announcement tests; use
Playwright + axe for page-level WCAG coverage. The two complement each
other and together cover what neither can do alone.

Per core issue #3338664, render-array output should be scanned for
accessibility violations inside PHPUnit. The Kernel/Functional layer
catches markup regressions earlier than a full Playwright run and
keeps the assertion close to the code that generated it.

```php
namespace Drupal\Tests\my_module\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\axe\Traits\AxeRenderArrayTrait;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
class TeaserAccessibilityTest extends KernelTestBase {

  use AxeRenderArrayTrait;

  protected static $modules = ['system', 'my_module'];

  public function testTeaserRendersWithoutAxeViolations(): void {
    $build = [
      '#theme' => 'my_module_teaser',
      '#title' => $this->t('Volunteer day'),
      '#summary' => $this->t('Saturday at the riverbank.'),
    ];
    $this->assertAxeClean($build, ['wcag2a', 'wcag2aa', 'wcag22aa']);
  }

}
```

`AxeRenderArrayTrait` is the helper proposed in #3338664 and is **not
yet committed to core** (as of this writing). Until it lands, write the
test using the `assertAxeClean(array, tags)` shape shown above — the
method name is taken from the proposal and will be updated here when the
trait is committed. If `AxeRenderArrayTrait` does not exist in your
Drupal version, check #3338664 for the current status and use the
committed API shape instead of the snippet above. Functional tests can
also call the trait against the rendered page HTML; reach for that level
only when the markup depends on a full HTTP request.

## Shift-left: pre-commit and CI layers

Catch accessibility regressions as early as possible. Four layers, each
cheaper than the next:

**Pre-commit** (target under 30 seconds, runs on changed files only):
`composer cs-fix` (already wired); `stylelint` with `stylelint-a11y` for
theme CSS; `twig-cs-fixer` for Twig anti-patterns; a small grep over
the diff for the high-signal banned strings — `aria-live=`,
`<div role="button"`, `extends NightwatchTestBase`, `outline:\s*0` and
`outline:\s*none` without a `:focus-visible` companion. Wire these
through `pre-commit` (Python) or `husky` + `lint-staged` (Node).

**MR CI gate** (re-runs the pre-commit checks for trust, plus the
heavier scans): `phpunit` with the `AxeRenderArrayTrait` from issue
#3338664; the Playwright + `@axe-core/playwright` suite from issue
#3553673, including the colour-mode loop above; `pa11y-ci` against a
built site if the theme changed.

**Scheduled** (nightly or weekly, full-site): an Axe crawl across the
default content profile; Lighthouse CI accessibility budgets; a
regression diff against last week's report.

**Manual** (rotating, human): screen-reader pass on each release
candidate; high-contrast mode check on a Windows machine; a keyboard-
only walk of the top user flows.

Pre-commit and MR layers fail the build on regression. Scheduled scans
file issues with the `drupal-a11y-qa` template; they do not block
deploys directly.

## What not to do

Do not insert your own `<div aria-live="polite|assertive">`. Use
`Drupal.announce()`.

Do not call `Drupal.announce()` for content the user is also seeing
update visibly *and* reading at the same time — it is for state changes
the screen reader would otherwise miss, not for narrating every UI
event.

Do not build a modal from primitives. Use `Drupal.dialog`. If you
genuinely cannot, document why in the issue and replicate the focus
trap, focus return, `aria-modal="true"`, and Escape behaviours
explicitly.

Do not hand-roll Tab-key trapping for non-modal widgets. Use
`Drupal.tabbingManager` instead; multiple widgets on the same page will
interfere with each other otherwise.

Do not rely on `element.focus()` inside an AJAX success callback.
Use `FocusCommand` (or an equivalent AJAX command) so refocusing
happens after the command queue has settled and the element exists.

Do not extend Nightwatch coverage. New tests use Playwright per
#3553673; existing Nightwatch tests should be converted when you touch
them, not extended.

Do not assert `expect(results.violations.length).toBe(0)` without
also asserting at least one keyboard-flow expectation. Axe alone is
not a green light.

Do not skip `#[RunTestsInSeparateProcesses]` on Kernel or Functional
PHPUnit tests — required from Drupal 11.3, recommended on 10.x. See
the `drupal-automated-testing` skill for the broader contract.

## See also

**Primary upstream reference (read this first):**
- [CI/CD Accessibility Best Practices](https://mgifford.github.io/ACCESSIBILITY.md/examples/CI_CD_ACCESSIBILITY_BEST_PRACTICES.html) — full pipeline stages, Lighthouse CI configuration, tool comparison, alert-fatigue guard, and the detect → propose → review loop for agent-driven remediation

**Drupal:**
- [Drupal.announce JavaScript API](https://api.drupal.org/api/drupal/core%21misc%21announce.es6.js)
- [Drupal.tabbingManager JavaScript API](https://api.drupal.org/api/drupal/core%21misc%21tabbingmanager.es6.js)
- [Drupal AJAX commands](https://www.drupal.org/docs/develop/drupal-apis/ajax-api/ajax-commands)
- [Issue #3553673 — Adopt Playwright for browser-level testing](https://www.drupal.org/project/drupal/issues/3553673)
- [Issue #3338664 — Axe-core scans in PHPUnit](https://www.drupal.org/project/drupal/issues/3338664)

**Tools:**
- [@axe-core/playwright](https://www.npmjs.com/package/@axe-core/playwright)
- [Lighthouse CI](https://github.com/GoogleChrome/lighthouse-ci)
- [Guidepup virtual screen reader](https://www.guidepup.dev/)
- [Playwright ARIA snapshots](https://playwright.dev/docs/aria-snapshots)

- Dispatcher: `../SKILL.md`
- Sibling sub-skills: `../drupal-a11y-fapi/`, `../drupal-a11y-dom/`, `../drupal-a11y-qa/`
- Source material: Mike Gifford (Drupal Core Accessibility Maintainer)
