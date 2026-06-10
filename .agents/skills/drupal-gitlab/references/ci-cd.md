# CI/CD

```bash
glab ci status                  # Pipeline status for current branch
glab ci view                    # Interactive pipeline view
glab ci trace <job-name>        # Stream full log of a job (best for debugging failures)
glab ci run                     # Trigger a new pipeline
```

`glab ci trace <job-name>` is the primary tool for debugging pipeline failures — it streams the full job log; the others are reference only.

## Gotchas

- **Pipeline triggers via the API are blocked** — `glab ci run` and `POST /projects/:id/pipeline` do not work on git.drupalcode.org (permission error or 301-redirect to `drupal.org/git-error`). **Pipelines fire on push events only.** To re-run CI, push a new commit (or an empty `--allow-empty` commit). This is the same infrastructure restriction that blocks API merges — see `references/merge-requests.md`.
- **Use `glab ci trace` over WebFetching the job URL** — the GitLab job page is JavaScript-rendered and returns no log to a fetcher. To read a failed job's log, get the job name/ID and stream it with `glab ci trace`, or fetch the trace via `glab api --hostname git.drupalcode.org "/projects/<id>/jobs/<job-id>/trace"`.
