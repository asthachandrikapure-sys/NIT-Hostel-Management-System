<?php
include("db.php");
$res = $conn->query("SHOW INDEX FROM students_info");
while($row = $res->fetch_assoc()){
    echo $row['Column_name'] . " | " . $row['Non_unique'] . "\n";
}
?>
