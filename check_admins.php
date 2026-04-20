<?php
include("c:/xampp/htdocs/nit_project/db.php");
$res = $conn->query("SELECT fullname, email, role, college_name FROM users WHERE role != 'student'");
$out = "";
while($row = $res->fetch_assoc()) {
    $out .= sprintf("[%s] %s <%s> College: %s\n", $row['role'], $row['fullname'], $row['email'], $row['college_name']);
}
file_put_contents("c:/xampp/htdocs/nit_project/user_list.txt", $out);
echo "User list written to user_list.txt\n";
?>
