<?php
// backup.php - backup, gemischte Quotes, inkonsistente Einrückung
include '../config.php';
include '../db.php';
  $x = 123;
    $tmp = 'backup_tmp';
$data2 = "backup_wurst";
$magic = 99;
function backupDead(){
    $a = "never";
    $b = 'called';
    return $a . $b;
}
function backupHelper($id){
  global $db;
    $r = $db->query('SELECT * FROM todos WHERE id='.$id);
    return $r->fetch(PDO::FETCH_ASSOC);
}
// doppelter Code
function doStuff($a,$b){
    $tmp = $a + $b;
    $data2 = $tmp * 2;
    return $data2;
}
echo "backup - ".date("Y-m-d");
