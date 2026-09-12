<?php

declare(strict_types=1);

use Art4\LegacyTodo\Taxonomy;
use Art4\LegacyTodo\Todo;
use Art4\LegacyTodo\Todos;

final class TodosTest extends PHPUnit\Framework\TestCase
{
    /** @var \PDO */
    private $pdo;

    /** @var Todos */
    private $todos;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, user_id INTEGER)');
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todo_tags (todo_id INTEGER, tag_id INTEGER)');
        $this->todos = new Todos($this->pdo, new Taxonomy($this->pdo));
    }

    private function seedTodo(array $row): int
    {
        $this->pdo->exec(
            "INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES ("
            . $row["user_id"] . ",'" . $row["title"] . "','" . $row["text"] . "','" . $row["status"] . "',"
            . $row["priority"] . ",'" . $row["due_date"] . "'," . $row["archived"] . ",'2026-01-10')",
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function testListActiveReturnsUnscopedActiveTodosSortedByStatusThenDue(): void
    {
        $this->seedTodo(["user_id" => 1, "title" => "Later open", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-12-31", "archived" => 0]);
        $this->seedTodo(["user_id" => 2, "title" => "Earlier open", "text" => "", "status" => "open", "priority" => 3, "due_date" => "2026-06-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 1, "title" => "Done stays", "text" => "", "status" => "done", "priority" => 2, "due_date" => "2026-01-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 3, "title" => "Archived hidden", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 1]);

        $titles = array_map(fn($todo) => $todo->title(), $this->todos->listActive());

        $this->assertSame(["Done stays", "Earlier open", "Later open"], $titles);
    }

    public function testEveryReadPathReturnsTheSameTodoShape(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "Einziges", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);
        $this->pdo->exec('INSERT INTO categories (name,user_id) VALUES ("Arbeit",1)');
        $this->pdo->exec("UPDATE todos SET category_id=1 WHERE id=" . $id);

        $shape = static function (Todo $todo): array {
            return [
                $todo->id(),
                $todo->userId(),
                $todo->title(),
                $todo->text(),
                $todo->status(),
                $todo->priority(),
                $todo->dueDate(),
                $todo->archived(),
                $todo->createdAt(),
                $todo->categoryId(),
                $todo->category(),
            ];
        };
        $expected = [$id, 1, "Einziges", "", "open", 1, "2026-01-01", false, "2026-01-10", 1, "Arbeit"];

        $this->assertSame($expected, $shape($this->todos->find($id)));
        $this->assertSame($expected, $shape($this->todos->search("Einziges")[0]));
        $this->assertSame($expected, $shape($this->todos->listActive()[0]));
        $this->assertSame($expected, $shape($this->todos->listFiltered("", "", "")[0]));
    }

    public function testListFilteredAppliesStatusPriorityAndDueFilters(): void
    {
        $this->seedTodo(["user_id" => 1, "title" => "Low done", "text" => "", "status" => "done", "priority" => 3, "due_date" => "2026-01-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 2, "title" => "High open", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-06-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 3, "title" => "Medium open late", "text" => "", "status" => "open", "priority" => 2, "due_date" => "2026-12-31", "archived" => 0]);
        $this->seedTodo(["user_id" => 4, "title" => "Archived hidden", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 1]);

        $this->assertSame(
            ["Low done", "High open", "Medium open late"],
            array_map(fn($todo) => $todo->title(), $this->todos->listFiltered("", "", "")),
        );
        $this->assertSame(["Low done"], array_map(fn($todo) => $todo->title(), $this->todos->listFiltered("done", "", "")));
        $this->assertSame(["High open"], array_map(fn($todo) => $todo->title(), $this->todos->listFiltered("", "1", "")));
        $this->assertSame(["Low done", "High open"], array_map(fn($todo) => $todo->title(), $this->todos->listFiltered("", "", "2026-06-15")));
        $this->assertSame(["Medium open late"], array_map(fn($todo) => $todo->title(), $this->todos->listFiltered("open", "2", "")));
    }

    public function testListFilteredRejectsInjectedStatus(): void
    {
        $this->seedTodo(["user_id" => 1, "title" => "Eins", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-12-31", "archived" => 0]);
        $this->seedTodo(["user_id" => 1, "title" => "Zwei", "text" => "", "status" => "done", "priority" => 2, "due_date" => "2026-01-01", "archived" => 0]);

        $rows = $this->todos->listFiltered("open' OR 1=1--", "", "");

        $this->assertSame([], $rows);
    }

    public function testSearchRejectsInjectedQuery(): void
    {
        $this->seedTodo(["user_id" => 1, "title" => "Eins", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-12-31", "archived" => 0]);

        $rows = $this->todos->search("' OR 1=1--");

        $this->assertSame([], $rows);
    }

    public function testSearchMatchesCaseInsensitiveOnTitleIgnoringArchived(): void
    {
        $this->seedTodo(["user_id" => 1, "title" => "Erstes Todo", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-12-31", "archived" => 0]);
        $this->seedTodo(["user_id" => 2, "title" => "noch was", "text" => "", "status" => "done", "priority" => 2, "due_date" => "2026-11-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 3, "title" => "Erstes TODO archiviert", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 1]);

        $this->assertSame(["Erstes Todo"], array_map(fn($todo) => $todo->title(), $this->todos->search("ERSTES")));
        $this->assertSame(["noch was"], array_map(fn($todo) => $todo->title(), $this->todos->search("NOCH")));
        $this->assertSame(["Erstes Todo"], array_map(fn($todo) => $todo->title(), $this->todos->search("todo")));
    }

    public function testFindReturnsTodoOrNull(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "Detail", "text" => "Beschreibung", "status" => "open", "priority" => 2, "due_date" => "2026-06-01", "archived" => 0]);
        $this->pdo->exec('INSERT INTO categories (name,user_id) VALUES ("Arbeit",1)');
        $this->pdo->exec("UPDATE todos SET category_id=1 WHERE id=" . $id);
        $archivedId = $this->seedTodo(["user_id" => 1, "title" => "Archived detail", "text" => "", "status" => "done", "priority" => 3, "due_date" => "2026-01-01", "archived" => 1]);

        $todo = $this->todos->find($id);

        $this->assertInstanceOf(Todo::class, $todo);
        $this->assertSame("Detail", $todo->title());
        $this->assertSame("Beschreibung", $todo->text());
        $this->assertSame($id, $todo->id());
        $this->assertSame(1, $todo->userId());
        $this->assertSame("open", $todo->status());
        $this->assertSame(2, $todo->priority());
        $this->assertSame("2026-06-01", $todo->dueDate());
        $this->assertFalse($todo->archived());
        $this->assertSame("2026-01-10", $todo->createdAt());
        $this->assertSame(1, $todo->categoryId());
        $this->assertSame("Arbeit", $todo->category());
        $this->assertNull($this->todos->find($id + 99));
        $this->assertInstanceOf(\Art4\LegacyTodo\Todo::class, $this->todos->find($archivedId));
    }

    public function testArchiveMarksTodoArchivedWithoutDeleting(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "Wird archiviert", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);

        $this->assertTrue($this->todos->archive($id));

        $row = $this->pdo->query("SELECT * FROM todos WHERE id=" . $id)->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame(1, (int) $row["archived"]);
        $this->assertSame("Wird archiviert", $row["title"]);
    }

    public function testCreateInsertsOpenTodoWithData2Wurst(): void
    {
        $result = $this->todos->create(7, "Neues Todo", "Text", 2, "2026-12-01");

        $this->assertTrue($result);
        $row = $this->pdo->query("SELECT * FROM todos")->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame("Neues Todo", $row["title"]);
        $this->assertSame("Text", $row["text"]);
        $this->assertSame("open", $row["status"]);
        $this->assertSame(7, (int) $row["user_id"]);
        $this->assertSame(2, (int) $row["priority"]);
        $this->assertSame("2026-12-01", $row["due_date"]);
        $this->assertSame(0, (int) $row["archived"]);
        $this->assertSame("wurst", $row["data2"]);
        $this->assertSame(date("Y-m-d"), $row["created_at"]);
    }

    public function testCreateRequiresTitle(): void
    {
        $this->assertFalse($this->todos->create(7, "", "Text", 2, "2026-12-01"));

        $this->assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM todos")->fetchColumn());
    }

    public function testCreateReturnsFalseWhenInsertFails(): void
    {
        $this->pdo->exec("DROP TABLE todos");

        $this->assertFalse($this->todos->create(7, "Neues Todo", "Text", 2, "2026-12-01"));
    }

    public function testUpdateChangesTitleTextPriorityAndStatus(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "Alt", "text" => "alter text", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);

        $result = $this->todos->update($id, "Neu", "neuer text", 3, "done");

        $this->assertTrue($result);
        $row = $this->pdo->query("SELECT * FROM todos WHERE id=" . $id)->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame("Neu", $row["title"]);
        $this->assertSame("neuer text", $row["text"]);
        $this->assertSame("done", $row["status"]);
        $this->assertSame(3, (int) $row["priority"]);
    }

    public function testUpdateRequiresTitle(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "Alt", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);

        $this->assertFalse($this->todos->update($id, "", "neu", 2, "done"));

        $row = $this->pdo->query("SELECT * FROM todos WHERE id=" . $id)->fetch(\PDO::FETCH_ASSOC);
        $this->assertSame("Alt", $row["title"]);
        $this->assertSame("open", $row["status"]);
    }

    public function testExportCsvReturnsFixedColumnsForUserTodosIncludingArchived(): void
    {
        $mine = $this->seedTodo(["user_id" => 1, "title" => "Eins", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);
        $this->seedTodo(["user_id" => 2, "title" => "Fremdes", "text" => "", "status" => "open", "priority" => 2, "due_date" => "2026-01-02", "archived" => 0]);
        $archived = $this->seedTodo(["user_id" => 1, "title" => "Archiviert", "text" => "", "status" => "done", "priority" => 3, "due_date" => "2026-01-03", "archived" => 1]);

        $csv = $this->todos->exportCsv(1);

        $this->assertSame(
            "id,title,status,priority,due_date,category_id,category,owner\n"
            . $archived . ",Archiviert,done,3,2026-01-03,,,1\n"
            . $mine . ",Eins,open,1,2026-01-01,,,1\n",
            $csv,
        );
    }

    public function testExportCsvShipsCategoryIdAndResolvedCategoryName(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "Kategorisiert", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);
        $this->pdo->exec('INSERT INTO categories (id,name,user_id) VALUES (1,"Arbeit",1)');
        $this->pdo->exec("UPDATE todos SET category_id=1 WHERE id=" . $id);

        $csv = $this->todos->exportCsv(1);

        $this->assertSame(
            "id,title,status,priority,due_date,category_id,category,owner\n"
            . $id . ",Kategorisiert,open,1,2026-01-01,1,Arbeit,1\n",
            $csv,
        );
    }

    public function testExportCsvQuotesTitleContainingComma(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "Eins, zwei", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);

        $csv = $this->todos->exportCsv(1);

        $this->assertSame(
            "id,title,status,priority,due_date,category_id,category,owner\n"
            . $id . ",\"Eins, zwei\",open,1,2026-01-01,,,1\n",
            $csv,
        );
    }

    public function testExportCsvDoublesQuoteInsideQuotedTitle(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => 'Sagt "Hallo"', "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);

        $csv = $this->todos->exportCsv(1);

        $this->assertSame(
            "id,title,status,priority,due_date,category_id,category,owner\n"
            . $id . ',"Sagt ""Hallo""",open,1,2026-01-01,,,1' . "\n",
            $csv,
        );
    }

    public function testExportCsvQuotesTitleContainingNewline(): void
    {
        $id = $this->seedTodo(["user_id" => 1, "title" => "Zeile 1\nZeile 2", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-01-01", "archived" => 0]);

        $csv = $this->todos->exportCsv(1);

        $this->assertSame(
            "id,title,status,priority,due_date,category_id,category,owner\n"
            . $id . ",\"Zeile 1\nZeile 2\",open,1,2026-01-01,,,1\n",
            $csv,
        );
    }

    public function testDashboardStatsCountsActiveOpenDoneAndOverdueAsOfDate(): void
    {
        $this->seedTodo(["user_id" => 1, "title" => "Offen vorher", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-06-14", "archived" => 0]);
        $this->seedTodo(["user_id" => 1, "title" => "Offen heute", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-06-15", "archived" => 0]);
        $this->seedTodo(["user_id" => 1, "title" => "Offen spät", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2026-06-16", "archived" => 0]);
        $this->seedTodo(["user_id" => 1, "title" => "Erledigt", "text" => "", "status" => "done", "priority" => 1, "due_date" => "2026-06-15", "archived" => 0]);
        $this->seedTodo(["user_id" => 1, "title" => "Archiviert alt", "text" => "", "status" => "open", "priority" => 1, "due_date" => "2025-01-01", "archived" => 1]);

        $stats = $this->todos->dashboardStats("2026-06-15");

        $this->assertSame(4, (int) $stats["c"]);
        $this->assertSame(3, (int) $stats["open"]);
        $this->assertSame(1, (int) $stats["done"]);
        $this->assertSame(1, (int) $stats["overdue"]);
    }
}
