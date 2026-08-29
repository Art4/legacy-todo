#!/usr/bin/env bash
# Dev-Tooling für legacy-todo – neuere PHP-Versionen NUR in Docker verwenden,
# niemals PHP vom Host-Directory ausführen. Der PHP-5.6-App-Container (run.sh)
# ist für Dev-Tools nicht geeignet (braucht neuere PHP-Version), daher hier eine
# separate Tooling-Image: composer:2 (Composer/PHP 8.x) bzw. php:8.3-cli.
set -euo pipefail

COMPOSER_IMAGE="composer:2"
PHP_IMAGE="php:8.3-cli"
SRC="$(cd "$(dirname "$0")" && pwd)"

cmd="${1:-help}"
shift || true

case "$cmd" in
  composer)
    # Composer läuft im Docker-Container; vendor/ wird dabei aktualisiert
    # (auf dem Host liegt nur der Dateibaum, kein PHP-Prozess).
    docker run --rm -v "${SRC}:/app" -w /app "${COMPOSER_IMAGE}" composer "$@"
    ;;
  rector)
    docker run --rm -v "${SRC}:/app" -w /app "${PHP_IMAGE}" vendor/bin/rector "$@"
    ;;
  phpstan)
    docker run --rm -v "${SRC}:/app" -w /app "${PHP_IMAGE}" vendor/bin/phpstan "$@"
    ;;
  phpunit)
    docker run --rm -v "${SRC}:/app" -w /app "${PHP_IMAGE}" vendor/bin/phpunit "$@"
    ;;
  php)
    docker run --rm -v "${SRC}:/app" -w /app "${PHP_IMAGE}" php "$@"
    ;;
  help|*)
    echo "Usage: $0 {composer|rector|phpstan|phpunit|php} <args...>"
    echo ""
    echo "  composer <args>   - Composer im composer:2-Container"
    echo "  rector <args>     - Rector im php:8.3-cli-Container"
    echo "  phpstan <args>    - PHPStan im php:8.3-cli-Container"
    echo "  phpunit <args>    - PHPUnit im php:8.3-cli-Container"
    echo "  php <args>        - Beliebige PHP-Befehle im php:8.3-cli-Container"
    echo ""
    echo "Alle Befehle mounten $SRC nach /app im Container. PHP läuft nie auf dem Host."
    ;;
esac
