<?php
session_start();

// Prevent browser caching for security
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
include("db.php");

// Protect page
if(!isset($_SESSION['username']) || $_SESSION['role'] != "student"){
    header("Location: login.php");
    exit();
}

// Receive AJAX POST
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $fee_id = intval($_POST['fee_id']);
    $payment_id = trim($_POST['payment_id']);
    $user_id = $_SESSION['user_id'];

    if(!empty($payment_id) && $fee_id > 0){
        $paid_amount = floatval($_POST['amount'] ?? 0);
        
        // Fetch bill amount to check if it's fully paid
        $check = $conn->prepare("SELECT amount FROM mess_fees WHERE id=?");
        $check->bind_param("i", $fee_id);
        $check->execute();
        $bill = $check->get_result()->fetch_assoc()['amount'];
        
        $new_status = ($paid_amount >= $bill) ? 'Paid' : 'Partial';

        $stmt = $conn->prepare("UPDATE mess_fees SET status=?, payment_id=?, paid_amount=?, paid_at=NOW() WHERE id=? AND user_id=?");
        $stmt->bind_param("ssdii", $new_status, $payment_id, $paid_amount, $fee_id, $user_id);

        if($stmt->execute() && $stmt->affected_rows > 0){
            // Generate Sequential Receipt ID: NIT-MESS-[YEAR]-[SERIAL]
            $year = date('Y');
            $prefix = "NIT-MESS-$year-";
            $res_count = $conn->query("SELECT MAX(receipt_id) as max_id FROM mess_fees WHERE receipt_id LIKE '$prefix%'");
            $max_row = $res_count->fetch_assoc();
            $next_num = 1;
            if ($max_row['max_id']) {
                $last_num = intval(substr($max_row['max_id'], -3));
                $next_num = $last_num + 1;
            }
            $receipt_id = $prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);

            $upd = $conn->prepare("UPDATE mess_fees SET receipt_id=? WHERE id=?");
            $upd->bind_param("si", $receipt_id, $fee_id);
            $upd->execute();
            
            $msg = ($new_status == 'Paid') ? "Payment recorded successfully! Receipt ID: $receipt_id" : "Partial payment of ₹$paid_amount recorded!";
            echo json_encode(["success" => true, "message" => $msg, "receipt_id" => $receipt_id]);
        } else {
            echo json_encode(["success" => false, "message" => "Could not update record."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid data."]);
    }
    exit();
}
?>
