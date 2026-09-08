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
        $todos = $this->todos->listActive();
        $stats = $this->todos->dashboardStats();

        return ["todos" => $todos, "stats" => $stats];
    }
}