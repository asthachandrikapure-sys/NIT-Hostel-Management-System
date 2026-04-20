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

// Protect page - Only Principal and Incharge can see this
if(!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['principal_poly', 'principal_engg', 'incharge_poly', 'incharge_engg'])){
    header("Location: login.php");
    exit();
}

$college = $_SESSION['college_name'];
$role = $_SESSION['role'];
$css_file = (strpos($role, 'principal') !== false) ? 'principal.css' : 'hostel_incharge.css';
$dashboard_link = (strpos($role, 'principal') !== false) ? 'principal.php' : 'hostel_incharge.php';

// Fetch Credentials for users in this college
// Including HODs and Students of this college
$query = "SELECT fullname, email, password, role, department_name 
          FROM users 
          WHERE college_name = '$college' AND role NOT IN ('admin')
          ORDER BY role, fullname";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credential Monitor - NIT Hostel</title>
    <link rel="stylesheet" href="<?php echo $css_file; ?>">
    <style>
        .table-container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #d32f2f; color: white; } /* Red for sensitive data */
        .pass-text { font-family: monospace; background: #eee; padding: 2px 5px; border-radius: 3px; }
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

    <header style="background: #b71c1c;"> <!-- Red header for security page -->
        <img src="nit_logo.png.jpg" alt="NIT Logo">
        Security Monitor: Credentials List (<?php echo $college; ?>)
    </header>

    <div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <div class="sidebar">
            <h2>Modules</h2>
            <a href="<?php echo $dashboard_link; ?>">Dashboard Home</a>
            <a href="principal_student_info.php">Student List</a>
            <a href="credentials_list.php" style="background: #ffebee; color: #b71c1c;">Login Credentials</a>
        </div>

        <div class="content">
            <h1>User Credentials List</h1>
            <p style="color:red; font-weight:bold;">⚠️ CAUTION: This page contains sensitive login information.</p>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Login ID (Email)</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                        <td><span style="text-transform: uppercase; font-size: 11px; font-weight: bold;"><?php echo $row['role']; ?></span></td>
                                        <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><span class="pass-text"><?php echo htmlspecialchars($row['password']); ?></span></td>
                                    </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;">No users found.</td></tr>
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
