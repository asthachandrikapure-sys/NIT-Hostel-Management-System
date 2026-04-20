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

// Handle Assign Duty
if(isset($_POST['assign_duty'])){
    $warden_id = $_POST['warden_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    $stmt = $conn->prepare("INSERT INTO warden_duties (warden_id, title, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $warden_id, $title, $description);
    
    if($stmt->execute()){
         $msg = "<p style='color:green; text-align:center;'>Duty successfully assigned to the warden!</p>";
    } else {
         $msg = "<p style='color:red; text-align:center;'>Error assigning duty: " . $conn->error . "</p>";
    }
}

// Fetch All Wardens for dropdown
$wardens_result = $conn->query("SELECT id, fullname, email FROM users WHERE role = 'warden'");

// Fetch Assigned Duties
$duties_query = "SELECT d.id, u.fullname, d.title, d.description, d.status, d.created_at 
                 FROM warden_duties d 
                 JOIN users u ON d.warden_id = u.id 
                 ORDER BY d.created_at DESC";
$duties_result = $conn->query($duties_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Duties - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; text-align: left; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        textarea.form-control { resize: vertical; height: 100px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #0b7a3f; color: white; }
        .btn-submit { background: #0b7a3f; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; }
        .btn-submit:hover { background: #095d30; }
        .status-completed { color: green; font-weight: bold; }
        .status-pending { color: orange; font-weight: bold; }
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
            <a href="admin_attendance.php">View Attendance Record</a>

            <a href="admin_reports.php">Global Reports</a>
            <a href="admin_warden_duties.php" style="background: #e7f3ea;">Assign Duties</a>
        </div>

        <div class="content">
            <h1>Assign Duties to Wardens</h1>
            <p>Allocate tasks and responsibilities to hostel wardens.</p>
            
            <?php if(isset($msg)) echo $msg; ?>

            <div class="card">
                <h3>Create New Duty Task</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Select Warden</label>
                        <select name="warden_id" class="form-control" required>
                            <option value="">-- Choose Warden --</option>
                            <?php if($wardens_result->num_rows > 0): ?>
                                <?php while($w = $wardens_result->fetch_assoc()): ?>
                                    <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['fullname']) . " (" . htmlspecialchars($w['email']) . ")"; ?></option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>No wardens registered.</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Task Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Weekly Room Inspection" required>
                    </div>
                    <div class="form-group">
                        <label>Task Description</label>
                        <textarea name="description" class="form-control" placeholder="Detail exactly what the warden needs to do..." required></textarea>
                    </div>
                    <button type="submit" name="assign_duty" class="btn-submit">Assign Duty</button>
                </form>
            </div>

            <div class="table-container">
                <h3>Assigned Duties History</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date Assigned</th>
                            <th>Warden Name</th>
                            <th>Duty Title</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($duties_result->num_rows > 0): ?>
                            <?php while($row = $duties_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><small><?php echo nl2br(htmlspecialchars($row['description'])); ?></small></td>
                                    <td>
                                        <?php if($row['status'] == 'Completed'): ?>
                                            <span class="status-completed">Completed</span>
                                        <?php else: ?>
                                            <span class="status-pending">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">No duties assigned yet.</td></tr>
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
