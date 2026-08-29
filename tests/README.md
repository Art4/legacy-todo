# Test layout

Tests live in one of seven `tests/` folders. Each folder is only created
(alongside its `phpunit.xml.dist` testsuite and `composer.json` `autoload-dev`
entry) once something actually needs it — an empty, never-populated folder is
noise, not structure.

| Folder | For |
|---|---|
| `tests/Unit/` | Classes following PSR-4 |
| `tests/Legacy/` | Functions, classes, or code under test that doesn't follow PSR-4 |
| `tests/Psr0/` | Code under test that follows PSR-0 |
| `tests/Integration/` | Real collaborators together (database, filesystem, multiple units) — no test doubles |
| `tests/Functional/` | End-to-end, through the application's real entry points |
| `tests/Fakes/` | Test doubles (fakes, stubs, mocks) — support code, not test cases themselves |
| `tests/Fixtures/` | Static test data (sample files, seed data) — support code, not test cases themselves |
