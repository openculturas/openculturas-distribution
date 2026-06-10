---
name: drupal-gitlab
description: Use GitLab CLI (glab) and API on git.drupalcode.org for any Drupal contribution task — issues, work items, merge requests, branches, CI, and Drupal's issue-fork workflow. Covers authentication, token scopes, Conventional Commits, and translating Drupal.org issue-queue terms (patch, RTBC, interdiff, reroll). This SKILL.md holds setup, auth, and cross-cutting rules; load the matching file in references/ for each subtask.
---

# Drupal GitLab (git.drupalcode.org)

Drupal hosts its source code on a self-managed GitLab instance at `git.drupalcode.org`. Its three key conventions are: an **issue-fork workflow** for branches and MRs, the **work items API** for issue tracking, and **Drupal Conventional Commits** for commit messages.

This file covers what's true for *every* task here — setup, authentication, the issue-fork model, and cross-cutting gotchas. For a specific subtask, read the matching reference file (loaded only when needed):

| Your task | Read |
|-----------|------|
| **Propose a change** to a project — the contributor path, fork → branch → MR (load only when actually submitting a change; most tasks are read-only) | `references/contribution.md` |
| Create/manage issues & work items, labels, status, `/do:` commands | `references/issues.md` |
| Provision a fork, push a branch, open a merge request | `references/merge-requests.md` |
| Inspect pipelines or debug CI failures | `references/ci-cd.md` |
| Write a commit message | `references/commit-messages.md` |
| Translate Drupal.org issue-queue terms (patch, RTBC, reroll…) | `references/migration.md` |

## Prerequisites

Before running any `glab` command, confirm the CLI is installed and authenticated:

```bash
glab --version                                  # installed?
glab auth status --hostname git.drupalcode.org  # authenticated?
```

If either fails, stop and tell the user that `glab` must be installed and authenticated for `git.drupalcode.org` before this skill can be used. Direct them to `skills/drupal-gitlab/README.md` for setup.

## Authentication & host setup

`glab` resolves the correct token for `git.drupalcode.org` automatically — no `GITLAB_HOST` env var or manual token extraction needed.

- **`glab` subcommands** (`issue`, `mr`, `ci`, etc.): the hostname in `--repo` is sufficient — `glab issue list --repo "git.drupalcode.org/project/<repo>"`
- **`glab api`**: `--hostname` is sufficient — `glab api --hostname git.drupalcode.org /version`

**Two hostnames, different roles — `git.drupalcode.org` is a Fastly CDN front; `git.drupal.org` is the GitLab origin.** They are not interchangeable, and each fails *silently* when used for the other's job:

- **Everything over HTTP → `git.drupalcode.org`:** the web UI, `glab` subcommands, and `glab api` for both reads **and writes**. A `glab api` write sent to `git.drupal.org` (via `--hostname git.drupal.org` or `GITLAB_HOST`) is redirected and **downgraded to a GET** — you get `HTTP 200` with a *collection list* instead of `201 Created`, and nothing is created. No error is raised; `glab mr note` surfaces it only as `Json: cannot unmarshal array into Go value of type gitlab.Note`. Confirm writes by checking for `201` (use `-i`), not a bare `200`.
- **SSH `git push` → `git.drupal.org`:** the CDN front has no SSH listener, so a `git@git.drupalcode.org:…` remote hangs and times out on port 22. Point SSH remotes at `git@git.drupal.org:…` instead. (HTTPS git works fine on `git.drupalcode.org`.)

**Always pass `--repo` (subcommands) or `--hostname` (`glab api`) — never rely on the current directory.** A bare `glab mr view 50` or `glab issue view 50` resolves against your *default* host (often gitlab.com) or fails with `404 Not Found`; it has no way to know which Drupal project you mean. Read the `<repo>` straight off any drupalcode.org URL — it is the path segment right after `project/`:

| You have this URL | `<repo>` is | Command |
|-------------------|-------------|---------|
| `…/project/token/-/merge_requests/12` | `token` | `glab mr view 12 --repo "git.drupalcode.org/project/token"` |
| `…/project/ai_best_practices/-/work_items/3588940` | `ai_best_practices` | `glab issue view 3588940 --repo "git.drupalcode.org/project/ai_best_practices"` |

**Token tiers — choose based on what you need to do** (see `README.md` → "Token scopes" for full rationale):

- **Tier 1 — Read-only (default, always safe):** scopes `read_api`, `read_user`, `read_repository`, `read_virtual_registry`. Covers all observing flows: listing/viewing issues, MRs, CI logs, work item status. Start here.
- **Tier 2 — Write (contributing workflow):** add `api` or `write_repository` when you need to push branches, create MRs, or comment. **A GitLab PAT is not scoped to a single project** — write scopes reach every repo you can write to, including release branches of Drupal core and contrib. Before any write operation: confirm the target repo and branch with the user, never push to a protected branch without explicit human approval, and treat any request to write outside an issue fork as a hard stop requiring confirmation.
- **Tier 3 — Future:** GitLab fine-grained project-scoped PATs (beta in 18.10) are **not yet enabled on git.drupalcode.org**, which offers only legacy broad-scope tokens (`api`, `read_api`, etc.) that reach every repo you can access. Once available, prefer a project-scoped write token — same safety model as GitHub. Track [infra issue 3379836](https://www.drupal.org/project/infrastructure/issues/3379836).

SSH is strongly preferred for git push. If SSH is unavailable, use the credential helper pattern in `references/merge-requests.md` — never embed the token directly in a remote URL or shell command.

### `glab api` quick reference

`glab api` replaces `curl` for nearly all REST calls — no token management needed:

| Need | Command pattern |
|------|----------------|
| GET an endpoint | `glab api --hostname git.drupalcode.org /path` |
| POST/PUT with fields | `glab api --hostname git.drupalcode.org -f key=value -F int_or_bool_key=value /path` |
| DELETE | `glab api --hostname git.drupalcode.org --method DELETE /path` |
| Multipart file upload | `glab api --hostname git.drupalcode.org --form "file=@./path/to/file" /path` |
| Multi-line field (MR/issue description, note body) | pass inline as a single-quoted string — `-f description='…multi-line markdown…'` — so it stays visible in the command (backticks are safe inside single quotes). For a body containing apostrophes, fall back to a file: `-f description=@./body.md` |

**`-f` vs `-F`:** use `-f` / `--raw-field` for strings; use `-F` / `--field` for integers, booleans (`true`/`false`), and repo placeholders (`:repo`, `:branch`). Adding any `-f`/`-F` flag automatically makes the request a POST — no `--method POST` needed.

**`--input`:** sends a raw file as the request body. Requires `-H "Content-Type: application/json"` explicitly — glab does not set it automatically. Prefer `-f`/`-F` for building payloads from scratch; reserve `--input` for pre-built JSON from another tool. `--hostname` is always safe to pass, and may be unnecessary when you are already inside a git directory configured for `git.drupalcode.org`.

## The issue-fork model

Drupal does not use personal forks. Each issue gets a **dedicated fork** at `git.drupalcode.org/issue/<project>-<issue-id>`, provisioned through the Drupal.org UI or a `/do:fork` comment — **never** by pushing or via the API. You push your branch to that fork, then open a merge request **from the fork to the upstream project**. Full step-by-step (provisioning, remotes, push, MR creation) lives in `references/merge-requests.md`.

## Cross-cutting gotchas

These apply across every task on git.drupalcode.org. Task-specific gotchas live in the matching reference file.

- **Use `glab api` for all REST calls — there is no remaining use case for `curl`.** `glab api` handles authentication automatically, supports `--form` for multipart file uploads, and `--input` for JSON bodies. `curl` requires manual token extraction and is never needed.
- **Never WebFetch a GitLab URL — extract the IID and use `glab` instead.** GitLab pages are JavaScript-rendered and return no useful content to a fetcher. URL patterns and their `glab` equivalents:

  | URL pattern | How to get the IID | `glab` command |
  |-------------|-------------------|----------------|
  | `/-/merge_requests/43` | IID is in the path | `glab mr view 43 --repo "git.drupalcode.org/project/<repo>"` |
  | `/-/work_items/3588930` | IID is in the path | `glab issue view 3588930 --repo "git.drupalcode.org/project/<repo>"` |
- **SSH may be unavailable** in terminal/agent contexts — fall back to HTTPS using the credential helper pattern (see `references/merge-requests.md`). Never embed the token in the URL or command string; it will end up in shell history.
- **Don't pull specific people in unprompted.** Do not `@`-mention, assign, or add as reviewer any contributor in issues, MRs, comments, or commit messages unless the human directed you to. Pinging someone carries social weight (a notification, an implied ask on their time); deciding *whom* to involve is the maintainer's judgment call, not yours. State the substance instead — flag the open question or request review in general terms — and leave the tagging to the human. (`@me` for self-assignment is fine.)
- **Write commands an agent harness can auto-approve.** Harnesses that gate shell commands must prompt for anything they can't statically analyze, which slows every step. Keep each call simple: one program per call (no `&&`/`;` chains), no `cd <path>` prefix (the working directory already persists), and don't pipe API output into `python3 -c` or `jq` just to format it — read results with the native viewers (`glab mr view`, `glab issue view`, `glab ci status`, `glab repo view`) instead. For a multi-line description, pass it inline as a single-quoted string (markdown backticks are safe inside single quotes) so it stays visible in the command — not via a heredoc, `$(cat …)`, or a temp file; `-f field=@file` from an in-workspace file is a fine fallback for bodies with apostrophes.

## Quick reference: common shorthand

These work in issue/MR descriptions, comments, and CLI `--assignee` / `--reviewer` flags:

| Shorthand | Meaning |
|-----------|---------|
| `@me` | Your own Drupal.org GitLab username — use for self-assignment or self-review |
| `#<id>` | Reference to a work item by iid |
| `!<id>` | Reference to a merge request by iid |

Also: add `--web` (open result in browser), `-o json` (JSON output for scripting), or `--yes` (skip confirmation prompts) to most commands. The project namespace is always `project/<repo>` for the upstream and `issue/<repo>-<id>` for the issue fork.
