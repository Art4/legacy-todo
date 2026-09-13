# AGENTS.md

## Agent skills

### Issue tracker

Issues live as GitHub issues on `Art4/legacy-todo`, managed via the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Standard five-role vocabulary (`needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`), unchanged. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context layout: `CONTEXT.md` + `docs/adr/` at the repo root. See `docs/agents/domain.md`.

## Environment

### No host installs — tools run in Docker

Never install packages, PHP extensions, or other tooling on the host system (`apt-get install`, `pecl
install`, `sudo` anything, etc.). This project's own tooling — PHPStan, Rector, Psalm, PHPUnit,
Composer, and the app runtime itself — always runs inside Docker, matching `run.sh` and
`.github/workflows/ci.yml`. If a tool isn't available on the host, run it the way CI does instead:
`docker run --rm -v "$PWD":/app -w /app php:8.3-cli ...` (see `.github/workflows/ci.yml` for the exact
command per tool) — never reach for the host's package manager to work around a missing extension.

### Scratch files go under `/tmp/opencode/`, never directly under `/tmp/`

This loop runs headless and unattended — nobody is watching to answer an interactive permission
prompt. A tool call that writes a file directly under `/tmp/` (e.g. `/tmp/pr-body.md`) triggers an
`external_directory` permission `ask` that can never be answered in that mode, stalling the pass
indefinitely (observed twice: an implement step's own scratch write for a PR body). `/tmp/opencode/`
is already pre-approved — write any scratch/temp file there instead (`/tmp/opencode/pr-body.md`,
etc.), or use a path inside the repo checkout.

## Continuous-refactoring suite

Refactoring Notes: `docs/refactoring/` — the continuous-refactoring
suite's own config, in-flight merge-request bookkeeping, and
rejected-tooling records live here.

Create-mode: see the Refactoring Notes' `bookkeeping.md` — that file is
the sole authoritative value, this is a pointer, not a copy.

Backlog label: `refactor:candidate` (native tracker only — see
`docs/agents/issue-tracker.md`).
