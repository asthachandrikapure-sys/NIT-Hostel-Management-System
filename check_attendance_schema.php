<?php
include("db.php");
$res = $conn->query("SHOW TABLES");
echo "Tables in database:\n";
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}

$tables_to_check = ['attendance', 'student_attendance'];
foreach($tables_to_check as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if($check->num_rows > 0) {
        echo "\nSchema for table: $table\n";
        $res = $conn->query("DESCRIBE $table");
        while($row = $res->fetch_assoc()) {
            echo "{$row['Field']} | {$row['Type']}\n";
        }
    }
}
?>
