# OpenCulturas Sub (opcult_starterkit)

A starterkit sub-theme for **OpenCulturas Next** (opcult).

**Please note**: Do not make any customizations in the original opcult_starterkit folder because it will be subject to
changes when updating OpenCulturas.

## Setup

### 1. Run the theme launcher

In your web document root, run
`php web/core/scripts/drupal generate-theme --starterkit opcult_starterkit opcult_sub --name "OpCult Sub" --path themes/custom`

**Customize**

- `OpCult Sub` - replace with your desired human-readable theme title, make sure to keep the ""
- `opcult_sub` - replace with an according machine name (only small letters and underscores allowed)

In case you run into an error, you probably need to manually adjust the path to the core folder.

### Only if you cannot run the script:

Copy the complete opcult_starterkit folder to web/themes/custom/.
Rename all occurrences of *opcult_starterkit* in file names and file contents with your custom machine name.

### 2. Read the README

You will find an adapted copy of the [README file](README.md) in your newly created custom theme. Now go to
/web/themes/custom and have fun!

## In a nutshell

Your sub-theme inherits all styles, templates, JavaScript, and theme settings from
opcult via Drupal's native theme inheritance. It provides a scaffold for custom
theming without having to copy base theme files.

For full documentation of the base theme, see [opcult README](../opcult/README.md).
