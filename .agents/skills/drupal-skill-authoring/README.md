# drupal-skill-authoring

Agent context for **writing, modifying, and reviewing Agent Skills** — in this
`ai_best_practices` repo and for Drupal skills generally.

It exists because the authoring conventions were previously discoverable only by
a human pasting them in: the canonical 500-line guideline and progressive
disclosure (from [agentskills.io/specification](https://agentskills.io/specification)
and [Anthropic's best-practices guide](https://platform.claude.com/docs/en/agents-and-tools/agent-skills/best-practices)),
the frontmatter rules the `validate-agent-skills` CI job enforces, and this
repo's eval checklist. Without it, agents rediscover (or guess) these every
session — e.g. inventing an arbitrary line limit because they didn't know the
spec says 500, or adding custom frontmatter keys that fail CI.

## Layout (progressive disclosure)

- `SKILL.md` — lean entry point: the CI-failing frontmatter rules, the 500-line
  guideline, the review checklist, and a routing table.
- `references/frontmatter.md` — field-by-field frontmatter rules (what fails CI).
- `references/progressive-disclosure.md` — the 500-line guideline, when/how to
  split into `references/`, and the canonical external specs.
- `references/contributing-here.md` — the `ai_best_practices` new-skill
  checklist, evals, and corrections format.

## Relationship to `docs/skill-authoring.md`

`docs/skill-authoring.md` is the canonical **declarative** reference for the
upstream decision framework (is this a skill? where does it live? skill vs docs
vs `AGENTS.md` vs MCP?). This skill is its **procedural** companion: it
operationalizes authoring for agents and links back to the doc for the framework.

## Scope

This skill covers the **universal** authoring rules plus **this repo's**
conventions. Context-specific guidance for other settings — Drupal core's
upstream review bar, contrib/module-owned skills, private client skills — is
intentionally out of scope and can be layered as separate skills owned by those
projects if the need materializes (see issue #3588925's scope discussion).
