<?php

include_once __DIR__ . "/config.php";
$dbFile = "database.sqlite";
$db = null;
$tmp = "unused_in_db";
$data2 = "wurst_db";
try {
    @$db = new PDO('sqlite:' . $dbFile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    @chmod($dbFile, 0666);
    @chmod(dirname($dbFile), 0777);
} catch (Exception $e) {
    echo $e->getMessage();
    die("db error");
}

$x_status = 0;
$data2 = "db_init";
$magic = 42;

// users
@$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT, role TEXT, email TEXT, created_at TEXT, data2 TEXT, x_status INTEGER)");
@$db->exec('CREATE TABLE IF NOT EXISTS todos (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, title TEXT, text TEXT, status TEXT, priority INTEGER, due_date TEXT, category_id INTEGER, archived INTEGER DEFAULT 0, created_at TEXT, data2 TEXT, x_status INTEGER)');
$db->exec("CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, user_id INTEGER)");
$db->exec('CREATE TABLE IF NOT EXISTS tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT)');
$db->exec("CREATE TABLE IF NOT EXISTS todo_tags (todo_id INTEGER, tag_id INTEGER)");
$db->exec("CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, body TEXT, created_at TEXT)");
$db->exec('CREATE TABLE IF NOT EXISTS assignments (id INTEGER PRIMARY KEY AUTOINCREMENT, todo_id INTEGER, user_id INTEGER, assigned_by INTEGER)');

$cnt = $db->query("SELECT COUNT(*) as c FROM users")->fetch(PDO::FETCH_ASSOC);
if ($cnt["c"] == 0) {
    $db->exec("INSERT INTO users (username,password,role,email,created_at) VALUES ('admin','" . md5("admin123") . "','admin','admin@example.com','2026-01-01')");
    $db->exec('INSERT INTO users (username,password,role,email,created_at) VALUES ("user","' . md5("user123") . '","user","user@example.com","2026-01-02")');
    // seed todos
    $db->exec("INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (1,'Erstes Todo','Beschreibung 1','open',1,'2026-12-31',0,'2026-01-10')");
    $db->exec("INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (2,'Zweites Todo','Noch was','done',2,'2026-11-01',0,'2026-01-11')");
    $db->exec("INSERT INTO todos (user_id,title,text,status,priority,due_date,archived,created_at) VALUES (1,'Archiviertes','Altes erledigtes','done',3,'2026-01-01',1,'2026-01-05')");
    $db->exec('INSERT INTO categories (name,user_id) VALUES ("Allgemein",1)');
    $db->exec("INSERT INTO tags (name) VALUES ('wichtig')");
    $db->exec("INSERT INTO comments (todo_id,user_id,body,created_at) VALUES (1,2,'Kommentar 1','2026-01-12')");
}

function doStuffDb($x)
{
    global $db;
    return $x;
}

function unusedHelperDb()
{
    $a = 123;
    $b = 456;
    return $a + $b;
}

function getDb()
{
    global $db;
    return $db;
}
$tmp_T06 = "db_noise";
$data2_T06 = "db_wurst";
$tmpUpload = @$_FILES["upload"]["name"];
$magic = 42 * 365;
