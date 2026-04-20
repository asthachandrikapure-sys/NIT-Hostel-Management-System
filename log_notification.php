<?php
session_start();

// Prevent browser caching for security
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Prevent browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include("db.php");

// Protect page
if(!isset($_SESSION['username'])){
    exit("Unauthorized");
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $user_id = $_SESSION['user_id'];
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : null;
    $type = $_POST['type']; // GatePass, Attendance, MessFee, Complaint
    $message = $_POST['message'];
    $sent_to = $_POST['sent_to']; // Parent / Student
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, student_id, type, message, sent_to) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user_id, $student_id, $type, $message, $sent_to);
    
    if($stmt->execute()){
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    exit();
}
?>
