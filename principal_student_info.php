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
if(!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['principal_poly', 'principal_engg', 'incharge_poly', 'incharge_engg'])){
    header("Location: login.php");
    exit();
}

$college = $_SESSION['college_name'];
$role = $_SESSION['role'];
$css_file = (strpos($role, 'principal') !== false) ? 'principal.css' : 'hostel_incharge.css';
$dashboard_link = (strpos($role, 'principal') !== false) ? 'principal.php' : 'hostel_incharge.php';

// Fetch Wardens and HODs in this College (Only for Principals)
$staff_result = null;
if(strpos($role, 'principal') !== false) {
    $staff_query = "SELECT id, fullname, email, role, department_name, created_at 
                    FROM users 
                    WHERE college_name = '$college' AND role IN ('warden', 'hod')
                    ORDER BY role, fullname";
    $staff_result = $conn->query($staff_query);
}

// Fetch All Students in this College
$query = "SELECT u.id, u.fullname, u.email, s.department_name, s.room_no, s.academic_year, s.parent_mobile, s.student_mobile 
          FROM users u 
          JOIN students_info s ON u.id = s.user_id 
          WHERE s.college_name = '$college'
          ORDER BY s.department_name, u.fullname";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Student List - NIT Hostel</title>
    <link rel="stylesheet" href="<?php echo $css_file; ?>">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #0b7a3f; color: white; }
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
            <a href="<?php echo $dashboard_link; ?>">Dashboard Home</a>
            <a href="principal_student_info.php" style="background: #e7f3ea;">Student List</a>
            <a href="credentials_list.php">Login Credentials</a>
        </div>

        <div class="content">
            <h1>User Directory - <?php echo $college; ?></h1>
            <p>Viewing registered users in the <?php echo $college; ?> college.</p>
            
            <?php if($staff_result && $staff_result->num_rows > 0): ?>
            <h2 style="margin-top: 30px;">HODs & Wardens</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Account Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $staff_result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                                <td><span style="font-weight:bold; font-size:11px; text-transform:uppercase;"><?php echo htmlspecialchars($row['role']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <h2 style="margin-top: 30px;">Student List</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Room No.</th>
                            <th>Year</th>
                            <th>Parent Contact</th>
                            <th>Student Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['fullname']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['room_no'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['academic_year'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['parent_mobile'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_mobile'] ?? 'N/A'); ?></td>
                                    </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;">No students found in this college.</td></tr>
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
