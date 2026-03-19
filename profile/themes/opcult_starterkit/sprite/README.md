# Icons for SVG sprite generation

The theme's gulpfile generates an SVG sprite file from the icons in /sprite/svg.

We are using [Phosphor Icons](https://phosphoricons.com/).

Icons can then be used in CSS or SASS with their file names. Have a look at the icon mixin
to find out how to include icons in SASS.

**Please note:** do not manually add icons to the sprite because it is generated and will be overwritten.
Instead, add new icons to the svg folder to add new icons
to the sprite.

Icons are rendered in stack mode. You will not see any icon when looking at oc-sprite.svg unless you
address a specific ID, e.g. <code>oc-sprite.svg#user-full</code>

Render a new sprite on occasion: `npm run svg`

## Files

```
(/profile/themes/opcult/)
├── gulpfile.mjs                # SVG functions
├── sprite
│   ├── svg                     # single SVG icons to process
│   ├── symbol
│   │   ├── _sprite.scss        # rendered SCSS file filling --icon-url variable related to .icon--* class (do not change manually)
│   ├── tpl
│   │   ├── scss-template.txt   # template file used to render entries in _sprite.scss
│   ├── oc-sprite.svg           # the rendered all-in-one svg file (do not change manually)
├── sass
│   ├── abstracts
│   │   ├── _mixins.scss        # icon mixin function
│   │   ├── _icons.scss         # icon-related css variables + placeholders

```


## Icons by name + fontawesome icons mapping

Sharing our annotated cheatsheet here to help you find new names for old icons:

| Usage                         | Phosphor Icon                                          | Fontawesome                                                           | Notes                                   |
|-------------------------------|--------------------------------------------------------|-----------------------------------------------------------------------|-----------------------------------------|
| Success feedback              | check-square                                           | check _or_ square-check                                               |                                         |
| Warning feedback              | warning                                                | triangle-exclamation                                                  |                                         |
| Error feedback                | warning-octagon _or_ prohibit                          | ban                                                                   |                                         |
| language menu trigger         | globe-simple _or_ translate                            | globe                                                                 |                                         |
| dark/light/system mode toggle | moon/sun/circle-half                                   | moon/sun/circle-half-stroke                                           |                                         |
| account menu trigger          | user                                                   | user                                                                  |                                         |
| menu trigger                  | list/x                                                 | bars/xmark                                                            |                                         |
| context menu trigger          | dots-three-vertical                                    | ellipsis-vertical                                                     | planned                                 |
| search                        | magnifying-glass                                       | magnifying-glass                                                      |                                         |
| login/logout                  | sign-in/sign-out                                       | arrow-right-to-bracket/arrow-right-from-bracket                       |                                         |
| password show/hide            | eye/eye-slash                                          | eye/eye-slash                                                         |                                         |
| tfa                           | fingerprint _or_ fingerprint-simple                    | fingerprint                                                           | optional                                |
| to-top link                   | arrow-line-up _or_ arrow-u-right-up                    | arrow-up-stroke                                                       |                                         |
| Profile picture placeholder   | user-circle                                            | user                                                                  |                                         |
| Location picture placeholder  | map-pin                                                | location-dot                                                          |                                         |
| bookmark                      | bookmark-simple                                        | bookmark outline/solid                                                | active w/fill                           |
| recommend                     | star _or_ heart-straight                               | star _or_ heart                                                       | active w/fill                           |
| comment                       | chat-text                                              | comment                                                               | optional                                |
| reminder/reminder off         | bell _or_ bell-ringing/bell-slash                      | bell/bell-slash                                                       | planned                                 |
| details toggle                | caret-up/caret-down                                    | chevron up/down                                                       | use rotate                              |
| slideshow navigation          | caret-left/caret-right                                 | chevron left/right                                                    | use rotate                              |
| abuse flag                    | hand _or_ prohibit _or_ warning-octagon                | hand _or_ octagon-exclamation \[pro\]                                 |                                         |
| claim                         | hand-arrow-down _or_ handshake                         | hands exchanging                                                      |                                         |
| share                         | share-network                                          | share-nodes _or_ share _or_ share-from-square _or_ square-share-nodes |                                         |
| anchor menu                   | arrow-line-down                                        | arrow-turn-down _or_ angles-down _or_ arrow-down                      | planned                                 |
| info icon                     | info                                                   | circle-info                                                           | optional                                |
| question icon                 | question                                               | circle-question                                                       | optional                                |
| copyright icon                | copyright                                              | copyright                                                             | optional                                |
| map marker                    | map-pin                                                | location-dot                                                          | use fill                                |
| location prefix               | map-pin                                                | location-dot                                                          |                                         |
| mail prefix                   | envelope-simple _or_ envelope                          | envelope                                                              |                                         |
| phone prefix                  | phone                                                  | phone                                                                 |                                         |
| website prefix                | globe _or_ globe-simple                                | globe                                                                 | check back w/ lang switcher             |
| legal prefix                  | scales _or_ gavel                                      | section _or_ gavel _or_ scale-balanced                                |
| navigation prefix             | compass                                                | compass                                                               |                                         |
| public transport prefix       | bus _or_ train                                         | bus-simple _or_ bus _or_ train-subway                                 |                                         |
| show on map                   | map-trifold _or_ map-pin-line _or_ map-pin-simple-area | map-location-dot                                                      |                                         |
| add button icon               | plus _or_ plus-circle                                  | plus _or_ circle-plus                                                 |                                         |
| add to calendar               | calendar-plus                                          | calendar-plus                                                         |                                         |
| download                      | download-simple                                        | download _or_ cloud-arrow-down                                        |                                         |
| export                        | export                                                 | file-export                                                           | optional                                |
| archive                       | archive                                                | box-archive                                                           | optional                                |
| table sort                    | arrows-vertical / arrow-up / arrow-down                | arrows-up-down or arrow-down-arrow-up / arrow-up / arrow-down         |                                         |
| peer marker                   | medal _or_ seal                                        | medal _or_ award _or_ crown _or_ certificate                          |                                         |
| filter trigger                | faders _or_ sliders-horizontal                         | sliders                                                               |                                         |
| edit                          | pencil or pencil-simple or pen-nib                     |                                                                       |                                         |
| view                          | eye                                                    |                                                                       |                                         |
| delete                        | trash                                                  | trash-can                                                             |                                         |
| revisions                     | cards-three                                            |                                                                       |                                         |
| translate                     | translate                                              |                                                                       |                                         |
| unpublished status            | eye-slash                                              |                                                                       |                                         |
| quote                         | quotes                                                 | quote-left, quote-right                                               | maybe use fill                          |
| grid/list toggle              | squares-four _or_ list-dashes                          | table-cells-large/table-list _or_ list                                |                                         |
| copy (to clipboard)           | clipboard _or_ copy-simple                             | copy                                                                  |                                         |
| external link                 | arrow-line-up-right _or_ arrow-square-out              | arrow-up-right-from-square                                            |                                         |
| accessibility                 | person-simple-circle                                   | universal-access                                                      |                                         |
| wheelchair                    | wheelchair                                             | wheelchair _or_ wheelchair-move                                       |                                         |
| wheelchair restrooms          | \-                                                     | \-                                                                    | pending phosphor request                |
| deaf                          | ear-slash                                              | ear-deaf                                                              |                                         |
| blind                         | eye-slash                                              | eye-low-vision _or_ person-walking-with-cane                          |                                         |
| audio description             | \-                                                     | audio-description                                                     |                                         |
| closed-captioning             | closed-captioning                                      | closed-captioning                                                     |                                         |
| subtitles                     | subtitles                                              | (closed-captioning)                                                   |                                         |
| sign language                 | \-                                                     | hands _or_ hands-asl-interpreting                                     | local: hands (pending phosphor request) |
| braille                       | \-                                                     | braille                                                               |                                         |
| Mastodon                      | mastodon-logo                                          | mastodon                                                              |                                         |
| Facebook                      | facebook-logo                                          | facebook _or_ facebook-f _or_ square-facebook                         |                                         |
| Instagram                     | instagram-logo                                         | instagram                                                             |                                         |
| Threads                       | threads-logo                                           | threads _or_ square-threads                                           |                                         |
| Bluesky                       | \-                                                     | bluesky _or_ square-bluesky                                           | local: bluesky-logo                     |
| YouTube                       | youtube-logo                                           | youtube _or_ square-youtube                                           |                                         |
| TikTok                        | tiktok-logo                                            | tiktok                                                                |                                         |
| Twitch                        | twitch-logo                                            | twitch                                                                |                                         |
| Linkedin                      | linkedin-logo                                          | linked-in _or_ linkedin                                               |                                         |
| WhatsApp                      | whatsapp-logo                                          | whatsapp or square-whatsapp                                           |                                         |
| Patreon                       | patreon-logo                                           | patreon                                                               |                                         |
| X/Twitter                     | x-logo/twitter-logo                                    | x-twitter _or_ square-x-twitter/twitter _or_ square-twitter           |                                         |
| Soundcloud                    | soundcloud-logo                                        | soundcloud                                                            |                                         |
| Vimeo                         | \-                                                     | vimeo-v _or_ square-vimeo                                             | local: vimeo                            |
| Snapchat                      | snapchat-logo                                          | snapchat _or_ square-snapchat                                         |                                         |
| Telegram                      | telegram-logo                                          | telegram                                                              |                                         |
| Drupal                        | \-                                                     | drupal                                                                | local: drupal-logo                      |
| Github                        | github-logo                                            | github _or_ square-github                                             |                                         |
| Gitlab                        | gitlab-logo-simple                                     | gitlab _or_ square-gitlab                                             |                                         |
| Person                        | person-simple _or_ user-focus _or_ user                | person-dots-from-line                                                 |                                         |
| Group                         | users-three _or_ users-four                            | people-line                                                           |                                         |
| Magazine                      | book-open                                              | book-open                                                             |                                         |
| Event offer                   | mask-happy _or_ popcorn                                | masks-theater                                                         |                                         |
| Calendar                      | calendar-dots                                          | calendar-check                                                        |                                         |

### Back-end

Paragraph icons

| Usage                      | Phosphor Icon                     | Notes |
|----------------------------|-----------------------------------|-------|
| Accessibility and triggers | person-simple-circle              |       |
| Address data               | map-pin                           |       |
| Block                      | stack                             |       |
| Bookable event             | list-plus _(?)_                   |       |
| Contact data               | address-book                      |       |
| Discounts                  | coin or tag                       |       |
| Download                   | download-simple                   |       |
| Gallery                    | images                            |       |
| Media                      | image _(?)_                       |       |
| Member                     | user                              |       |
| Press quote                | quotes                            |       |
| Teaser external            | arrow-square-out                  |       |
| Teaser section             | squares-four                      |       |
| Teaser to content          | article                           |       |
| Teaser to term             | tag-simple _or_ hash              |       |
| Text                       | text-align-justify or text-t      |       |
| Text slider                | slideshow _or_ textbox            |       |
| View                       | grid-nine _or_ grid-four          |       |
| Wheelchair accessibility   | wheelchair-motion _or_ wheelchair |       |
| Wrapper section            | rectangle-dashed                  |       |

