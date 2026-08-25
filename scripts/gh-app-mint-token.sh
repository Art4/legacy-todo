#!/usr/bin/env bash
#
# Mints a short-lived (1h) GitHub App Installation Access Token for the
# "legacy-todo-refactor-bot" GitHub App and prints ONLY the token to stdout.
#
# No secret material lives in this repo: App ID, Installation ID and the
# private-key path are read from an external config file outside the repo
# (default: ~/.config/gh-app/legacy-todo-refactor-bot.env, written by
# scripts/setup-github-app-refactor-bot.sh). Override with GH_APP_BOT_CONFIG.
#
# Usage: token=$(./scripts/gh-app-mint-token.sh)
set -euo pipefail

CONFIG_FILE="${GH_APP_BOT_CONFIG:-$HOME/.config/gh-app/continuous-refactoring-bot.env}"

if [[ ! -f "$CONFIG_FILE" ]]; then
  echo "gh-app-mint-token: config file not found: $CONFIG_FILE" >&2
  echo "  Run scripts/setup-github-app-refactor-bot.sh first, or set GH_APP_BOT_CONFIG." >&2
  exit 1
fi

# shellcheck disable=SC1090
source "$CONFIG_FILE"

: "${APP_ID:?APP_ID missing in $CONFIG_FILE}"
: "${INSTALLATION_ID:?INSTALLATION_ID missing in $CONFIG_FILE}"
: "${PEM_PATH:?PEM_PATH missing in $CONFIG_FILE}"

if [[ ! -r "$PEM_PATH" ]]; then
  echo "gh-app-mint-token: private key not readable: $PEM_PATH" >&2
  exit 1
fi

for bin in openssl curl jq; do
  command -v "$bin" >/dev/null 2>&1 || { echo "gh-app-mint-token: '$bin' is required but not found" >&2; exit 1; }
done

b64url() { openssl base64 -A | tr '+/' '-_' | tr -d '='; }

now=$(date +%s)
iat=$((now - 60))       # allow for clock drift
exp=$((now + 540))      # 9 minutes; GitHub caps JWT lifetime at 10 minutes

header=$(printf '{"alg":"RS256","typ":"JWT"}' | b64url)
payload=$(printf '{"iat":%d,"exp":%d,"iss":%s}' "$iat" "$exp" "$APP_ID" | b64url)
signing_input="${header}.${payload}"
signature=$(printf '%s' "$signing_input" | openssl dgst -sha256 -sign "$PEM_PATH" | b64url)
jwt="${signing_input}.${signature}"

response=$(curl -sS -f -X POST \
  -H "Authorization: Bearer ${jwt}" \
  -H "Accept: application/vnd.github+json" \
  "https://api.github.com/app/installations/${INSTALLATION_ID}/access_tokens") || {
    echo "gh-app-mint-token: failed to mint installation token (check APP_ID/INSTALLATION_ID/private key in $CONFIG_FILE)" >&2
    exit 1
  }

token=$(printf '%s' "$response" | jq -r '.token // empty')
if [[ -z "$token" ]]; then
  echo "gh-app-mint-token: no token in response: $response" >&2
  exit 1
fi

printf '%s' "$token"
