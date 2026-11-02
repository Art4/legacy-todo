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

`phpunit.xml.dist` also sets `beStrictAboutCoverageMetadata="true"`: a test that runs code it neither covers nor uses is risky, and so it fails the run too. PHPUnit applies this check only in a run that collects coverage, so the job "Test coverage floor" in CI enforces it, not the plain test job.

- A unit test lists what it uses: every class it runs without covering gets a `#[UsesClass]`.
- A test that enters through a script and not through a class is `#[CoversNothing]`; PHPUnit skips the check for it, so it lists nothing.
- `tests/bootstrap.php` loads `src/Bootstrap.php` before the first test, because that file starts with a statement outside its class that no attribute can name; without it the first test to load the file would be flagged, and which test that is depends on the run order.

## What the coverage figure measures

`<source>` in `phpunit.xml.dist` lists `src/` and `public/`, the application code; test code and tooling files are not measured. The coverage floor in `.coverage-floor` guards that figure, set a few points below the measured value, so a test is judged by what it checks and not by the lines it adds.
