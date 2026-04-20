<?php
include("db.php");

// 1. Add remarks column if not exists
$check_remarks = $conn->query("SHOW COLUMNS FROM gate_passes LIKE 'remarks'");
if($check_remarks->num_rows == 0){
    $conn->query("ALTER TABLE gate_passes ADD COLUMN remarks TEXT DEFAULT NULL AFTER return_date");
    echo "Added 'remarks' column to gate_passes table.<br>";
} else {
    echo "'remarks' column already exists.<br>";
}

// 2. Update status column to include all stages
// Stages: Pending HOD Approval -> Pending Hostel In-Charge Approval -> Pending Warden Approval -> Approved/Rejected
$conn->query("ALTER TABLE gate_passes MODIFY COLUMN status ENUM('Pending HOD Approval', 'Pending Hostel In-Charge Approval', 'Pending Warden Approval', 'Approved', 'Rejected') DEFAULT 'Pending HOD Approval'");
echo "Updated 'status' column enum values.<br>";

echo "Schema update completed.";
?>
