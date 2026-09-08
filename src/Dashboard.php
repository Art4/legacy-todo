<?php

namespace Art4\LegacyTodo;

require_once __DIR__ . "/Todos.php";

class Dashboard
{
    /** @var Todos */
    private $todos;

    public function __construct(Todos $todos)
    {
        $this->todos = $todos;
    }

    /**
     * @param array<string, mixed> $query
     * @return array{todos: array<int, array<string, mixed>>, stats: array{c: int, open: int, done: int, overdue: int}}
     */
    public function overview(array $query)
    {
        $q = $query["q"] ?? "";
        $status = $query["status"] ?? "";
        $prio = $query["priority"] ?? "";
        $due = $query["due"] ?? "";

        if ($q != "") {
            $todos = $this->todos->search($q);
        } elseif ($status != "" || $prio != "" || $due != "") {
            $todos = $this->todos->listFiltered($status, $prio, $due);
        } else {
            $todos = $this->todos->listActive();
        }
        $stats = $this->todos->dashboardStats();

        return ["todos" => $todos, "stats" => $stats];
    }

    /** @return string */
    public function exportCsv(int $userId)
    {
        return $this->todos->exportCsv($userId);
    }
}