---
name: drupal-expert-corrections
description: Turn expert corrections about Drupal into durable improvements via a four-step Capture/Classify/Remediate/Verify pipeline, a six-way failure taxonomy, and an append-only JSONL log that can be promoted into eval cases.
---

# Capturing Expert Corrections

## When to use this skill

Use this skill when a knowledgeable user corrects a factual claim the agent
made about Drupal. The goal is not only to accept the correction, but to turn
it into a durable improvement that can change future sessions.

## The pipeline

### Capture

When a knowledgeable user corrects a factual claim, log a structured entry with:

- what was claimed, incorrectly
- what is correct, from the user correction
- which Drupal subsystem is affected
- a failure classification
- the reasoning chain that failed, not just what was wrong, but why the agent
  reasoned incorrectly

The log format is append-only JSONL so corrections accumulate and can be
analyzed for patterns.

### Classify

Each correction is tagged with why the agent got it wrong. Use the failure
taxonomy in this skill so the next action is tied to the kind of failure, not
just the topic.

### Remediate

Based on the classification, propose a fix in this priority order:

1. Patch the skill.
2. Add an eval case.
3. Propose a hook.
4. File an issue.

The highest priority fix is the one that prevents the same bad reasoning from
showing up again in later sessions.

### Verify

Run the eval framework to confirm the fix improves output quality. The loop is
not closed until the correction has been translated into a change and that
change is checked.

## Correction log format

Append one JSON object per line. Keep the log append-only.

```json
{
  "timestamp": "2026-04-06T14:23:00Z",
  "subsystem": "caching",
  "classification": "SKILL_GAP",
  "claimed": "Cache tags are invalidated synchronously",
  "corrected": "Invalidation is two-phase: checksum update is immediate, item eviction is lazy on next read",
  "reasoning_failure": "Assumed invalidation means deletion because most cache systems work that way",
  "confidence": 0.7,
  "project_id": "a1b2c3d4e5f6",
  "eval_seed": {
    "prompt": "Explain how Drupal cache tag invalidation works. Be specific about when stale items are removed.",
    "must_contain_any": ["lazy", "next read", "checksum"],
    "must_not_contain": ["synchronously deleted", "eagerly deletes"]
  }
}
```

- `timestamp`: when the correction was captured, in UTC ISO 8601 format.
- `subsystem`: the Drupal area affected, such as caching, entity, config, or
  routing.
- `classification`: one of the failure types in the taxonomy below.
- `claimed`: the incorrect statement the agent made.
- `corrected`: the user-supplied correction that should replace it.
- `reasoning_failure`: why the reasoning failed, not only the surface error.
- `confidence`: a 0.3 to 0.9 score. Repeated sightings can increase confidence.
- `project_id`: a portable project hash, typically derived from the git remote.
- `eval_seed`: optional data that can be promoted into an eval case.

## Failure taxonomy

| Classification | Failure source | Example |
| --- | --- | --- |
| `SKILL_GAP` | Guidance missing from skill | No skill covers cache tag invalidation model |
| `SKILL_STALE` | Skill has outdated info | Skill still recommends a superseded migration pattern after the core API changed |
| `EVAL_GAP` | No eval catches this error | Agent writes Drupal 7-style code and nothing flags it |
| `HOOK_CANDIDATE` | Deterministic rule not enforced | A PHPCS violation should be caught by a hook |
| `CONFABULATION` | Model invented plausible details | Agent uses `\Drupal::entityManager()` instead of `\Drupal::entityTypeManager()` |
| `ASSUMPTION_ERROR` | Correct facts, wrong inference | Agent knows the right service but uses `\Drupal::service()` inside a controller instead of constructor DI |

## Remediate priority

Use this order:

1. Patch the skill.
2. Add an eval case.
3. Propose a hook.
4. File an issue.

If the same subsystem or classification collects 3 or more corrections, treat it
as a cluster. Promote the fix from a one-off note to a dedicated skill section,
eval suite addition, or known-pitfalls block.

## Verify

Run the eval framework after remediation. If the correction has an `eval_seed`,
convert it into an `evals.json` case with `evals/promote_correction.py`, then
run the skill's static checks and behavioral evals. The correction is not done
until the updated guidance is tested.

## Escalation and promotion

At the Drupal subsystem level, 3 or more corrections affecting the same
subsystem or failure type should trigger a larger intervention, such as a
dedicated skill section or a new eval suite.

At the project level, repeated corrections can stay project-scoped first. If the
same pattern appears in 2 or more projects and the average confidence reaches
0.8 or higher, promote it for broader adoption instead of leaving it local to
one site or harness.

## Cross-tool compatibility

The correction log is plain JSONL, so it is agent-agnostic. Claude Code can
store corrections under a per-project corrections directory; the exact path is
defined by the runtime capture follow-up issue. Equivalent adapters for Codex
and Gemini are out of scope here, but the entry shape stays the same across
tools.

## Worked examples

### SKILL_GAP: cache tag invalidation

See the entry in "Correction log format" above for this case. Remediate:
- patch the caching guidance in the relevant skill
- add an eval case from `eval_seed`

### CONFABULATION: invented Drupal API

```json
{
  "timestamp": "2026-04-06T15:02:00Z",
  "subsystem": "entity",
  "classification": "CONFABULATION",
  "claimed": "Use \\Drupal::entityManager() to get entity storage",
  "corrected": "Use \\Drupal::entityTypeManager() to get entity storage",
  "reasoning_failure": "Invented a plausible legacy-style API name instead of checking the current Drupal service entry point",
  "confidence": 0.8,
  "project_id": "a1b2c3d4e5f6",
  "eval_seed": {
    "prompt": "Show the correct Drupal service entry point for loading entity storage in Drupal 10 or 11.",
    "must_contain_any": ["entityTypeManager", "entity storage"],
    "must_not_contain": ["entityManager()"]
  }
}
```

Remediate:
- patch the skill if the wrong API appears in guidance
- add an eval case so invented APIs are caught next time

### ASSUMPTION_ERROR: service location is right, usage pattern is wrong

```json
{
  "timestamp": "2026-04-06T16:11:00Z",
  "subsystem": "services",
  "classification": "ASSUMPTION_ERROR",
  "claimed": "Inside the controller, call \\Drupal::service('my_module.helper')",
  "corrected": "Inject the service through the controller constructor and create() method",
  "reasoning_failure": "Knew the correct service but chose service location over the Drupal controller dependency injection pattern",
  "confidence": 0.7,
  "project_id": "a1b2c3d4e5f6"
}
```

Remediate:
- patch the skill to prefer constructor DI in controller examples
- add an eval if the pattern keeps recurring

## Prior art

This skill follows alex_ua's design for the correction pipeline. It borrows
runtime architecture ideas from `continuous-learning-v2` in the
`everything-claude-code` plugin, especially hook-based capture, confidence
scoring, project hashing, and the observer-agent pattern for detecting repeat
corrections. Homunculus is relevant only as prior art behind the atomic
observation and confidence-weighted approach.

## Runtime capture (out of scope in this skill)

This skill defines the behavior and data shape. Actual PreToolUse and
PostToolUse hook wiring, background observation, confidence updates, and
cross-harness adapters are separate follow-up work. Use the
`continuous-learning-v2` implementation as the reference architecture when that
runtime issue is opened.
