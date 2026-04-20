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
include("check_late_fees.php");

// Protect page
if(!isset($_SESSION['username']) || $_SESSION['role'] != "student"){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Exemption Request
if(isset($_POST['submit_exemption'])){
    $fee_id = $_POST['exempt_fee_id'];
    $reason = trim($_POST['exemption_reason']);
    
    $stmt = $conn->prepare("UPDATE mess_fees SET exemption_reason=?, exemption_status='Pending' WHERE id=? AND user_id=?");
    $stmt->bind_param("sii", $reason, $fee_id, $user_id);
    if($stmt->execute()){
         $msg = "<p style='color:green; text-align:center;'>Extension request submitted to Principal successfully!</p>";
    } else {
         $msg = "<p style='color:red; text-align:center;'>Failed to submit request.</p>";
    }
}

// Fetch Mess Fee Data
$query = "SELECT id, month_year, amount, base_amount, late_fee_added, status, payment_id, paid_at, exemption_status, exemption_reason, receipt_id FROM mess_fees WHERE user_id = ? ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Function to fetch attendance count for a month
function getAttendanceCount($conn, $user_id, $month_year) {
    try {
        $m = DateTime::createFromFormat('M Y', $month_year);
        if (!$m) return 0;
        $start = $m->format('Y-m-01');
        $end = $m->format('Y-m-t');
        $res = $conn->query("SELECT COUNT(*) as days FROM attendance WHERE user_id = $user_id AND status = 'Present' AND date BETWEEN '$start' AND '$end'");
        return $res->fetch_assoc()['days'] ?? 0;
    } catch(Exception $e) { return 0; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Mess Fee - Student Dashboard</title>
    <link rel="stylesheet" href="student.css">
    <style>
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #0b7a3f;
            color: white;
        }
        .status-paid { color: green; font-weight: bold; }
        .status-pending { color: red; font-weight: bold; }
        .btn-pay {
            background: linear-gradient(135deg, #1565c0, #1e88e5);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            transition: 0.3s;
        }
        .btn-pay:hover { transform: scale(1.05); box-shadow: 0 2px 8px rgba(21,101,192,0.4); }
        .btn-receipt {
            background: #0b7a3f;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
        }
        .btn-receipt:hover { background: #095d30; }
        .payment-msg {
            text-align: center;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
            font-weight: bold;
            display: none;
        }

        .btn-issue {
            background: #ff9800;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            margin-left: 5px;
            transition: 0.3s;
        }
        .btn-issue:hover { background: #f57c00; transform: scale(1.05); }

        /* General Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .upi-modal {
            background: white;
            padding: 25px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            position: relative;
        }
        .upi-modal h2 { margin-top: 0; color: #333; }
        .upi-modal p { color: #666; margin-bottom: 20px; font-size: 14px; }
        .upi-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px dashed #ccc;
            margin-bottom: 20px;
        }
        .upi-details img { width: 150px; height: 150px; margin-bottom: 10px; }
        .upi-id-text { font-weight: bold; font-size: 16px; color: #1565c0; letter-spacing: 0.5px; }
        
        .upi-btn {
            display: block;
            width: 100%;
            background: #1565c0;
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            transition: 0.3s;
        }
        .upi-btn:hover { background: #0d47a1; }
        
        .utr-form { text-align: left; }
        .utr-form label { font-weight: bold; font-size: 13px; display: block; margin-bottom: 5px; }
        .utr-form input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 15px;
            box-sizing: border-box;
            font-family: monospace;
            font-size: 14px;
        }
        .utr-form button {
            width: 100%;
            background: #0b7a3f;
            color: white;
            border: none;
            padding: 10px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }
        .utr-form button:hover { background: #095d30; }
        .close-modal {
            position: absolute;
            top: 10px; right: 15px;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            text-decoration: none;
        }
        .status-paid { display: none; }
        .status-pending { display: none; }
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
            <a href="student.php">Dashboard Home</a>
            <a href="student_profile.php">View Profile</a>
            <a href="student_attendance.php">View Attendance Record</a>
            <a href="student_gatepass.php">Gate Pass Request</a>
            <a href="student_complaints.php">Register Complaint</a>
            <a href="student_mess_fee.php" style="background: #e7f3ea;">View Mess Fee Record</a>
        </div>

        <div class="content">
            <h1>Mess Fee Record</h1>
            <p>View your monthly mess fee details and pay online.</p>

            <?php if(isset($msg)) echo $msg; ?>
            <div id="paymentMsg" class="payment-msg"></div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Month / Year</th>
                            <th>Days Present</th>
                            <th>Amount (₹)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $days = getAttendanceCount($conn, $user_id, $row['month_year']);
                                $fine_text = ($row['late_fee_added']) ? "<br><small style='color:#c62828'>Incl. ₹100 Fine</small>" : "";
                            ?>
                                <tr id="row-<?php echo $row['id']; ?>">
                                    <td><?php echo htmlspecialchars($row['month_year']); ?></td>
                                    <td><strong><?php echo $days; ?> Days</strong></td>
                                    <td>
                                        <strong>₹<?php echo number_format($row['amount'], 2); ?></strong>
                                        <?php echo $fine_text; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($row['receipt_id'])): ?>
                                            <a href="download_receipt.php?id=<?php echo $row['id']; ?>" class="btn-receipt" target="_blank">📄 Receipt</a>
                                        <?php endif; ?>

                                        <?php if($row['status'] != 'Paid'): ?>
                                            <button class="btn-pay" onclick="openUpiModal(<?php echo $row['id']; ?>, <?php echo $row['amount']; ?>, '<?php echo addslashes($row['month_year']); ?>', <?php echo $row['late_fee_added']; ?>)">
                                                💳 Pay Now
                                            </button>
                                            
                                            <?php if($row['exemption_status'] == 'None' || $row['exemption_status'] == 'Rejected'): ?>
                                                <button class="btn-issue" onclick="openExemptionModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['month_year']); ?>')">
                                                    ⚠️ Report Issue
                                                </button>
                                            <?php elseif($row['exemption_status'] == 'Pending'): ?>
                                                <span style="display:block; margin-top:5px; font-size:11px; color:orange; font-weight:bold;">Extension Pending Approval</span>
                                            <?php elseif($row['exemption_status'] == 'Approved'): ?>
                                                <span style="display:block; margin-top:5px; font-size:11px; color:green; font-weight:bold;">Extension Approved</span>
                                            <?php endif; ?>
                                            
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No mess fee records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <!-- UPI Payment Modal -->
    <div id="upiModal" class="modal-overlay">
        <div class="upi-modal">
            <span class="close-modal" onclick="closeUpiModal()">&times;</span>
            <h2 id="modalTitle">Pay Mess Fee</h2>
            
            <div style="margin-bottom: 20px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Enter Amount to Pay (₹):</label>
                <input type="number" id="manualAmount" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:18px; font-weight:bold; text-align:center; box-sizing:border-box;" value="0" oninput="updateUpiDetails()">
            </div>
            
            <div class="upi-details">
                <img id="upiQrCode" src="" alt="UPI QR Code" style="width:200px; height:200px; display:block; margin: 0 auto; border: 1px solid #eee; padding: 5px; border-radius: 8px;">
                <p style="margin-top:10px; font-weight:bold;">Scan to pay via any UPI App</p>
                <div class="upi-id-text">9623790720@ybl</div>
            </div>

            <a href="#" id="upiDeepLink" class="upi-btn">Pay via UPI App 📱</a>

            <div class="utr-form">
                <label for="utrInput">Enter 12-digit UTR / Transaction ID:</label>
                <input type="text" id="utrInput" placeholder="e.g. 312345678901" maxlength="12" pattern="\d{12}">
                <button type="button" onclick="submitUtr()">Submit & Verify Payment</button>
            </div>
            
            <input type="hidden" id="currentFeeId" value="">
        </div>
    </div>

    <!-- Exemption Modal -->
    <div id="exemptionModal" class="modal-overlay">
        <div class="upi-modal">
            <span class="close-modal" onclick="closeExemptionModal()">&times;</span>
            <h2 id="exemptionTitle">Report Issue / Request Extension</h2>
            <p>If you are facing problems paying the fee, explain your issue. Your request will be reviewed by the Principal.</p>
            
            <form method="POST" action="" style="text-align:left;">
                <input type="hidden" id="exemptFeeId" name="exempt_fee_id" value="">
                <div style="margin-bottom: 15px;">
                    <label style="font-weight:bold; font-size:13px; display:block; margin-bottom:5px;">Reason for Extension:</label>
                    <textarea name="exemption_reason" rows="4" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;" required placeholder="Please explain your situation..."></textarea>
                </div>
                <button type="submit" name="submit_exemption" style="width:100%; background:#ff9800; color:white; border:none; padding:10px; font-weight:bold; border-radius:6px; cursor:pointer;">Submit Request</button>
            </form>
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

<script>
const upiId = "9623790720@ybl";
const payeeName = "NIT Hostel";
let currentManualFeeId = null;

function openUpiModal(feeId, amount, monthYear, isLate) {
    currentManualFeeId = feeId;
    document.getElementById('currentFeeId').value = feeId;
    document.getElementById('modalTitle').innerText = "Mess Fee - " + monthYear;
    document.getElementById('manualAmount').value = amount;
    document.getElementById('manualAmount').readOnly = true; // Make read-only for automated fees
    
    // Show fine message
    let fineHtml = isLate ? "<p style='color:#c62828; font-size:12px; font-weight:bold; margin-bottom:10px;'>Total includes ₹100 Late Payment Fine</p>" : "";
    let existingMsg = document.getElementById('fineMsg');
    if (!existingMsg) {
        let p = document.createElement('div');
        p.id = 'fineMsg';
        document.getElementById('manualAmount').parentNode.appendChild(p);
        existingMsg = p;
    }
    existingMsg.innerHTML = fineHtml;

    updateUpiDetails();
    
    // Clear previous input
    document.getElementById('utrInput').value = '';
    
    // Show Modal
    document.getElementById('upiModal').style.display = 'flex';
}

function updateUpiDetails() {
    const amount = document.getElementById('manualAmount').value;
    const feeId = document.getElementById('currentFeeId').value;
    
    if(!amount || amount <= 0) {
        document.getElementById('upiQrCode').style.opacity = '0.3';
        document.getElementById('upiDeepLink').style.pointerEvents = 'none';
        document.getElementById('upiDeepLink').style.opacity = '0.5';
        return;
    }
    
    document.getElementById('upiQrCode').style.opacity = '1';
    document.getElementById('upiDeepLink').style.pointerEvents = 'auto';
    document.getElementById('upiDeepLink').style.opacity = '1';

    // Construct UPI Intent String
    const upiString = `upi://pay?pa=${upiId}&pn=${encodeURIComponent(payeeName)}&am=${amount}&cu=INR&tn=MessFee_${feeId}`;
    
    // Set Deep Link
    const deepLinkBtn = document.getElementById('upiDeepLink');
    deepLinkBtn.href = upiString;
    
    // Add click event for desktop warning
    deepLinkBtn.onclick = function(e) {
        if(!/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            e.preventDefault();
            alert("Desktop browser detected! Deep links only work on mobile devices. Please scan the QR code above with your phone's UPI app (PhonePe, GPay, etc.) to pay.");
        }
    };
    
    // Generate QR Code using a more reliable API (QRServer)
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(upiString)}`;
    const qrImg = document.getElementById('upiQrCode');
    qrImg.src = qrUrl;
    qrImg.style.display = 'block';
}

function closeUpiModal() {
    document.getElementById('upiModal').style.display = 'none';
}

function submitUtr() {
    const feeId = document.getElementById('currentFeeId').value;
    const utr = document.getElementById('utrInput').value.trim();
    
    if(utr.length < 10) {
        alert("Please enter a valid Transaction ID / UTR number.");
        return;
    }
    
    // Send to server
    const xhr = new XMLHttpRequest();
    const amount = document.getElementById('manualAmount').value;
    xhr.open("POST", "process_payment.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if(xhr.readyState === 4 && xhr.status === 200) {
            let res;
            try {
                res = JSON.parse(xhr.responseText);
            } catch(e) {
                alert("Unexpected response from server.");
                return;
            }
            
            closeUpiModal();
            const msgBox = document.getElementById('paymentMsg');
            
            if(res.success) {
                msgBox.style.display = 'block';
                msgBox.style.background = '#e8f5e9';
                msgBox.style.color = '#2e7d32';
                msgBox.textContent = '✅ ' + res.message;

            if(res.success) {
                msgBox.style.display = 'block';
                msgBox.style.background = '#e8f5e9';
                msgBox.style.color = '#2e7d32';
                msgBox.textContent = '✅ ' + res.message;

                // Update row UI for Receipt
                const rowEl = document.getElementById('row-' + feeId);
                if(rowEl) {
                    const actionTd = rowEl.querySelector('td:last-child');
                    actionTd.innerHTML = '<a href="download_receipt.php?id=' + feeId + '" class="btn-receipt" target="_blank">📄 Receipt</a>';
                    
                    if(res.message.includes("Partial")) {
                        actionTd.innerHTML += ' <button class="btn-pay" onclick="openUpiModal(' + feeId + ', ' + amount + ', \'...\')">💳 Pay Bal</button>';
                    }
                }
            }
            } else {
                msgBox.style.display = 'block';
                msgBox.style.background = '#ffebee';
                msgBox.style.color = '#c62828';
                msgBox.textContent = '❌ ' + res.message;
            }
        }
    };
    xhr.send("fee_id=" + feeId + "&payment_id=" + encodeURIComponent(utr) + "&amount=" + encodeURIComponent(amount));
}

function openExemptionModal(feeId, monthYear) {
    document.getElementById('exemptFeeId').value = feeId;
    document.getElementById('exemptionTitle').innerText = "Report Issue - " + monthYear;
    document.getElementById('exemptionModal').style.display = 'flex';
}

function closeExemptionModal() {
    document.getElementById('exemptionModal').style.display = 'none';
}
</script>

</body>
</html>
