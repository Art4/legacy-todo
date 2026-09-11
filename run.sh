#!/usr/bin/env bash
# Helfer-Script für legacy-todo – startet die App-Laufzeit (aktuell PHP 7.4) über Docker,
# kein Host-PHP nötig. Datenbank: SQLite.
set -euo pipefail

IMAGE="php:7.4-apache"
CONTAINER="legacy-todo-php74"
PORT="${PORT:-8086}"
SRC="$(cd "$(dirname "$0")" && pwd)"

cmd="${1:-help}"

case "$cmd" in
  up)
    if docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
      echo "Container $CONTAINER existiert bereits – starte neu..."
      docker rm -f "$CONTAINER" >/dev/null 2>&1 || true
    fi
    "$0" install
    echo "Starte $IMAGE auf Port $PORT -> $SRC:/var/www/html (Webroot: public/)"
    docker run -d --name "$CONTAINER" -p "${PORT}:80" \
      -v "${SRC}:/var/www/html" \
      -v "${SRC}/docker/000-default.conf:/etc/apache2/sites-available/000-default.conf" \
      "$IMAGE" >/dev/null
    echo "-> http://localhost:${PORT}/"
    docker ps --filter "name=${CONTAINER}"
    ;;
  install)
    # Einmalige (idempotente) Datenbank-Einrichtung: Schema anlegen und bei
    # leerer Datenbank mit Demo-Daten befüllen. Läuft bewusst NICHT im
    # Request-Lebenszyklus, sondern nur beim ersten Start (ADR-0010).
    # Verwendet dieselbe PHP-Version wie die CI-Werkzeuge (nicht die 7.4-Laufzeit),
    # da der Composer-Dev-Stack nur auf PHP 8.x parst (siehe issue #239).
    echo "Richte SQLite-Datenbank ein ($SRC/database.sqlite)..."
    docker run --rm -v "${SRC}:/app" -w /app php:8.3-cli php src/install.php
    ;;
  down)
    docker rm -f "$CONTAINER" 2>/dev/null || echo "Container $CONTAINER nicht gefunden"
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
    echo "Usage: $0 {up|down|install|shell|logs|exec <cmd>}"
    echo ""
    echo "  up          - Startet PHP 7.4 Apache Container (Port $PORT) mit SQLite"
    echo "  down        - Stoppt und löscht Container"
    echo "  install     - Einmalige Datenbank-Einrichtung (Schema + Demo-Daten, idempotent)"
    echo "  shell       - Bash im Container"
    echo "  exec <cmd>  - Befehl im Container ausführen"
    echo "  logs        - Container-Logs verfolgen"
    echo ""
    echo "Env: PORT=8086 $0 up  (Port überschreiben)"
    ;;
esac
