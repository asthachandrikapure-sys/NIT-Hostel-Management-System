<?php
$conn = mysqli_connect("localhost", "root", "root", "hostel_db");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$res = $conn->query("SHOW TABLES");
while ($row = $res->fetch_row()) {
    echo $row[0] . PHP_EOL;
}
?>
