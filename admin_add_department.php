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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "admin"){
    header("Location: login.php");
    exit();
}

$msg = "";

// Handle Department & HOD Creation
if(isset($_POST['add_dept'])){
    $dept_name = trim($_POST['department_name']);
    $college = $_POST['college_type'];
    $hod_name = trim($_POST['hod_fullname']);
    $hod_email = trim($_POST['hod_email']);
    $hod_password = trim($_POST['hod_password']);
    
    // 1. Check if user already exists
    $check_user = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_user->bind_param("s", $hod_email);
    $check_user->execute();
    if($check_user->get_result()->num_rows > 0){
        $msg = "<p style='color:red;'>Error: Email already registered.</p>";
    } else {
        // 2. Create HOD User
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role, college_name, department_name) VALUES (?, ?, ?, 'hod', ?, ?)");
        $stmt->bind_param("sssss", $hod_name, $hod_email, $hod_password, $college, $dept_name);
        
        if($stmt->execute()){
            $msg = "<p style='color:green;'>Department '$dept_name' added and HOD account created successfully!</p>";
        } else {
            $msg = "<p style='color:red;'>Error: " . $conn->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Department - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .form-container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 600px; margin: 20px auto; }
        input, select { padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; width: 100%; box-sizing: border-box; }
        .btn-submit { background: #0b7a3f; color: white; border: none; padding: 14px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 15px; font-size: 16px; }
        .btn-submit:hover { background: #096332; }
    </style>
    <link rel="stylesheet" href="responsive.css">
<script>
    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>
</head>
<body>

    <header>
        <img src="nit_logo.png.jpg" alt="NIT Logo">
        Admin Dashboard
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Modules</h2>
            <a href="admin.php">Dashboard Home</a>
            <a href="admin_student_info.php">Student List</a>
            <a href="admin_manage_users.php">Manage Staff Users</a>
            <a href="admin_add_department.php" style="background: #e7f3ea;">Add Department</a>
            <a href="admin_gatepass.php">Gate Pass Approvals</a>
        </div>

        <div class="content">
            <h1>Create New Department</h1>
            <p>Add a new academic department and generate its HOD login access.</p>
            
            <?php echo $msg; ?>

            <div class="form-container">
                <form method="POST" action="">
                    <h3 style="margin-top:0;">Department Details</h3>
                    <select name="college_type" required>
                        <option value="">-- Select College --</option>
                        <option value="Polytechnic">Polytechnic</option>
                        <option value="Engineering">Engineering</option>
                    </select>
                    <input type="text" name="department_name" placeholder="Department Name (e.g. Computer Science)" required>
                    
                    <h3 style="margin-top:20px;">HOD Account Details</h3>
                    <input type="text" name="hod_fullname" placeholder="HOD Full Name" required>
                    <input type="email" name="hod_email" placeholder="HOD Email (Login ID)" required>
                    <input type="text" name="hod_password" placeholder="HOD Password" required>
                    
                    <button type="submit" name="add_dept" class="btn-submit">Add Department & Generate Login</button>
                </form>
            </div>
        </div>

    </div>

    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    <script>
    function toggleSidebar() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('active');
        document.body.classList.toggle('no-scroll');
    }
    function closeSidebar() {
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.classList.remove('no-scroll');
    }
    document.querySelectorAll('.sidebar a').forEach(function(link) {
        link.addEventListener('click', closeSidebar);
    });
    var overlay = document.getElementById('sidebarOverlay');
    if (overlay) overlay.addEventListener('click', closeSidebar);
    </script>
</body>
</html>
