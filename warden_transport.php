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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "warden"){
    header("Location: login.php");
    exit();
}

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_driver'])) {
        $name = $_POST['name'];
        $phone = $_POST['phone_number'];
        $type = $_POST['vehicle_type'];
        $status = $_POST['availability_status'];
        $stmt = $conn->prepare("INSERT INTO transport_contacts (name, phone_number, vehicle_type, availability_status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $phone, $type, $status);
        $stmt->execute();
    } elseif (isset($_POST['delete_driver'])) {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM transport_contacts WHERE id = $id");
    } elseif (isset($_POST['update_status'])) {
        $id = intval($_POST['id']);
        $status = $conn->real_escape_string($_POST['availability_status']);
        $conn->query("UPDATE transport_contacts SET availability_status = '$status' WHERE id = $id");
    }
    header("Location: warden_transport.php");
    exit();
}

$drivers = $conn->query("SELECT * FROM transport_contacts ORDER BY availability_status ASC, name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Assistance - Warden Dashboard</title>
    <link rel="stylesheet" href="warden.css">
    <style>
        .transport-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .transport-table th, .transport-table td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .transport-table th { background: #f4f4f4; color:#333; }
        .btn { padding: 8px 12px; border: none; cursor: pointer; border-radius: 4px; color: white; display:inline-block; margin:2px; font-size:13px; }
        .btn-add { background: #4caf50; }
        .btn-delete { background: #f44336; }
        .btn-update { background: #2196f3; }
        .form-container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; max-width: 500px; }
        .form-container input, .form-container select { padding: 10px; margin: 5px 0 15px 0; width: 100%; border:1px solid #ccc; border-radius:4px;}
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
            <a href="warden_gatepass.php">Approve / Reject Gate Pass</a>
            <a href="warden_attendance.php">Mark Attendance</a>
            <a href="warden_notifications.php">My Notifications</a>
            <a href="warden_reports.php">Monthly Reports</a>
            <a href="warden_complaints.php">Manage Complaints</a>
            <a href="warden_profile.php">Warden Profile</a>
            <a href="warden_duties.php">My Duties</a>
            <a href="warden_transport.php" style="background: #e7f3ea;">Transport Assistance</a>
        </div>
        <div class="content">
            <h1>Transport Assistance Management</h1>
            <p style="color:#666;margin-bottom:20px;">Manage auto driver contact numbers for students.</p>

            <div class="form-container">
                <h3 style="margin-bottom:10px;">Add New Driver</h3>
                <form method="POST">
                    <input type="text" name="name" placeholder="Driver Name" required>
                    <input type="text" name="phone_number" placeholder="Phone Number" required>
                    <div style="display:flex;gap:10px;">
                        <select name="vehicle_type">
                            <option value="Auto Rickshaw">Auto Rickshaw</option>
                            <option value="Cab/Taxi">Cab/Taxi</option>
                            <option value="Van/Bus">Van/Bus</option>
                        </select>
                        <select name="availability_status">
                            <option value="Available">Available</option>
                            <option value="Not Available">Not Available</option>
                        </select>
                    </div>
                    <button type="submit" name="add_driver" class="btn btn-add" style="width:100%"><span class="icon">➕</span> Add Driver</button>
                </form>
            </div>

            <h3 style="margin-top:20px; margin-bottom:10px;">Driver List</h3>
            <div style="overflow-x:auto;">
                <table class="transport-table">
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Vehicle Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php if($drivers && $drivers->num_rows > 0): ?>
                        <?php while($row = $drivers->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['vehicle_type']); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <select name="availability_status" onchange="this.form.submit()" style="padding:4px;border-radius:4px;border:1px solid #ccc;">
                                        <option value="Available" <?php echo $row['availability_status']=='Available'?'selected':''; ?>>Available</option>
                                        <option value="Not Available" <?php echo $row['availability_status']=='Not Available'?'selected':''; ?>>Not Available</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this driver?');">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_driver" class="btn btn-delete">🗑 Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;padding:20px;">No drivers added yet.</td></tr>
                    <?php endif; ?>
                </table>
            </div>
            <br><br>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
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
