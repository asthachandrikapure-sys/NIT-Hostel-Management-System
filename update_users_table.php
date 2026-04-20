<?php
include("db.php");
$sql = "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
if ($conn->query($sql) === TRUE) {
    echo "Column 'created_at' added successfully.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}
?>
