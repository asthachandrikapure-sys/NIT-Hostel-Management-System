<?php
include("db.php");
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'created_at'");
if ($result->num_rows == 0) {
    echo "Column 'created_at' does NOT exist in 'users' table.\n";
} else {
    echo "Column 'created_at' exists in 'users' table.\n";
}
?>
