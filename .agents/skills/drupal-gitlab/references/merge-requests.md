# Issue forks & merge requests

## Issue-fork workflow

Drupal does not use personal forks. Instead, each issue gets a **dedicated fork** provisioned through Drupal.org. The fork lives at:

```
https://git.drupalcode.org/issue/<project>-<issue-id>
```

### Step 1 — Provision the issue fork (required before pushing)

After a work item is created, GitLab automatically posts a comment containing two links:

- **Attribute your contribution:**
  `https://new.drupal.org/contribution-record?source_link=https://git.drupalcode.org/project/<project>/-/work_items/<issue-id>`
- **Manage forks, branches, and MRs:**
  `https://new.drupal.org/drupalorg/issue-fork/management?source_link=https://git.drupalcode.org/project/<project>/-/work_items/<issue-id>`

Provision the fork one of two ways:

- **Option A — comment command (simpler):** post a comment on the work item containing `/do:fork`. The Drupal.org integration provisions the fork and a branch automatically. To grant access to an existing fork, post `/do:access`.
- **Option B — web UI:** go to the **management URL** and click **"Create issue fork"**.

Either way provisions the fork at `https://git.drupalcode.org/issue/<project>-<issue-id>`.

**The fork cannot be created by pushing or via the API** — use `/do:fork` or the management URL first.

### Step 2 — Set up remotes and push

Your local repo will have two remotes after the fork is provisioned. **Mind the host:** `git.drupalcode.org` is a Fastly CDN that serves HTTP(S) only — for **SSH** remotes use the origin host `git.drupal.org`, or the push hangs and times out on port 22 (see SKILL.md → "Two hostnames, different roles"). HTTPS remotes use `git.drupalcode.org`.

```
# SSH remotes (preferred) — origin host
origin                  git@git.drupal.org:project/<project>.git          ← upstream
<project>-<issue-id>    git@git.drupal.org:issue/<project>-<issue-id>.git ← issue fork

# HTTPS remotes — CDN host
origin                  https://git.drupalcode.org/project/<project>.git
<project>-<issue-id>    https://git.drupalcode.org/issue/<project>-<issue-id>.git
```

**Branch naming:**
```
{issue-id}-{short-description}   # e.g. {issue-id}-add-issue-templates
```

**Push to the issue fork** (not to origin):
```bash
# SSH — note the git.drupal.org host, NOT git.drupalcode.org (the CDN has no SSH listener)
git remote add <project>-<issue-id> git@git.drupal.org:issue/<project>-<issue-id>.git
git push <project>-<issue-id> {branch-name}

# HTTPS (if SSH is unavailable) — never embed the token in the URL
git -c credential.helper='!f() { echo username={username}; echo password=$(glab config get token --host git.drupalcode.org); }; f' \
  push https://git.drupalcode.org/issue/<project>-<issue-id>.git {branch-name}
```

## Merge requests (cross-project)

MRs go **from the issue fork to the upstream project**. `glab mr create` cannot create cross-project MRs — use the REST API directly.

> **Path encoding (first GitLab-API gotcha):** the REST API identifies a project by its namespaced path *URL-encoded* — the `/` becomes `%2F`. So `project/<repo>` is written `project%2F<repo>`, and `issue/<project>-<issue-id>` becomes `issue%2F<project>-<issue-id>` in the API paths below. Keep this in mind whenever you adapt these commands to another project.

**Check for an existing MR before creating one:**
```bash
glab mr list --repo "git.drupalcode.org/project/<repo>"

# View a specific MR
glab mr view <mr-iid> --repo "git.drupalcode.org/project/<repo>"
```

> **Keep each step a single command** — no `cd`, no `| python3`, no `$(...)` plumbing — so an agent harness can auto-approve it (see SKILL.md → Cross-cutting gotchas). Two things keep it clean: the **issue fork needs no ID lookup** (it is identified by its path in the URL), and the **description is passed inline as a single-quoted string** so it stays visible in the command. Markdown backticks are safe inside single quotes; just avoid apostrophes in the body (or fall back to `-f description=@file` with an in-workspace file for bodies that need them).

**Step 1 — draft the MR body.** Read the template and fill it in, then append the required AI disclosure:
```bash
cat .gitlab/merge_request_templates/Default.md
```
```
AI-Generated: Yes (Used [tool] to [brief description of how AI was used].)
```
Drupal's [AI contribution policy](https://www.drupal.org/docs/develop/issues/issue-procedures-and-etiquette/policy-on-the-use-of-ai-when-contributing-to-drupal) requires this for any significant AI-assisted contribution. All MRs created via this skill qualify.

**Step 2 — find the upstream project's numeric ID** (the only ID you need, for `target_project_id`):
```bash
glab api --hostname git.drupalcode.org "/projects/project%2F<repo>"
```
Read the `id` field from the response.

**Step 3 — create the MR** from the fork (its path goes in the URL) to upstream, body inline:
```bash
glab api --hostname git.drupalcode.org \
  -F target_project_id=<upstream-id> \
  -f source_branch="{branch-name}" \
  -f target_branch="main" \
  -f title="feat: #{issue-id} Short summary" \
  -f description='## Summary

What this MR does, in a single-quoted multi-line string so it stays visible in
the command. Markdown `code` is fine inside single quotes.

Closes #{issue-id}' \
  -F remove_source_branch=true \
  "/projects/issue%2F<project>-<issue-id>/merge_requests"
```
The response is the created MR — read its `web_url`, or confirm with `glab mr view <iid> --repo "git.drupalcode.org/project/<repo>"`.

MR conventions:
- **Title**: `{type}: #{issue-id} Short summary` — Conventional Commits format. **GitLab squash-merges use the MR *title* as the commit message**, so the title itself must follow the standard, not just your commits. (The Step 3 command already sets `remove_source_branch: true`.) See `references/commit-messages.md`.
- **Target branch**: confirm with the user if not `main`.
- **Link the issue**: put `Closes #<id>` in the description so GitLab wires the issue ↔ MR automatically.

## Gotchas

- **The issue fork must be provisioned via `/do:fork` or the Drupal.org UI before you can push** — pushing to a non-existent fork URL returns a 404; the fork API via `glab`/`curl` also does not work (Drupal's CDN returns 301 to `drupal.org/git-error`).
- **The auto-posted comment also contains a contribution attribution link** — `https://new.drupal.org/contribution-record?source_link=...` — remind the user to fill this in so maintainers can grant credit.
- **MR merges and pipeline triggers via the API are blocked** — even a Maintainer with a full `api`-scoped PAT cannot merge via the REST API (`PUT /projects/:id/merge_requests/:iid/merge`) or trigger pipelines via `POST /projects/:id/pipeline` (or `glab ci run`). Both return permission errors or 301-redirect to `drupal.org/git-error`. This is an intentional infrastructure-level restriction on git.drupalcode.org, not a misconfiguration. All merges must go through the GitLab web UI; pipelines fire on push events only. See [infra issue 3379836](https://www.drupal.org/project/infrastructure/issues/3379836) for background.
- **`glab mr create` can pick the wrong `source_project_id`** — if multiple `issue-*` remotes exist locally, `glab` may use one of them as the MR source even when the branch lives on upstream. The MR is created but reports `sha: None` and `has_conflicts: true` immediately, and no pipeline can fire. Workaround: for same-project MRs, create via `glab api` with explicit `source_project_id`, or remove unrelated `issue-*` remotes from the local repo first.
- **`detailed_merge_status: mergeable` from the API does not mean the UI merge button is available** — Drupal.org projects require fast-forward merges. The API reports `mergeable` when there are no conflicts, blocking discussions, or CI failures — but does not account for whether the branch needs a rebase to fast-forward onto the target. If the branch is behind `1.0.x`, a rebase is required first.
- **Merging one MR makes every *other* open MR targeting the same branch stale.** Because merges must fast-forward, the moment one MR lands it advances the target branch, so each remaining sibling MR is now behind and its merge button greys out. Merge sibling MRs **back-to-back**, and after each merge rebase the rest: `git fetch origin <target>`, `git rebase origin/<target>`, resolve any conflicts, then `git push --force-with-lease`. (Expect conflicts when two siblings touched the same file — resolve by keeping *both* changes.)
- **Default to the issue fork even if you (or the user) are a maintainer.** Drupal's collaboration culture is that *anyone* can contribute to an issue through its fork, so work belongs on the issue fork and the MR is cross-project — even when you have push access to `project/<repo>`. Pushing your branch directly to the main project would lock non-maintainers out of collaborating on it, which is especially undesirable on community-facing projects (you would otherwise have to grant push access broadly). A maintainer *can* technically push to `origin`, but do not default to it; use the issue fork unless the user explicitly asks otherwise.
