# Proposing a change (the contributor path, end-to-end)

Read this only when you are actually **submitting a change** to a Drupal project
— a fix, a feature, a doc edit. Most interaction with a project is read-only
(browsing issues, reading code, checking CI); none of that needs this file. If
you are giving back, this is the happy-path sequence plus the two decision
points that trip people up. The operational detail for each step lives in the
linked reference file — this page is the map that puts them in order.

## Before you start: two questions

### 1. Is this project on GitLab yet?

Drupal.org is mid-migration. Most projects use GitLab work items; some still use
the legacy drupal.org issue queue, and `glab` cannot see the legacy queue.

- **Migrated** — issues live at `git.drupalcode.org/project/<repo>/-/work_items/<id>`. Use `glab` for everything (this skill).
- **Not migrated** — issues live at `www.drupal.org/project/<repo>/issues`. Create and manage issues on the drupal.org web UI (or the `drupalorg-cli` tool); `glab issue` commands will not find them.
- **How to tell:** follow the project's "Issues" link, or run `glab issue list --repo "git.drupalcode.org/project/<repo>"`. An empty result on an obviously active project usually means it is still on the legacy queue. Old `/project/<repo>/issues/<id>` URLs auto-redirect to the GitLab work item once a project is migrated.

### 2. Do you need an issue fork — or can you skip it?

- **Maintainer** (push access to `project/<repo>`): skip the fork. Push your branch straight to `origin` and open the MR from there.
- **Contributor** (no push access): you need an issue fork — see the decision tree below.

## The happy path (contributor, migrated project)

1. **Find or create the work item** — `glab issue view <id>` / `glab issue create`. → `references/issues.md`
2. **Get a usable issue fork** (decision tree below) — *the step most people miss; you cannot push without it.*
3. **Set up remotes & branch** — add the fork remote, then branch as `{issue-id}-{short-description}`. → `references/merge-requests.md`
4. **Commit** in Conventional Commits format with `By:` lines. → `references/commit-messages.md`
5. **Push to the fork & open the MR** via the REST API — not `glab mr create`, which cannot do cross-project (fork→upstream) MRs. → `references/merge-requests.md`
6. **Wire the MR to the issue** — put `Closes #<id>` in the description; GitLab links both sides and auto-closes the issue on merge.

## Decision tree: getting a usable issue fork

A fork is never created by pushing or via the API — always provision it first.

```
Does an issue fork exist yet?
├─ No  → create it:   /do:fork    (issue comment)   ·or·  "Create fork"    (Drupal.org mgmt page)
└─ Yes → do you already have push access to it?
         ├─ Yes → use it
         └─ No  → request access: /do:access  (issue comment)   ·or·  "Request access" (Drupal.org mgmt page)
                  then use it
```

- The management page link is auto-posted by DrupalBot in a comment on the work item: `https://new.drupal.org/drupalorg/issue-fork/management?source_link=...`.
- `/do:fork` creates a fork + branch from the default branch; `/do:access` grants the current user access to an existing fork. Both are Drupal.org custom commands → `references/issues.md`.
- **Check access before requesting it:** try `git fetch <fork-remote>` (or `git push`), or list members with `glab api --hostname git.drupalcode.org "/projects/issue%2F<project>-<issue-id>/members/all"`. A push that 403s means you need `/do:access`; a 404 on the fork URL means the fork does not exist yet (`/do:fork`).

## After the MR is open

- Mark the MR **Draft** while still working, **Ready** when it needs review — this maps to the Needs Work / Needs Review issue states. → `references/issues.md`
- CI fires on push events only; re-run it by pushing a commit. → `references/ci-cd.md`
- Merges go through the GitLab web UI — API/CLI merges are blocked on git.drupalcode.org. → `references/merge-requests.md`

## See also

- Old-workflow vocabulary (patch, RTBC, reroll, interdiff): `references/migration.md`
- Origin: #3588932 (skillify the contribution workflow) and #3588913 (happy-path sequence)
