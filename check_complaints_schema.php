<?php
include("db.php");
$result = $conn->query("SHOW COLUMNS FROM complaints");
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
