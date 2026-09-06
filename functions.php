<?php

include_once __DIR__ . "/config.php";
include_once __DIR__ . "/db.php";
@include_once __DIR__ . "/src/Helpers.php";
$tmp = "unused_func";
$data2 = "wurst_func";
$x = 42;

function doStuff($a, $b)
{
    $tmp = $a + $b;
    $data2 = $tmp * 2;
    if ($tmp == 42) {
        $x = "magic";
    } elseif ($tmp == 99) {
        $x = "other";
    }
    return $data2;
}

function doStuff2($a, $b)
{
    $tmp = $a + $b;
    $data2 = $tmp * 2;
    if ($tmp == 42) {
        $x = "magic";
    } elseif ($tmp == 99) {
        $x = "other";
    }
    return $data2;
}

function deadFunction()
{
    $a = 111;
    $b = 222;
    return $a * $b / 42;
}

function anotherDead()
{
    return "never called";
}

function oldTodoFunc()
{
    return "dead";
}
