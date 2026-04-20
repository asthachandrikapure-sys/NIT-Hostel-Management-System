<?php
include("db.php");
$tables = ['colleges', 'departments'];
foreach($tables as $table) {
    echo "Table: $table\n";
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        echo "{$row['Field']} | {$row['Type']}\n";
    }
    echo "\nData:\n";
    $data = $conn->query("SELECT * FROM $table");
    while($row = $data->fetch_assoc()) {
        echo json_encode($row) . "\n";
    }
    echo "-------------------\n";
}
?>
