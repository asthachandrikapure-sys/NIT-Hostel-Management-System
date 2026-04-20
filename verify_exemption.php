<?php
include "db.php";
include "check_late_fees.php";

echo "1. VERIFY AUTOMATED FEE GENERATION\n";
// Ensure there's at least one student
$student = $conn->query("SELECT id FROM users WHERE role = 'student' LIMIT 1")->fetch_assoc();
if($student){
    $sid = $student['id'];
    $current_month = date('M Y');
    // Clear existing fee for current month if any to test automation
    $conn->query("DELETE FROM mess_fees WHERE user_id = $sid AND month_year = '$current_month'");
    
    // Run automation
    check_and_apply_late_fees($conn);
    
    $check = $conn->query("SELECT id, amount, status FROM mess_fees WHERE user_id = $sid AND month_year = '$current_month'")->fetch_assoc();
    if($check){
        echo "Auto-generated fee for $current_month found: Rs {$check['amount']} (Status: {$check['status']})\n";
    } else {
        echo "FAILED: Auto-generated fee not found.\n";
    }
} else {
    echo "No students found to test automation.\n";
}

echo "\n2. VERIFY EXEMPTION BYPASS LOGIC\n";
// Create a fake late scenario
$past_month = date('M Y', strtotime('-1 month'));
$conn->query("DELETE FROM mess_fees WHERE user_id = $sid AND month_year = '$past_month'");
$conn->query("INSERT INTO mess_fees (user_id, student_name, month_year, amount, status, exemption_status) 
              VALUES ($sid, 'Test Student', '$past_month', 3500, 'Pending', 'Approved')");

echo "Inserted Approved exemption for $past_month. Running late fee logic...\n";
check_and_apply_late_fees($conn);

$res = $conn->query("SELECT amount, late_fee_added FROM mess_fees WHERE user_id = $sid AND month_year = '$past_month'")->fetch_assoc();
echo "Fee amount for $past_month: Rs {$res['amount']} (Late Fee Added: {$res['late_fee_added']})\n";
if($res['late_fee_added'] == 0){
    echo "SUCCESS: Late fee bypassed due to Approved exemption.\n";
} else {
    echo "FAILED: Late fee was still added despite Approved exemption.\n";
}

// Clean up
$conn->query("DELETE FROM mess_fees WHERE user_id = $sid");
?>
