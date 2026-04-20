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

// Ensure table exists for safety
$conn->query("CREATE TABLE IF NOT EXISTS warden_duties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    warden_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'Completed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (warden_id) REFERENCES users(id) ON DELETE CASCADE
)");

$user_id = $_SESSION['user_id'] ?? 0;
if($user_id == 0) {
    // Fallback if user_id not in session, get from db by email
    $email = $_SESSION['email'];
    $u_res = $conn->query("SELECT id FROM users WHERE email='$email'");
    if($u_row = $u_res->fetch_assoc()) {
        $user_id = $u_row['id'];
        $_SESSION['user_id'] = $user_id;
    }
}

// Handle Mark as Completed
$msg = "";
if(isset($_POST['mark_completed'])){
    $duty_id = $_POST['duty_id'];
    
    $stmt = $conn->prepare("UPDATE warden_duties SET status='Completed' WHERE id=? AND warden_id=?");
    $stmt->bind_param("ii", $duty_id, $user_id);
    
    if($stmt->execute()){
         $msg = "<p style='color:green; text-align:center;'>Duty marked as completed!</p>";
    } else {
         $msg = "<p style='color:red; text-align:center;'>Error updating duty: " . $conn->error . "</p>";
    }
}

// Fetch Pending Duties
$pending_query = "SELECT id, title, description, created_at 
                  FROM warden_duties 
                  WHERE warden_id = '$user_id' AND status = 'Pending' 
                  ORDER BY created_at ASC";
$pending_result = $conn->query($pending_query);

// Fetch Completed Duties History
$history_query = "SELECT id, title, description, created_at 
                  FROM warden_duties 
                  WHERE warden_id = '$user_id' AND status = 'Completed' 
                  ORDER BY created_at DESC";
$history_result = $conn->query($history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Duties - Warden Dashboard</title>
    <link rel="stylesheet" href="warden.css">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #d84315; color: white; }
        .btn-complete { background: #4caf50; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-complete:hover { background: #388e3c; }
        .status-completed { color: green; font-weight: bold; }
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
            <a href="warden_profile.php">Warden Profile</a>
            <a href="warden_duties.php" style="background: #fbe9e7;">My Duties</a>
        </div>

        <div class="content">
            <h1>My Assigned Duties</h1>
            <p>View administrative tasks assigned to you by the Hostel Admin.</p>
            
            <?php if(!empty($msg)) echo $msg; ?>

            <h2>Pending Tasks</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date Assigned</th>
                            <th>Task Title</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pending_result->num_rows > 0): ?>
                            <?php while($row = $pending_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                                    <td><small><?php echo nl2br(htmlspecialchars($row['description'])); ?></small></td>
                                    <td>
                                        <form method="POST" action="">
                                            <input type="hidden" name="duty_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="mark_completed" class="btn-complete">Mark Completed</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No pending tasks. Great job!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2>Completed Tasks History</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date Assigned</th>
                            <th>Task Title</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($history_result->num_rows > 0): ?>
                            <?php while($row = $history_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                                    <td><small><?php echo nl2br(htmlspecialchars($row['description'])); ?></small></td>
                                    <td><span class="status-completed">Completed</span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No completed tasks yet.</td></tr>
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
