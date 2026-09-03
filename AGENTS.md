# AGENTS.md

## Agent skills

### Issue tracker

Issues live as GitHub issues on `Art4/legacy-todo`, managed via the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Standard five-role vocabulary (`needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`), unchanged. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context layout: `CONTEXT.md` + `docs/adr/` at the repo root. See `docs/agents/domain.md`.

## Continuous-refactoring suite

Refactoring Notes: `docs/refactoring/` — the continuous-refactoring
suite's own config, in-flight merge-request bookkeeping, and
rejected-tooling records live here.

Create-mode: see the Refactoring Notes' `bookkeeping.md` — that file is
the sole authoritative value, this is a pointer, not a copy.

Backlog label: `refactor:candidate` (native tracker only — see
`docs/agents/issue-tracker.md`).
