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

// Handle Attendance Marking
if(isset($_POST['mark_attendance'])){
    $att_date = $_POST['attendance_date'];
    
    // Check if attendance already taken for this date
    $check_query = $conn->prepare("SELECT id FROM attendance WHERE date = ?");
    $check_query->bind_param("s", $att_date);
    $check_query->execute();
    $check_result = $check_query->get_result();
    
    if($check_result->num_rows > 0) {
        // Update existing attendance
        if(isset($_POST['status']) && is_array($_POST['status'])) {
            foreach($_POST['status'] as $user_id => $status) {
                $student_name = $_POST['student_name'][$user_id]; 
                $u_stmt = $conn->prepare("SELECT college_name, department_name FROM users WHERE id = ?");
                $u_stmt->bind_param("i", $user_id);
                $u_stmt->execute();
                $user_row = $u_stmt->get_result()->fetch_assoc();
                $college = $user_row['college_name'] ?? null;
                $dept = $user_row['department_name'] ?? null;

                $stmt = $conn->prepare("INSERT INTO attendance (user_id, student_name, college_name, department_name, date, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $user_id, $student_name, $college, $dept, $att_date, $status);
                $stmt->execute();
            }
            $msg = "<p style='color:green; text-align:center;'>Attendance recorded for $att_date</p>";
        }
    }
}

// Default to today
$display_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch students and their attendance for the selected date
$query = "SELECT u.id, u.fullname, s.room_no, s.course_type, s.academic_year, a.status 
          FROM users u 
          LEFT JOIN students_info s ON u.id = s.user_id 
          LEFT JOIN attendance a ON u.id = a.user_id AND a.date = '$display_date'
          WHERE u.role = 'student'" . (isset($_SESSION['college_type']) && $_SESSION['college_type'] ? " AND s.college_type = '" . $conn->real_escape_string($_SESSION['college_type']) . "'" : "") . "
          ORDER BY s.room_no ASC, u.fullname ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; overflow-x: auto; }
        .controls { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px;}
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #0b7a3f; color: white; }
        .date-picker { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; margin-right: 10px; }
        .btn-view { background: #2196F3; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .btn-view:hover { background: #0b7dda; }
        .btn-save { background: #0b7a3f; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; display: block; width: 100%; margin-top: 20px; }
        .btn-save:hover { background: #095d30; }
        
        .rad-pres { accent-color: #4caf50; }
        .rad-abs { accent-color: #f44336; }
        .rad-leave { accent-color: #ff9800; }
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
            <a href="admin_student_info.php">Update Student Info</a>
            <a href="admin_mess_fee.php">Update Mess Fee Record</a>
            <a href="admin_gatepass.php">Approve/Reject Gate Pass</a>

            <a href="admin_complaints.php">View Complaints</a>
            <a href="admin_attendance.php" style="background: #e7f3ea;">View Attendance Record</a>

            <a href="admin_reports.php">Global Reports</a>
            <a href="admin_warden_duties.php">Assign Duties</a>
</div>

        <div class="content">
            <h1>Daily Attendance</h1>
            <p>View and mark attendance for all hostel students.</p>
            
            <?php if(isset($msg)) echo $msg; ?>

            <div class="controls">
                <form method="GET" action="" style="display:flex; align-items:center;">
                    <label style="margin-right: 10px; font-weight:bold;">Select Date:</label>
                    <input type="date" name="date" class="date-picker" value="<?php echo htmlspecialchars($display_date); ?>">
                    <button type="submit" class="btn-view">Fetch</button>
                </form>
            </div>

            <div class="table-container">
                <form method="POST" action="">
                    <input type="hidden" name="attendance_date" value="<?php echo htmlspecialchars($display_date); ?>">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Room No.</th>
                                <th>College</th>
                                <th>Year</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Leave</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <input type="hidden" name="student_name[<?php echo $row['id']; ?>]" value="<?php echo htmlspecialchars($row['fullname']); ?>">
                                        <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['room_no'] ?? 'Unassigned'); ?></td>
                                        <td><?php echo htmlspecialchars($row['course_type'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['academic_year'] ?? 'N/A'); ?></td>
                                        <td>
                                            <input type="radio" name="status[<?php echo $row['id']; ?>]" value="Present" class="rad-pres" 
                                                <?php echo ($row['status'] == 'Present' || empty($row['status'])) ? 'checked' : ''; ?>>
                                        </td>
                                        <td>
                                            <input type="radio" name="status[<?php echo $row['id']; ?>]" value="Absent" class="rad-abs"
                                                <?php echo ($row['status'] == 'Absent') ? 'checked' : ''; ?>>
                                        </td>
                                        <td>
                                            <input type="radio" name="status[<?php echo $row['id']; ?>]" value="Leave" class="rad-leave"
                                                <?php echo ($row['status'] == 'Leave') ? 'checked' : ''; ?>>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align:center;">No students found in the database.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if($result->num_rows > 0): ?>
                        <button type="submit" name="mark_attendance" class="btn-save">Save Attendance for <?php echo date('d M Y', strtotime($display_date)); ?></button>
                    <?php endif; ?>
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
