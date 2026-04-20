<?php
$conn = new mysqli('localhost', 'root', 'root', 'hostel_db');
if($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$output = "";
$tables = ['users', 'students_info', 'gate_passes', 'mess_fees'];

foreach($tables as $table) {
    $output .= "--- $table ---\n";
    $res = $conn->query("DESCRIBE $table");
    while($row = $res->fetch_assoc()) {
        $output .= implode(" | ", $row) . "\n";
    }
}

file_put_contents('schema_output.txt', $output);
echo "Schema written to schema_output.txt\n";
$conn->close();
?>
