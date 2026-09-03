# Issue tracker: GitHub

Issues and specs for this repo live as GitHub issues.

**Use `./scripts/bot-gh-refactor.sh` instead of plain `gh` for every operation below.** It's a drop-in wrapper — same subcommands and flags as `gh` — but it authenticates as this repo's dedicated GitHub App bot (mints a short-lived installation token, scoped only to this repo) instead of a human's personal `gh` login. It fails loudly if the bot token can't be minted, rather than silently falling back to a personal account. Run it from the repo root (paths below assume that).

## Conventions

Labels are native on GitHub — no local mirror. The continuous-refactoring suite's `refactor:candidate` label, and the triage roles from `docs/agents/triage-labels.md`, apply directly — no `refactor:delivered` or other in-flight label; a candidate's linked pull request, native to the tracker, is what's in flight.

- **Create an issue**: `./scripts/bot-gh-refactor.sh issue create --title "..." --body "..."`. Use a heredoc for multi-line bodies.
- **Read an issue**: `./scripts/bot-gh-refactor.sh issue view <number> --comments`, filtering comments by `jq` and also fetching labels.
- **List issues**: `./scripts/bot-gh-refactor.sh issue list --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'` with appropriate `--label` and `--state` filters.
- **Comment on an issue**: `./scripts/bot-gh-refactor.sh issue comment <number> --body "..."`
- **Apply / remove labels**: `./scripts/bot-gh-refactor.sh issue edit <number> --add-label "..."` / `--remove-label "..."`
- **Close**: `./scripts/bot-gh-refactor.sh issue close <number> --comment "..."`

Infer the repo from `git remote -v`; `gh` does this automatically when run inside a clone. This repo's remote: `git@github.com:Art4/legacy-todo.git`.

Note: pushing commits/branches still goes through plain `git push` over the existing SSH `origin` remote (the human's identity) — the GitHub App bot only covers `gh`/API operations (issues, PR metadata, comments), not git push authentication. See the project chat history / `scripts/setup-github-app-refactor-bot.sh` for background.

## Pull requests as a triage surface

**PRs as a request surface: no.** _(Set to `yes` if this repo treats external PRs as feature requests; `/triage` reads this flag.)_

When set to `yes`, PRs run through the same labels and states as issues, using the `./scripts/bot-gh-refactor.sh pr` equivalents:

- **Read a PR**: `./scripts/bot-gh-refactor.sh pr view <number> --comments` and `./scripts/bot-gh-refactor.sh pr diff <number>` for the diff.
- **List external PRs for triage**: `./scripts/bot-gh-refactor.sh pr list --state open --json number,title,body,labels,author,authorAssociation,comments` then keep only `authorAssociation` of `CONTRIBUTOR`, `FIRST_TIME_CONTRIBUTOR`, or `NONE` (drop `OWNER`/`MEMBER`/`COLLABORATOR`).
- **Comment / label / close**: `./scripts/bot-gh-refactor.sh pr comment`, `./scripts/bot-gh-refactor.sh pr edit --add-label`/`--remove-label`, `./scripts/bot-gh-refactor.sh pr close`.

GitHub shares one number space across issues and PRs, so a bare `#42` may be either: resolve with `./scripts/bot-gh-refactor.sh pr view 42` and fall back to `./scripts/bot-gh-refactor.sh issue view 42`.

## When a skill says "publish to the issue tracker"

Create a GitHub issue.

## When a skill says "fetch the relevant ticket"

Run `./scripts/bot-gh-refactor.sh issue view <number> --comments`.

## Wayfinding operations

Used by `/wayfinder`. The **map** is a single issue with **child** issues as tickets.

- **Map**: a single issue labelled `wayfinder:map`, holding the Notes / Decisions-so-far / Fog body. `./scripts/bot-gh-refactor.sh issue create --label wayfinder:map`.
- **Child ticket**: an issue linked to the map as a GitHub sub-issue (`./scripts/bot-gh-refactor.sh api` on the sub-issues endpoint). Where sub-issues aren't enabled, add the child to a task list in the map body and put `Part of #<map>` at the top of the child body. Labels: `wayfinder:<type>` (`research`/`prototype`/`grilling`/`task`). Once claimed, the ticket is assigned to the driving dev.
- **Blocking**: GitHub's **native issue dependencies**, the canonical, UI-visible representation. Add an edge with `./scripts/bot-gh-refactor.sh api --method POST repos/<owner>/<repo>/issues/<child>/dependencies/blocked_by -F issue_id=<blocker-db-id>`, where `<blocker-db-id>` is the blocker's numeric **database id** (`./scripts/bot-gh-refactor.sh api repos/<owner>/<repo>/issues/<n> --jq .id`, _not_ the `#number` or `node_id`). GitHub reports `issue_dependencies_summary.blocked_by` (open blockers only, the live gate). Where dependencies aren't available, fall back to a `Blocked by: #<n>, #<n>` line at the top of the child body. A ticket is unblocked when every blocker is closed.
- **Frontier query**: list the map's open children (`./scripts/bot-gh-refactor.sh issue list --state open`, scoped to the map's sub-issues / task list), drop any with an open blocker (`issue_dependencies_summary.blocked_by > 0`, or an open issue in the `Blocked by` line) or an assignee; first in map order wins.
- **Claim**: `./scripts/bot-gh-refactor.sh issue edit <n> --add-assignee @me`, the session's first write.
- **Resolve**: `./scripts/bot-gh-refactor.sh issue comment <n> --body "<answer>"`, then `./scripts/bot-gh-refactor.sh issue close <n>`, then append a context pointer (gist + link) to the map's Decisions-so-far.
