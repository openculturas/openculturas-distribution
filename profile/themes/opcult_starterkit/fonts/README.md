# Custom Fonts

Place your custom web font files in this directory.

## How to use a custom font

1. **Add your font files** to a subdirectory named after the font family:

   ```
   fonts/
   └── YourFontName/
       └── woff2/
           └── YourFontName.woff2
   ```

2. Create a _fonts.scss file in your sass folder and **declare `@font-face`** in that file:

   ```scss
   /* latin */
   @font-face {
     font-family: 'YourFontName';
     font-style: normal;
     font-weight: 100 900;
     src: url(../fonts/YourFontName/woff2/YourFontName.woff2) format('woff2');
     unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6,
       U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC,
       U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
   }
   ```

   For multiple character sets (e.g. latin-ext), add additional `@font-face`
   declarations with the appropriate `src` and `unicode-range`.

3. Add and **override the CSS custom properties** to that file to use your font:

   ```scss
   :root {
     --oc-font-family-default: "YourFontName", sans-serif;
     --oc-font-family-head: "YourFontName", sans-serif;
   }
   ```

4. **Compile** your SCSS:

   ```bash
   npm run build
   ```

## Where to get fonts

You can download open-source web fonts from sources like:

- [DOULOS SIL](https://software.sil.org/fonts/)
- [Google Fonts](https://fonts.google.com/) (download, do not hotlink)

Use a tool like [google-webfonts-helper](https://gwfh.mranftl.com/fonts) to
download self-hosted font files directly.

## Privacy note

For GDPR compliance, always **host web fonts locally** on your server rather
than loading them from external CDNs (e.g. Google Fonts CDN). This ensures
no user data is shared with third-party services.
