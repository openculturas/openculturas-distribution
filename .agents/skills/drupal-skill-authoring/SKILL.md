---
name: drupal-skill-authoring
description: Use when writing, modifying, or reviewing an Agent Skill (a SKILL.md file and its references/) — for this ai_best_practices repo or for Drupal skills generally. Covers the canonical Agent Skills spec: the allowed frontmatter fields (only name, description, license, allowed-tools, metadata, compatibility — custom keys like status or drupal-version fail CI), the name-must-match-directory rule, the 500-line SKILL.md guideline and progressive-disclosure pattern, the references/ convention, and this repo's contributor checklist (paired evals, static-checks.json, corrections). Load before creating or editing any skill so it follows the spec and passes the validate-agent-skills CI job.
---

# Authoring Drupal Agent Skills

A skill is **procedural** guidance loaded only when relevant: task-specific
decision ordering, checks, defaults, gotchas, and escalation cues. Docs are
**declarative**. Do not use a skill to restate Drupal.org docs, API docs, or
change records — include just enough to route the agent, then link the canonical
source.

This file is the lean entry point. Load the matching reference only when you
need it:

| Your task | Read |
|-----------|------|
| Write or fix frontmatter (the part that fails CI) | `references/frontmatter.md` |
| Decide what goes in SKILL.md vs split into `references/` — the 500-line guideline | `references/progressive-disclosure.md` |
| Add or change a skill **in this `ai_best_practices` repo** (evals, checklist, CI) | `references/contributing-here.md` |

For the upstream decision framework — *is this a skill at all? where should it
live? skill vs docs vs `AGENTS.md` vs MCP?* — read `docs/skill-authoring.md` in
this repo. That page is the canonical declarative reference; this skill is the
procedural companion that operationalizes it.

## The rules that actually fail CI

The `validate-agent-skills` job runs `agent-skills-validator` against every file
under `skills/`. It checks **frontmatter only** — not body length. The failures
it catches, in order of how often they bite:

- **Custom frontmatter keys.** Only six fields are allowed: `name`,
  `description`, `license`, `allowed-tools`, `metadata`, `compatibility`.
  Top-level keys like `status:`, `drupal-version:`, `last-reviewed:`, or `owner:`
  are a hard fail. (`metadata:` *can* legally nest arbitrary fields per the spec
  and passes CI — but this repo's convention keeps bookkeeping out of frontmatter
  anyway; see `references/frontmatter.md`.)
- **`name` ≠ directory name.** `name:` must exactly match the skill's directory
  (`skills/<name>/`), be lowercase, ≤64 chars, hyphen-separated, with no leading,
  trailing, or doubled hyphens.
- **`description` too long.** ≤1024 characters, and it must be a non-empty
  string. This is the most common "overly long skill" CI failure — the body can
  be any length; the description cannot.
- **`compatibility` too long.** If present, ≤500 characters. Most skills should
  omit it (see `references/frontmatter.md` for when it earns its place).

Full field-by-field rules, limits, and valid/invalid examples are in
`references/frontmatter.md`.

## Keep SKILL.md small

The Agent Skills spec targets **SKILL.md under 500 lines** and pushes detail
into `references/*.md` loaded on demand (progressive disclosure). 500 is the
canonical number — do not pick an arbitrary smaller limit. The validator does
**not** enforce it, but the spec and Anthropic's best-practices guide both do,
and it keeps the always-loaded context lean.

The pattern: SKILL.md holds what's true for *every* task in the domain plus a
routing table; each `references/<subtask>.md` holds the depth for one subtask.
When and how to split is in `references/progressive-disclosure.md`, which also
links the two canonical external specs.

## Write a good skill, not just a valid one

Passing CI means *valid*, not *good*. The canonical authoring guidance is
Anthropic's best-practices guide (linked in
`references/progressive-disclosure.md`). The highest-leverage heuristics for an
agent author:

- **Assume the reader is already smart.** Add only what an agent doesn't already
  know — repo-specific facts, gotchas, exact commands. Cut explanations of
  general knowledge; every loaded token competes with the task.
- **Write the description to trigger** (see `references/frontmatter.md`): third
  person, concrete keywords, both *what it does* and *when to use it*.
- **Match degrees of freedom to the task.** Fragile, must-be-exact steps → give
  the precise command; open-ended judgment → give direction and trust the agent.
- **Evals first.** Find the gap by running the task *without* the skill, write
  evals for that gap, then write the minimum skill that passes them — not the
  reverse.
- **References one level deep.** Each `references/*.md` links directly from
  SKILL.md; avoid reference chains (agents partial-read nested files).

## Reviewing someone else's skill

When reviewing a skill MR, check, in order:

1. **Does it earn being a skill?** Procedural, reusable, task-triggered — not a
   doc dump or a one-off. If unsure, `docs/skill-authoring.md` has the admission
   checklist.
2. **Frontmatter passes the validator** (the rules above). Run it locally:
   `vendor/bin/agent-skills-validator skills`.
3. **Description triggers correctly** — it should name the situations that
   should load the skill, in the words an agent would match on, not just
   summarize the contents.
4. **SKILL.md is lean**, depth lives in `references/`, and nothing duplicates
   canonical Drupal sources.
5. **Evals exist and pass** — `static-checks.json`, `evals.json`, `README.md`
   under `evals/<name>/`. See `references/contributing-here.md`.
