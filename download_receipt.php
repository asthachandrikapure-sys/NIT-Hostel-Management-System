<?php
session_start();
include("db.php");

// Protect page
if(!isset($_SESSION['username']) || $_SESSION['role'] != "student"){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$fee_id = intval($_GET['id'] ?? 0);

if($fee_id == 0){
    die("Invalid request.");
}

// Fetch fee record
$stmt = $conn->prepare("SELECT mf.*, u.fullname, u.email, si.course_type, si.academic_year FROM mess_fees mf JOIN users u ON mf.user_id = u.id LEFT JOIN students_info si ON u.id = si.user_id WHERE mf.id=? AND mf.user_id=? AND mf.receipt_id IS NOT NULL");
$stmt->bind_param("ii", $fee_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    die("Receipt not found or payment not completed.");
}

$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - NIT Hostel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f0f0;
            padding: 20px;
        }
        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .receipt-header {
            background: linear-gradient(135deg, #0b7a3f, #14a85e);
            color: white;
            text-align: center;
            padding: 25px 20px;
        }
        .receipt-header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            padding: 4px;
            margin-bottom: 10px;
        }
        .receipt-header h1 { font-size: 22px; margin-bottom: 5px; }
        .receipt-header p { font-size: 13px; opacity: 0.9; }
        .receipt-body { padding: 25px 30px; }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed #e0e0e0;
        }
        .receipt-row:last-child { border-bottom: none; }
        .receipt-label { color: #777; font-weight: 600; font-size: 14px; }
        .receipt-value { color: #333; font-weight: 700; font-size: 14px; text-align: right; }
        .receipt-total {
            background: #f9fdfb;
            border-top: 2px solid #0b7a3f;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: bold;
        }
        .receipt-total .label { color: #0b7a3f; }
        .receipt-total .value { color: #0b7a3f; }
        .receipt-footer {
            text-align: center;
            padding: 15px;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #eee;
        }
        .receipt-badge {
            display: inline-block;
            background: #4caf50;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }
        .btn-print {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 12px;
            background: #0b7a3f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-print:hover { background: #095d30; }
        @media print {
            .btn-print, .no-print { display: none !important; }
            body { background: white; padding: 0; }
            .receipt-container { box-shadow: none; }
        }
    </style>
<script>
    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>
</head>
<body>

<div class="receipt-container">
    <div class="receipt-header">
        <img src="nit_logo.png.jpg" alt="NIT Logo">
        <h1>Payment Receipt</h1>
        <p>NIT Hostel Management System</p>
    </div>

    <div class="receipt-body">
        <div class="receipt-row">
            <span class="receipt-label">Student Name</span>
            <span class="receipt-value"><?php echo htmlspecialchars($row['fullname']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Email</span>
            <span class="receipt-value"><?php echo htmlspecialchars($row['email']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Branch</span>
            <span class="receipt-value"><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Department</span>
            <span class="receipt-value"><?php echo htmlspecialchars($row['college_name'] ?? 'N/A'); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Month / Year</span>
            <span class="receipt-value"><?php echo htmlspecialchars($row['month_year']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Receipt ID</span>
            <span class="receipt-value" style="color:#0b7a3f; font-weight: 800;"><?php echo htmlspecialchars($row['receipt_id']); ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Payment ID (UTR)</span>
            <span class="receipt-value"><?php echo !empty($row['payment_id']) ? htmlspecialchars($row['payment_id']) : 'Admin Updated'; ?></span>
        </div>
        <div class="receipt-row">
            <span class="receipt-label">Paid On</span>
            <span class="receipt-value"><?php echo date('d M Y, h:i A', strtotime($row['paid_at'])); ?></span>
        </div>
    </div>

    <div class="receipt-total">
        <span class="label">Amount Paid</span>
        <span class="value">₹<?php echo number_format($row['paid_amount'] ?? $row['amount'], 2); ?></span>
    </div>

    <div class="receipt-footer">
        <p style="font-size: 11px; margin-top: 10px; color: #777; font-style: italic;">Thank you for your payment!</p>
    </div>
</div>

<div class="no-print" style="text-align:center; margin-top:20px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
    <button class="btn-print" onclick="window.print()">🖨️ Print Receipt</button>
    <button class="btn-print" style="background:#1565c0;" onclick="downloadPDF()">⬇️ Download PDF</button>
</div>
<p class="no-print" style="text-align:center; margin-top:10px;">
    <a href="student_mess_fee.php" style="color:#0b7a3f; font-weight:bold;">← Back to Mess Fee</a>
</p>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    var element = document.querySelector('.receipt-container');
    var opt = {
        margin:       0.5,
        filename:     'MessFee_Receipt_<?php echo $row["month_year"]; ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>

</body>
</html>
