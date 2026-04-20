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

// Protect Page - Management only
if(!isset($_SESSION['username']) || $_SESSION['role'] != "management"){
    header("Location: login.php");
    exit();
}

// Staff Activity Log (recent actions)
$staff_actions_query = "SELECT c.id, c.title, c.handled_by, c.status, c.action_taken, c.created_at, c.college_name
                        FROM complaints c 
                        WHERE c.handled_by IS NOT NULL 
                        ORDER BY c.created_at DESC";
$staff_actions = $conn->query($staff_actions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Actions - Super Admin Dashboard</title>
    <link rel="stylesheet" href="management.css">
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
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> 👋</span>
        <span class="role-badge">Super Admin</span>
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Super Admin</h2>
            <a href="management_dashboard.php"><span class="icon">📊</span> Dashboard</a>
            <a href="management_complaints.php"><span class="icon">📝</span> All Complaints</a>
            <a href="management_staff_actions.php" class="active"><span class="icon">👥</span> Staff Actions</a>
            <a href="logout.php" style="margin-top:30px;color:#f87171;"><span class="icon">🚪</span> Logout</a>
        </div>

        <div class="content">
            <h1>Staff Action Log</h1>
            <p class="subtitle">Monitor actions taken by Admin, Principal, and Hostel In-Charge on complaints.</p>

            <div class="section">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Complaint</th>
                                <th>Handled By</th>
                                <th>Action</th>
                                <th>Status Set</th>
                                <th>College</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($staff_actions && $staff_actions->num_rows > 0): ?>
                                <?php while($sa = $staff_actions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($sa['created_at'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($sa['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($sa['handled_by']); ?></td>
                                        <td>
                                            <?php if($sa['action_taken'] == 'yes'): ?>
                                                <span style="color:#34d399;">✅ Yes</span>
                                            <?php else: ?>
                                                <span style="color:#f87171;">❌ No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if($sa['status'] == 'Resolved') echo '<span class="tag" style="background:rgba(16,185,129,0.15);color:#34d399;">Resolved</span>';
                                            elseif($sa['status'] == 'In Progress') echo '<span class="tag tag-progress">In Progress</span>';
                                            else echo '<span class="tag tag-open">' . htmlspecialchars($sa['status']) . '</span>';
                                            ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($sa['college_name'] ?? 'N/A'); ?></small></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="no-data">No staff actions logged yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
