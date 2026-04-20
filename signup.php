<?php
session_start();
include("db.php");
include("check_late_fees.php");

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

// Fetch colleges and departments for the form
$colleges_res = $conn->query("SELECT * FROM colleges");
$departments_raw = $conn->query("SELECT d.name, c.name as college_name FROM departments d JOIN colleges c ON d.college_id = c.id");
$depts_data = [];
while($d = $departments_raw->fetch_assoc()){
    $depts_data[$d['college_name']][] = $d['name'];
}
$depts_json = json_encode($depts_data);

if(isset($_POST['signup'])){

    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'student'; // Force role to student for signup
    $college_name = $_POST['college_type'];
    $department_name = $_POST['department'];
    // Derive college_type
    $college_type = null;
    if(stripos($college_name, 'Polytechnic') !== false || stripos($college_name, 'poly') !== false) $college_type = 'poly';
    elseif(stripos($college_name, 'Engineering') !== false || stripos($college_name, 'engg') !== false) $college_type = 'engineering';

    if($password != $confirm_password){
        $error = "Passwords do not match!";
    }
    else{
        // Check if email already exists using prepared statement
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();

        if($check_res->num_rows > 0){
            $error = "Email already registered! Please use another one.";
        } else {
            // Insert into users table using prepared statement
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, college_name, department_name, college_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $fullname, $email, $password, $role, $college_name, $department_name, $college_type);

            if($stmt->execute()){
                $user_id = $stmt->insert_id;
                
                // If student, create record in students_info safely
                if($role == 'student'){
                    $si_stmt = $conn->prepare("INSERT INTO students_info (user_id, student_name, college_name, department_name, college_type) VALUES (?, ?, ?, ?, ?)");
                    $si_stmt->bind_param("issss", $user_id, $fullname, $college_name, $department_name, $college_type);
                    $si_stmt->execute();
                }

                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $fullname;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;

                echo "<script>alert('Register successfully!'); window.location='student.php';</script>";
                exit();
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

include("signup.html");
?>