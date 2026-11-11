# CKEditor 5 Plugin Pack and Premium Features

Two official CKEditor-maintained contrib modules extend Drupal's built-in
CKEditor 5. Check them **before writing any custom plugin** — most "missing"
editor features already exist in one of them.

## Before building custom: the check-first rule

When a user asks for a custom CKEditor plugin, resolve in this order:

1. **Drupal core** — the CKEditor 5 module already ships more than the default
   toolbar shows; check the text format's available buttons first.
2. **CKEditor 5 Plugin Pack** (free) — scan the plugin list below.
3. **CKEditor 5 Premium Features** (commercial) — collaboration, AI,
   conversion features.
4. **The module's issue queue** — if the feature is close to an existing
   plugin but not quite there, search the queue for a feature request and
   point the user at it (comment/+1, or file a new one) *in addition to* any
   local workaround:
   - <https://www.drupal.org/project/issues/ckeditor5_plugin_pack>
   - <https://www.drupal.org/project/issues/ckeditor5_premium_features>
   Search before filing — the queues are small and duplicates are closed.
5. **Custom plugin** (`custom-plugins-dll.md`) — only when none of the above
   covers it. A custom plugin is a maintenance commitment across CKEditor
   upgrades; an upstream feature request is often the better long-term answer
   even when you also build a stopgap.

## CKEditor 5 Plugin Pack (free)

<https://www.drupal.org/project/ckeditor5_plugin_pack> —
`composer require 'drupal/ckeditor5_plugin_pack:^1.5'`

Free, official, one submodule per plugin: enable only what you need, then add
the button/plugin to the text format at `/admin/config/content/formats`.
Plugins include (1.5.x, September 2026): Auto Image, Block Indentation,
Bookmarks, Emoji, Find and Replace, Fonts, Fullscreen Mode, Highlight, Layout
Tables, Link Attributes, Media Embed, Page Break, Paste Markdown, Restricted
Editing, Select All, Templates, Text Transformation, To-do Document List, Word
Count, and WProofreader (free tier). Some plugins need a newer core minor
(e.g. Emoji/Fullscreen 10.5+, Restricted Editing 10.6+) — the project page
lists per-plugin requirements.

## CKEditor 5 Premium Features (commercial)

<https://www.drupal.org/project/ckeditor5_premium_features> —
`composer require 'drupal/ckeditor5_premium_features:^1.8'`

Requires a license key (14-day trial at portal.ckeditor.com). Feature areas:

- **CKEditor AI** — AI Chat next to the document, AI Review (accept/dismiss
  per suggestion, custom editorial review checks since 1.8.1), AI Chat
  shortcuts (1.8.1), quick actions on selected text, your choice of LLM
  provider. Since 1.8.2, the Drupal AI module's Context Control Center feeds
  global site context into CKEditor AI — configure context once in Drupal AI
  and every editor field inherits it.
- **Collaboration** — track changes, comments, revision history, real-time
  co-editing, notifications.
- **Content conversion** — Export to PDF (v2 engine) / Word, import from
  Word, document outline, table of contents.
- **Productivity** — slash commands, format painter, enhanced paste from
  Office, multi-level lists, footnotes, merge fields; WProofreader full tier.

## Version discipline

Both modules keep one release line per Drupal generation — as of late 2026:
**1.x** for Drupal 10.3+/11 (CKEditor 5 v47.x, bug fixes only), **2.x** for
Drupal 12+ (CKEditor 5 v48+, new features; pre-release — see
`drupal-12-outlook.md`). Don't trust this snapshot: check the module release
pages and changelogs for which major declares support for the site's core
major, install that line, and never mix lines. Configuration is per text
format (toolbar + plugin settings); Premium Features additionally needs the
license configured at its module settings page. The module changelogs on
drupal.org are the canonical record of what shipped in which release.
