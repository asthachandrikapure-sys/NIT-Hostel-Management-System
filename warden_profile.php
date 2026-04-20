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

$user_id = $_SESSION['user_id'] ?? 0;
if($user_id == 0) {
    // Fallback if user_id not in session, get from db by email safely
    $email = $_SESSION['email'];
    $u_stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $u_stmt->bind_param("s", $email);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result();
    if($u_row = $u_res->fetch_assoc()) {
        $user_id = $u_row['id'];
        $_SESSION['user_id'] = $user_id;
    }
}

// Handle Update Profile
$msg = "";
if(isset($_POST['update_profile'])){
    $hostel_name = trim($_POST['hostel_name']);
    $contact_no = trim($_POST['contact_no']);

    // Check if record exists in wardens_info safely
    $c_stmt = $conn->prepare("SELECT id FROM wardens_info WHERE user_id=?");
    $c_stmt->bind_param("i", $user_id);
    $c_stmt->execute();
    $check = $c_stmt->get_result();
    
    if($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE wardens_info SET hostel_name=?, contact_no=? WHERE user_id=?");
        $stmt->bind_param("ssi", $hostel_name, $contact_no, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO wardens_info (user_id, hostel_name, contact_no) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $hostel_name, $contact_no);
    }
    
    if($stmt->execute()){
         $msg = "<p style='color:green; text-align:center;'>Profile updated successfully!</p>";
    } else {
         $msg = "<p style='color:red; text-align:center;'>Error updating profile: " . $conn->error . "</p>";
    }
}

// Fetch Warden Data safely
$query = "SELECT u.fullname, u.email, w.hostel_name, w.contact_no 
          FROM users u 
          LEFT JOIN wardens_info w ON u.id = w.user_id 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$warden_data = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden Profile - Dashboard</title>
    <link rel="stylesheet" href="warden.css">
    <style>
        .profile-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }
        .form-control[readonly] {
            background-color: #f5f5f5;
            color: #777;
            cursor: not-allowed;
        }
        .btn-update {
            background: #d84315;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            width: 100%;
            transition: background 0.3s;
        }
        .btn-update:hover {
            background: #bf360c;
        }
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
            <a href="warden_attendance.php">Mark Attendance</a>
            <a href="warden_complaints.php">Manage Complaints</a>
            <a href="warden_notifications.php">My Notifications</a>
            <a href="warden_reports.php">Monthly Reports</a>
            <a href="warden_profile.php" style="background: #fbe9e7;">Warden Profile</a>
            <a href="warden_duties.php">My Duties</a>
            <a href="warden_transport.php">Transport Assistance</a>
        </div>

        <div class="content">
            <h1>My Profile</h1>
            <p>View and update your personal and hostel details.</p>
            
            <?php if(!empty($msg)) echo $msg; ?>

            <div class="profile-container">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($warden_data['fullname']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($warden_data['email']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Assigned Hostel / Block</label>
                        <input type="text" name="hostel_name" class="form-control" value="<?php echo htmlspecialchars($warden_data['hostel_name'] ?? ''); ?>" placeholder="e.g. Block A">
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_no" class="form-control" value="<?php echo htmlspecialchars($warden_data['contact_no'] ?? ''); ?>" placeholder="Phone number">
                    </div>
                    <button type="submit" name="update_profile" class="btn-update">Save Changes</button>
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
