# CKEditor docs for AI agents

CKEditor publishes its documentation in machine-friendly formats
(<https://ckeditor.com/docs/ckeditor5/latest/getting-started/ai-coding-agents.html>).
Use them in this order instead of scraping HTML:

1. **Markdown per page — `latest` only** — swap `.html` for `.md` on a
   `ckeditor.com/docs/ckeditor5/latest/` URL, or send an
   `Accept: text/markdown` header. **This does not work on versioned docs**:
   under `/<major.minor.0>/` the `.md` URL 404s and the Accept header
   silently returns HTML. For version-pinned reading (see
   `version-matrix.md`), fetch the versioned **HTML** page; when you want the
   Markdown rendering, read the `/latest/` `.md` page for convenience and
   verify anything version-sensitive against the pinned HTML docs.

2. **Bulk context** — `https://ckeditor.com/docs/llms-full.txt` bundles all
   guides as plain text; `https://ckeditor.com/docs/llms.txt` is the index.
   Both track *latest* — cross-check anything version-sensitive against the
   pinned versioned docs before acting on it.

3. **MCP server** — `https://ckeditor5.mcp.kapa.ai` offers semantic search
   over the docs (one-time Google/GitHub SSO; works in Claude Code, Cursor,
   Copilot, and other MCP clients). Good for "which API does X" questions;
   still verify the answer applies to the bundled version.

4. **Official agent skills** — `npx skills add ckeditor/skills` installs
   CKEditor's own skills from <https://github.com/ckeditor/skills>.

## Feedback loop

When the docs are wrong or contradict observed behavior, open an issue on
<https://github.com/ckeditor/skills> describing the contradiction. If the
mistake led this skill astray, also log it in
`corrections/drupal-ckeditor5-plugins.jsonl` (see the
`drupal-expert-corrections` skill) so it can seed a future behavioral eval.

## Drupal caveat

CKEditor's documentation describes the standalone editor. For the Drupal-side
wiring — `<module>.ckeditor5.yml`, `CKEditor5Plugin` PHP classes, text formats
and filter settings — Drupal core documentation and this skill's other
references take precedence when the two disagree.
