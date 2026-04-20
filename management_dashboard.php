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

// Protect Page - Management only
if(!isset($_SESSION['username']) || $_SESSION['role'] != "management"){
    header("Location: login.php");
    exit();
}

// Handle marking alert as read
if(isset($_POST['mark_read'])){
    $alert_id = intval($_POST['alert_id']);
    $conn->query("UPDATE management_alerts SET is_read = 1 WHERE id = $alert_id");
}

// ==================== FETCH ALL DASHBOARD DATA ====================

// Total Students (All Colleges)
$total_students = $conn->query("SELECT COUNT(*) FROM students_info")->fetch_row()[0] ?? 0;
$poly_students = $conn->query("SELECT COUNT(*) FROM students_info WHERE college_type='poly'")->fetch_row()[0] ?? 0;
$engg_students = $conn->query("SELECT COUNT(*) FROM students_info WHERE college_type='engineering'")->fetch_row()[0] ?? 0;

// Total Complaints
$total_complaints = $conn->query("SELECT COUNT(*) FROM complaints")->fetch_row()[0] ?? 0;
$open_complaints = $conn->query("SELECT COUNT(*) FROM complaints WHERE status='Open' OR status='Pending'")->fetch_row()[0] ?? 0;
$resolved_complaints = $conn->query("SELECT COUNT(*) FROM complaints WHERE status='Resolved'")->fetch_row()[0] ?? 0;




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - NIT Hostel</title>
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
            <a href="management_dashboard.php" class="active"><span class="icon">📊</span> Dashboard</a>
            <a href="management_complaints.php"><span class="icon">📝</span> All Complaints</a>
            <a href="logout.php" style="margin-top:30px;color:#f87171;"><span class="icon">🚪</span> Logout</a>
        </div>

        <div class="content">
            <h1>Super Admin Control Center</h1>
            <p class="subtitle">Real-time overview of all colleges, complaints, and staff actions</p>

            <!-- ======= SUMMARY CARDS ======= -->
            <div class="summary-cards">
                <div class="card purple" style="cursor:pointer; position:relative;" onclick="window.location.href='management_students.php'">
                    <div class="card-icon">🎓</div>
                    <h3>Total Students</h3>
                    <div class="count"><?php echo $total_students; ?></div>
                    <div class="meta">Poly: <?php echo $poly_students; ?> | Engg: <?php echo $engg_students; ?></div>
                    <div style="margin-top:15px;"><a href="management_students.php" style="display:inline-block; background:rgba(255,255,255,0.2); padding:8px 15px; border-radius:4px; color:white; text-decoration:none; font-weight:bold;">View All Students ➡️</a></div>
                </div>
                
                <div class="card red" style="cursor:pointer; position:relative;" onclick="window.location.href='management_complaints.php'">
                    <div class="card-icon">⚠️</div>
                    <h3>Total Complaints</h3>
                    <div class="count"><?php echo $total_complaints; ?></div>
                    <div class="meta">Open/Pending: <?php echo $open_complaints; ?> | Resolved: <?php echo $resolved_complaints; ?></div>
                    <div style="margin-top:15px;"><a href="management_complaints.php" style="display:inline-block; background:rgba(255,255,255,0.2); padding:8px 15px; border-radius:4px; color:white; text-decoration:none; font-weight:bold;">View All Complaints ➡️</a></div>
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
