# Cookies Popover

Improves the accessibility of the [COOKiES](https://www.drupal.org/project/cookies)
module's (cookiesjsr) consent-settings layer:

- Promotes the settings layer into the browser's native top layer via the
  [Popover API](https://developer.mozilla.org/en-US/docs/Web/API/Popover_API),
  so it renders as a proper modal with a backdrop and the rest of the page
  marked `inert`.
- Lets you open that layer from a real `<button>` instead of the legacy
  hash-triggered `<a href="#editCookieSettings">` link.
- Turns the "Accept all cookies" link in cookiesjsr's blocked-embed fallback
  overlay (`.cookies-fallback--link`) into a `<button>` too.

## Dependencies

- [COOKiES](https://www.drupal.org/project/cookies) consent management
- [Menu Link Attributes](https://www.drupal.org/project/menu_link_attributes) for transforming settings menu links into functional settings buttons

## Adding a button that reopens the settings layer

There are two ways to place one:

### Block

Place the **Cookie settings (reopen button)** block (plugin ID
`cookies_settings_button`, category *Cookies Popover*) anywhere blocks can go. Its button text is
configurable per placement and defaults to *"Privacy settings"*.

### Menu link

To turn a menu link into a button that opens the settings layer:

1. Install and enable the
   [Menu Link Attributes](https://www.drupal.org/project/menu_link_attributes)
   module and grant the relevant user roles the
   **"Use menu link attributes"** permission.
2. Create or edit the menu link. Set **Link** to `<button>` — Drupal's
   built-in "no link" route that renders a menu link as a `<button>` element
   instead of an `<a>`.
3. Open the **Attributes** section and set **Link class(es)** to
   `editCookieSettings`.

Any element with the `editCookieSettings` class — not just menu links —
gets the click handler; the block above uses the same class internally. 
**Please note**: named for consistency with the COOKiES's default hash link.
In case you have changed or will change that string, there's no connection between 
that setting and the link class this module expects.

## How it works

`js/cookies-popover.js` attaches a click handler to every `.editCookieSettings`
element that sets `location.hash` to the value of `cookies.config`'s
**"Open settings dialog"** hash (`open_settings_hash`).
cookiesjsr reacts to that hash change and opens the layer; this module then
promotes it via the Popover API.

## Caveat: the settings layer must be on the page

The `editCookieSettings` click handler ships in this module's library, which
is only attached when the **Cookies UI** block (plugin `cookies_ui_block`,
the consent banner) renders — there is no separate, page-independent
attachment. A button on a page where that block is hidden does nothing.

By default, the Cookies UI block is hidden on pages listed in its
*Pages* visibility ignore list, which is kept in sync with the
privacy policy and imprint links configured at *COOKiES Widget Texts*
(`/admin/config/system/cookies/texts`) — see
`Drupal\openculturas_custom\EventSubscriber\OpenculturasCustomCookiesLegalPagesSubscriber`.
A button placed only in a menu that also appears on those excluded pages will
be visible but inert there.

## Troubleshooting

- **Click does nothing:** check that the Cookies UI block is visible on the
  current page (see caveat above), and that `cookies.config`'s
  `open_settings_hash` is set.
- **`showPopover() failed` warning in the console:** the browser doesn't
  support the Popover API; the module falls back to no behavior change
  rather than breaking the layer.
