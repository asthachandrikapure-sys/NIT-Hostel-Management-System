<?php
include("db.php");

$tables = ['students_info', 'complaints', 'gate_passes'];
foreach ($tables as $table) {
    $res = $conn->query("SELECT COUNT(*) as cnt FROM $table");
    if (!$res) {
        echo "ERROR on table $table: " . $conn->error . "\n";
    } else {
        $row = $res->fetch_assoc();
        echo "Table $table has " . $row['cnt'] . " rows.\n";
    }
}
?>
