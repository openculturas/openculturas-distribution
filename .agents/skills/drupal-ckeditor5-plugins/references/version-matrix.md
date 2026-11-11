# Drupal ↔ CKEditor 5 version alignment

**Derive the versions from the installed site — never from memory or a
snapshot.** Core versions change between minor releases, so any copied table
goes stale; the procedure below is the only reliable source.

## Derive it (normative)

The live core file is authoritative:

```bash
# In an installed site (web/core/ in Composer layouts, core/ from the web root):
grep -A2 '^ckeditor5:' web/core/core.libraries.yml

# For a specific core release, without a checkout — use the exact version tag:
curl -s "https://git.drupalcode.org/project/drupal/-/raw/11.3.2/core/core.libraries.yml" \
  | grep -A2 '^ckeditor5:'
```

The bundled CKEditor version may change **between patch releases of the same
core minor** (one 11.3.x patch may bundle a different CKEditor than the
next), so never map a core *branch* to a CKEditor version — only the exact
installed release counts. A branch-HEAD URL (`…/raw/11.3.x/…`) shows the
latest patch only; use the installed site's own file, or the exact tag.

The `version:` value under the `ckeditor5:` key is the bundled release. It may
carry a **Drupal fork suffix** — `X.Y.Z-drupal.A`, e.g. `47.6.3-drupal.1` —
meaning core ships a Drupal-maintained fork that does not exist on npm (see
[#3624195](https://www.drupal.org/project/drupal/issues/3624195)).

From the bundled version, derive:

1. **Docs URL** — strip any `-drupal.A` suffix, truncate to
   `<major>.<minor>.0`: bundled `47.6.3-drupal.1` →
   `https://ckeditor.com/docs/ckeditor5/47.6.0/`. Exact-patch URLs (e.g.
   `/47.6.3/`) return 404. Versioned docs are **HTML only** — the Markdown
   variants (`.md` suffix, `Accept: text/markdown`) exist solely under
   `/latest/` and silently return HTML on versioned URLs; see `agent-docs.md`.
   Do not treat `ckeditor.com/docs/ckeditor5/latest/` as authoritative for a
   Drupal site: it documents a newer CKEditor than core bundles, and on the
   v47→v48 boundary even the installation method differs.
2. **npm devDependency for plugin builds** — fork versions are absent from
   npm; the mapping rule lives in `custom-plugins-dll.md`.
3. **Contrib module major** (Plugin Pack / Premium Features) — not derived
   from this file: check the module release pages and changelogs, covered in
   `plugin-pack-premium-features.md`.

Older Drupal 10 minors (10.3, 10.4) still install the modules' current line
but are out of core security support — when a project sits there, flag the
core update to the user rather than targeting the old branch.

Worked example of the whole chain (values will differ — always re-derive):
bundled `47.6.3-drupal.1` → docs `ckeditor.com/docs/ckeditor5/47.6.0/` →
npm devDependency per the rule in `custom-plugins-dll.md`.
