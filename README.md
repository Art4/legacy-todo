# legacy-todo

Eine To-Do-Listen-Anwendung in PHP mit gewachsenem Legacy-Code. Aktuell läuft sie auf **PHP
7.4** – das ist der heutige Status quo, keine Vorgabe, auf der die Anwendung bleiben soll. Sie
wird laufend weiterentwickelt und modernisiert; ein weiteres PHP-Versions-Upgrade (etwa auf 8.x)
bleibt ein offener Backlog-Punkt.

> **Sicherheitshinweis:** Die Anwendung hat bekannte, noch nicht behobene Schwachstellen
> (SQL-Konkatenation, XSS, fehlende Autorisierung etc.) – bis die behoben sind, nur lokal mit
> Dummy-Daten betreiben, niemals öffentlich deployen.

## Setup

Kein Host-PHP nötig – die Laufzeit läuft komplett über Docker.

```bash
./run.sh up      # PHP-7.4-Apache-Container starten (Port 8086), SQLite
./run.sh lint    # php -l über alle .php-Dateien
./run.sh shell   # Bash im Container
./run.sh logs    # Container-Logs verfolgen
./run.sh down    # Container stoppen und löschen
```

App: http://localhost:8086/

Composer und statische Analyse (PHPStan, PHP CS Fixer, Rector) sind als Dev-Tooling vorhanden und
laufen in CI – Details siehe `.github/workflows/ci.yml` und `composer.json`. Die Laufzeit selbst
läuft auf PHP 7.4 im Container; CI lintet und testet unter derselben PHP-Version.

## Weiterentwicklung

Der laufende Modernisierungs-Fahrplan (welche Tooling- und Struktur-Bausteine schon vorhanden
sind, was als Nächstes ansteht) steht in `docs/refactoring/bookkeeping.md`. Offene
Verbesserungs-Vorschläge sind als GitHub-Issues mit dem Label `refactor:candidate` erfasst.

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
