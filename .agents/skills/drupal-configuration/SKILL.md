---
name: drupal-configuration
description: Guidance for working with Drupal's configuration system — choosing between config, state, and settings; writing config schema; simple config vs. config entities; config dependencies; third-party settings; the config export workflow; and the override system. Covers the mistakes AI agents make most frequently when generating configuration code.
---

# Working with Drupal configuration

## When to use this guidance

You are writing or modifying code that stores, retrieves, or manages Drupal
configuration. This includes module settings forms, config entities, runtime
state, and environment-specific values. Drupal has three distinct storage
mechanisms that serve different purposes, and AI agents consistently pick the
wrong one.

## Guidance

### The three storage mechanisms

Drupal separates stored data into config, state, and settings. Choosing the
wrong one causes deployment bugs, security issues, or data loss.

**Config** (`\Drupal::config()`) stores site-building decisions that should be
the same across all environments: content types, view definitions, module
settings like "items per page." Config is exported to YAML, committed to git,
and imported on deploy. Use config for anything an admin configures once and
expects to persist across deployments.

Note: the examples below use `\Drupal::config()` and `\Drupal::state()` for
brevity. In services, controllers, forms, and plugins, inject the
`config.factory` or `state` service via dependency injection instead of using
static calls.

**State** (`\Drupal::state()`) stores runtime data that changes frequently and
is environment-specific: last cron run, system timestamps, maintenance mode
flag. State is never exported. If you store something in config that changes on
every cron run, config exports will constantly show diffs and risk overwriting
production state on the next deploy.

**Settings** (`$settings` in settings.php) stores environment-specific values
that must not leave the server: database credentials, API keys, hash salts,
environment flags. Settings is not exportable and not accessible through the
admin UI. If you store an API key in config, it gets exported to YAML and
committed to git — a security vulnerability.

#### Decision tree

| Question | Use |
| --- | --- |
| Same across all environments? | Config |
| Changes frequently at runtime? | State |
| Environment-specific or secret? | Settings |
| Admin manages multiple instances through UI? | Config entity |
| Simple key-value module settings? | Simple config |

Note: config can differ per environment via the **config override system**
(settings.php overrides, environment-specific modules). "Same across all
environments" means the _stored_ value is the same — overrides layer on top
at runtime without changing the exported YAML. If a value needs to differ per
environment, store the default in config and override it in settings.php.

### Simple config vs. config entities

**Simple config** (`\Drupal::config('my_module.settings')`) is a single YAML
file of key-value pairs. Use it for module settings with a small number of
options: API endpoint URL, items per page, feature toggle.

**Config entities** implement `ConfigEntityInterface` and are managed through
the admin UI with list, add, edit, and delete operations. Use them when admins
need to create multiple instances of a structured configuration: image styles,
text formats, views, search indexes. Config entities also provide dependency
tracking, access control, and proper lifecycle hooks — these may be needed even
for single-instance configuration when your config depends on other config
entities or modules.

If you only need to store a few settings for your module with no dependency
tracking needs, use simple config with a settings form. If users need to create
and manage multiple named instances, or if the config participates in dependency
chains with other config entities, use a config entity.

### Config dependencies

Every config entity and every config file in `config/install/` or
`config/optional/` must declare its dependencies. The `ConfigImporter` validates
dependencies on import — missing dependencies cause import failures.

Dependencies are declared in the `dependencies` key of the YAML file:

```yaml
dependencies:
  module:
    - node
    - views
  config:
    - node.type.article
  enforced:
    module:
      - my_module
```

Dependency types:
- **module**: modules that must be installed for this config to function.
- **config**: other config entities that this config depends on.
- **theme**: themes that this config depends on.
- **enforced**: dependencies that, when removed, cause this config to be deleted.

For config entities defined in code, dependencies are calculated automatically
by `calculateDependencies()`. For install config YAML files, declare
dependencies explicitly. Missing dependencies will not cause errors at install
time but will break config import/export workflows.

### Third-party settings

When your module needs to attach settings to a config entity it does not own
(e.g., adding a setting to a content type or field config defined by another
module), use the `third_party_settings` mechanism:

```php
// Store a setting on a config entity owned by another module.
$content_type->setThirdPartySetting('my_module', 'custom_flag', TRUE);
$content_type->save();

// Read it back.
$flag = $content_type->getThirdPartySetting('my_module', 'custom_flag', FALSE);
```

Do not modify other modules' config keys directly. Third-party settings are
namespaced by module and are automatically cleaned up when your module is
uninstalled.

Third-party settings also require a schema definition. Declare them using the
`*.third_party.my_module` pattern:

```yaml
node.type.*.third_party.my_module:
  type: mapping
  label: 'My module settings'
  mapping:
    custom_flag:
      type: boolean
      label: 'Custom flag'
```

Without this schema, the data persists in storage but schema validation fails,
config translation breaks, and config inspector cannot display the values.

### Config schema

Every config file in `config/install/` or `config/optional/` must have a
matching schema definition in `config/schema/*.schema.yml`. Config without
schema breaks:

- **Config validation** — strict schema enforcement has been rolled out
  incrementally through Drupal 10.3+ and 11.x. The `RequiredConstraint`
  landed in 10.3; additional strict validation continues in later versions.
  Missing or incorrect schema causes validation failures during config import.
- **Config translation** — untranslatable without schema
- **Config inspector** — cannot display config structure
- **Config export/import** — type coercion fails silently

```yaml
# my_module.schema.yml
my_module.settings:
  type: config_object
  label: 'My module settings'
  mapping:
    api_endpoint:
      type: string
      label: 'API endpoint'
    items_per_page:
      type: integer
      label: 'Items per page'
    enabled:
      type: boolean
      label: 'Enabled'
```

The schema types (`string`, `integer`, `boolean`, `sequence`, `mapping`) must
match the actual values in the YAML file. Additional types exist for specific
purposes: `label` for translatable human-readable strings, `uri` for URIs,
`path` for internal paths, and `date_format` for date format strings. The most
common agent error is using `string` for a human-readable label — use `label`
instead so the value is translatable. Other common mistakes: defining a field
as `string` when the YAML value is an integer, or omitting `sequence` for list
values.

### The export workflow

The correct workflow for config changes:

1. Make changes through the admin UI or programmatically via the API
2. Export with `drush cex` (config export) or via the admin UI at
   `/admin/config/development/configuration`
3. Review the diff in `config/sync/`
4. Commit the YAML files to git

Do not hand-edit YAML files in `config/sync/` on deployed sites unless you are
certain the values match the schema. For module development, writing YAML
directly in `config/install/` and `config/optional/` is the expected workflow,
with schema validation as the quality gate. Do not skip the export step for
`config/sync/` — Drupal's config system normalizes values during export, and
hand-edited files may have wrong types or missing keys.

### Config overrides

Drupal has a layered override system: settings.php overrides, language
overrides, and module overrides. The API separates original and overridden
values:

```php
// Gets the overridden (active) value:
$value = \Drupal::config('system.site')->get('name');

// Gets the original stored value, ignoring overrides:
$raw = \Drupal::config('system.site')->getOriginal('name', FALSE);

// Gets a mutable config object for writing:
$config = \Drupal::configFactory()->getEditable('system.site');
$config->set('name', 'New Name')->save();
```

When you need to read what the site is currently using (with overrides applied),
use `get()`. When you need the original stored value without overrides, use
`getOriginal()` on the immutable config object. When you need to change config,
use `getEditable()`. Do not use `getEditable()` just to read the original value
— it returns a mutable object intended for writing. Overrides are read-only; do
not try to save an overridden config object.

### Config actions and recipes (Drupal 10.3+)

Drupal 10.3+ introduces **config actions** and the **recipe system** as
alternatives to hook_update_N for config changes. Config actions allow modules
to declare config modifications declaratively in recipe.yml files:

```yaml
config:
  actions:
    node.type.article:
      setThirdPartySetting:
        - my_module
        - custom_flag
        - true
```

Recipes are composable configuration packages that can be applied during
install or on demand. They replace many use cases for install profiles and
custom install hooks.

A module's own default configuration still belongs in `config/install/` and
`config/optional/` YAML files — this is the stable, established mechanism and
recipes do not replace it. Use config actions in recipes for cross-module
orchestration and site-building workflows. Use them instead of
`hook_install()` when you need to modify config owned by other modules during
installation.

## What not to do

Do not store API keys, passwords, or other secrets in config. Use
`$settings['my_api_key']` in settings.php, and read it with
`Settings::get('my_api_key')`.

Do not store timestamps, counters, or frequently changing runtime data in
config. Use `\Drupal::state()->set()` and `->get()` instead.

Do not create config files without a matching schema definition. Config
validation will reject them, and translation will be broken in any version.

Do not hand-edit YAML files in `config/sync/` as the primary way to make
config changes. Make changes through the UI or API, then export.

Do not try to save an overridden config object. Overrides are read-only.
Use `getEditable()` to get a saveable copy that ignores overrides.

Do not create a config entity when simple config would suffice — unless you
need dependency tracking, access control, or lifecycle hooks that only config
entities provide. Evaluate the actual requirements before choosing.

Do not modify config keys owned by other modules directly. Use
`third_party_settings` to namespace your data.

Do not omit `dependencies` from install config YAML files. Missing dependencies
cause config import failures.

## See also

- [Configuration API overview](https://www.drupal.org/docs/drupal-apis/configuration-api)
- [Config schema/metadata](https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-schemametadata)
- [State API](https://www.drupal.org/docs/drupal-apis/state-api)
- [Configuration override system](https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-override-system)
- [Recipes](https://www.drupal.org/docs/extending-drupal/drupal-recipes)
