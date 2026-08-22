# legacy-todo

Bewusst schlechte PHP-8.3-Legacy-Anwendung **To-Do-Liste** – Testdaten für Refactoring-Suite.

Spezifikation: siehe `../legacy-todo.md` (extrahiert aus `../README.md`).

> **Sicherheitswarnung:** Enthält absichtlich eingebaute Schwachstellen (SQL-Konkatenation, XSS, fehlende Autorisierung etc.) – nur lokal mit Dummy-Daten betreiben, niemals öffentlich deployen.

## Geplanter Funktionsumfang (aus `legacy-todo.md`)

- Registrierung / Login
- To-dos CRUD + erledigt markieren
- Filter nach Status / Priorität / Fälligkeit
- Kategorien, Tags, Kommentare, Zuweisung
- Dashboard, Suche, CSV-Export
- Admin-Seite

## Geplante Legacy-Smells

`god_class`, `global_state`, `duplicate_code`, `sql_concatenation`, `missing_output_encoding`, `long_method`, `dead_code`, `mixed_concerns`, `n_plus_one_query`, `missing_authorization`

## Git-Tags (vorgesehen)

`v0-legacy`, `v1-security-fixed`, `v2-extracted-services`, `v3-clean-architecture`

## Status

Gerüst – initiale Git-Aufsicht, Code folgt im nächsten Schritt.
