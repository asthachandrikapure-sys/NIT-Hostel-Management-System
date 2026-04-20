<?php
session_start();

// Prevent browser caching for security
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
include("db.php");

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if(!isset($_SESSION['username']) || $_SESSION['role'] != "management"){
    header("Location: login.php");
    exit();
}

// Fetch all students with college name
$students = $conn->query("SELECT u.fullname, s.college_name, s.college_type, s.department_name, s.academic_year 
                           FROM students_info s 
                           JOIN users u ON s.user_id = u.id
                           ORDER BY s.college_type ASC, s.college_name ASC, u.fullname ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Students - Super Admin Dashboard</title>
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
            <a href="management_students.php" class="active"><span class="icon">🎓</span> All Students</a>
            <a href="management_complaints.php"><span class="icon">📝</span> All Complaints</a>
            <a href="logout.php" style="margin-top:30px;color:#f87171;"><span class="icon">🚪</span> Logout</a>
        </div>

        <div class="content">
            <h1>All Students</h1>
            <p class="subtitle">Complete list of students across all colleges.</p>

            <div class="section">
                <div class="filter-bar">
                    <select id="filter-college" onchange="filterTable('students-table', this.value, 2)">
                        <option value="">All Colleges</option>
                        <option value="poly">Polytechnic</option>
                        <option value="engineering">Engineering</option>
                    </select>
                </div>

                <div class="table-container">
                    <table id="students-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student Name</th>
                                <th>College</th>
                                <th>Department</th>
                                <th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($students && $students->num_rows > 0): ?>
                                <?php $i = 1; while($row = $students->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                                        <td>
                                            <?php if($row['college_type'] == 'poly'): ?>
                                                <span class="tag tag-poly">Polytechnic</span>
                                            <?php elseif($row['college_type'] == 'engineering'): ?>
                                                <span class="tag tag-engg">Engineering</span>
                                            <?php else: ?>
                                                <small><?php echo htmlspecialchars($row['college_name'] ?? 'N/A'); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($row['academic_year'] ?? 'N/A'); ?></small></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="no-data">No students found.</td></tr>
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
