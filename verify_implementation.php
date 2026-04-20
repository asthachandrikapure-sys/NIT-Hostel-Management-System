<?php
include "db.php";
include "check_late_fees.php";

echo "1. VERIFY NPOLY ID\n";
$conn->query("UPDATE students_info SET npoly_id = 'NP-2026-001' WHERE id = 1");
$row = $conn->query("SELECT npoly_id FROM students_info WHERE id = 1")->fetch_assoc();
echo "NPoly ID saved as: " . ($row['npoly_id'] ?? 'NULL') . "\n";

echo "\n2. VERIFY GATE PASS FLOW\n";
$conn->query("INSERT INTO gate_passes (user_id, student_name, reason, leave_date, return_date) VALUES (1, 'Test Student', 'Test', '2026-03-20', '2026-03-22')");
$pass_id = $conn->insert_id;
$status = $conn->query("SELECT status FROM gate_passes WHERE id = $pass_id")->fetch_assoc()['status'];
echo "Initial Status: $status\n";

$conn->query("UPDATE gate_passes SET status = 'Pending Warden Approval' WHERE id = $pass_id");
$status = $conn->query("SELECT status FROM gate_passes WHERE id = $pass_id")->fetch_assoc()['status'];
echo "Status after HOD: $status\n";

$conn->query("UPDATE gate_passes SET status = 'Approved' WHERE id = $pass_id");
$status = $conn->query("SELECT status FROM gate_passes WHERE id = $pass_id")->fetch_assoc()['status'];
echo "Status after Warden: $status\n";

echo "\n3. VERIFY MESS FEE LOGIC\n";
$conn->query("DELETE FROM mess_fees");
// Add a pending fee for Jan 2026
$conn->query("INSERT INTO mess_fees (user_id, student_name, month_year, amount, status) VALUES (1, 'Test Student', 'Jan 2026', 3500, 'Pending')");
// Add a pending fee for Feb 2026
$conn->query("INSERT INTO mess_fees (user_id, student_name, month_year, amount, status) VALUES (1, 'Test Student', 'Feb 2026', 3500, 'Pending')");

// Run the script directly
check_and_apply_late_fees($conn);

$res = $conn->query("SELECT month_year, amount, late_fee_added FROM mess_fees ORDER BY id");
while($f = $res->fetch_assoc()){
    echo "Fee for {$f['month_year']}: Rs {$f['amount']} (Late added: {$f['late_fee_added']})\n";
}

// Clean up test data
$conn->query("DELETE FROM gate_passes WHERE id = $pass_id");
$conn->query("DELETE FROM mess_fees");
?>
