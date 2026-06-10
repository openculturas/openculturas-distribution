# Adding a skill to `ai_best_practices`

Project-specific workflow for contributing a skill to this repo. For the upstream
"should this be a skill, and where should it live?" framework, see
`docs/skill-authoring.md`.

## Checklist

When you add a skill, every item below has to happen — the CI job and the
installer are independent and won't remind you:

- [ ] `skills/<name>/SKILL.md` — valid frontmatter (`references/frontmatter.md`),
      lean body (`references/progressive-disclosure.md`).
- [ ] `skills/<name>/references/*.md` — any progressive-disclosure files.
- [ ] `evals/<name>/static-checks.json` — at minimum a `file_exists` check and an
      `agent_skill_frontmatter` check.
- [ ] `evals/<name>/evals.json` — behavioral cases (thin to start is fine).
- [ ] `evals/<name>/README.md` — what the evals cover.

## Skills auto-discover — no template wiring

Skills ship by **existing under `skills/`**. Clients that support the Agent
Skills spec load them automatically from the installed `.agents/skills/`
directory; the `.agents/AGENTS.md.template` is only a compatibility fallback and
does **not** need a per-skill entry. (Older guidance said to "wire the skill into
the managed region" — that step is obsolete; don't add skill listings there.)

## Evals: the minimum that passes CI

The spec recommends **eval-driven development**: run the task *without* the skill
to find the real gap, write evals for that gap, then write the minimum skill that
passes them. The checklist above is order-agnostic about which file you create
first, but reaching for the evals early keeps the skill solving a real problem
rather than a guessed one.

`static-checks.json` is the CI-safe layer (no API calls). Every assertion needs
an `id`, a `description`, and a `check`; the rest of the fields depend on the
check type:

| `check` | Required fields | Passes when |
|---|---|---|
| `file_exists` | `path` (repo-relative) | the file at `path` exists |
| `agent_skill_frontmatter` | — | `skill_file`'s frontmatter is spec-valid (`name` matches the directory, `description` present and non-empty) |
| `frontmatter_fields` | `fields` (array of names) | every named key is present in `skill_file`'s frontmatter |
| `contains` | `text` | `text` is found in the target |
| `order` | `first`, `second` | `first` appears before `second` in the target |
| `max_lines` | `limit` (integer) | the target is `limit` lines or fewer |

The three content checks (`contains`, `order`, `max_lines`) read the top-level
`skill_file` by default; add a per-assertion `"file": "skills/<name>/references/<x>.md"`
to target a reference instead. `file_exists` is the exception — it takes `path`,
not `file`. A useful starting set:

```json
{
  "skill_file": "skills/<name>/SKILL.md",
  "assertions": [
    { "id": "S01", "description": "Skill file exists",
      "check": "file_exists", "path": "skills/<name>/SKILL.md" },
    { "id": "S02", "description": "Frontmatter has required fields",
      "check": "agent_skill_frontmatter" },
    { "id": "S03", "description": "SKILL.md entry point stays lean",
      "check": "max_lines", "limit": 500 },
    { "id": "S04", "description": "Convention landed in the right reference",
      "check": "contains", "file": "skills/<name>/references/<x>.md",
      "text": "<expected phrase>" }
  ]
}
```

Use `max_lines` with the spec's **500** (not an arbitrary smaller number), and
scope `contains` checks to a `references/<file>.md` with the `"file"` field when
asserting that content landed in the right reference.

Run locally — same checks as CI, no API calls:

```bash
python3 evals/run-evals.py --static                 # all skills
python3 evals/run-evals.py --static --skill <name>  # one skill
```

`evals.json` (behavioral) and `python3 evals/compare.py` need a live AI CLI; CI
runs only the static layer.

## Frontmatter validation

```bash
vendor/bin/agent-skills-validator skills/<name>
```

This is exactly what the `validate-agent-skills` CI job runs. See
`references/frontmatter.md` for the rules it enforces.

## Corrections → evals

When an expert corrects an AI mistake in a skill's domain, log it in
`corrections/<name>.jsonl` (one JSON object per line: `timestamp`, `subsystem`,
`classification` of `SKILL_GAP` | `SKILL_STALE` | `TOOL_MISUSE`, `claimed`,
`corrected`, `reasoning_failure`, `confidence`, optional `eval_seed`). An
`eval_seed` is the bridge that turns a real-world correction into a future
behavioral eval.

## Commit format

Conventional Commits, with the issue id:

```
skill: #<issue-id> Short summary
```

Types in use: `feat` `fix` `docs` `refactor` `test` `ci` `task` `skill`.
