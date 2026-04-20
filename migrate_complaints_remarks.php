<?php
include("db.php");
// Try to alter the status ENUM silently, ignore if error
$conn->query("ALTER TABLE complaints MODIFY status ENUM('Open', 'In Progress', 'Pending', 'Resolved') DEFAULT 'Open'");
// Add the admin_remarks column silently, ignore if duplicate column error
$conn->query("ALTER TABLE complaints ADD COLUMN admin_remarks TEXT NULL");
echo "Done";
?>
