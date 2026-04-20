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

if(!isset($_GET['id'])){
    header("Location: admin_manage_users.php");
    exit();
}

$user_id = (int)$_GET['id'];

// Get User Detail
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if(!$user){
    echo "User not found.";
    exit();
}

// Fetch additional info if it's a warden
$warden_info = null;
if($user['role'] == 'warden') {
    $w_stmt = $conn->prepare("SELECT * FROM wardens_info WHERE user_id=?");
    $w_stmt->bind_param("i", $user_id);
    $w_stmt->execute();
    $warden_info = $w_stmt->get_result()->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Staff Profile - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .profile-container {
            background: white; padding: 30px; border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-top: 20px;
            max-width: 600px; margin-left: auto; margin-right: auto;
        }
        .profile-header {
            text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;
        }
        .profile-header h2 { margin: 0; color: #333; }
        .profile-header .role-badge { 
            display: inline-block; background: #0b7a3f; color: white; 
            padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: bold;
            margin-top: 10px; text-transform: uppercase;
        }
        .detail-row {
            display: flex; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dotted #ccc;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label {
            flex: 0 0 150px; font-weight: bold; color: #555;
        }
        .detail-value {
            flex: 1; color: #222;
        }
        .btn-back {
            display: inline-block; background: #666; color: white; padding: 10px 20px;
            border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 20px;
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
        Staff Profile View
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Modules</h2>
            <a href="admin.php">Dashboard Home</a>
            <a href="admin_student_info.php">Student List</a>
            <a href="admin_manage_users.php" style="background: #e7f3ea;">Manage Staff Users</a>
            <a href="admin_gatepass.php">Gate Pass Approvals</a>
        </div>

        <div class="content">
            <h1 style="text-align:center;">Staff Details</h1>

            <div class="profile-container">
                <div class="profile-header">
                    <h2><?php echo htmlspecialchars($user['fullname']); ?></h2>
                    <div class="role-badge"><?php echo htmlspecialchars($user['role']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Email ID</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">System Password</div>
                    <div class="detail-value"><code style="background:#f5f5f5; padding:3px 6px; border-radius:4px;"><?php echo htmlspecialchars($user['password']); ?></code></div>
                </div>

                <?php if($user['college_name']): ?>
                <div class="detail-row">
                    <div class="detail-label">College Assigned</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['college_name']); ?></div>
                </div>
                <?php endif; ?>

                <?php if($user['department_name']): ?>
                <div class="detail-row">
                    <div class="detail-label">Department</div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['department_name']); ?></div>
                </div>
                <?php endif; ?>

                <?php if($warden_info): ?>
                <div class="detail-row">
                    <div class="detail-label">Hostel Name</div>
                    <div class="detail-value"><?php echo htmlspecialchars($warden_info['hostel_name']); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Contact No</div>
                    <div class="detail-value"><?php echo htmlspecialchars($warden_info['contact_no']); ?></div>
                </div>
                <?php endif; ?>

                <div class="detail-row">
                    <div class="detail-label">Account Created</div>
                    <div class="detail-value"><?php echo (!empty($user['created_at'])) ? date('d M Y, h:i A', strtotime($user['created_at'])) : 'N/A'; ?></div>
                </div>

                <div style="text-align:center;">
                    <a href="admin_manage_users.php" class="btn-back">⬅ Back to Staff List</a>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
