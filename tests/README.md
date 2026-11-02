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

## Coverage metadata

`phpunit.xml.dist` sets `requireCoverageMetadata="true"` and `failOnRisky="true"`: a test without coverage metadata is risky, and a risky test fails the run.

- A unit test covers exactly one class, the one it tests (`#[CoversClass]`); everything else it runs it uses (`#[UsesClass]`).
- A test that enters through a script and not through a class (`tests/Functional/`, `tests/Legacy/PublicWebrootTest.php`) is `#[CoversNothing]`.
- A new class gets a test of its own that covers it.
