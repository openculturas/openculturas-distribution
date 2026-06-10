# Progressive disclosure and the 500-line guideline

## The number is 500

The Agent Skills specification targets **SKILL.md under 500 lines**. This is the
canonical limit — do not substitute a smaller, arbitrary number (a past
maintenance session set an eval `max_lines` of 320 purely because the author
didn't know the spec said 500; that is the mistake this skill exists to prevent).

The validator does **not** enforce line count — it only checks frontmatter. So
500 is a design discipline, not a CI gate. But keeping the always-loaded entry
point lean is the whole point of the pattern: everything in SKILL.md is loaded
into context the moment the skill triggers, whether or not the current task needs
it.

## The pattern

```
skills/<name>/
  SKILL.md            # lean entry point, < 500 lines
  references/
    <subtask-a>.md    # depth, loaded only when that subtask is at hand
    <subtask-b>.md
  README.md           # human-facing (not loaded by agents)
```

- **SKILL.md** holds what is true for *every* task in the domain — orientation,
  cross-cutting rules, the hard gotchas — plus a **routing table** that maps each
  subtask to its reference file.
- **`references/<subtask>.md`** holds the full depth for one subtask. The agent
  reads it on demand when the routing table points there.

The routing table is the hinge. Make it scannable: a `| task | read |` table so
the agent can jump straight to the one file it needs.

## When to split

Split a section out into `references/` when:

- SKILL.md is approaching 500 lines, **or**
- a section is only relevant to a subset of tasks (most tasks pay the context
  cost for nothing if it stays inline), **or**
- a section has grown into step-by-step depth (a long command sequence, an
  exhaustive table) that an agent needs only when actually doing that subtask.

Keep inline: orientation, the decision of *which* path to take, and gotchas
severe enough that an agent must see them before it picks a path.

Keep each reference focused too — the existing `drupal-gitlab` skill caps its
reference files around 200 lines. A reference that sprawls is a sign it should be
two references, or that some of it is really documentation that belongs on
Drupal.org.

## Don't duplicate canonical sources

Progressive disclosure is about splitting *your* procedural guidance, not about
copying external docs into `references/`. A reference file should still point to
the canonical Drupal.org page, API doc, or change record rather than reproduce
it.

## Canonical external specs

These two define the standard this skill enforces. Link them; don't paste them.

- **Agent Skills specification** — the format, frontmatter schema, and the
  500-line / progressive-disclosure guidance:
  <https://agentskills.io/specification>
- **Anthropic Agent Skills best practices** — authoring guidance, including
  writing descriptions that trigger well and structuring `references/`:
  <https://platform.claude.com/docs/en/agents-and-tools/agent-skills/best-practices>
