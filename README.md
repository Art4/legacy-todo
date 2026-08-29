# legacy-todo

Bewusst schlechte PHP-7.2-Legacy-Anwendung **To-Do-Liste** – Testdaten für Refactoring-Suite.

> **Sicherheitswarnung:** Enthält absichtlich eingebaute Schwachstellen (SQL-Konkatenation, XSS, fehlende Autorisierung etc.) – nur lokal mit Dummy-Daten betreiben, niemals öffentlich deployen.

## Setup

Kein Host-PHP nötig – alles läuft über Docker (PHP 7.2).

```bash
./run.sh up      # PHP-7.2-Apache-Container starten (Port 8086), SQLite
./run.sh lint    # php -l über alle .php-Dateien (einzig erlaubter Check bei PHP 7.2)
./run.sh shell   # Bash im Container
./run.sh logs    # Container-Logs verfolgen
./run.sh down    # Container stoppen und löschen
```

App: http://localhost:8086/

## Tests

Entwicklungstests laufen über PHPUnit (dev-Dependency, wird in CI installiert):

```bash
composer install       # dev-Dependencies installieren (benötigt ein Composer-bekanntes PHP)
vendor/bin/phpunit     # Tests ausführen
```

## Fachliche Anforderungen

### Funktionsumfang

- Benutzer registrieren und anmelden.
- To-dos erstellen, bearbeiten und löschen.
- To-dos als erledigt markieren.
- Nach Status, Priorität und Fälligkeit filtern.
- Kategorien und Tags verwalten.
- Kommentare zu To-dos hinzufügen.
- To-dos Benutzern zuweisen.
- Eine einfache Dashboard-Ansicht anzeigen.
- To-dos als CSV exportieren.
- Suchfunktion anbieten.
- Admin-Seite für Benutzer und Kategorien bereitstellen.

### Verhalten, das erhalten bleiben muss

- Ein neues To-do benötigt mindestens einen Titel.
- Ein To-do besitzt genau einen Status.
- Nur der Eigentümer oder ein Administrator darf es bearbeiten.
- Erledigte To-dos erscheinen weiterhin in der Historie.
- Die Standardsortierung ist: offen zuerst, danach Fälligkeit.
- Beim Löschen wird das To-do archiviert statt physisch gelöscht.
- Eine Suche muss Groß-/Kleinschreibung ignorieren.
- CSV-Exporte enthalten immer dieselben Spalten.
- Nicht eingeloggte Benutzer werden zur Login-Seite weitergeleitet.
