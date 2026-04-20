<?php
include("db.php");
$res = $conn->query("DESCRIBE students_info");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
?>
