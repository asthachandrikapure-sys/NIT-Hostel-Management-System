<?php
include("db.php");

// Check current schema
$r = $conn->query("SHOW COLUMNS FROM complaints");
echo "Current columns:\n";
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

// Add admin_remarks column if missing
$has_remarks = false;
$r2 = $conn->query("SHOW COLUMNS FROM complaints LIKE 'admin_remarks'");
if ($r2 && $r2->num_rows > 0) {
    $has_remarks = true;
    echo "\nadmin_remarks column already exists.\n";
} else {
    $res = $conn->query("ALTER TABLE complaints ADD COLUMN admin_remarks TEXT NULL");
    echo $res ? "\nSUCCESS: admin_remarks column added.\n" : "\nFAILED: " . $conn->error . "\n";
}

// Update status ENUM to include Pending
$res2 = $conn->query("ALTER TABLE complaints MODIFY status ENUM('Open','In Progress','Pending','Resolved') DEFAULT 'Open'");
echo $res2 ? "SUCCESS: status ENUM updated.\n" : "FAILED status update: " . $conn->error . "\n";
?>
