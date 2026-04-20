<?php
// Function to check and update fees based on attendance and late fine
function check_and_apply_late_fees($conn, $user_id = null) {
    $current_date = new DateTime();
    $today_day = (int)$current_date->format('j');
    $current_month_year = $current_date->format('M Y');

    // 1. AUTO-GENERATION (Optional fallback if no records exist)
    $student_res = $conn->query("SELECT id, fullname FROM users WHERE role = 'student'");
    while($student = $student_res->fetch_assoc()){
        $sid = $student['id'];
        $fname = $student['fullname'];
        $check_stmt = $conn->prepare("SELECT id FROM mess_fees WHERE user_id = ? AND month_year = ?");
        $check_stmt->bind_param("is", $sid, $current_month_year);
        $check_stmt->execute();
        if($check_stmt->get_result()->num_rows == 0){
            $ins_stmt = $conn->prepare("INSERT INTO mess_fees (user_id, student_name, month_year, amount, base_amount, status) VALUES (?, ?, ?, 3500.00, 3500.00, 'Pending')");
            $ins_stmt->bind_param("iss", $sid, $fname, $current_month_year);
            $ins_stmt->execute();
        }
    }

    // 2. RECALCULATE ATTENDANCE-BASED FEES & LATE FINES
    $query = "SELECT id, user_id, month_year, status FROM mess_fees WHERE status != 'Paid'";
    if ($user_id !== null) $query .= " AND user_id = " . intval($user_id);
    
    $fees_res = $conn->query($query);
    while($fee = $fees_res->fetch_assoc()){
        $fee_id = $fee['id'];
        $sid = $fee['user_id'];
        $month_year = $fee['month_year']; // e.g. "Mar 2026"

        // a) Count Attendance for that Month
        try {
            $month_date = DateTime::createFromFormat('M Y', $month_year);
            if (!$month_date) continue;
            
            $start_date = $month_date->format('Y-m-01');
            $end_date = $month_date->format('Y-m-t');

            $count_res = $conn->query("SELECT COUNT(*) as days FROM attendance WHERE user_id = $sid AND status = 'Present' AND date BETWEEN '$start_date' AND '$end_date'");
            $days = $count_res->fetch_assoc()['days'];

            // Tier logic
            $base_fee = 0;
            if ($days >= 16) $base_fee = 3500;
            elseif ($days >= 1) $base_fee = 1750;
            else $base_fee = 0; // Or keep existing amount if no attendance marked yet? 
                               // Let's stick to user rules: 1-15 = 1750, 16-30 = 3500.

            // b) Check Late Fine
            $fine = 0;
            $fee_month = (int)$month_date->format('n');
            $fee_year = (int)$month_date->format('Y');
            $curr_month = (int)$current_date->format('n');
            $curr_year = (int)$current_date->format('Y');

            $is_late = false;
            if ($curr_year > $fee_year || ($curr_year == $fee_year && $curr_month > $fee_month)) {
                $is_late = true;
            } elseif ($curr_year == $fee_year && $curr_month == $fee_month && $today_day > 10) {
                $is_late = true;
            }

            if ($is_late) $fine = 100;

            $total_amount = $base_fee + $fine;
            $late_fee_added = $fine > 0 ? 1 : 0;

            // Update record
            $upd = $conn->prepare("UPDATE mess_fees SET base_amount = ?, amount = ?, late_fee_added = ? WHERE id = ?");
            $upd->bind_param("ddii", $base_fee, $total_amount, $late_fee_added, $fee_id);
            $upd->execute();

            // Auto-mark ₹0 fees as Paid (0 attendance = no charge)
            if ($total_amount == 0) {
                $conn->query("UPDATE mess_fees SET status='Paid', paid_at=NOW() WHERE id=$fee_id AND status != 'Paid'");
            }
        } catch (Exception $e) {
            continue;
        }
    }
}

// Run universally upon inclusion
if (isset($conn)) {
    check_and_apply_late_fees($conn);
}
?>
