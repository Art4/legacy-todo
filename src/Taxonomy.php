<?php

namespace Art4\LegacyTodo;

class Taxonomy
{
    /** @var \PDO */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** @return array<int, array<string, mixed>> */
    public function listCategories()
    {
        return $this->pdo->query("SELECT * FROM categories")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return array<int, array<string, mixed>> */
    public function listTags()
    {
        return $this->pdo->query("SELECT * FROM tags")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** @return bool */
    public function createCategory(string $name, int $userId)
    {
        if ($name === "") {
            return false;
        }
        $stmt = $this->pdo->prepare("INSERT INTO categories (name,user_id) VALUES (?,?)");

        return $stmt->execute([$name, $userId]);
    }

    /** @return bool */
    public function createTag(string $name)
    {
        if ($name === "") {
            return false;
        }
        $stmt = $this->pdo->prepare("INSERT INTO tags (name) VALUES (?)");

        return $stmt->execute([$name]);
    }

    /** @param array<int, int> $todoIds
     *  @return array<int, string>
     */
    public function categoryNamesForTodos(array $todoIds)
    {
        $names = array_fill_keys($todoIds, "");
        if ($todoIds === []) {
            return $names;
        }
        $placeholders = implode(",", array_fill(0, count($todoIds), "?"));
        $stmt = $this->pdo->prepare("SELECT t.id AS todo_id, c.name FROM todos t JOIN categories c ON c.id=t.category_id WHERE t.id IN ($placeholders)");
        $stmt->execute($todoIds);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $names[(int) $row["todo_id"]] = $row["name"];
        }

        return $names;
    }

    /** @param array<int, int> $todoIds
     *  @return array<int, array<int, array<string, int>>>
     */
    public function tagsForTodos(array $todoIds)
    {
        $tags = [];
        foreach ($todoIds as $id) {
            $tags[$id] = [];
        }
        if ($todoIds === []) {
            return $tags;
        }
        $placeholders = implode(",", array_fill(0, count($todoIds), "?"));
        $stmt = $this->pdo->prepare("SELECT * FROM todo_tags WHERE todo_id IN ($placeholders)");
        $stmt->execute($todoIds);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $tags[(int) $row["todo_id"]][] = ["todo_id" => (int) $row["todo_id"], "tag_id" => (int) $row["tag_id"]];
        }

        return $tags;
    }
}
