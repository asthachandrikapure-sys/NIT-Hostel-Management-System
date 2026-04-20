<?php
$conn = new mysqli('localhost', 'root', 'root', 'hostel_db');
if($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

function printTable($conn, $table) {
    echo "\n--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        echo sprintf("%-15s | %-20s\n", $row['Field'], $row['Type']);
    }
}

printTable($conn, 'users');
printTable($conn, 'students_info');
printTable($conn, 'gate_passes');
printTable($conn, 'mess_fees');

$conn->close();
?>
