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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "student"){
    header("Location: login.php");
    exit();
}

$contacts = $conn->query("SELECT * FROM bharosa_contacts ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bharosa Cell Support - Student Dashboard</title>
    <link rel="stylesheet" href="student.css">
    <style>
        .bharosa-hero {
            background: linear-gradient(135deg, #1a237e, #4a148c);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        .bharosa-hero h2 { margin: 0 0 10px 0; font-size: 22px; }
        .bharosa-hero p { margin: 5px 0; font-size: 14px; opacity: 0.9; max-width: 600px; margin-left: auto; margin-right: auto; }
        .bharosa-hero .shield { font-size: 42px; margin-bottom: 10px; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
        }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.2); }
        .action-btn .icon { font-size: 20px; }
        .btn-website { background: linear-gradient(135deg, #1565c0, #0d47a1); }
        .btn-register { background: linear-gradient(135deg, #e65100, #bf360c); }

        .bharosa-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            border-left: 4px solid #4a148c;
        }
        .bharosa-card h3 { margin: 0 0 10px 0; color: #333; }
        .bharosa-card p { margin: 5px 0; color: #555; font-size: 14px; }
        .bharosa-card .contact-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px; }

        .btn-call {
            background: #4caf50; color: white; padding: 10px 18px;
            text-decoration: none; border-radius: 6px; font-weight: bold;
            display: inline-flex; align-items: center; gap: 6px; font-size: 13px;
        }
        .btn-call:hover { background: #45a049; }
        .btn-wa {
            background: #25D366; color: white; padding: 10px 18px;
            text-decoration: none; border-radius: 6px; font-weight: bold;
            display: inline-flex; align-items: center; gap: 6px; font-size: 13px;
        }
        .btn-wa:hover { background: #1da851; }
        .btn-email {
            background: #1a73e8; color: white; padding: 10px 18px;
            text-decoration: none; border-radius: 6px; font-weight: bold;
            display: inline-flex; align-items: center; gap: 6px; font-size: 13px;
        }
        .btn-email:hover { background: #1557b0; }

        .info-label { font-weight: bold; color: #333; }
        .section-title { font-size: 18px; font-weight: bold; color: #333; margin: 25px 0 15px 0; border-bottom: 2px solid #4a148c; padding-bottom: 8px; }
        .no-data { text-align: center; color: #999; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
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
            <a href="student_profile.php">View Profile</a>
            <a href="student_attendance.php">View Attendance Record</a>
            <a href="student_gatepass.php">Gate Pass Request</a>
            <a href="student_complaints.php">Register Complaint</a>
            <a href="student_mess_fee.php">View Mess Fee Record</a>
            <a href="student_notifications.php">Notifications</a>
            <a href="student_rules.php">Hostel Rules 📋</a>
            <a href="student_reports.php">View My Reports</a>
            <a href="student_transport.php">Transport Assistance</a>
            <a href="student_bharosa.php" style="background: #e7f3ea;">Bharosa Cell Support</a>
        </div>
        <div class="content">

            <!-- Hero Section -->
            <div class="bharosa-hero">
                <div class="shield">🛡️</div>
                <h2>Bharosa Cell Support</h2>
                <p><strong>Purpose:</strong> Bharosa Cell is a government initiative providing safety, counseling, and legal support to women, children, and senior citizens in distress.</p>
                <p style="margin-top:8px; font-size:13px;">If you or anyone you know needs help, don't hesitate to reach out. All services are <strong>free and confidential</strong>.</p>
            </div>


            <!-- Contact Cards -->
            <div class="section-title">📞 Bharosa Cell Contacts</div>

            <?php if($contacts && $contacts->num_rows > 0): ?>
                <?php while($row = $contacts->fetch_assoc()):
                    $phone_clean = preg_replace('/[^0-9]/', '', $row['phone']);
                    $wa_phone = (strlen($phone_clean) == 10) ? '91' . $phone_clean : $phone_clean;
                ?>
                <div class="bharosa-card">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p><span class="info-label">Phone:</span> <?php echo htmlspecialchars($row['phone']); ?></p>
                    <?php if(!empty($row['email'])): ?>
                        <p><span class="info-label">Email:</span> <?php echo htmlspecialchars($row['email']); ?></p>
                    <?php endif; ?>
                    <div class="contact-actions">
                        <a href="tel:<?php echo htmlspecialchars($row['phone']); ?>" class="btn-call">📞 Call Now</a>
                        <a href="https://wa.me/<?php echo $wa_phone; ?>?text=Hello%2C%20I%20need%20support%20from%20Bharosa%20Cell." target="_blank" class="btn-wa">💬 Send Message</a>
                        <?php if(!empty($row['email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($row['email']); ?>?subject=Support%20Request%20-%20NIT%20Student" class="btn-email">✉️ Email</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">No Bharosa Cell contacts available at the moment. Please check back later.</div>
            <?php endif; ?>

            <!-- Important Notes -->
            <div style="background-color: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin-top: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h4 style="margin: 0 0 5px 0; color: #856404; font-size: 16px;">⚠️ Warning</h4>
                <p style="margin: 0; color: #856404; font-size: 14px;">This is an official Bharosa Cell contact number. Please use it responsibly. Do not misuse or make fun of this service.</p>
            </div>

            <div style="background-color: #f8d7da; border-left: 5px solid #dc3545; padding: 15px; margin-top: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h4 style="margin: 0 0 5px 0; color: #721c24; font-size: 16px;">🚨 Emergency Contact</h4>
                <p style="margin: 0; color: #721c24; font-size: 14px;">For safety and security issues, you can also contact the police at <strong>112</strong> (Kalmeshwar).</p>
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
