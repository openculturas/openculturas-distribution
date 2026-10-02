# Favicons for your theme

The default OpenCulturas favicons and site.webmanifest have been copied to your theme. Now you want to replace them.

If you want to keep the OpenCulturas favicons, delete this `favicons/` folder instead: your theme then uses the
`favicons/` folder of its base theme opcult.

## Required steps

1. Provide a square icon graphic, optimally SVG, otherwise at least 512 x 512 pixels, that is still recognizable
at very small sizes.
2. Build the variants accordingly (see copied files). A tool like [RealFaviconGenerator](https://realfavicongenerator.net/)
could be a convenient helper.
3. Replace the icons but keep the file names.
4. Set `name` and `short_name` in site.webmanifest to your site name. The theme generator filled in your theme's
name there.
5. Clear caches.

Reload the page and check whether you see your icon in a browser tab.

## Where these favicons are used

Once your theme is active, these files are picked up automatically — no extra configuration needed:

- The site-wide `metatag_favicons` defaults render the icon `<link>` tags. They resolve
  a `[site:favicon-path]` token to your theme's `favicons/` folder. As soon as the folder exists, every file is
  expected there under its standard name; a missing file means a broken icon link, there is no per-file
  fallback to the base theme. Check the browser's network tab: Drupal's log doesn't list every missing file,
  e.g. missing `.png` and `.ico` files get Drupal's fast 404 response without a "page not found" entry.
- The `site.webmanifest` link is rendered by opcult's `html.html.twig`. If your theme overrides that template,
  keep the manifest `<link>` tag.
- `site.webmanifest`'s own `icons[].src` entries — keep these as bare filenames (no leading `/`), since they
  resolve relative to the manifest file's own location.

## Uploading a favicon instead

In the "Favicon" section at `admin/appearance/settings/opcult_starterkit`, a site owner can uncheck
"Use the favicon supplied by the theme" and upload their own file. This only replaces the `favicon.ico` link.
Browsers that support SVG favicons (e.g. Chrome, Edge and Firefox) keep showing `favicon.svg` in the tab, and iOS
keeps `apple-touch-icon.png`. To replace all icons, replace the files in this folder or override the icon
tags at `admin/config/search/metatag/global`.
