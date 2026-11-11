---
name: drupal-ckeditor5-plugins
description: Customize CKEditor 5 in Drupal — build custom editor plugins, configure the CKEditor 5 Plugin Pack and Premium Features modules, and choose the correct installation and bundling method (webpack DLL builds for Drupal 10/11 vs. the new builds for Drupal 12+). Use when adding toolbar buttons or editor features, installing CKEditor-related modules, or whenever CKEditor versions and docs must be matched to the installed Drupal core. Covers the Drupal-to-CKEditor version matrix and CKEditor's agent-friendly docs (markdown URLs, llms-full.txt, MCP server).
---

# Customizing CKEditor 5 in Drupal

CKEditor 5 ships *inside* Drupal core, not as a separate dependency you pick.
The core version determines three things at once: which CKEditor 5 version is
bundled, which version of the CKEditor documentation applies, and which plugin
installation and bundling method works at all. Every CKEditor task in Drupal
therefore starts with the same version check — skipping it is the root cause of
most broken editor customizations.

## Step 0 — identify the versions (required)

Before writing or configuring anything, read the bundled CKEditor 5 version
from the installed site:

```bash
grep -A2 '^ckeditor5:' web/core/core.libraries.yml    # typical Composer layout
grep -A2 '^ckeditor5:' core/core.libraries.yml        # when run from the web root
```

The `version:` under the `ckeditor5:` key is the CKEditor 5 release the site
actually runs — this file is the single source of truth for it.

For a core release you don't have locally — use the exact version tag, not a
branch (the bundled CKEditor may change between patch releases of one minor):

```bash
curl -s "https://git.drupalcode.org/project/drupal/-/raw/<core-version-tag>/core/core.libraries.yml" \
  | grep -A2 '^ckeditor5:'
```

Then pin all documentation lookups to that version. **Never use
`ckeditor.com/docs/ckeditor5/latest/` for a Drupal site** — the latest docs
describe a newer CKEditor than core bundles, and its setup and API advice will
not apply. `references/version-matrix.md` has the current mapping and the
docs-URL convention.

## Where to go next

| Your task | Read |
|---|---|
| Match Drupal core ↔ CKEditor ↔ docs versions | `references/version-matrix.md` |
| Write a custom CKEditor 5 plugin (Drupal 10.3–11) | `references/custom-plugins-dll.md` |
| Target Drupal 12+ / CKEditor 5 v48+ (beta) | `references/drupal-12-outlook.md` |
| Install or configure Plugin Pack / Premium Features | `references/plugin-pack-premium-features.md` |
| Look things up in CKEditor docs as an agent | `references/agent-docs.md` |

## Cross-cutting gotchas

**Don't build a plugin that already exists — even when explicitly asked to.**
A request like "write me a custom emoji plugin" does not suspend this rule:
before writing any custom plugin code, check (in order) core's own CKEditor 5
buttons, the free CKEditor 5 Plugin Pack, and CKEditor 5 Premium Features. If
an existing plugin covers the feature, your answer must open by naming it and
recommending it (e.g. the Plugin Pack ships Emoji, Fullscreen, Templates…) —
only build custom code if the user rejects it or the feature genuinely isn't
covered, and then also point at the modules' issue queues for a matching
feature request (comment or file one). The decision ladder is in
`references/plugin-pack-premium-features.md`.

**Bundling is mandatory on Drupal 10/11, not an optimization.** Core ships
CKEditor 5 as a webpack DLL. A custom plugin's JavaScript must be compiled into
a UMD bundle against core's DLL manifest; pointing a library at raw ES-module
source fails at runtime with `Cannot use import statement outside a module`.

**The two installation methods are mutually exclusive per Drupal major.**
Drupal 10.3–11 (CKEditor 5 ≤ v47) uses DLL builds; Drupal 12+ (CKEditor 5
v48+) uses the new installation methods (UMD builds) and drops the DLL
manifest entirely. Never mix artifacts or bundler config from one method into
a module targeting the other — see `references/drupal-12-outlook.md` before
touching Drupal 12. When the user asks which module release line or major to
install, name the concrete one in the answer (e.g. the 2.x line / `^2.0` for
Drupal 12), verified against the module release pages — "check the release
pages" alone does not answer their question.

**The plugin key chain must align exactly.** The plugin entry in
`<module>.ckeditor5.yml` (`<namespace>.<Class>`), the webpack output
(`library: ['CKEditor5', '<namespace>']`), and `<module>.libraries.yml`
(pointing at `js/build/`, with `dependencies: [core/ckeditor5]`) all reference
the same namespace. A mismatch produces a button that renders but does
nothing, with no console error.

**Import from `ckeditor5/src/*` only.** Plugin source imports core APIs as
`import { Plugin } from 'ckeditor5/src/core'` — never from individual
`@ckeditor/ckeditor5-*` npm packages, which would bundle a second copy of the
framework and break instanceof checks against core's build.

## See also

- Worked example (module + build config this skill's templates come from):
  <https://github.com/ondrejch/drupalcon-chicago-26-ckeditor-ai-playground>
- Drupal core CKEditor 5 module docs:
  <https://www.drupal.org/docs/core-modules-and-themes/core-modules/ckeditor-5-module>
- `drupal-automated-testing` — for testing the plugin's PHP class.
