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
if(!isset($_SESSION['username']) || $_SESSION['role'] != "student"){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pass_id = intval($_GET['id'] ?? 0);

if($pass_id == 0){
    die("Invalid request.");
}

// Fetch gate pass record — only if Approved
$stmt = $conn->prepare("SELECT gp.*, u.fullname, u.email, si.room_no, si.course_type, si.academic_year 
                         FROM gate_passes gp 
                         JOIN users u ON gp.user_id = u.id 
                         LEFT JOIN students_info si ON u.id = si.user_id 
                         WHERE gp.id=? AND gp.user_id=? AND gp.status='Approved'");
$stmt->bind_param("ii", $pass_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    die("Gate Pass not found or not yet approved.");
}

$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Pass - NIT Hostel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f0f0;
            padding: 20px;
        }
        .pass-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.12);
            overflow: hidden;
            border: 3px solid #0b7a3f;
        }
        .pass-header {
            background: linear-gradient(135deg, #0b7a3f, #14a85e);
            color: white;
            text-align: center;
            padding: 20px 15px;
        }
        .pass-header img {
            width: 55px; height: 55px;
            border-radius: 50%;
            background: white;
            padding: 3px;
            margin-bottom: 8px;
        }
        .pass-header h1 { font-size: 22px; margin-bottom: 3px; letter-spacing: 2px; text-transform: uppercase; }
        .pass-header p { font-size: 12px; opacity: 0.9; }

        .pass-badge {
            text-align: center;
            padding: 10px;
            background: #e8f5e9;
        }
        .badge-approved {
            display: inline-block;
            background: #4caf50;
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .pass-body { padding: 20px 25px; }
        .pass-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #e0e0e0;
        }
        .pass-row:last-child { border-bottom: none; }
        .pass-label { color: #777; font-weight: 600; font-size: 13px; }
        .pass-value { color: #222; font-weight: 700; font-size: 14px; text-align: right; max-width: 60%; }

        .pass-dates {
            display: flex;
            justify-content: space-around;
            background: #f9fdfb;
            border-top: 2px solid #0b7a3f;
            border-bottom: 2px solid #0b7a3f;
            padding: 15px 10px;
            text-align: center;
        }
        .date-block h3 { color: #0b7a3f; font-size: 18px; margin-bottom: 3px; }
        .date-block small { color: #888; font-size: 11px; font-weight: 600; text-transform: uppercase; }

        .pass-reason {
            padding: 15px 25px;
            background: #fafafa;
        }
        .pass-reason h4 { color: #555; font-size: 12px; margin-bottom: 5px; text-transform: uppercase; }
        .pass-reason p { color: #333; font-size: 14px; line-height: 1.5; }

        .pass-footer {
            text-align: center;
            padding: 12px;
            font-size: 11px;
            color: #999;
            border-top: 1px solid #eee;
            background: #fafafa;
        }
        .pass-id {
            text-align: center;
            padding: 8px;
            font-size: 11px;
            color: #aaa;
            font-family: monospace;
        }

        .btn-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .btn-action {
            display: inline-block;
            width: 180px;
            padding: 12px;
            background: #0b7a3f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
        }
        .btn-action:hover { background: #095d30; }
        .btn-download { background: #1565c0; }
        .btn-download:hover { background: #0d47a1; }

        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            .pass-container { box-shadow: none; border: 3px solid #0b7a3f; }
        }
        @media (max-width: 480px) {
            .pass-container { border-radius: 10px; }
            .pass-body { padding: 15px; }
            .pass-value { font-size: 13px; }
            .date-block h3 { font-size: 16px; }
        }
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

<div class="pass-container" id="gatePassCard">
    <div class="pass-header">
        <img src="nit_logo.png.jpg" alt="NIT Logo">
        <h1>Gate Pass</h1>
        <p>NIT Hostel Management System</p>
    </div>

    <div class="pass-badge">
        <span class="badge-approved">✓ APPROVED</span>
    </div>

    <div class="pass-body">
        <div class="pass-row">
            <span class="pass-label">Student Name</span>
            <span class="pass-value"><?php echo htmlspecialchars($row['fullname']); ?></span>
        </div>
        <div class="pass-row">
            <span class="pass-label">Email</span>
            <span class="pass-value"><?php echo htmlspecialchars($row['email']); ?></span>
        </div>
        <div class="pass-row">
            <span class="pass-label">Room No.</span>
            <span class="pass-value"><?php echo htmlspecialchars($row['room_no'] ?? 'N/A'); ?></span>
        </div>
        <div class="pass-row">
            <span class="pass-label">Department</span>
            <span class="pass-value"><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></span>
        </div>
        <div class="pass-row">
            <span class="pass-label">College</span>
            <span class="pass-value"><?php echo htmlspecialchars($row['course_type'] ?? 'N/A'); ?></span>
        </div>
        <div class="pass-row">
            <span class="pass-label">Year</span>
            <span class="pass-value"><?php echo htmlspecialchars($row['academic_year'] ?? 'N/A'); ?></span>
        </div>
    </div>

    <div class="pass-dates">
        <div class="date-block">
            <small>Leave Date</small>
            <h3><?php echo date('d M Y', strtotime($row['leave_date'])); ?></h3>
        </div>
        <div style="width:1px; background:#ccc;"></div>
        <div class="date-block">
            <small>Return Date</small>
            <h3><?php echo date('d M Y', strtotime($row['return_date'])); ?></h3>
        </div>
    </div>

    <div class="pass-reason">
        <h4>Reason for Leave</h4>
        <p><?php echo nl2br(htmlspecialchars($row['reason'])); ?></p>
    </div>

    <div class="pass-id">
        PASS ID: GP-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?> | Issued: <?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?>
    </div>

    <div class="pass-footer">
        This is a digitally generated gate pass approved by the Hostel Warden.
    </div>
</div>

<div class="btn-actions no-print">
    <button class="btn-action" onclick="window.print()">🖨️ Print Pass</button>
    <button class="btn-action btn-download" onclick="downloadPDF()">⬇️ Download PDF</button>
</div>
<p class="no-print" style="text-align:center; margin-top:12px;">
    <a href="student_gatepass.php" style="color:#0b7a3f; font-weight:bold;">← Back to Gate Pass</a>
</p>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    var element = document.getElementById('gatePassCard');
    var opt = {
        margin:       0.3,
        filename:     'GatePass_GP-<?php echo str_pad($row["id"], 5, "0", STR_PAD_LEFT); ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>

</body>
</html>
