<?php 
session_start();
include 'db.php';

if(!isset($_SESSION['student_data'])) {
    header("Location: st_login.php");
    exit();
}

$s = $_SESSION['student_data'];
$sid = $s['student_id'];
$sid = mysqli_real_escape_string($conn, $sid);

/* ==========================================
   1. Fetch Student Registration Date
   ========================================== */
$student_sql = "SELECT registered_date FROM students WHERE student_id = '$sid'";
$student_res = mysqli_query($conn, $student_sql);
$student_row = mysqli_fetch_assoc($student_res);
$reg_date_str = $student_row['registered_date'] ?? date('Y-m-d');


$reg_timestamp = strtotime($reg_date_str);
$reg_day = (int)date('j', $reg_timestamp);

if ($reg_day > 25) {
    $start_billing_date = date('Y-m-01', strtotime('+1 month', $reg_timestamp));
} else {
    $start_billing_date = date('Y-m-01', $reg_timestamp);
}

$start_month_period = new DateTime($start_billing_date);
$current_month_period = new DateTime(date('Y-m-01')); 
$current_month_period->modify('+1 month'); 

$interval = new DateInterval('P1M');
$month_range = new DatePeriod($start_month_period, $interval, $current_month_period);

/* ==========================================
   2. Fetch Enrolled Classes
   ========================================== */
$classes_sql = "SELECT c.id as class_id, c.subject, c.monthly_fee 
                FROM student_classes sc
                JOIN classes c ON sc.class_id = c.id
                WHERE sc.student_id = '$sid'";
$classes_res = mysqli_query($conn, $classes_sql);

$enrolled_classes = [];
while($row = mysqli_fetch_assoc($classes_res)) {
    $enrolled_classes[] = $row;
}

/* ==========================================
   3. Fetch Existing Payments Array
   ========================================== */
$payments_sql = "SELECT class_id, month, amount, paid_date FROM payments WHERE student_id = '$sid'";
$payments_res = mysqli_query($conn, $payments_sql);

$paid_records = [];
while($row = mysqli_fetch_assoc($payments_res)) {
    $key = $row['class_id'] . "_" . $row['month'];
    $paid_records[$key] = $row;
}

/* ==========================================
   4. Generate Master Ledger & Statistics
   ========================================== */
$payment_ledger = [];
$subject_summaries = [];
$total_paid_amount = 0;
$total_pending_amount = 0;


foreach($enrolled_classes as $class) {
    $subject_summaries[$class['subject']] = [
        'paid' => 0,
        'pending' => 0
    ];
}

foreach($enrolled_classes as $class) {
    // එක් එක් විෂයට අදාළව හිඟ මුදල් පවතින පළමු මාසය හඳුනා ගැනීමට Flag එකක් භාවිත කරයි
    $unpaid_month_encountered = false;

    foreach($month_range as $date_obj) {
        $month_name = $date_obj->format('F'); 
        $year_val = $date_obj->format('Y');
        $lookup_key = $class['class_id'] . "_" . $month_name;
        
        $is_paid = isset($paid_records[$lookup_key]);
        $paid_date = $is_paid ? date('M d, Y', strtotime($paid_records[$lookup_key]['paid_date'])) : 'Pending';
        $fee = $class['monthly_fee'];

        $payable = false;

        if($is_paid) {
            $total_paid_amount += $fee;
            $subject_summaries[$class['subject']]['paid'] += $fee;
        } else {
            $total_pending_amount += $fee;
            $subject_summaries[$class['subject']]['pending'] += $fee;
            
            // පෙර හිඟ මාස නොමැති නම් පමණක් මෙම මාසය ගෙවීමට අවස්ථාව ලබා දෙයි
            if (!$unpaid_month_encountered) {
                $payable = true;
                $unpaid_month_encountered = true; // ඉදිරි මාස සියල්ල Block කරයි
            }
        }

        $payment_ledger[] = [
            'class_id' => $class['class_id'],
            'subject' => $class['subject'],
            'month' => $month_name . " " . $year_val,
            'fee' => $fee,
            'status' => $is_paid ? 'Paid' : 'Unpaid',
            'paid_date' => $paid_date,
            'payable' => $payable // Frontend එක පාලනය කිරීමට නව Variable එකක්
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sigma Institute | Premium Fee Ledger</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Round">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --bg: #0b0f19;
            --card-glass: rgba(17, 24, 39, 0.45);
            --sidebar: #070a13;
            --border-glass: rgba(255, 255, 255, 0.08);
            --gold: #f59e0b;
            --blue: #3b82f6;
            --mint: #10b981;
            --rose: #ef4444;
            --text: #f3f4f6;
            --muted: #9ca3af;
            --input-bg: rgba(31, 41, 55, 0.6);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
            background-image: radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.07) 0%, transparent 50%),
                              radial-gradient(circle at 20% 80%, rgba(16, 185, 129, 0.04) 0%, transparent 50%);
            background-attachment: fixed;
        }

        /* Responsive Layout Overrides */
        .main-content { 
            padding: 30px 20px; 
            max-width: 1600px; 
            transition: all 0.3s ease;
        }

        @media (min-width: 1025px) {
            .main-content { 
                margin-left: 280px; 
                padding: 50px 55px; 
            }
        }

        .poetry-box {
            background: linear-gradient(135deg, rgba(255,255,255,0.02), rgba(255,255,255,0.005));
            border-left: 3px solid rgba(59, 130, 246, 0.4);
            border-radius: 0 16px 16px 0;
            padding: 16px 20px;
            margin-bottom: 30px;
            font-style: italic;
            color: #d1d5db;
            backdrop-filter: blur(5px);
            font-size: 0.95rem;
        }

        @media (min-width: 768px) {
            .poetry-box { padding: 16px 24px; margin-bottom: 40px; }
        }

        /* Grid Optimization for Cards */
        .stat-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        
        @media (min-width: 768px) {
            .stat-grid { gap: 24px; margin-bottom: 40px; }
        }

        .stat-card { background: var(--card-glass); border: 1px solid var(--border-glass); border-radius: 24px; padding: 24px; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        @media (min-width: 768px) { .stat-card { padding: 32px; } }
        .stat-card:hover { transform: translateY(-5px); border-color: rgba(255,255,255,0.15); box-shadow: 0 15px 30px rgba(0,0,0,0.2); }
        
        .stat-card.glow-pending {
            border-color: rgba(239, 68, 68, 0.15);
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.04), rgba(17, 24, 39, 0.4));
        }
        .stat-card.glow-pending:hover { border-color: rgba(239, 68, 68, 0.3); box-shadow: 0 15px 30px rgba(239, 68, 68, 0.05); }
        
        .stat-card.glow-settled {
            border-color: rgba(16, 185, 129, 0.15);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.03), rgba(17, 24, 39, 0.4));
        }
        .stat-card.glow-settled:hover { border-color: rgba(16, 185, 129, 0.3); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.05); }

        .stat-value { font-size: calc(1.6rem + 0.6vw); font-weight: 800; color: #fff; line-height: 1.2; letter-spacing: -1px; }
        .stat-label { font-size: 0.8rem; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 6px; }
        .stat-icon-wrap { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }

        .sub-pill-card { background: rgba(255,255,255,0.015); border: 1px solid var(--border-glass); border-radius: 20px; padding: 20px; transition: all 0.3s ease; backdrop-filter: blur(10px); }
        .sub-pill-card:hover { border-color: rgba(255,255,255,0.12); background: rgba(255,255,255,0.03); transform: translateY(-2px); }

        .panel-card { background: var(--card-glass); border: 1px solid var(--border-glass); border-radius: 24px; padding: 20px; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); margin-bottom: 30px; }
        @media (min-width: 768px) { .panel-card { border-radius: 28px; padding: 35px; margin-bottom: 40px; } }
        
        .panel-title { font-size: 1.15rem; font-weight: 700; color: #fff; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; letter-spacing: 1px; }
        
        .filter-box { position: relative; }
        .filter-box .material-icons-round { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 20px; }
        .form-select-custom { width: 100%; background: var(--input-bg); border: 1px solid var(--border-glass); color: var(--text); padding: 14px 18px 14px 50px; border-radius: 16px; font-weight: 600; appearance: none; cursor: pointer; transition: all 0.3s; font-size: 0.9rem; backdrop-filter: blur(5px); }
        .form-select-custom:focus { outline: none; border-color: rgba(59, 130, 246, 0.5); background-color: #111827; box-shadow: 0 0 15px rgba(59, 130, 246, 0.1); }
        .filter-box::after { content: '\e5cf'; font-family: 'Material Icons Round'; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }

        /* Responsive Table Overrides */
        .table-responsive { overflow-x: auto; margin-top: 10px; border-radius: 20px; -webkit-overflow-scrolling: touch; }
        .custom-table { width: 100%; color: #fff; border-collapse: separate; border-spacing: 0 12px; min-width: 700px; }
        .custom-table th { color: var(--muted); font-size: 0.75rem; padding: 12px 24px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; border: none;}
        .custom-table td { background: rgba(255,255,255,0.015); padding: 18px 24px; border-top: 1px solid rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.02); transition: all 0.3s; }
        .custom-table tr td:first-child { border-top-left-radius: 18px; border-bottom-left-radius: 18px; border-left: 1px solid rgba(255,255,255,0.02); }
        .custom-table tr td:last-child { border-top-right-radius: 18px; border-bottom-right-radius: 18px; border-right: 1px solid rgba(255,255,255,0.02); }
        
        .payment-row { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .payment-row.row-unpaid.payable:hover td { background: rgba(239, 68, 68, 0.04) !important; border-color: rgba(239, 68, 68, 0.15); cursor: pointer; }
        .payment-row.row-paid:hover td { background: rgba(16, 185, 129, 0.03) !important; border-color: rgba(16, 185, 129, 0.15); }
        .payment-row.row-locked { opacity: 0.5; cursor: not-allowed; }

        .status-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 0.7rem; font-weight: 700; padding: 8px 14px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-paid { background: rgba(16, 185, 129, 0.08); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.15); }
        .status-unpaid { background: rgba(239, 68, 68, 0.08); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); animation: pulseGlow 3s infinite ease-in-out; }
        .status-locked { background: rgba(156, 163, 175, 0.08); color: #9ca3af; border: 1px solid rgba(156, 163, 175, 0.2); }

        @keyframes pulseGlow {
            0% { opacity: 0.9; }
            50% { opacity: 1; box-shadow: 0 0 12px rgba(239, 68, 68, 0.15); }
            100% { opacity: 0.9; }
        }

        /* Modal Responsiveness */
        .premium-modal .modal-content { background: #0e1322; border: 1px solid var(--border-glass); border-radius: 24px; padding: 15px; color: #fff; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        @media (min-width: 576px) { .premium-modal .modal-content { border-radius: 28px; padding: 20px; } }
        .premium-modal .modal-header { border-bottom: none; padding-bottom: 10px; }
        .premium-modal .modal-footer { border-top: none; }
        .pay-summary-box { background: rgba(255,255,255,0.015); border-radius: 20px; padding: 20px; margin-bottom: 25px; border: 1px solid rgba(255,255,255,0.04); }
        .btn-checkout { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; border: none; padding: 16px; border-radius: 16px; font-weight: 700; width: 100%; transition: all 0.3s ease; letter-spacing: 0.5px; }
        .btn-checkout:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(59, 130, 246, 0.35); color: white; }

        .no-records-row { display: none; }
        
        /* Compatibility for Mobile App Sidebar Integration */
        @media (max-width: 1024px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>

<body>
    <?php include 'st_sidebar.php'; ?>
   
    <main class="main-content">
        <header class="page-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 animate__animated animate__fadeIn">
            <div>
                <h1 style="font-weight: 800; letter-spacing: -1px; margin-bottom: 4px;">Finance Desk</h1>
                <p style="color: var(--muted); font-weight: 500; margin: 0; font-size: 0.95rem;">Review statements, verify payment history, and settle up-to-date fees.</p>
            </div>
            <span class="material-icons-round text-secondary d-none d-md-block" style="font-size: 2.8rem; opacity: 0.15;">payments</span>
        </header>

        <div class="poetry-box animate__animated animate__fadeIn">
            <span class="material-icons-round" style="font-size: 18px; vertical-align: middle; margin-right: 5px; color: var(--blue);">auto_stories</span>
           "දැනුම අදම ආයෝජනය කරන්න, මන්ද එහි ප්‍රතිලාභ ජීවිත කාලය පුරාම පවතී. සෑම සන්ධිස්ථානයක්ම ඔබේ උදාර ගමනාන්තය කරා යන මාර්ගයකි."
        </div>

        <div class="stat-grid animate__animated animate__fadeInUp">
            <div class="stat-card glow-pending">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value" style="color: #f87171;">Rs. <?php echo number_format($total_pending_amount, 2); ?></div>
                        <div class="stat-label" style="color: rgba(248,113,113,0.7);">Total Outstanding Fee</div>
                    </div>
                    <div class="stat-icon-wrap" style="background: rgba(239, 68, 68, 0.08); color: #f87171;">
                        <span class="material-icons-round">error_outline</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card glow-settled">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value" style="color: #34d399;">Rs. <?php echo number_format($total_paid_amount, 2); ?></div>
                        <div class="stat-label">Total Settled Amount</div>
                    </div>
                    <div class="stat-icon-wrap" style="background: rgba(16, 185, 129, 0.08); color: #34d399;">
                        <span class="material-icons-round">check_circle_outline</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value" style="font-size: 1.4rem; padding-top: 8px;"><?php echo date('F d, Y', $reg_timestamp); ?></div>
                        <div class="stat-label">Enrollment Milestone Date</div>
                    </div>
                    <div class="stat-icon-wrap" style="background: rgba(59, 130, 246, 0.08); color: #60a5fa;">
                        <span class="material-icons-round">auto_award</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4 g-3 animate__animated animate__fadeInUp" style="animation-delay: 0.05s;">
            <?php foreach($subject_summaries as $sub_title => $data): ?>
                <div class="col-xl-4 col-md-6">
                    <div class="sub-pill-card d-flex justify-content-between align-items-center">
                        <div style="min-width: 0; padding-right: 10px;">
                            <div class="fw-bold text-white text-truncate" style="letter-spacing: 0.3px; font-size: 0.9rem;"><?php echo htmlspecialchars($sub_title); ?></div>
                            <div class="text-muted" style="font-size: 0.8rem; margin-top: 4px;">Paid: Rs. <?php echo number_format($data['paid']); ?></div>
                        </div>
                        <?php if($data['pending'] > 0): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 flex-shrink-0" style="font-size: 0.75rem; font-weight: 700;">
                                Due: Rs.<?php echo number_format($data['pending']); ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 flex-shrink-0" style="font-size: 0.75rem; font-weight: 700;">
                                Clear
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="panel-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
            <div class="panel-title">
                <span class="material-icons-round text-primary">analytics</span>
                Statement Invoices Breakdown
            </div>

            <div class="filter-wrapper row g-3 mb-4">
                <div class="col-sm-6 filter-box">
                    <span class="material-icons-round">menu_book</span>
                    <select id="subjectFilter" class="form-select-custom">
                        <option value="ALL">All Subjects</option>
                        <?php foreach($enrolled_classes as $class): ?>
                            <option value="<?php echo htmlspecialchars($class['subject']); ?>"><?php echo htmlspecialchars($class['subject']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 filter-box">
                    <span class="material-icons-round">tune</span>
                    <select id="statusFilter" class="form-select-custom">
                        <option value="ALL">All Status Tracking</option>
                        <option value="Paid">Paid Receipts</option>
                        <option value="Unpaid">Unpaid / Due Invoices</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="custom-table" id="paymentTable">
                    <thead>
                        <tr>
                            <th>Enrolled Course</th>
                            <th>Billing Cycle Period</th>
                            <th>Net Fee Amount</th>
                            <th>Transaction Handover Date</th>
                            <th>Status Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payment_ledger as $row): 
                            if ($row['status'] === 'Paid') {
                                $row_class = 'row-paid';
                            } elseif ($row['payable']) {
                                $row_class = 'row-unpaid payable';
                            } else {
                                $row_class = 'row-unpaid row-locked';
                            }
                        ?>
                            <tr class="payment-row <?php echo $row_class; ?>" 
                                data-subject="<?php echo htmlspecialchars($row['subject']); ?>" 
                                data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                <?php if($row['status'] === 'Unpaid' && $row['payable']): ?>
                                onclick="openPaymentGateway('<?php echo htmlspecialchars($row['subject']); ?>', '<?php echo $row['month']; ?>', '<?php echo $row['fee']; ?>', '<?php echo $row['class_id']; ?>')"
                                <?php endif; ?>>
                                
                                <td class="fw-bold text-white"><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2 text-secondary">
                                        <span class="material-icons-round text-muted" style="font-size: 16px;">calendar_month</span>
                                        <?php echo $row['month']; ?>
                                    </div>
                                </td>
                                <td class="fw-bold <?php echo $row['status'] === 'Unpaid' ? 'text-danger' : 'text-white'; ?>">
                                    Rs. <?php echo number_format($row['fee'], 2); ?>
                                </td>
                                <td class="<?php echo $row['status'] === 'Unpaid' ? 'text-danger fw-semibold' : 'text-muted'; ?>">
                                    <?php echo $row['paid_date']; ?>
                                </td>
                                <td>
                                    <?php if($row['status'] === 'Paid'): ?>
                                        <span class="status-badge status-paid">
                                            <span class="material-icons-round" style="font-size: 14px;">verified</span>Paid
                                        </span>
                                    <?php elseif($row['payable']): ?>
                                        <span class="status-badge status-unpaid">
                                            <span class="material-icons-round" style="font-size: 14px;">bolt</span>Pay Now
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-locked" title="Please pay the previous month first">
                                            <span class="material-icons-round" style="font-size: 14px;">lock</span>Locked
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <tr id="noRecordsRow" class="no-records-row">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <span class="material-icons-round d-block mb-2" style="font-size: 2.5rem; opacity: 0.3;">search_off</span>
                                No financial configuration declarations match your filtering setups.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <div class="modal fade premium-modal" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><span class="material-icons-round text-success align-middle me-2">shield</span>Secure Checkout</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">You are about to initiate an encrypted online transaction for your institute course subscription.</p>
                    
                    <div class="pay-summary-box">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted small">Course</span>
                            <span id="modalSubject" class="text-white fw-bold text-end ms-2">Subject</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted small">Billing Cycle</span>
                            <span id="modalMonth" class="text-white fw-semibold text-end ms-2">Month</span>
                        </div>
                        <hr style="border-color: rgba(255,255,255,0.08)">
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="fw-bold text-white">Amount Due</span>
                            <span id="modalFee" class="fw-800 text-success fs-4">Rs. 0.00</span>
                        </div>
                    </div>

                    <form action="initiate_payment.php" method="POST">
                        <input type="hidden" name="class_id" id="formClassId">
                        <input type="hidden" name="billing_month" id="formMonth">
                        <input type="hidden" name="amount" id="formAmount">
                        <button type="submit" class="btn-checkout">
                            <span class="material-icons-round align-middle me-2" style="font-size: 18px;">credit_card</span>Proceed to Payment Gateway
                        </button>
                    </form>
                </div>
                <div class="modal-footer justify-content-center">
                    <div class="text-muted" style="font-size: 0.7rem; display: flex; align-items: center; gap: 4px;">
                        <span class="material-icons-round" style="font-size: 12px; color: #34d399;">lock</span> 256-bit SSL Encrypted Transaction System
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const subjectFilter = document.getElementById("subjectFilter");
            const statusFilter = document.getElementById("statusFilter");
            const rows = document.querySelectorAll(".payment-row");
            const noRecordsRow = document.getElementById("noRecordsRow");

            function executeFiltering() {
                const targetSubject = subjectFilter.value;
                const targetStatus = statusFilter.value;
                let recordsFound = 0;

                rows.forEach(row => {
                    const subjectAttr = row.getAttribute("data-subject");
                    const statusAttr = row.getAttribute("data-status");

                    const matchSubject = (targetSubject === "ALL" || subjectAttr === targetSubject);
                    const matchStatus = (targetStatus === "ALL" || statusAttr === targetStatus);

                    if (matchSubject && matchStatus) {
                        row.style.display = "";
                        recordsFound++;
                    } else {
                        row.style.display = "none";
                    }
                });

                noRecordsRow.style.display = (recordsFound === 0) ? "table-row" : "none";
            }

            subjectFilter.addEventListener("change", executeFiltering);
            statusFilter.addEventListener("change", executeFiltering);
        });

        function openPaymentGateway(subject, month, fee, classId) {
            document.getElementById('modalSubject').innerText = subject;
            document.getElementById('modalMonth').innerText = month;
            document.getElementById('modalFee').innerText = 'Rs. ' + parseFloat(fee).toLocaleString(undefined, {minimumFractionDigits: 2});
            
            document.getElementById('formClassId').value = classId;
            document.getElementById('formMonth').value = month;
            document.getElementById('formAmount').value = fee;

            var myModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            myModal.show();
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>