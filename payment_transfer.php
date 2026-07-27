<?php
session_start();
include "db.php";

// 1. Admin/Superadmin Security Check
$allowed_roles = ['admin', 'superadmin'];
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), $allowed_roles)) {
    header("Location: dashboard.php?error=unauthorized_access");
    exit();
}

// -------------------------------------------------------------
// AJAX Requests Handling (Dynamic Filtering)
// -------------------------------------------------------------

// AJAX 1: Fetch Registered Classes for Selected Student
if (isset($_GET['action']) && $_GET['action'] === 'get_classes') {
    header('Content-Type: application/json');
    $student_id = intval($_GET['student_id'] ?? 0);
    
    $query = "SELECT c.id, c.subject, t.name AS teacher_name 
              FROM student_classes sc
              JOIN classes c ON sc.class_id = c.id
              LEFT JOIN teachers t ON c.teacher_id = t.id
              WHERE sc.student_id = ?
              ORDER BY c.subject ASC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }
    echo json_encode($classes);
    exit();
}

// AJAX 2: Fetch Eligible "From Months" (Paid AND Attendance <= 1)
if (isset($_GET['action']) && $_GET['action'] === 'get_eligible_months') {
    header('Content-Type: application/json');
    $student_id = intval($_GET['student_id'] ?? 0);
    $class_id   = intval($_GET['class_id'] ?? 0);
    
    // Get all Paid months for this student & class
    $paid_query = "SELECT month, amount FROM payments 
                   WHERE student_id = ? AND class_id = ? AND amount > 0";
    $stmt_p = $conn->prepare($paid_query);
    $stmt_p->bind_param("ii", $student_id, $class_id);
    $stmt_p->execute();
    $paid_res = $stmt_p->get_result();
    
    $paid_months = [];
    while ($row = $paid_res->fetch_assoc()) {
        $paid_months[$row['month']] = floatval($row['amount']);
    }
    
    // Get attendance counts per month
    $att_query = "SELECT MONTHNAME(date) as month_name, COUNT(DISTINCT DATE(date)) as att_count 
                 FROM attendance 
                 WHERE student_id = ? AND class_id = ? AND status = 'Present'
                 GROUP BY MONTHNAME(date)";
    $stmt_a = $conn->prepare($att_query);
    $stmt_a->bind_param("ii", $student_id, $class_id);
    $stmt_a->execute();
    $att_res = $stmt_a->get_result();
    
    $attendance_map = [];
    while ($row = $att_res->fetch_assoc()) {
        $attendance_map[$row['month_name']] = intval($row['att_count']);
    }
    
    // Filter logic: Eligible ONLY IF Paid > 0 AND Attendance Count <= 1
    $eligible_months = [];
    foreach ($paid_months as $month => $paid_amount) {
        $count = $attendance_map[$month] ?? 0;
        if ($count <= 1) {
            $eligible_months[] = [
                'month' => $month,
                'count' => $count,
                'paid_amount' => $paid_amount
            ];
        }
    }
    
    echo json_encode($eligible_months);
    exit();
}

// -------------------------------------------------------------
// Main Form Submission Processing
// -------------------------------------------------------------
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_transfer'])) {
    $student_id = intval($_POST['student_id']);
    $class_id   = intval($_POST['class_id']);
    $from_month = mysqli_real_escape_string($conn, $_POST['from_month']);
    $to_month   = mysqli_real_escape_string($conn, $_POST['to_month']);
    $reason     = mysqli_real_escape_string($conn, trim($_POST['reason']));
    $user_id    = $_SESSION['user_id'] ?? 1;

    // Validation 1: Same Month Check
    if ($from_month === $to_month) {
        $message = "<div class='alert-custom error animate-pop'>
                      <div class='alert-icon'><i class='bi bi-exclamation-triangle-fill fs-4'></i></div>
                      <div><strong>දෝෂයකි:</strong> එකම මාසය ඇතුළත Payment Transfer එකක් සිදුකළ නොහැක!</div>
                    </div>";
    } else {
        // Backend Condition Checks (Strict Verification)
        
        // 1. Check if Payment Exists & Get Amount
        $pay_check = mysqli_query($conn, "SELECT id, amount FROM payments WHERE student_id = $student_id AND class_id = $class_id AND month = '$from_month' AND amount > 0");
        $pay_data  = mysqli_fetch_assoc($pay_check);

        // 2. Check Attendance Count
        $att_check = mysqli_query($conn, "SELECT COUNT(DISTINCT DATE(date)) as att_count FROM attendance WHERE student_id = $student_id AND class_id = $class_id AND status = 'Present' AND MONTHNAME(date) = '$from_month'");
        $att_data  = mysqli_fetch_assoc($att_check);
        $att_count = intval($att_data['att_count'] ?? 0);

        if (!$pay_data) {
            // Block Condition 1: Payment Not Found
            $message = "<div class='alert-custom error animate-pop'>
                          <div class='alert-icon'><i class='bi bi-x-circle-fill fs-4'></i></div>
                          <div><strong>Transfer එක Block කරන ලදී:</strong> $from_month මාසය සඳහා සිසුවා ගෙවීමක් සිදු කර නොමැත!</div>
                        </div>";
        } elseif ($att_count > 1) {
            // Block Condition 2: Attendance Exceeded
            $message = "<div class='alert-custom error animate-pop'>
                          <div class='alert-icon'><i class='bi bi-x-circle-fill fs-4'></i></div>
                          <div><strong>Transfer එක Block කරන ලදී:</strong> සිසුවා $from_month මාසයේ දින $att_count ක් පන්තියට පැමිණ ඇත (අවසර ඇත්තේ දින 1ක් හෝ ඊට අඩුවෙන් පැමිණි මාස සඳහා පමණි).</div>
                        </div>";
        } else {
            // Success Pathway: Execute Database Transaction
            $transfer_amount = floatval($pay_data['amount']);

            mysqli_begin_transaction($conn);
            try {
                // Record Audit Log
                $stmt = $conn->prepare("INSERT INTO payment_transfers (student_id, class_id, from_month, to_month, amount, reason, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissdsi", $student_id, $class_id, $from_month, $to_month, $transfer_amount, $reason, $user_id);
                $stmt->execute();

                // Clear Old Month Balance (Set amount to 0)
                $conn->query("UPDATE payments SET amount = 0.00 WHERE student_id = $student_id AND class_id = $class_id AND month = '$from_month'");

                // Apply Balance to Target "To Month"
                $check_to = $conn->query("SELECT id, amount FROM payments WHERE student_id = $student_id AND class_id = $class_id AND month = '$to_month'");
                if ($check_to->num_rows > 0) {
                    $conn->query("UPDATE payments SET amount = amount + $transfer_amount, paid_date = CURDATE() WHERE student_id = $student_id AND class_id = $class_id AND month = '$to_month'");
                } else {
                    $conn->query("INSERT INTO payments (student_id, class_id, month, amount, paid_date) VALUES ($student_id, $class_id, '$to_month', $transfer_amount, CURDATE())");
                }

                mysqli_commit($conn);
                $message = "<div class='alert-custom success animate-pop'>
                              <div class='alert-icon'><i class='bi bi-check-circle-fill fs-4'></i></div>
                              <div>ගෙවීම් සාර්ථකව <strong>$from_month</strong> මාසයේ සිට <strong>$to_month</strong> මාසයට මාරු කරන ලදී! (Amount: LKR $transfer_amount)</div>
                            </div>";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $message = "<div class='alert-custom error animate-pop'>
                              <div class='alert-icon'><i class='bi bi-exclamation-triangle-fill fs-4'></i></div>
                              <div>Transaction Error: " . htmlspecialchars($e->getMessage()) . "</div>
                            </div>";
            }
        }
    }
}

// Fetch Active Students List
$students_query = mysqli_query($conn, "SELECT student_id, student_name, qr_token FROM students ORDER BY student_name ASC");
?>

<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Payment Transfer | SIGMA ERP</title>
    
    <!-- Fonts & Styling Libraries -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #0a0d14;
            --card-bg: rgba(18, 24, 38, 0.7);
            --border-color: rgba(255, 255, 255, 0.08);
            --accent-blue: #3b82f6;
            --accent-cyan: #06b6d4;
            --accent-purple: #8b5cf6;
            --accent-gradient: linear-gradient(135deg, #3b82f6 0%, #06b6d4 50%, #8b5cf6 100%);
            --glow-color: rgba(59, 130, 246, 0.4);
            --text-muted: #94a3b8;
            --sidebar-w: 280px;
        }

        body { 
            background-color: var(--bg-dark); 
            color: #f1f5f9;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image: 
                radial-gradient(at 0% 0%, rgba(59, 130, 246, 0.12) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(139, 92, 246, 0.12) 0px, transparent 50%);
        }

        .main-content { 
            margin-left: var(--sidebar-w); 
            padding: 40px; 
            flex: 1;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        /* Interactive Floating Graphic Elements */
        .ambient-glow-1 {
            position: fixed;
            top: 10%;
            right: 15%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(6, 182, 212, 0.18) 0%, rgba(0, 0, 0, 0) 70%);
            z-index: 0;
            pointer-events: none;
            animation: floatGlow 10s infinite alternate ease-in-out;
        }

        .ambient-glow-2 {
            position: fixed;
            bottom: 10%;
            left: 30%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
            z-index: 0;
            pointer-events: none;
            animation: floatGlow 14s infinite alternate-reverse ease-in-out;
        }

        @keyframes floatGlow {
            0% { transform: translate(0, 0) scale(1); opacity: 0.5; }
            50% { transform: translate(30px, 40px) scale(1.1); opacity: 0.8; }
            100% { transform: translate(-20px, -30px) scale(0.95); opacity: 0.5; }
        }

        /* Top Header Visuals */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }

        .header-title-wrapper {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-icon-box {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(6, 182, 212, 0.2));
            border: 1px solid rgba(59, 130, 246, 0.4);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #38bdf8;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            animation: pulseIcon 3s infinite ease-in-out;
        }

        @keyframes pulseIcon {
            0%, 100% { box-shadow: 0 0 15px rgba(56, 189, 248, 0.3); }
            50% { box-shadow: 0 0 25px rgba(56, 189, 248, 0.6); }
        }

        /* Enhanced Glass Card Style */
        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--border-color);
            border-radius: 28px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--accent-gradient);
        }

        /* Banner Graphics Section */
        .transfer-illustration-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-around;
            position: relative;
            overflow: hidden;
        }

        .transfer-step-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            z-index: 1;
        }

        .transfer-step-icon .circle-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .transfer-step-icon span {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .transfer-arrow-anim {
            font-size: 1.5rem;
            color: var(--accent-cyan);
            animation: arrowShift 1.5s infinite ease-in-out;
        }

        @keyframes arrowShift {
            0%, 100% { transform: translateX(-5px); opacity: 0.5; }
            50% { transform: translateX(5px); opacity: 1; }
        }

        /* Modernized Form Elements */
        .form-label {
            font-weight: 600;
            color: #cbd5e1;
            font-size: 0.83rem;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-control, .form-select {
            background-color: rgba(15, 23, 42, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #f8fafc !important;
            border-radius: 16px;
            padding: 14px 20px;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-blue) !important;
            box-shadow: 0 0 20px var(--glow-color) !important;
            transform: translateY(-2px);
        }

        .form-select option {
            background-color: #0f172a;
            color: #f8fafc;
        }

        .form-select:disabled {
            background-color: rgba(255, 255, 255, 0.02) !important;
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* Gradient Button Styling */
        .btn-gradient {
            background: var(--accent-gradient);
            background-size: 200% auto;
            border: none;
            color: #fff;
            font-weight: 700;
            padding: 16px 28px;
            border-radius: 16px;
            width: 100%;
            font-size: 1rem;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            cursor: pointer;
        }

        .btn-gradient:hover {
            background-position: right center;
            transform: translateY(-3px);
            box-shadow: 0 15px 30px -5px rgba(6, 182, 212, 0.5);
            color: #fff;
        }

        /* Enhanced Custom Alerts */
        .alert-custom {
            padding: 18px 24px;
            border-radius: 18px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 0.95rem;
            backdrop-filter: blur(15px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .alert-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .alert-custom.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .alert-custom.success .alert-icon {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .alert-custom.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-custom.error .alert-icon {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        .policy-badge {
            background: rgba(59, 130, 246, 0.05);
            border: 1px dashed rgba(59, 130, 246, 0.3);
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 32px;
            color: #93c5fd;
            font-size: 0.88rem;
            line-height: 1.6;
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .badge-helper {
            font-size: 0.72rem;
            padding: 3px 10px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.08);
            color: #a5f3fc;
            font-weight: 500;
            text-transform: none;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-pop {
            animation: popIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes popIn {
            0% { opacity: 0; transform: scale(0.92); }
            100% { opacity: 1; transform: scale(1); }
        }

        .spinner-mini {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #38bdf8;
            animation: spin 0.8s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 991.98px) {
            .main-content { margin-left: 0; padding: 20px; }
            .transfer-illustration-box { flex-direction: column; gap: 15px; }
            .transfer-arrow-anim { transform: rotate(90deg); }
            @keyframes arrowShift {
                0%, 100% { transform: rotate(90deg) translateY(-5px); opacity: 0.5; }
                50% { transform: rotate(90deg) translateY(5px); opacity: 1; }
            }
        }
    </style>
</head>
<body>

    <!-- Ambient Floating Graphic Glows -->
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <!-- Include Sidebar Navigation -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Top Bar Header -->
        <div class="top-header">
            <div class="header-title-wrapper">
                <div class="header-icon-box">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <div>
                    <h2 class="fw-bold m-0 d-flex align-items-center gap-2" style="letter-spacing: -0.5px;">
                        Smart Payment Transfer
                    </h2>
                    <small class="text-muted">සිසුන්ගේ පන්ති ගාස්තු ඊළඟ මාසය සඳහා Transfer කිරීමේ පද්ධතිය</small>
                </div>
            </div>
            <div class="d-none d-sm-flex align-items-center gap-3 bg-dark px-3 py-2 rounded-pill border border-secondary border-opacity-25 shadow-sm">
                <div class="avatar-sm rounded-circle bg-info bg-opacity-20 text-info d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <span class="fw-semibold text-light me-2"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
            </div>
        </div>

        <!-- Alert Notification Box -->
        <?= $message ?>

        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="glass-card">
                    
                    <!-- Graphic Visual Flow Banner -->
                    <div class="transfer-illustration-box">
                        <div class="transfer-step-icon">
                            <div class="circle-avatar" id="icon-step-1"><i class="bi bi-person-vcard text-info"></i></div>
                            <span>1. සිසුවා තෝරන්න</span>
                        </div>
                        <i class="bi bi-chevron-right transfer-arrow-anim"></i>
                        <div class="transfer-step-icon">
                            <div class="circle-avatar" id="icon-step-2"><i class="bi bi-journal-check text-primary"></i></div>
                            <span>2. පන්තිය</span>
                        </div>
                        <i class="bi bi-chevron-right transfer-arrow-anim"></i>
                        <div class="transfer-step-icon">
                            <div class="circle-avatar" id="icon-step-3"><i class="bi bi-calendar-minus text-warning"></i></div>
                            <span>3. From Month</span>
                        </div>
                        <i class="bi bi-chevron-right transfer-arrow-anim"></i>
                        <div class="transfer-step-icon">
                            <div class="circle-avatar" id="icon-step-4"><i class="bi bi-calendar-plus text-success"></i></div>
                            <span>4. To Month</span>
                        </div>
                    </div>

                    <!-- Policy Rule Highlights -->
                    <div class="policy-badge">
                        <i class="bi bi-shield-check fs-3 text-info flex-shrink-0"></i>
                        <div>
                            <strong class="text-light">Strict Verification Engine Rules:</strong>
                            <ul class="m-0 ps-3 mt-1 text-light-50">
                                <li>ගෙවීමක් සිදුකර ඇති මාස පමණක් තේරීමට අවසර ඇත (<code>Paid > 0</code>).</li>
                                <li>එම මාසය තුළ සිසුවා පැමිණ සිටින්නේ <strong>දින 1ක් හෝ 0ක් නම් පමණක්</strong> මාරු කිරීම සඳහා සුදුසුකම් ලබයි (<code>Attendance &le; 1</code>).</li>
                            </ul>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <div class="row g-4">
                            
                            <!-- Step 1: Student Selection -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    <span><i class="bi bi-person-vcard text-info me-2"></i> සිසුවා තෝරන්න</span>
                                </label>
                                <select name="student_id" id="student_id" class="form-select" required onchange="fetchStudentClasses(this.value)">
                                    <option value="" disabled selected>Search & Select Student...</option>
                                    <?php while ($s = mysqli_fetch_assoc($students_query)): ?>
                                        <option value="<?= $s['student_id'] ?>">
                                            <?= htmlspecialchars($s['student_name']) ?> (ID: <?= $s['student_id'] ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Step 2: Class Selection -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    <span><i class="bi bi-journal-bookmark text-primary me-2"></i> පන්තිය/විෂය</span>
                                    <span id="class_loader" class="badge-helper d-none"><span class="spinner-mini me-1"></span> Loading...</span>
                                </label>
                                <select name="class_id" id="class_id" class="form-select" required disabled onchange="fetchEligibleMonths()">
                                    <option value="" disabled selected>First Select a Student...</option>
                                </select>
                            </div>

                            <!-- Step 3: Source Month (Filtered by both conditions) -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    <span><i class="bi bi-calendar-minus text-warning me-2"></i> මුදල් ගෙවූ මාසය (From Month)</span>
                                    <span id="month_loader" class="badge-helper d-none"><span class="spinner-mini me-1"></span> Verifying Rules...</span>
                                </label>
                                <select name="from_month" id="from_month" class="form-select" required disabled>
                                    <option value="" disabled selected>Select Class First...</option>
                                </select>
                            </div>

                            <!-- Step 4: Target Month -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    <span><i class="bi bi-calendar-plus text-success me-2"></i> මාරු කළ යුතු මාසය (To Month)</span>
                                </label>
                                <select name="to_month" id="to_month" class="form-select" required>
                                    <option value="" disabled selected>Choose Target Month...</option>
                                    <?php
                                    $months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                                    foreach ($months as $m) {
                                        echo "<option value='$m'>$m</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Transfer Reason Textarea -->
                            <div class="col-12">
                                <label class="form-label">
                                    <span><i class="bi bi-chat-left-text text-info me-2"></i> Transfer කිරීමට හේතුව (Reason)</span>
                                </label>
                                <textarea name="reason" class="form-control" rows="3" placeholder="උදා: සති 1කට පසු අසනීප වී රෝහල් ගත කිරීම නිසා පන්තියට සහභාගී වීමට නොහැකි විය..." required></textarea>
                            </div>

                            <!-- Execute Action Button -->
                            <div class="col-12 mt-4">
                                <button type="submit" name="process_transfer" class="btn btn-gradient">
                                    <i class="bi bi-arrow-left-right fs-5"></i> Payment Transfer එක අනුමත කර ක්‍රියාත්මක කරන්න
                                </button>
                            </div>

                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Visual feedback animation step highlights
        function updateStepHighlight(stepNum) {
            const el = document.getElementById(`icon-step-${stepNum}`);
            if (el) {
                el.style.background = 'rgba(56, 189, 248, 0.25)';
                el.style.borderColor = '#38bdf8';
                el.style.transform = 'scale(1.1)';
            }
        }

        // Load classes dynamically for selected student
        function fetchStudentClasses(studentId) {
            updateStepHighlight(1);
            const classSelect = document.getElementById('class_id');
            const fromMonthSelect = document.getElementById('from_month');
            const classLoader = document.getElementById('class_loader');

            classSelect.innerHTML = '<option value="" disabled selected>Loading classes...</option>';
            classSelect.disabled = true;
            fromMonthSelect.innerHTML = '<option value="" disabled selected>Select Class First...</option>';
            fromMonthSelect.disabled = true;
            
            classLoader.classList.remove('d-none');

            fetch(`payment_transfer.php?action=get_classes&student_id=${studentId}`)
                .then(res => res.json())
                .then(data => {
                    classLoader.classList.add('d-none');
                    classSelect.innerHTML = '';

                    if (data.length === 0) {
                        classSelect.innerHTML = '<option value="" disabled selected>No Classes Found!</option>';
                    } else {
                        classSelect.innerHTML = '<option value="" disabled selected>Select Enrolled Class...</option>';
                        data.forEach(cls => {
                            const option = document.createElement('option');
                            option.value = cls.id;
                            option.textContent = `${cls.subject} - (${cls.teacher_name ? cls.teacher_name : 'No Teacher'})`;
                            classSelect.appendChild(option);
                        });
                        classSelect.disabled = false;
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    classLoader.classList.add('d-none');
                });
        }

        // Fetch valid months: Filter by both PAID status AND Attendance <= 1
        function fetchEligibleMonths() {
            updateStepHighlight(2);
            const studentId = document.getElementById('student_id').value;
            const classId = document.getElementById('class_id').value;
            const fromMonthSelect = document.getElementById('from_month');
            const monthLoader = document.getElementById('month_loader');

            if (!studentId || !classId) return;

            fromMonthSelect.innerHTML = '<option value="" disabled selected>Validating Paid & Attendance Eligibility...</option>';
            fromMonthSelect.disabled = true;
            monthLoader.classList.remove('d-none');

            fetch(`payment_transfer.php?action=get_eligible_months&student_id=${studentId}&class_id=${classId}`)
                .then(res => res.json())
                .then(data => {
                    monthLoader.classList.add('d-none');
                    fromMonthSelect.innerHTML = '';

                    if (data.length === 0) {
                        fromMonthSelect.innerHTML = '<option value="" disabled selected>සුදුසු මාස නොමැත (Unpaid OR Attendance > 1)</option>';
                        fromMonthSelect.disabled = true;
                    } else {
                        fromMonthSelect.innerHTML = '<option value="" disabled selected>Choose Eligible Paid Month...</option>';
                        data.forEach(m => {
                            const option = document.createElement('option');
                            option.value = m.month;
                            option.textContent = `${m.month} (Paid: LKR ${m.paid_amount} | Attended: ${m.count} Day/s)`;
                            fromMonthSelect.appendChild(option);
                        });
                        fromMonthSelect.disabled = false;
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    monthLoader.classList.add('d-none');
                });
        }

        document.getElementById('from_month').addEventListener('change', () => updateStepHighlight(3));
        document.getElementById('to_month').addEventListener('change', () => updateStepHighlight(4));
    </script>
</body>
</html>