<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include("db.php"); // Make sure db.php defines $conn

// Prevent browser caching of login page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// If already logged in, redirect to respective dashboard
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if($role == 'management') header("Location: management_dashboard.php");
    elseif($role == 'admin') header("Location: admin.php");
    elseif($role == 'student') header("Location: student.php");
    elseif($role == 'warden') header("Location: warden.php");
    elseif($role == 'hod') header("Location: hod.php");
    elseif($role == 'principal_poly' || $role == 'principal_engg') header("Location: principal.php");
    elseif($role == 'incharge_poly' || $role == 'incharge_engg') header("Location: hostel_incharge.php");
    else header("Location: dashboard.php");
    exit();
}

$error = "";

// Check if form submitted
if(isset($_POST['login'])) {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    // Prepare statement to fetch user by email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Check role compatibility
        $isValidRole = false;
        $dbRole = $row['role'];
        if($role == 'management' && $dbRole == 'management') $isValidRole = true;
        elseif($role == 'admin' && $dbRole == 'admin') $isValidRole = true;
        elseif($role == 'student' && $dbRole == 'student') $isValidRole = true;
        elseif($role == 'warden' && $dbRole == 'warden') $isValidRole = true;
        elseif($role == 'hod' && (strpos($dbRole, 'hod') !== false || strpos($dbRole, 'principal') !== false || strpos($dbRole, 'incharge') !== false)) $isValidRole = true;

        // Verify password and role
        if($isValidRole && $password == $row['password']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['fullname'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['college_name'] = $row['college_name'] ?? null;
            $_SESSION['department_name'] = $row['department_name'] ?? null;
            $_SESSION['college_type'] = $row['college_type'] ?? null;

            // Redirect based on role
            if($row['role'] == 'management') {
                header("Location: management_dashboard.php");
            } elseif($row['role'] == 'admin') {
                header("Location: admin.php");
            } elseif($row['role'] == 'student') {
                header("Location: student.php");
            } elseif($row['role'] == 'warden') {
                header("Location: warden.php");
            } elseif($row['role'] == 'hod') {
                header("Location: hod.php");
            } elseif($row['role'] == 'principal_poly' || $row['role'] == 'principal_engg') {
                header("Location: principal.php");
            } elseif($row['role'] == 'incharge_poly' || $row['role'] == 'incharge_engg') {
                header("Location: hostel_incharge.php");
            } else {
                header("Location: dashboard.php"); // default fallback
            }
            exit();
        } else {
            $error = "Invalid Email or Password!";
        }

    } else {
        $error = "Invalid Email or Password!";
    }

}

include("login.html");
?>