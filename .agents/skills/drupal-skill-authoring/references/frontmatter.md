# Skill frontmatter rules

The `validate-agent-skills` CI job (`ronaldtebrake/agent-skills-validator`)
parses the YAML frontmatter of every `skills/**/SKILL.md` and fails the pipeline
on any violation below. The body of the file is never checked — only the
frontmatter.

## Allowed fields

Exactly these six keys are permitted. Anything else fails:

| Field | Required | Limit / rule |
|-------|----------|--------------|
| `name` | yes | ≤64 chars, lowercase, must match the directory name |
| `description` | yes | non-empty string, ≤1024 chars |
| `license` | no | string (e.g. an SPDX id) — distribution terms |
| `allowed-tools` | no | per the Agent Skills spec |
| `compatibility` | no | string, ≤500 chars — runtime requirements |
| `metadata` | no | structured object per the spec |

**Do not invent top-level custom keys.** `status:`, `drupal-version:`,
`last-reviewed:`, `owner:`, `tags:` as their own frontmatter keys are hard
failures — they are not in the allowed set above.

`metadata:` is the one escape hatch, and it is important to be precise about it:
the spec defines it as an arbitrary string-to-string map for "additional
properties not defined by the Agent Skills spec" (its own examples are `author`
and `version`), and the validator does **not** inspect its contents. So custom
fields nested under `metadata:` are **spec-legal and pass CI**. The constraint
here is not the spec or the validator — it is *this project's convention*:
lifecycle, ownership, freshness, and version metadata are handled by the owning
project, the issue/MR process, and git history, not by frontmatter (see
`docs/skill-authoring.md` and `AGENTS.md`). Treat `metadata:` as allowed-but-
discouraged for bookkeeping in this repo; don't claim it is forbidden.

## `name`

- Must be a non-empty string.
- Lowercase only.
- ≤64 characters.
- Letters, digits, and hyphens only (Unicode letters allowed).
- No leading or trailing hyphen; no consecutive hyphens (`--`).
- **Must exactly equal the directory name.** `skills/drupal-gitlab/SKILL.md`
  must declare `name: drupal-gitlab`. Renaming the directory means renaming
  `name:` to match, and vice versa.
- **No XML tags, and no reserved words `anthropic` or `claude`** (Agent Skills
  spec). The repo validator does **not** check this — a name like `claude-tools`
  passes CI but violates the spec, so avoid it anyway. The spec also suggests
  gerund-style names (`writing-documentation`); noun phrases (`drupal-gitlab`)
  are an accepted alternative and the norm in this repo.

## `description`

This is the single most important field: it is what an agent matches on to
decide whether to load the skill, and it is the most common CI failure (the body
can be any length, the description cannot exceed 1024 chars).

Write it to **trigger**, not to summarize. Name the situations that should load
the skill, in the vocabulary an agent would use, plus the key facts it covers.
Write in **third person** ("Processes X…", "Use when…") — the description is
injected into the system prompt, and first/second person ("I can help…", "You
can use…") degrades discovery. No XML tags (spec rule the validator misses).

Good (triggers + scope):

```yaml
description: Use when writing, modifying, or reviewing an Agent Skill — covers
  the allowed frontmatter fields, the 500-line guideline, and this repo's eval
  checklist. Load before creating or editing any skill.
```

Weak (vague, no trigger):

```yaml
description: Information about how skills work.
```

If a rich description approaches 1024 chars, that is a signal the SKILL.md is
trying to cover too much — split the skill, don't truncate the trigger.

## `compatibility`

Use only when the skill has a concrete runtime requirement: a required tool, a
local Drupal checkout, network access, or a specific agent/client environment.
Most skills should omit it. Do **not** use it as a substitute for Drupal-version
metadata — when versions matter, instruct the agent to inspect the local project
and canonical change records instead. Max 500 characters.

## Validate locally before pushing

```bash
vendor/bin/agent-skills-validator skills            # all skills
vendor/bin/agent-skills-validator skills/<name>     # one skill
```

CI only runs on merge-request events with changes under `skills/**`, so run it
yourself first.

## Canonical spec

The frontmatter schema is defined by the Agent Skills specification:
<https://agentskills.io/specification>. The validator implements a subset of it
(the fields above); when the two disagree, the validator is what gates CI.
