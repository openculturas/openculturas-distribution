---
name: drupal-automated-testing
description: Guidance for writing or modifying automated tests for Drupal modules (core, contrib, custom). Covers Functional, Kernel, FunctionalJavascript, and Unit tests; required PHPUnit attributes; test namespaces; the Functional dual-container trap; DDEV env vars; and mistakes agents often make.
---

# Writing automated tests for Drupal

## When to use this guidance

You are writing or modifying automated tests for a Drupal module, whether for
core, contrib, or custom code. This guidance covers test type selection, common
traps, and patterns that differ from general PHP testing advice. Drupal's testing
conventions are idiosyncratic and AI agents frequently get them wrong by default.

## Guidance

### When tests are needed

Bug fixes almost always need a test. Without one, the same bug can silently
return. Behaviour changes almost always need a test. Configuration-only changes
usually do not. If you are modifying an existing feature, find the existing test
first and update it. If you are adding a new feature, write a new test covering
the happy path and at least the most likely error paths.

### Choose the right test type

Drupal has five test types. Reach for Functional or Kernel first. Internet
documentation and AI agents frequently suggest unit tests as the starting point.
In Drupal, that advice is wrong. Drupal code is mostly about integrating
services, hooks, and entities through a dependency injection container, which
makes integration-level tests far more valuable.

**Functional tests** (`\Drupal\Tests\BrowserTestBase`) are the best default
choice. They boot a real Drupal site, install modules with all config and schema,
and can test both PHP APIs and the UI. Use them when the feature involves HTTP
requests, forms, pages, or when the module dependency tree is complex enough that
setting everything up manually would be harder than letting Drupal do it.

**Kernel tests** (`\Drupal\KernelTests\KernelTestBase`) are good for testing
services, APIs, and hook implementations that do not need a UI. They are faster
than Functional tests but provide a minimal environment: no config is installed,
no entity database tables are created, and module dependencies are not resolved
automatically. You must call `$this->installEntitySchema('node')`,
`$this->installConfig(['my_module'])`, and similar setup methods explicitly. If
your Kernel test throws "table does not exist" errors, this is almost certainly
the cause.

**FunctionalJavascript tests** (`\Drupal\FunctionalJavascriptTests\WebDriverTestBase`)
are needed only when the UI interaction involves JavaScript, such as AJAX form
updates, modal dialogs, or dynamic element visibility. These tests are prone to
flakiness because interactions are asynchronous. The critical pattern to follow:

```php
// Wrong: does not wait for the element to appear.
$this->assertSession()->waitForElement('css', '#page-title');

// Correct: waits and asserts the result.
$this->assertNotEmpty(
  $this->assertSession()->waitForElement('css', '#page-title')
);
```

Always explicitly assert the result of `waitForElement` and similar methods.
They return `NULL` if the element is not found, and not asserting the return
value silently produces a false-passing test. After pressing a button that
triggers AJAX, wait for the expected element, then assert.

**Unit tests** (`\Drupal\Tests\UnitTestCase`) are appropriate only for pure
functions with no Drupal container dependencies. They are uncommon in Drupal.

**Build tests** (`\Drupal\BuildTests\Framework\BuildTestBase`) test codebase
layout scenarios such as Composer dependency resolution. They are advanced and
uncommon.

### Required class attributes

Kernel, Functional, and FunctionalJavascript test classes should declare the
`#[RunTestsInSeparateProcesses]` attribute from PHPUnit (requires
`use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;`). This became a
hard requirement in Drupal 11.3 and is strongly recommended on 10.x. Unit tests
run in-process and do not need this attribute.

### Test namespaces

Tests must be in the correct namespace or the test runner will not discover them:

- Unit: `\Drupal\Tests\<module>\Unit`
- Kernel: `\Drupal\Tests\<module>\Kernel`
- Functional: `\Drupal\Tests\<module>\Functional`
- FunctionalJavascript: `\Drupal\Tests\<module>\FunctionalJavascript`

The `FunctionalJavascript` capitalisation is important.

### The dual-container trap in Functional tests

Functional tests run two separate Drupal instances that share the same database
but have separate PHP memory spaces: one in PHPUnit and one in the web server.
State set in the test process (like registering a service or setting a variable)
is not visible to the web server process, and vice versa. This causes confusing
bugs around caching and service registration. If you hit these, targeted cache
clears or container rebuilds may help, but always add a comment explaining why.

### Running tests

Use DDEV. Set these two environment variables, which PHPUnit requires:

- `SIMPLETEST_BASE_URL`: the local HTTP address of your Drupal site
- `SIMPLETEST_DB`: the database connection string

### Reducing test repetition

If you have multiple similar test cases, use `setUp()`, data providers, or the
`#[TestWith]` attribute rather than duplicating code across test methods.

## What not to do

Do not reach for Unit tests first. Most Drupal code depends on the container
and unit-testing it requires extensive mocking that breaks on any refactor.

Do not skip `waitForElement` assertions in FunctionalJavascript tests. A bare
`waitForElement` call without asserting the return value produces a test that
always passes, even when the element never appears.

Do not assume Kernel tests install config or create database tables. You must
call `installEntitySchema()`, `installConfig()`, and `installSchema()` in setUp.

Do not assume state set in your Functional test code is visible to the web
server process. The dual-container architecture means they have separate memory.

Do not submit tests without `SIMPLETEST_BASE_URL` and `SIMPLETEST_DB` set.
PHPUnit will either fail or silently skip your tests.

Do not write Nightwatch tests. They are JavaScript-based, prone to flakiness,
and the Drupal community is moving toward Playwright as a replacement.

## See also

- [Drupal automated testing guide](https://www.drupal.org/docs/develop/automated-testing)
- [PHPUnit in Drupal](https://www.drupal.org/docs/develop/automated-testing/phpunit-in-drupal)
- [KernelTestBase API](https://api.drupal.org/api/drupal/core%21tests%21Drupal%21KernelTests%21KernelTestBase.php/class/KernelTestBase/11.x)
- [BrowserTestBase API](https://api.drupal.org/api/drupal/core%21tests%21Drupal%21Tests%21BrowserTestBase.php/class/BrowserTestBase/11.x)
- Source material: phenaproxima (Drupal core committer), issue #3581672
