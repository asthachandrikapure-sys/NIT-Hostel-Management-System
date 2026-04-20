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

// Message handling from explicit redirects
if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'){
    $msg = "<div style='background: #ffebee; padding: 15px; border-radius: 8px; border: 1px solid #f44336; margin-bottom: 20px; text-align: center;'>";
    $msg .= "<p style='color:#f44336; font-weight:bold;'>🗑️ Student account deleted successfully.</p>";
    $msg .= "</div>";
}

// Fetch Students Data - filtered by college_type
$college_type = $_SESSION['college_type'] ?? null;
if ($college_type) {
    $query = "SELECT u.id, u.fullname, u.email, s.npoly_id, s.room_no, s.academic_year, s.college_name, s.parent_name, s.parent_mobile, s.student_mobile 
              FROM users u 
              LEFT JOIN students_info s ON u.id = s.user_id 
              WHERE u.role = 'student' AND s.college_type = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $college_type);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT u.id, u.fullname, u.email, s.npoly_id, s.room_no, s.academic_year, s.college_name, s.parent_name, s.parent_mobile, s.student_mobile 
              FROM users u 
              LEFT JOIN students_info s ON u.id = s.user_id 
              WHERE u.role = 'student'";
    $result = $conn->query($query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Info - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #0b7a3f;
            color: white;
        }
        input[type="text"] {
            width: 90%;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .update-btn {
            background: #0b7a3f;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .update-btn:hover {
            background: #095d30;
        }
        .btn-notify { 
            background: #1a73e8; 
            color: white; 
            padding: 6px 12px; 
            border-radius: 4px; 
            border: none;
            font-size: 12px; 
            font-weight: bold;
            cursor: pointer;
            display: inline-block;
        }
        .btn-notify:hover { background: #1557b0; }
        .btn-notify:disabled { background: #a0a0a0; cursor: default; }
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
        Welcome, <?php echo $_SESSION['username']; ?> 👋
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Modules</h2>
            <a href="admin.php">Dashboard Home</a>
            <a href="admin_student_info.php" style="background: #e7f3ea;">Update Student Info</a>
            <a href="admin_mess_fee.php">Update Mess Fee Record</a>
            <a href="admin_gatepass.php">Approve/Reject Gate Pass</a>

            <a href="admin_complaints.php">View Complaints</a>
            <a href="admin_attendance.php">View Attendance Record</a>
            <a href="admin_reports.php">Global Reports</a>
            <a href="admin_warden_duties.php">Assign Duties</a>
</div>

        <div class="content">
            <h1>Update Student Info</h1>
            <p>Manage room allocations and academic details for all registered students.</p>
            
            <?php if(isset($msg)) echo $msg; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <a href="view_student_details.php?id=<?php echo $row['id']; ?>" style="text-decoration:none; color:#0b7a3f; font-weight:bold;">
                                                <?php echo htmlspecialchars($row['fullname']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <button class="update-btn" onclick="window.location.href='view_student_details.php?id=<?php echo $row['id']; ?>'">Manage Info</button>
                                        </td>
                                    </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align:center;">No students registered yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
