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

// Handle Update Request
if(isset($_POST['update_student'])){
    $user_id = $_POST['user_id'];
    $npoly_id = trim($_POST['npoly_id'] ?? ''); // Added missing npoly_id
    $room_no = trim($_POST['room_no'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '');
    $college_name = !empty(trim($_POST['college_type'] ?? '')) ? trim($_POST['college_type']) : null;
    $parent_name = trim($_POST['parent_name'] ?? '');
    $parent_mobile = trim($_POST['parent_mobile'] ?? '');
    $student_mobile = trim($_POST['student_mobile'] ?? '');

    // Check if record exists in students_info safely
    $check_stmt = $conn->prepare("SELECT id FROM students_info WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check = $check_stmt->get_result();
    
    if($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE students_info SET npoly_id=?, room_no=?, academic_year=?, college_name=?, parent_name=?, parent_mobile=?, student_mobile=? WHERE user_id=?");
        $stmt->bind_param("sssssssi", $npoly_id, $room_no, $academic_year, $college_name, $parent_name, $parent_mobile, $student_mobile, $user_id);
    } else {
        $student_name = $_POST['student_name'] ?? 'Unknown';
        $stmt = $conn->prepare("INSERT INTO students_info (user_id, student_name, npoly_id, room_no, academic_year, college_name, parent_name, parent_mobile, student_mobile) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssss", $user_id, $student_name, $npoly_id, $room_no, $academic_year, $college_name, $parent_name, $parent_mobile, $student_mobile);
    }
    
    if($stmt->execute()){
         $msg_text = "Student profile for " . ($_POST['student_name'] ?? 'your ward') . " has been updated (Room: $room_no, College: $college_name).";
         
         $msg = "<div style='background: #e8f5e9; padding: 15px; border-radius: 8px; border: 1px solid #4caf50; margin-bottom: 20px; text-align: center;'>";
         $msg .= "<p style='color:green; font-weight:bold;'>✔️ Student info updated successfully!</p>";
         $msg .= "<button class='btn-notify' onclick='logNotification($user_id, \"StudentInfo\", \"" . addslashes($msg_text) . "\", \"Parent\"); this.disabled=true; this.innerText=\"✓ Notified\";'>🔔 Send Notification</button>";
         $msg .= "</div>";
    } else {
         $msg = "<p style='color:red;'>Error updating info.</p>";
    }
}

// Fetch Students Data
$query = "SELECT u.id, u.fullname, u.email, u.password, s.npoly_id, s.room_no, s.department_name, s.academic_year, s.college_name, s.parent_name, s.parent_mobile, s.student_mobile, s.profile_photo 
          FROM users u 
          LEFT JOIN students_info s ON u.id = s.user_id 
          WHERE u.role = 'student'";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Info - Warden Dashboard</title>
    <link rel="stylesheet" href="warden.css">
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
            background-color: #d84315; /* Warden typical theme color in Nit Project ? Actually standard is dark orange for warden here usually or we stick to existing */
            /* I'll use warden.css colors but let's define a distinct header */
            color: white;
        }
        input[type="text"] {
            width: 90%;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .update-btn {
            background: #d84315;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .update-btn:hover {
            background: #bf360c;
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
            <a href="warden.php">Dashboard Home</a>
            <a href="warden_student_info.php" style="background: #fbe9e7;">Update Student Info</a>
            <a href="warden_mess_fee.php">Update Mess Fee Record</a>
            <a href="warden_gatepass.php">Approve/Reject Gate Pass</a>
            <a href="warden_attendance.php">Mark Attendance</a>
            <a href="warden_complaints.php">Manage Complaints</a>
            <a href="warden_notifications.php">My Notifications</a>
            <a href="warden_reports.php">Monthly Reports</a>
            <a href="warden_profile.php">Warden Profile</a>
            <a href="warden_duties.php">My Duties</a>
            <a href="warden_transport.php">Transport Assistance</a>
        </div>

        <div class="content">
            <h1>Update Student Info</h1>
            <p>Manage room allocations and academic details for students.</p>
            
            <?php if(isset($msg)) echo $msg; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if(!empty($row['profile_photo'])): ?>
                                                <img src="uploads/profile_photos/<?php echo $row['profile_photo']; ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;">
                                            <?php else: ?>
                                                <div style="width: 40px; height: 40px; border-radius: 50%; background: #eee; border: 1px solid #ddd;"></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="view_student_details.php?id=<?php echo $row['id']; ?>" style="text-decoration:none; color:#d84315; font-weight:bold;">
                                                <?php echo htmlspecialchars($row['fullname']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <button class="update-btn" onclick="window.location.href='view_student_details.php?id=<?php echo $row['id']; ?>'">View</button>
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
