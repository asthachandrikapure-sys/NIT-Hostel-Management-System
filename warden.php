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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "warden"){
    header("Location: login.php");
    exit();
}

include("db.php");

// Fetch stats for Warden
// Get Warden's specific information from session (set during login)
$college = $_SESSION['college_name'] ?? '';
$dept = $_SESSION['department_name'] ?? '';

// 1. Total Students in Warden's jurisdiction
$ts_stmt = $conn->prepare("SELECT COUNT(*) as total FROM students_info WHERE (college_name = ? OR ? = '') AND (department_name = ? OR ? = '')");
$ts_stmt->bind_param("ssss", $college, $college, $dept, $dept);
$ts_stmt->execute();
$total_students = $ts_stmt->get_result()->fetch_assoc()['total'];

// 2. Pending Gate Passes (Level 4 - Warden Approval)
$pg_stmt = $conn->prepare("SELECT COUNT(*) as total FROM gate_passes g 
                     JOIN students_info s ON g.user_id = s.user_id 
                     WHERE g.status = 'Pending Warden Approval' 
                     AND (s.college_name = ? OR ? = '') 
                     AND (s.department_name = ? OR ? = '')");
$pg_stmt->bind_param("ssss", $college, $college, $dept, $dept);
$pg_stmt->execute();
$pending_gp = $pg_stmt->get_result()->fetch_assoc()['total'];

// 3. Open Complaints
$oc_stmt = $conn->prepare("SELECT COUNT(*) as total FROM complaints 
                     WHERE (status = 'Open' OR status = 'In Progress')
                     AND (college_name = ? OR ? = '') 
                     AND (department_name = ? OR ? = '')");
$oc_stmt->bind_param("ssss", $college, $college, $dept, $dept);
$oc_stmt->execute();
$open_complaints = $oc_stmt->get_result()->fetch_assoc()['total'];

include("warden.html");
?>