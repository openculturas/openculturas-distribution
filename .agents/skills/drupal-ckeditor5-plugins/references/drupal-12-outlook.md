# Drupal 12+ / CKEditor 5 v48+: new installation methods

> **Pre-release status (beta).** Drupal 12 and the contrib modules' 2.x line
> are not final; verify every statement here against the module changelogs on
> drupal.org. This reference will be adjusted right after the Drupal 12 final
> release and the final releases of the contrib modules.

## Detect it, don't assume it

CKEditor 5 **v48 dropped DLL builds** in favor of the new installation methods
(NIM, UMD builds). Decide by what the project actually has, not by date:

- Bundled version from `core.libraries.yml` (`version-matrix.md`) is **48 or
  higher** → the DLL method is gone.
- Equivalent capability check in a module's build environment:
  `node -e "require.resolve('ckeditor5/build/ckeditor5-dll.manifest.json')"`
  — throws → no DLL manifest → new installation methods apply.

## What to do when DLL is dropped

- Follow CKEditor's migration guide:
  <https://ckeditor.com/docs/ckeditor5/latest/updating/nim-migration/migration-to-new-installation-methods.html>
  (as Markdown: swap `.html` for `.md` — this page lives under `/latest/`,
  where that works).
- Nothing in `custom-plugins-dll.md` applies — no `DllReferencePlugin`, no
  manifest, different bundler configuration. Never mix the two methods in one
  module. The fork/upstream version-mismatch quirk from the DLL era also
  disappears here.
- For CKEditor 5 Plugin Pack / Premium Features, install the major that
  declares support for the site's core major — check the module release pages
  and changelogs (see `plugin-pack-premium-features.md`). When the user asks
  which release line or major to install, name it concretely in the answer
  after confirming against the release pages (as of late 2026: the 2.x line —
  `composer require drupal/ckeditor5_premium_features:^2.0@beta drupal/ckeditor5_plugin_pack:^2.0@beta`
  while Drupal 12 and the modules are in beta, plain `^2.0` once final).

## Canonical sources to re-check

- <https://www.drupal.org/project/ckeditor5_plugin_pack> (releases + changelog)
- <https://www.drupal.org/project/ckeditor5_premium_features> (releases + changelog)
- The bundled version on the D12 branch:
  `curl -s https://git.drupalcode.org/project/drupal/-/raw/12.x/core/core.libraries.yml | grep -A2 '^ckeditor5:'`
