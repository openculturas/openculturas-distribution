drupal-gitlab
=============

Skill for managing GitLab operations on `git.drupalcode.org` using the GitLab CLI (`glab`).
Covers branch and merge request workflows, issue/work item management, CI/CD monitoring, and
Drupal's issue-fork model.

Requirements
------------

*   A drupal.org account — access to `git.drupalcode.org` is linked to your drupal.org identity.
*   A personal access token created at
    `https://git.drupalcode.org/-/user_settings/personal_access_tokens`.
    See "Token scopes" below to choose the right scopes for your workflow.
*   `glab` (GitLab CLI) installed — see below.

Token scopes
------------

Choose your token tier based on what you need to do:

**Tier 1 — Read-only (default, always safe)**

Scopes: `read_api`, `read_user`, `read_repository`, `read_virtual_registry`

Covers all observing flows: listing/viewing issues and MRs, reading CI logs,
checking work item status, reviewing MR feedback. Start here. If you only need
to follow along with a project, you never need more than this.

**Tier 2 — Write (contributing workflow)**

Add `api` (for issue/MR/comment operations) or `write_repository` (for `git push`)
when your workflow requires it.

**Understand the risk before proceeding.** Unlike GitHub fine-grained PATs (which
can be scoped to a single repository), a GitLab PAT currently applies to your
*entire account*:

*   `write_repository` can push to every repository you have write access to —
    including release branches of Drupal core and contrib modules.
*   `api` can comment, open/close issues and MRs, and act on your behalf anywhere
    you have permission. Actions are indistinguishable from your own.
*   Accidental pushes to protected branches or wrong-target MRs are not always
    reversible and can affect downstream releases.

**Guardrails when using a write token with an agent:**

*   Confirm the target repo and branch before every push
*   Never push to a protected branch without explicit human approval
*   Treat any request to write outside an issue fork as a hard stop requiring confirmation
*   Prefer short-lived tokens; rotate after a session if possible
*   Never share or commit the token

**Tier 3 — Future: fine-grained project-scoped PATs**

GitLab fine-grained PATs (project-scoped write access, same model as GitHub) are
currently in beta: https://about.gitlab.com/blog/fine-grained-pats/

Once GA, this will be the recommended approach for write workflows — a token that
can only write to the specific project you're working on, eliminating the
account-wide blast radius. This skill will be updated when that lands.

Installing glab
---------------

*   macOS: `brew install glab`
*   Linux (Debian/Ubuntu): download a binary from the releases page or add the official apt
    repository — see https://docs.gitlab.com/cli/ for repository setup commands.
*   Linux (Fedora/RHEL): `dnf install glab`
*   Windows: `scoop install glab`

Full installation documentation: https://docs.gitlab.com/cli/

Configuring glab for git.drupalcode.org
----------------------------------------

Run the interactive login command and choose token-based authentication when prompted:

```bash
glab auth login --hostname git.drupalcode.org
```

Verify the configuration:

```bash
glab auth status --hostname git.drupalcode.org
```

Work Items and Legacy Issues
-----------------------------

Drupal.org is migrating all projects from its proprietary issue queue to GitLab work items
(`/-/work_items/<id>`), which is the current issue-tracking system for new and migrated projects.
Some projects still use the legacy Drupal.org issue queue and may not have corresponding GitLab
work items. The skill supports both cases and does not require a work item to exist.

Issue Fork Workflow
--------------------

Drupal's issue fork system requires a manual provisioning step before you can push code.
After a work item is created, GitLab posts a comment with a link to the Drupal.org issue
management page:

    https://new.drupal.org/drupalorg/issue-fork/management?source_link=https://git.drupalcode.org/project/<project>/-/work_items/<issue-id>

On that page, click **"Create issue fork"** to provision the fork repository at:

    https://git.drupalcode.org/issue/<project>-<issue-id>

You cannot create the fork by pushing or via the GitLab API — Drupal's CDN blocks those
routes (returns 301 to `drupal.org/git-error`). Always provision through the management
page first.

The same comment also contains a contribution attribution link. Fill it in so project
maintainers can grant you credit for the contribution.

Further Reading
----------------

*   GitLab quick actions (shorthand syntax for issues, MRs, and comments):
    https://docs.gitlab.com/user/project/quick_actions/
*   GitLab CLI (glab) full command reference: https://docs.gitlab.com/cli/
