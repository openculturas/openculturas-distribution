# Issues / work items

Drupal.org is transitioning all projects from its proprietary issue queue to GitLab work items. **Work items is the current, preferred method** and all new projects use it. However, some projects still rely on the legacy Drupal.org issue queue and may not have a GitLab work item for every branch or MR. Do not require a work item to exist — if none is found, continue without one and inform the user.

Issue URLs use `/-/work_items/<id>` (not `/-/issues/<id>`). Use `glab issue create` as normal — it will create a work item automatically.

**Look up issue templates:**
```bash
ls .gitlab/issue_templates/
cat ".gitlab/issue_templates/<TemplateName>.md"
```

**Create an issue** (`--label` is optional; check available project labels first with `glab label list`):
```bash
glab issue create \
  --title "Short descriptive title of the issue" \
  --description "$(cat /tmp/issue_body.md)" \
  --label "<label1>,<label2>" \
  --assignee "{username}" \
  --repo "git.drupalcode.org/project/<repo>"
```

**Common issue operations:**
```bash
glab issue list    --repo "git.drupalcode.org/project/<repo>"
glab issue view <id> --repo "git.drupalcode.org/project/<repo>"
glab issue comment <id> -m "Message" --repo "git.drupalcode.org/project/<repo>"
```

## State labels

Issue workflow state (Needs Work, Needs Review, RTBC, etc.) is tracked via **scoped labels**, not the work item status widget. Projects typically use:

| Label | Meaning |
|---|---|
| `state::needsWork` | Reviewer requested changes |
| `state::needsReview` | Ready for a reviewer; MR is in "Ready" state |
| `state::rtbc` | Reviewed and Tested By the Community — ready to merge |

**Draft vs. Ready on the MR maps to issue state:**
- MR in Draft → Needs Work (contributor is still making changes)
- MR marked Ready → Needs Review (waiting for a reviewer)
- MR approved + CI green + `state::rtbc` label → RTBC

Label names are project-configurable. If a project uses different names, check `glab label list --repo "git.drupalcode.org/project/<repo>"` first.

## Drupal.org comment commands (`/do:`)

Drupal.org's GitLab integration processes `/do:` commands posted as comments on work items. These are **not** standard GitLab quick actions — they are custom and only work on git.drupalcode.org. All authenticated contributors can use them.

| Command | What it does |
|---|---|
| `/do:fork` | Provisions the issue fork + branch from the default branch |
| `/do:access` | Grants current user access to an existing fork |
| `/do:label ~label1 ~label2` | Adds labels (e.g. `~state::rtbc`) |
| `/do:unlabel ~label1` | Removes a label |
| `/do:relabel ~label1 ~label2` | Replaces all labels |
| `/do:assign @username` | Adds an assignee |
| `/do:unassign @username` | Removes an assignee |
| `/do:reassign @username` | Replaces all assignees |

Full reference: `https://new.drupal.org/drupalorg/gitlab-custom-commands`

## Work Item Status (GraphQL)

Issue status ("To do" / "In progress" / "Done") is a work item widget, distinct from the state labels above. Read it via GraphQL:

```bash
# Read current status
glab api graphql --hostname git.drupalcode.org -f query='
{
  project(fullPath: "<namespace>/<repo>") {
    workItems(iid: "<issue-iid>") {
      nodes {
        id
        widgets {
          ... on WorkItemWidgetStatus {
            type
            status { id name iconName }
          }
        }
      }
    }
  }
}'
```

System-defined status GIDs follow the pattern `gid://gitlab/WorkItems::Statuses::SystemDefined::Status/<n>` — query the current status first to confirm available IDs before attempting an update via the `workItemUpdate` mutation. If your permissions are insufficient, inform the user and let them set status manually in the UI.

## Gotchas

- **`glab issue comment` requires `-m`, not `--body`** — `--body` is not supported; always use `-m "Message"` for inline text or `--description "$(cat file)"` for multiline content.
- **Not all projects have GitLab work items** — some still use the legacy Drupal.org issue queue. If `glab issue list` returns nothing, inform the user and continue without a work item.
