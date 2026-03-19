# Template overrides

You can override Drupal core templates, contributed modules templates or OpenCulturas custom templates
by adding templates to your theme folder that follow a specific naming convention.

As a rule of thumb: to override a given template, your custom theme must provide a file with the exact same file name.

To override templates you need to:

- Locate the template you wish to override.
- Copy the template file from its base location into your theme folder.
- (optionally) Rename the template according to the naming conventions in order to target a more specific subset of areas where the template is used.
- Modify the template to your liking.

Once you copy a template file into your theme and **clear the cache**, Drupal will start using your instance of
the template file instead of the base version.

You do not necessarily need to use the same folder structure (but it is recommended when you are planning
a larger number of templates). Have a look into the opcult/templates.

Read more about [Working with Twig templates](https://www.drupal.org/docs/develop/theming-drupal/twig-in-drupal/working-with-twig-templates)
the Drupal way.

## Please verify

OpenCulturas comes with two custom blocks that have been placed in your theme's block layout (/admin/structure/block).

**Make sure** the corresponding two templates have been copied to templates/block/ and have the correct file name:

- block--[your-theme-name]-search-toggle.html.twig
- block--[your-theme-name]-utility-menu-account.html.twig
