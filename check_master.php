<?php
include('c:/xampp/htdocs/nit_project/db.php');
echo "Colleges:\n";
$res = $conn->query("SELECT * FROM colleges");
while($r = $res->fetch_assoc()) print_r($r);

echo "Departments:\n";
$res = $conn->query("SELECT * FROM departments");
while($r = $res->fetch_assoc()) print_r($r);
