# Config Split

[Config Split](https://www.drupal.org/project/config_split) keeps
environment-specific configuration out of the main export. Each split is a
config entity (`config_split.config_split.<id>`) with its own folder. While a
split is active, `drush cex` writes its share of the config to that folder and
`drush cim` merges it back in. While it is inactive, both commands ignore it.

Check the project first: `composer show drupal/config_split` for the version
(this guide matches 2.x), and the `config_split.config_split.*.yml` files in
`config/sync/` for the splits that already exist.

## Complete or partial split

| The config... | Split type | Example |
| --- | --- | --- |
| should not exist at all on some environments | Complete | devel or stage_file_proxy and their config, a dev-only view |
| exists everywhere, but differs on some environments | Partial | a view with an extra display on dev, a role with extra permissions on staging |

**Complete split.** The listed config is removed from `config/sync/` and stored
only in the split. A listed module is also removed from `core.extension` on
export and installed again on import with the split active. Config that
depends on a listed module is split off with it, or changed as if the module
were uninstalled.

**Partial split.** The listed config stays in `config/sync/`. On export, the
active config is compared with the copy already in `config/sync/`, and only the
difference is saved in the split as `config_split.patch.<config name>.yml`. On
import with the split active, the patch is applied on top.

Pick partial whenever the item must still exist where the split is off. A
complete split removes it there on the next `drush cim`, because import deletes
config that is not in the export.

## Partial split or settings.php override

Both make config differ per environment. Use a `$config` override in
settings.php for a single value, a secret, or anything that must never be
exported: overrides are applied at runtime and never reach the YAML. Use a
partial split when the difference is structural or spans many keys (an added
display, handler or permission), is easier to make in the UI, and should be
exported and shared with the team.

## Setting up a split

1. Add the split at `/admin/config/development/configuration/config-split`.
   Give it a folder outside `config/sync/`, such as `../config/dev` next to
   `../config/sync`. Never put the split folder inside the sync directory.
2. Save it with status off and export, so `config/sync/` holds it as inactive.
3. Turn it on only where it applies, in that environment's settings.php, then
   run `drush cr`:

   ```php
   $config['config_split.config_split.dev']['status'] = TRUE;
   ```

   The override is not exported, so `config/sync/` keeps `status: false` and
   every other environment stays off.
4. Edit a split only while it is active. An inactive split is ignored on
   export, and its folder is left as it was.

## Trap: partial items must already be in config/sync/

A partial split can only compare against a copy that is already in
`config/sync/`. If a listed item is not there yet, `drush cex` moves it
**completely** into the split. A required item such as `system.site` then
disappears from `config/sync/`, and every environment without the split loses
it on import.

For a new partial split: export once with the split off, so the base version
lands in `config/sync/`. Then turn the split on, make the environment-specific
change and export again. Afterwards, check with `git status` that the item is
still in `config/sync/` and the split folder holds only a
`config_split.patch.*.yml` file for it.

## See also

- [Configuration Split documentation](https://www.drupal.org/docs/contributed-modules/configuration-split)
- [Config Split project page](https://www.drupal.org/project/config_split)
