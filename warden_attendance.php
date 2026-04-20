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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "warden"){
    header("Location: login.php");
    exit();
}

// Fetch Students
$query = "SELECT u.id, u.fullname, s.room_no, s.college_name, s.academic_year, s.parent_mobile 
          FROM users u 
          LEFT JOIN students_info s ON u.id = s.user_id 
          WHERE u.role = 'student' 
          ORDER BY s.room_no ASC";
$result = $conn->query($query);

// Get Recent Absentees for Notification
$recent_absent_query = "SELECT a.id, a.user_id, a.student_name, a.date, s.parent_mobile 
                        FROM attendance a
                        LEFT JOIN students_info s ON a.user_id = s.user_id
                        WHERE a.status = 'Absent' AND a.date = CURDATE()
                        ORDER BY a.id DESC";
$absent_result = $conn->query($recent_absent_query);

// Handle Attendance Submission
$msg = "";
if(isset($_POST['submit_attendance'])){
    $date = $_POST['attendance_date'];
    
    // Check if attendance already marked for this date
    $check_date = $conn->prepare("SELECT id FROM attendance WHERE date = ? LIMIT 1");
    $check_date->bind_param("s", $date);
    $check_date->execute();
    $existing = $check_date->get_result();
    
    if($existing->num_rows > 0){
        $msg = "<p style='color:red; text-align:center;'>Attendance already marked for $date. If you need to edit, please contact DB Admin.</p>";
    } else {
        $success_count = 0;
        
        // Prepare statement outside loop for efficiency
        foreach($_POST['status'] as $user_id => $status) {
            $student_name = $_POST['student_name'][$user_id];
            
            $u_stmt = $conn->prepare("SELECT college_name, department_name FROM users WHERE id = ?");
            $u_stmt->bind_param("i", $user_id);
            $u_stmt->execute();
            $user_row = $u_stmt->get_result()->fetch_assoc();
            $college = $user_row['college_name'] ?? null;
            $dept = $user_row['department_name'] ?? null;

            $stmt = $conn->prepare("INSERT INTO attendance (user_id, student_name, college_name, department_name, date, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $user_id, $student_name, $college, $dept, $date, $status);
            if($stmt->execute()){
                $success_count++;
            }
        }
        
        $msg = "<p style='color:green; text-align:center;'>Attendance marked successfully for $success_count students on $date!</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Warden Dashboard</title>
    <link rel="stylesheet" href="warden.css">
    <style>
        .form-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            overflow-x: auto;
        }
        
        .date-picker {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            justify-content: center;
        }
        .date-picker label { font-weight: bold; }
        .date-picker input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #d84315; color: white; }
        
        .radio-group { display: flex; gap: 15px; }
        .radio-group label { cursor: pointer; display: flex; align-items: center; gap: 5px; }
        
        .btn-submit {
            background: #d84315;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            display: block;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
        }
        .btn-submit:hover { background: #bf360c; }
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
        .absent-notify-section { margin-top: 30px; }
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
            <a href="warden.php">Dashboard Home</a>
            <a href="warden_student_info.php">Update Student Info</a>
            <a href="warden_mess_fee.php">Update Mess Fee Record</a>
            <a href="warden_gatepass.php">Approve/Reject Gate Pass</a>
            <a href="warden_attendance.php" style="background: #fbe9e7;">Mark Attendance</a>
            <a href="warden_complaints.php">Manage Complaints</a>
            <a href="warden_notifications.php">My Notifications</a>
            <a href="warden_reports.php">Monthly Reports</a>
            <a href="warden_profile.php">Warden Profile</a>
            <a href="warden_duties.php">My Duties</a>
            <a href="warden_transport.php">Transport Assistance</a>
        </div>

        <div class="content">
            <h1>Mark Daily Attendance</h1>
            <p>Select a date and record attendance for all hostel students.</p>
            
            <?php echo $msg; ?>

            <div class="form-container">
                <form method="POST" action="">
                    
                    <div class="date-picker">
                        <label for="attendance_date">Select Date:</label>
                        <input type="date" id="attendance_date" name="attendance_date" value="<?php echo date('Y-m-d'); ?>" required max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Room No.</th>
                                <th>Student Name</th>
                                <th>College</th>
                                <th>Year</th>
                                <th>Attendance Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result->num_rows > 0): ?>
                                <?php 
                                    // Reset pointer if we need to iterate again, but doing once here
                                    while($row = $result->fetch_assoc()): 
                                        $uid = $row['id'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['room_no'] ?? 'Unassigned'); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['fullname']); ?></strong>
                                            <input type="hidden" name="student_name[<?php echo $uid; ?>]" value="<?php echo htmlspecialchars($row['fullname']); ?>">
                                        </td>
                                        <td><?php echo htmlspecialchars($row['college_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['academic_year'] ?? 'N/A'); ?></td>
                                        <td>
                                            <div class="radio-group">
                                                <label><input type="radio" name="status[<?php echo $uid; ?>]" value="Present" required checked> Present</label>
                                                <label><input type="radio" name="status[<?php echo $uid; ?>]" value="Absent"> Absent</label>
                                                <label><input type="radio" name="status[<?php echo $uid; ?>]" value="Leave"> Leave</label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align:center;">No students found to mark attendance.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                
                <?php if($result->num_rows > 0): ?>
                    <button type="submit" name="submit_attendance" class="btn-submit">Submit Attendance</button>
                <?php endif; ?>
                
            </form>
            </div>

            <?php if($absent_result->num_rows > 0): ?>
            <div class="absent-notify-section">
                <h2>Notify Parents of Absent Students (Today)</h2>
                <div class="form-container">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Date</th>
                                <th>Parent Mobile</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($abs = $absent_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($abs['student_name']); ?></td>
                                    <td><?php echo date('d-M-Y', strtotime($abs['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($abs['parent_mobile'] ?? 'Not Provided'); ?></td>
                                    <td>
                                        <?php 
                                            $msg_text = $abs['student_name'] . " was marked ABSENT for today's attendance (" . date('d-M-Y', strtotime($abs['date'])) . ").";
                                            $phone = preg_replace('/[^0-9]/', '', $abs['parent_mobile'] ?? '');
                                            if (strlen($phone) == 10) $phone = '91' . $phone;
                                            $wa_url = "https://wa.me/" . $phone . "?text=" . urlencode($msg_text);
                                        ?>
                                        <a href="<?php echo $wa_url; ?>" target="_blank" class="btn-notify" style="text-decoration:none;"
                                           onclick="logNotification(<?php echo $abs['user_id']; ?>, 'Attendance', '<?php echo addslashes($msg_text); ?>', 'Parent');">
                                           📲 WhatsApp
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
            <?php endif; ?>

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
