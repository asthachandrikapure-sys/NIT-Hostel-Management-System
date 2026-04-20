<?php
session_start();

// Prevent browser caching for security
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/* Protect Page */
if(!isset($_SESSION['username']) || $_SESSION['role'] != "hod"){
    header("Location: login.php");
    exit();
}

include("db.php");

$dept = $_SESSION['department_name'];
$college = $_SESSION['college_name'];

// Fetch stats for dashboard
// 1. Total Students in this department
$total_students_query = "SELECT COUNT(*) as total FROM students_info WHERE department_name = '$dept' AND college_name = '$college'";
$total_students_result = $conn->query($total_students_query);
$total_students = ($total_students_result) ? $total_students_result->fetch_assoc()['total'] : 0;

// 2. Pending Gate Passes for this department
$pending_gp_query = "SELECT COUNT(*) as total FROM gate_passes g 
                     JOIN students_info s ON g.user_id = s.user_id 
                     WHERE g.status = 'Pending HOD Approval' AND s.department_name = '$dept' AND s.college_name = '$college'";
$pending_gp_result = $conn->query($pending_gp_query);
$pending_gp = ($pending_gp_result) ? $pending_gp_result->fetch_assoc()['total'] : 0;

include("hod.html");
?>
