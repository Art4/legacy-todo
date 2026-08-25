#!/usr/bin/env bash
#
# Runs `gh` as the legacy-todo-refactor-bot GitHub App identity, scoped to
# only this repo. Never touches `gh auth login` state, and never exports the
# bot token beyond this one invocation — GH_TOKEN is set only for the `gh`
# process this script execs.
#
# Fail-closed: if a bot token can't be minted, this hard-fails instead of
# silently falling back to your personal `gh` login.
#
# Usage:
#   ./scripts/bot-gh-refactor.sh issue create --title "..." --body "..."
#   ./scripts/bot-gh-refactor.sh pr create --title "..." --body "..." --base main
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" &>/dev/null && pwd)"

TOKEN="$("$SCRIPT_DIR/gh-app-mint-token.sh")" || {
  echo "bot-gh-refactor: could not mint a bot token — refusing to fall back to your personal gh account." >&2
  exit 1
}

if [[ -z "$TOKEN" ]]; then
  echo "bot-gh-refactor: minted an empty token — aborting." >&2
  exit 1
fi

GH_TOKEN="$TOKEN" exec gh "$@"
