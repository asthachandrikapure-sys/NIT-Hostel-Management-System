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

// All Complaints for monitoring
$all_complaints_query = "SELECT c.id, c.user_id, u.fullname, c.student_name, c.college_name, c.college_type, c.title, c.description, c.concerning, c.status, c.action_taken, c.handled_by, c.admin_remarks, c.created_at 
                         FROM complaints c 
                         LEFT JOIN users u ON c.user_id = u.id 
                         ORDER BY c.created_at DESC";
$all_complaints = $conn->query($all_complaints_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Complaints - Super Admin Dashboard</title>
    <link rel="stylesheet" href="management.css">
    <style>
        .btn-view-remarks { background: #8b5cf6; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; }
        .btn-view-remarks:hover { background: #7c3aed; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 400px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); position: relative; }
        .modal-header { font-size: 16px; font-weight: bold; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; color: #333; }
        .modal-body { font-size: 14px; color: #555; max-height: 60vh; overflow-y: auto; background: #f9f9f9; padding: 10px; border-left: 3px solid #8b5cf6; border-radius: 4px; }
        .close-modal { position: absolute; top: 15px; right: 15px; cursor: pointer; font-size: 18px; color: #888; border: none; background: none; }
        .close-modal:hover { color: #f44336; }
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
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> 👋</span>
        <span class="role-badge">Super Admin</span>
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Super Admin</h2>
            <a href="management_dashboard.php"><span class="icon">📊</span> Dashboard</a>
            <a href="management_complaints.php" class="active"><span class="icon">📝</span> All Complaints</a>
            <a href="management_staff_actions.php"><span class="icon">👥</span> Staff Actions</a>
            <a href="logout.php" style="margin-top:30px;color:#f87171;"><span class="icon">🚪</span> Logout</a>
        </div>

        <div class="content">
            <h1>All Complaints Monitoring</h1>
            <p class="subtitle">View and filter all complaints across Polytechnic and Engineering colleges.</p>

            <div class="section">
                <div class="filter-bar">
                    <select id="filter-college" onchange="filterTable('complaints-table', this.value, 4)">
                        <option value="">All Colleges</option>
                        <option value="poly">Polytechnic</option>
                        <option value="engineering">Engineering</option>
                    </select>
                    <select id="filter-status" onchange="filterTable('complaints-table', this.value, 5)">
                        <option value="">All Status</option>
                        <option value="Open">Open</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                </div>

                <div class="table-container">
                    <table id="complaints-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Title</th>
                                <th>Concerning</th>
                                <th>College</th>
                                <th>Status</th>
                                <th>Admin Remark</th>
                                <th>Action Taken</th>
                                <th>Handled By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($all_complaints && $all_complaints->num_rows > 0): ?>
                                <?php while($row = $all_complaints->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['student_name'] ?? $row['fullname']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><small><?php echo htmlspecialchars($row['concerning'] ?? 'N/A'); ?></small></td>
                                        <td>
                                            <?php if($row['college_type'] == 'poly'): ?>
                                                <span class="tag tag-poly">Poly</span>
                                            <?php elseif($row['college_type'] == 'engineering'): ?>
                                                <span class="tag tag-engg">Engg</span>
                                            <?php else: ?>
                                                <small>N/A</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($row['status'] == 'Open'): ?>
                                                <span class="tag tag-open">Open</span>
                                            <?php elseif($row['status'] == 'In Progress' || $row['status'] == 'Pending'): ?>
                                                <span class="tag tag-progress"><?php echo htmlspecialchars($row['status']); ?></span>
                                            <?php else: ?>
                                                <span class="tag" style="background:rgba(16,185,129,0.15);color:#34d399;">Resolved</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($row['admin_remarks'])): ?>
                                                <button class="btn-view-remarks" onclick="openRemarksModal(this.nextElementSibling.innerHTML)">View</button>
                                                <div style="display:none;"><?php echo nl2br(htmlspecialchars($row['admin_remarks'])); ?></div>
                                            <?php else: ?>
                                                <small style="color:#aaa;">—</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($row['action_taken'] == 'yes'): ?>
                                                <span style="color:#34d399;">✅ Yes</span>
                                            <?php else: ?>
                                                <span style="color:#f87171;">❌ No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($row['handled_by'] ?? '—'); ?></small></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="no-data">No complaints found.</td></tr>
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

    <div id="remarksModalOverlay" class="modal-overlay" onclick="if(event.target === this) closeRemarksModal()">
        <div class="modal-content">
            <button class="close-modal" onclick="closeRemarksModal()">✖</button>
            <div class="modal-header">All Admin Remarks</div>
            <div id="modalRemarkText" class="modal-body"></div>
        </div>
    </div>
</body>
</html>
