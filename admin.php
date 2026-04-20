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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "admin"){
    header("Location: login.php");
    exit();
}

include("db.php");

$college_type = $_SESSION['college_type'] ?? null;

// Fetch counts for dashboard summaries - filtered by college_type
if ($college_type) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM students_info WHERE college_type = ?");
    $stmt->bind_param("s", $college_type);
    $stmt->execute();
    $total_students = $stmt->get_result()->fetch_row()[0] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE college_type = ?");
    $stmt->bind_param("s", $college_type);
    $stmt->execute();
    $total_complaints = $stmt->get_result()->fetch_row()[0] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) FROM gate_passes WHERE college_type = ?");
    $stmt->bind_param("s", $college_type);
    $stmt->execute();
    $total_gatepasses = $stmt->get_result()->fetch_row()[0] ?? 0;
} else {
    $total_students = $conn->query("SELECT COUNT(*) FROM students_info")->fetch_row()[0] ?? 0;
    $total_complaints = $conn->query("SELECT COUNT(*) FROM complaints")->fetch_row()[0] ?? 0;
    $total_gatepasses = $conn->query("SELECT COUNT(*) FROM gate_passes")->fetch_row()[0] ?? 0;
}



include("admin.html");
?>