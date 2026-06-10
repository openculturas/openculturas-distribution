---
name: drupal-writing-documentation
description: Guidance for writing or updating Drupal-facing documentation—README files, change records, module help, and technical notes—for core, contrib, and custom work. Emphasizes audience, accuracy, and linking to canonical sources.
---

# How to write excellent documentation for Drupal

## When to use this guidance

You are writing or editing documentation for the Drupal project — whether that
is a drupal.org wiki page, API documentation in source code, a contrib module
README, or a user guide. Drupal's documentation ecosystem is fragmented across
several distinct systems with different editing workflows, and there are
community conventions around quality and scope that are not obvious to newcomers.

## Understanding where Drupal documentation lives

Drupal documentation exists in several distinct silos, each with a different
editing workflow.

**drupal.org wiki pages** are the most common. Anyone with a drupal.org account
can edit them. Pages are organised into guides; many guides have one or more
named maintainers listed in the right-hand sidebar, but maintainers are often
inactive. To understand who has been actively working on a page, use the
"Discussion" tab in the top-right navigation widget — it shows the full revision
history and contributors. Assume that most edits are minor fixes by drive-by
contributors, not the original author.

**api.drupal.org** is generated directly from Drupal core's source code. If
something is wrong or missing on api.drupal.org, the fix goes in the source code
(docblocks, inline comments), not on the website. Changes appear on the API site
after the next generation run.

**The Drupal User Guide** is maintained as AsciiDoc files inside a drupal.org
project. You cannot edit it by clicking an Edit button on drupal.org. Propose
changes by working with the source files in that project.

**The Drupal CMS User Guide** follows the same pattern but uses Markdown files
in a separate git repository. The two guides overlap significantly but cover
different products (Drupal core vs. Drupal CMS).

**Contrib module documentation** on drupal.org is technically open to anyone,
but is typically maintained by the module's contributors. Coordinate with them
when making substantive changes.

## Writing good API documentation

The basics — documenting function parameters and return values — are usually
done adequately. What is most often missing:

- **When and why to use this function.** Don't just describe what it does.
  Explain the situations where a developer should reach for it, and situations
  where they should not.
- **Relationship to the broader ecosystem.** A single method or function is
  rarely used in isolation. Explain how it fits into the surrounding API
  subsystem. For example: how does this hook relate to the Entity API? Providing
  these pathways saves developers from shooting themselves in the foot because
  they only understood one piece of a larger picture.

## Core principles for all Drupal documentation

### Document Drupal; link to everything else

Drupal's documentation should document Drupal, and link out to documentation for
other tools. A common mistake is embedding lengthy instructions for third-party
tools (how to install Docker, how to configure Apache virtual hosts, how to set
up PHP) inside Drupal docs. Those instructions go stale quickly and are already
better maintained elsewhere. Focus on the Drupal-specific steps, and link to the
authoritative source for anything that isn't Drupal.

### Be opinionated

Avoid content sprawl driven by trying to cover every possible option. Readers —
especially beginners — need a recommended path they can follow with confidence.
Give them one. If multiple valid approaches exist, name the community-preferred
one first and be explicit: *"Start with DDEV. If that doesn't work for your
situation, here are alternatives."* Then list the alternatives with links rather
than full documentation for each.

This is the difference between knowledge (documenting every feature and every
option) and wisdom (documenting how people actually use things successfully).

### Know your audience

Before writing, picture the person reading this page. Is this someone
encountering Drupal or this subsystem for the first time? Or is it reference
documentation for experienced developers who just need a quick reminder? The
right level of detail is completely different for each. Drupal developers are
generally experienced engineers — do not over-explain things that are not Drupal-
specific. Do cover the parts that are genuinely Drupal-specific and surprising.

### Keep pages short and scannable

Most readers arrive with a specific question. They want to scan the page, find
the relevant section, and ignore everything they already know. Prefer:

- Minimum words needed to get the message across.
- Multiple shorter pages over one very long page, as long as the hierarchy is
  clear.
- Headings and structure that let readers skip to the part they need.

Shorter, well-structured pages also rank better in search engines and are easier
for LLMs to retrieve relevant context from.

### Use Drupal's terminology correctly

Drupal has a specific vocabulary where common words carry precise meanings
(*entity*, *node*, *bundle*, *field*, *region*, *block*). Use these terms
correctly so that experienced Drupal developers immediately understand what you
mean. Read the [Drupal Content Style Guide](https://www.drupal.org/drupalorg/style-guide)
before writing. Key rules from the style guide include:

- *Drupal* is always capitalised.
- *JavaScript* is always one word, capital J, capital S.

Nobody will refuse your contribution for a style guide violation, but following
it improves quality and reduces the editing burden on maintainers.

## Common mistakes to avoid

**Do not document things that aren't Drupal-specific.** If a configuration step
is the same for any PHP application, it belongs in the tool's own documentation,
not on drupal.org.

**Do not document every possible option.** A page that exhaustively lists every
IDE ever invented, with full configuration instructions for each, is less useful
than one that says "use PhpStorm or VS Code; here's what you need for either."
Completeness is not the same as usefulness.

**Do not bury the key steps.** If there is a short path to success — a handful
of commands that get most people to a working result — put that first, ideally as
a TL;DR or quick-start block. Explanation can follow for readers who want to
understand what they are doing and why.

**Do not assume your page will live in isolation.** Link generously to related
pages, upstream guides, and API documentation so that readers can get the broader
context they need.

## A good example to follow

The drupal.org page for setting up a local Drupal development environment is a
strong model:

- Clear, specific audience (someone setting up their first local environment).
- TL;DR at the top: copy-paste commands for people who just want to get started.
- Enough explanation below to understand what those commands do and why.
- Opinionated recommendation (DDEV) with acknowledgment that other valid options
  exist and links to them, without embedding full documentation for each.

## Getting help

**For general documentation guidance** — style questions, the drupal.org IA,
access issues (e.g. you need a page unpublished or deleted) — post in the
`#documentation` Slack channel.

**For help documenting a specific feature or module** — go to the Slack channel
or drupal.org issue queue for that feature. The people there are the subject
matter experts. The documentation team can help you write well; they cannot
substitute for the people who built the thing.

## See also

- [Content Style Guide](https://www.drupal.org/drupalorg/style-guide)
- [README.md template for contrib modules](https://www.drupal.org/docs/develop/managing-a-drupalorg-theme-module-or-distribution/documenting-your-project/readme-template)
- [Guidance on documenting contrib modules](https://www.drupal.org/docs/develop/managing-a-drupalorg-theme-module-or-distribution)
- [Core gatekeeper policy](https://www.drupal.org/about/core/policies/core-change-policies/core-gates)
- Source material: eojthebrave (Founding member of the Documentation Working Group), https://www.drupal.org/docs/working-group/documentation-working-group-overview
