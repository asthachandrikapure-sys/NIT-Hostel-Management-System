<?php
include("db.php");

// Update status column to include 'Pending Admin Approval'
$conn->query("ALTER TABLE gate_passes MODIFY COLUMN status ENUM('Pending HOD Approval', 'Pending Admin Approval', 'Pending Hostel In-Charge Approval', 'Pending Warden Approval', 'Approved', 'Rejected') DEFAULT 'Pending HOD Approval'");
echo "Updated 'status' column enum values to include 'Pending Admin Approval'.<br>";

echo "Schema update completed.";
?>
