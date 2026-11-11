# Custom CKEditor 5 plugins on Drupal 10.3–11 (DLL builds)

Applies to any core that bundles CKEditor 5 ≤ v47 — in practice Drupal
10.3 through 11.x. For Drupal 12+ / CKEditor 5 v48+ this entire pattern is
obsolete; read `drupal-12-outlook.md` instead.

Before building: confirm the feature isn't already shipped by core, the
CKEditor 5 Plugin Pack, or Premium Features, and check those modules' issue
queues for an existing feature request — the decision ladder is in
`plugin-pack-premium-features.md`.

## Why bundling is mandatory

Core ships CKEditor 5 as a prebuilt webpack DLL. Custom plugin JavaScript must
be compiled into a UMD bundle against core's DLL manifest
(`ckeditor5-dll.manifest.json`) so that imports resolve to the one framework
instance core already loads. Pointing a Drupal library at raw ES-module source
fails at runtime with `Cannot use import statement outside a module`.

## The six files

Worked name throughout: module `ckeditor5_timestamp`, plugin `timestamp`.
Keep the namespace identical in every marked spot — see Troubleshooting.

**1. `ckeditor5_timestamp.info.yml`**

```yaml
name: CKEditor 5 Timestamp
type: module
description: 'Adds a CKEditor 5 plugin that inserts the current timestamp.'
package: CKEditor
core_version_requirement: '^10.3 || ^11'
dependencies:
  - drupal:ckeditor5
```

**2. `ckeditor5_timestamp.ckeditor5.yml`** — registers the plugin with Drupal:

```yaml
ckeditor5_timestamp_timestamp:
  ckeditor5:
    plugins:
      - timestamp.Timestamp        # <namespace>.<Class> — must match webpack output
  drupal:
    label: Timestamp
    library: ckeditor5_timestamp/timestamp
    toolbar_items:
      timestamp:
        label: Timestamp
    elements: false                # or the HTML elements the plugin creates
```

**3. `ckeditor5_timestamp.libraries.yml`** — points at the *built* bundle:

```yaml
timestamp:
  js:
    js/build/timestamp.js: { minified: true }
  dependencies:
    - core/ckeditor5
```

**4. `js/ckeditor5_plugins/timestamp/src/index.js`** — imports only from
`ckeditor5/src/*`:

```javascript
import { Plugin } from 'ckeditor5/src/core';
import { ButtonView } from 'ckeditor5/src/ui';

class Timestamp extends Plugin {
  init() {
    const editor = this.editor;
    editor.ui.componentFactory.add('timestamp', (locale) => {
      const button = new ButtonView(locale);
      button.set({ label: 'Timestamp', withText: true, tooltip: true });
      button.on('execute', () => {
        editor.model.change((writer) => {
          editor.model.insertContent(writer.createText(new Date().toString()));
        });
        editor.editing.view.focus();
      });
      return button;
    });
  }

  static get pluginName() {
    return 'Timestamp';
  }
}

export default { Timestamp };
```

**5. `webpack.config.js`** — the load-bearing part of the pattern:

```javascript
const path = require('path');
const webpack = require('webpack');

module.exports = {
  entry: './js/ckeditor5_plugins/timestamp/src/index.js',
  output: {
    path: path.resolve(__dirname, 'js/build'),
    filename: 'timestamp.js',
    library: ['CKEditor5', 'timestamp'],   // exports CKEditor5.timestamp
    libraryTarget: 'umd',
    libraryExport: 'default',
  },
  plugins: [
    new webpack.DllReferencePlugin({
      manifest: require.resolve('ckeditor5/build/ckeditor5-dll.manifest.json'),
      scope: 'ckeditor5/src',
      name: 'CKEditor5.dll',
    }),
  ],
};
```

In `package.json`, pin the `ckeditor5` devDependency close to the bundled core
version (`version-matrix.md`) — this pin only serves the local dev
environment: it keeps the build and your IDE's import resolution in order. The
bundled version may be a Drupal fork (`X.Y.Z-drupal.A`, e.g.
`47.6.3-drupal.1`) that does not exist on npm; in that case install the
nearest upstream release at or above `X.(Y+1).0` — for example
`47.6.3-drupal.0` → `47.7.0` (verify with `npm view ckeditor5@<version>`).
The DLL-build version and the bundled version not matching 100% is a known
quirk of the DLL era; it is solved by the new installation methods (UMD
builds) from Drupal 12. Never cross into the next major (v48+ drops the DLL
manifest and the build stops resolving), and never use `latest`.
Build scripts: `"build": "webpack --mode production"`. Node 18+.

**6. `src/Plugin/CKEditor5Plugin/Timestamp.php`**

```php
<?php

declare(strict_types=1);

namespace Drupal\ckeditor5_timestamp\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;

/**
 * CKEditor 5 Timestamp plugin.
 */
class Timestamp extends CKEditor5PluginDefault {

}
```

Extend `CKEditor5PluginDefault` for toolbar-only plugins; add
`CKEditor5PluginConfigurableInterface` only when the plugin has settings.

## Build and enable

```bash
npm install && npm run build      # produces js/build/timestamp.js
drush en ckeditor5_timestamp
drush cr
```

Then drag the button into the toolbar of a text format at
`/admin/config/content/formats`.

## Troubleshooting

- **`Cannot use import statement outside a module`** — the library loads
  unbundled source: run the build and make sure `libraries.yml` points at
  `js/build/`, not `js/ckeditor5_plugins/.../src/`.
- **Button missing from the toolbar config UI** — `.ckeditor5.yml` key
  mismatch (plugin definition key, `library`, or `toolbar_items`); rebuild
  caches after fixing.
- **Button renders but does nothing, no console error** — webpack
  `library: ['CKEditor5', '<namespace>']` doesn't match the
  `<namespace>.<Class>` entry in `.ckeditor5.yml`.
- **Changes don't show up** — rebuild (`npm run build`) and `drush cr`; both
  layers cache.
