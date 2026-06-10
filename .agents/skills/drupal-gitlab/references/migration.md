# Drupal.org → GitLab migration

Drupal's contribution workflow moved from Drupal.org's proprietary issue queue
to GitLab on git.drupalcode.org. Contributors fluent in the old system use
vocabulary that maps directly to GitLab concepts. When you hear d.o terminology,
translate it — do not ask the contributor to reframe.

---

## Vocabulary translation

| Drupal.org term | GitLab equivalent | Notes |
|---|---|---|
| Patch | Merge request (MR) | No patch files — all work lives in MR branches |
| Interdiff | MR "Changes" tab, or `git diff <old-sha>..<new-sha>` | Shows what changed between MR revisions |
| Needs reroll | Branch needs rebase onto current target | `git rebase origin/1.0.x` or merge with target |
| RTBC | `state::rtbc` label + MR approved + CI green | Short for "Reviewed and Tested By the Community"; apply the label via `/do:label ~state::rtbc` comment or the GitLab label UI |
| Needs review | MR open, awaiting a reviewer | |
| Needs work | MR has requested changes | Reviewer left comments requesting changes |
| Fixed | MR merged / issue closed | |
| Postponed | Issue deferred, no active MR | Won't fix for now |
| Active | In progress, someone is working on it | |
| Follow-up issue | Child item or linked item | Use "Child items" widget on the work item |
| Issue credit / attribution | Contribution record URL | Posted automatically by GitLab in a comment when the work item is created — fill it in at `new.drupal.org/contribution-record?source_link=...` |
| Issue fork (d.o) | Issue fork on git.drupalcode.org | Provisioned via `/do:fork` or the Drupal.org UI, not via API |
| Commit (applying a patch) | Merge (web UI only) | API merges are blocked on git.drupalcode.org |
| Comment on patch | Inline MR review comment | Use the MR "Changes" tab to comment on specific lines |
| Version tag (e.g. 11.x) | Target branch | |
| `By:` line in commit | `By:` line in Conventional Commit message | Uses Drupal.org usernames, not GitLab names |

---

## Status workflow translation

Old d.o states map to GitLab labels and MR states. Workflow state is tracked
via **scoped labels** (e.g. `state::needsWork`), not a built-in status button.
All contributors can apply labels via `/do:label ~state::rtbc` comment commands.

```
Active           →  MR open, assigned, in progress
Needs Work       →  state::needsWork label; MR in Draft state
Needs Review     →  state::needsReview label; MR in Ready state
RTBC             →  state::rtbc label + MR approved + CI passing
Fixed            →  MR merged, issue auto-closed
Postponed        →  Issue open, MR closed or not created
Won't fix        →  Issue closed, no MR
```

The MR Draft/Ready toggle is the closest GitLab-native signal for Needs
Work / Needs Review — maintainers filter by this alongside label state.
Label names are project-configurable; check `glab label list` if the
above names are not present.

---

## Things that work differently

**No patch files.** There are no `.patch` file attachments in GitLab. All work
lives in a branch on an issue fork. When a contributor says "I posted a patch,"
they mean they pushed a branch and opened (or need to open) an MR.

**No interdiff files.** Interdiffs are generated on demand from the MR history.
To see what changed between two revisions of an MR, use the "Changes" tab and
select the revision range, or run `git diff <sha1>..<sha2>`.

**Rerolling = rebasing.** When a contributor says "the patch needs a reroll" or
"can you reroll this?" they mean the branch has conflicts with the current target
and needs to be rebased:
```bash
git fetch origin
git rebase origin/1.0.x
git push --force-with-lease <issue-fork-remote> <branch>
```

**Credit attribution is a separate step.** On d.o, issue credit was granted by
maintainers in the commit. On git.drupalcode.org, GitLab posts a comment on the
work item containing a contribution record link — the contributor must fill this
in themselves to receive credit.

**Merging requires the web UI.** Maintainers cannot merge via the API or CLI
(`glab mr merge` does not work on git.drupalcode.org). Always direct a
contributor asking "can you commit this?" to the GitLab web UI merge button.

---

## Common contributor phrases and what they mean

- **"I attached an interdiff"** → They pushed new commits to the MR branch; review the updated Changes tab
- **"This is RTBC"** → They believe the MR is ready to merge; verify CI is green and check for unresolved threads
- **"Can you commit this?"** → They want a maintainer to merge the MR via the web UI
- **"The patch needs a reroll"** → The MR branch has conflicts; rebase onto target
- **"I'll write a follow-up"** → They plan to open a new issue (child item) for remaining work
- **"Needs another review"** → A previous reviewer requested changes; they've addressed them and want a fresh look
- **"It's in the queue"** → There is an open work item but no MR yet

---

## See also

- Operational detail: the parent `SKILL.md`, plus `references/merge-requests.md` and `references/issues.md`
- Origin: issue #3588914 (migration vocabulary), folded into the `drupal-gitlab` skill via #3588940
