# legacy-todo

Eine To-Do-Listen-Anwendung in PHP mit gewachsenem Legacy-Code. Aktuell läuft sie auf **PHP
5.6** – das ist der heutige Status quo, keine Vorgabe, auf der die Anwendung bleiben soll. Sie
wird laufend weiterentwickelt und modernisiert; ein PHP-Versions-Upgrade ist ein offener
Backlog-Punkt (siehe Issue #89 zur Vorbereitung eines Umstiegs auf PHP 7.4).

> **Sicherheitshinweis:** Die Anwendung hat bekannte, noch nicht behobene Schwachstellen
> (SQL-Konkatenation, XSS, fehlende Autorisierung etc.) – bis die behoben sind, nur lokal mit
> Dummy-Daten betreiben, niemals öffentlich deployen.

## Setup

Kein Host-PHP nötig – die Laufzeit läuft komplett über Docker.

```bash
./run.sh up      # PHP-5.6-Apache-Container starten (Port 8086), SQLite
./run.sh lint    # php -l über alle .php-Dateien
./run.sh shell   # Bash im Container
./run.sh logs    # Container-Logs verfolgen
./run.sh down    # Container stoppen und löschen
```

App: http://localhost:8086/

Composer und statische Analyse (PHPStan, PHP CS Fixer, Rector) sind als Dev-Tooling vorhanden und
laufen in CI – Details siehe `.github/workflows/ci.yml` und `composer.json`. Die Laufzeit selbst
bleibt vorerst PHP 5.6 im Container. Zur Vorbereitung auf einen PHP-7.4-Umstieg lintet CI den
Code zusätzlich unter PHP 7.4; die App läuft weiterhin auf PHP 5.6.

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
