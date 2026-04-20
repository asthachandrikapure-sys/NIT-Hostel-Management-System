<?php
include("db.php");
$res = $conn->query("DESCRIBE mess_fees");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
