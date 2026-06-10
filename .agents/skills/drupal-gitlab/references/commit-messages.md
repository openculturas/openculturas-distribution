# Commit message format

Drupal uses the **Conventional Commits** specification. Every commit must follow this format:

```
{type}: #{issue-id} Short summary of the change

Optional body — explain the why, not the what.
Wrap at ~72 characters.

By: drupal-username
By: other-contributor
```

**Types:** `feat` · `fix` · `docs` · `refactor` · `test` · `ci` · `perf` · `task` · `revert`

**Rules:**
- The issue ID is the last segment of the issue URL (e.g. `3586461` from `/-/work_items/3586461`). The numeric ID is identical whether sourced from drupal.org (e.g. `drupal.org/project/foo/issues/3586461`) or from GitLab — no conversion needed.
- `By:` lines use **Drupal.org usernames**, not GitLab names, email addresses, or `@username` syntax — ask the user if unsure.
- Use `By:` for all contributors (author, reviewer, reporter) — maintainers may also use `Co-authored-by:`, `Reviewed-by:`, or `Reported-by:` for specificity.

```
feat: #0000001 Add standardized commit message format

By: drupal-username
```
