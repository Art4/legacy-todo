#!/usr/bin/env bash
# Helfer-Script für legacy-todo – PHP 7.2 nur im Docker, nichts auf dem Host installieren.
# Datenbank: nur SQLite, keine mysqli/pdo_mysql Overhead.
set -euo pipefail

IMAGE="php:7.2-apache"
CONTAINER="legacy-todo-php72"
PORT="${PORT:-8086}"
SRC="$(cd "$(dirname "$0")" && pwd)"

cmd="${1:-help}"

case "$cmd" in
  up)
    if docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
      echo "Container $CONTAINER existiert bereits – starte neu..."
      docker rm -f "$CONTAINER" >/dev/null 2>&1 || true
    fi
    echo "Starte $IMAGE auf Port $PORT -> $SRC:/var/www/html"
    docker run -d --name "$CONTAINER" -p "${PORT}:80" -v "${SRC}:/var/www/html" "$IMAGE" >/dev/null
    echo "-> http://localhost:${PORT}/"
    docker ps --filter "name=${CONTAINER}"
    ;;
  down)
    docker rm -f "$CONTAINER" 2>/dev/null || echo "Container $CONTAINER nicht gefunden"
    ;;
  lint)
    # Nur Linting erlaubt, kein Composer, keine Tests
    echo "Linting PHP-Dateien in $SRC mit $IMAGE (php -l)..."
    docker run --rm -v "${SRC}:/var/www/html" -w /var/www/html "$IMAGE" bash -c 'find . -name "*.php" -print0 | xargs -0 -n1 php -l'
    ;;
  shell)
    docker exec -it "$CONTAINER" bash
    ;;
  exec)
    shift
    docker exec -it "$CONTAINER" "$@"
    ;;
  logs)
    docker logs -f "$CONTAINER"
    ;;
  help|*)
    echo "Usage: $0 {up|down|lint|shell|logs|exec <cmd>}"
    echo ""
    echo "  up          - Startet PHP 7.2 Apache Container (Port $PORT) mit SQLite"
    echo "  down        - Stoppt und löscht Container"
    echo "  lint        - php -l über alle .php Dateien (nur erlaubt Check)"
    echo "  shell       - Bash im Container"
    echo "  exec <cmd>  - Befehl im Container ausführen"
    echo "  logs        - Container-Logs verfolgen"
    echo ""
    echo "Env: PORT=8086 $0 up  (Port überschreiben)"
    ;;
esac
