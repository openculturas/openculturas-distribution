<!--
Draft release-notes section for the "extended release notes" magazine article
(openculturas.org), following the tone/length/format of past entries like
https://www.openculturas.org/en/magazine/openculturas-3-1 — problem-first
paragraph, solution paragraph, capability bullets, italic "Please note" admin
callout, italic closing action line.

Status: draft, still being iterated on.
-->

## Images that fit where they're actually shown

Until now, every image on your site rendered at one fixed size and aspect
ratio, no matter how large or small its slot on the page actually was — a
phone loaded the same file as a widescreen desktop, and a header image cropped
for a full-width banner looked just as cropped in a narrower layout.

OpenCulturas now ships a responsive-image toolkit: six new aspect ratios
(landscape and portrait), each with its own family of pre-sized derivatives,
wired up through Drupal's native responsive image system. Once you enable it,
a browser automatically picks the smallest file that still looks sharp for its
actual display size — faster page loads, especially on mobile, with no
visible difference in quality.

- Header images, teaser images, and profile pictures switch over with one
  click on the module's settings page — no manual display configuration
  needed to get started
- Six ratios are available if you want a different one for a given view mode
  (16:9, 2:1, 1:1, 4:3, 3:4, 9:16) — for example, the half-width "Title block
  side by side with image" hero layout often suits a taller crop better than
  the default 2:1
- Gallery images and images placed directly in body text switch over too —
  no longer a single fixed size regardless of how each image
  is actually cropped, so unusually wide or tall images fit their slot
  correctly instead of overflowing or getting stretched
- Teaser image overrides in the custom teaser paragraphs now allow changing
  the image style (which is by default identical with the corresponding source
  teaser image style).
- A guided migration tool carries over your editors' existing hand-drawn crops
  onto the new ratios, so no framing gets lost

*Please note:* this is an optional module ("OpenCulturas - Responsive
images") — nothing changes on existing sites until you choose to install it.

*To put the update to use:* install the module, then follow the Configure
link (`/admin/config/media/openculturas_responsive_images`), migrate your
site's existing image crops, then switch the image displays.
