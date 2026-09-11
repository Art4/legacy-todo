<?php

namespace Art4\LegacyTodo;

/**
 * Single responsible module: owns everything needed to make a database file
 * request-ready — idempotent schema creation and seed-if-empty. It is invoked
 * once, at first run or explicit migration time, never from the request
 * lifecycle (ADR-0010).
 */
class Installer
{
    /** @var \PDO */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function install(): void
    {
        $this->createSchema();
        $this->seedIfEmpty();
    }

    private function createSchema(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)");
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)');
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, user_id INTEGER)");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS todo_tags (todo_id INTEGER, tag_id INTEGER)");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT)");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS assignments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, assigned_by INTEGER)");
    }

    private function seedIfEmpty(): void
    {
        $cnt = $this->pdo->query("SELECT COUNT(*) as c FROM users")->fetch(\PDO::FETCH_ASSOC);
        if ($cnt['c'] == 0) {
            $this->pdo->exec("INSERT INTO users (username,password,role,email,created_at) VALUES ('admin','" . password_hash('admin123', PASSWORD_BCRYPT) . "','admin','admin@example.com','2026-01-01')");
            $this->pdo->exec("INSERT INTO users (username,password,role,email,created_at) VALUES ('user','" . password_hash('user123', PASSWORD_BCRYPT) . "','user','user@example.com','2026-01-02')");
            $this->pdo->exec("INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (1,'Erstes Todo','Beschreibung 1','open',1,'2026-12-31',0,'2026-01-10')");
            $this->pdo->exec("INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (2,'Zweites Todo','Noch was','done',2,'2026-11-01',0,'2026-01-11')");
            $this->pdo->exec("INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (1,'Archiviertes','Altes erledigtes','done',3,'2026-01-01',1,'2026-01-05')");
            $this->pdo->exec("INSERT INTO categories (name,user_id) VALUES ('Allgemein',1)");
            $this->pdo->exec("INSERT INTO tags (name) VALUES ('wichtig')");
            $this->pdo->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (1,2,'Kommentar 1','2026-01-12')");
        }
    }
}
