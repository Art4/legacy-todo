# test layout

PHPUnit test layout convention. A folder only gets created once something actually needs it.

| Folder | For |
|---|---|
| `tests/Unit/` | Classes following PSR-4 |
| `tests/Legacy/` | Functions, classes, or code under test that doesn't follow PSR-4 |
| `tests/Psr0/` | Code under test that follows PSR-0 |
| `tests/Integration/` | Real collaborators together (database, filesystem, multiple units) — no test doubles |
| `tests/Functional/` | End-to-end, through the application's real entry points |
| `tests/Fakes/` | Test doubles (fakes, stubs, mocks) — support code, not test cases themselves |
| `tests/Fixtures/` | Static test data (sample files, seed data) — support code, not test cases themselves |
