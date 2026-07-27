<?php 
if (session_status() === PHP_SESSION_NONE) {
     session_start();
}

// 🔐 Admin සහ Superadmin ආරක්ෂාව තහවුරු කිරීම
$allowed_roles = ['admin', 'superadmin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
     header("Location: dashboard.php?error=unauthorized");
     exit();
}

/** @var mysqli $conn */
include "db.php"; 
date_default_timezone_set("Asia/Colombo"); 

$today = date('Y-m-d'); 
$current_month = date('F'); 

/* =========================================================================
   🤖 AUTOMATIC MONTHLY REGISTRATION
   ========================================================================= */
$check_month_query = mysqli_query($conn, "SELECT 1 FROM payments WHERE month = '$current_month' LIMIT 1");
if (mysqli_num_rows($check_month_query) == 0) {
    $auto_reg_sql = "INSERT INTO payments (student_id, class_id, month, amount, paid_date)
                     SELECT sc.student_id, sc.class_id, '$current_month', 0.00, NULL
                     FROM student_classes sc";
    mysqli_query($conn, $auto_reg_sql);
}

/* ============================= LIVE SEARCH ============================= */ 
if(isset($_GET['live_search'])){ 
     header('Content-Type: application/json'); 
     $name = mysqli_real_escape_string($conn, $_GET['student_name']); 
     $sql = "SELECT student_id, student_name FROM students WHERE student_name LIKE '%$name%' LIMIT 10"; 
     $res = mysqli_query($conn, $sql); 
     $data = []; 
     while($row = mysqli_fetch_assoc($res)){ $data[] = $row; } 
     echo json_encode($data); 
     exit; 
} 

/* ============================= GET ATTENDANCE & FEE LOGIC ============================= */
if(isset($_GET['get_attendance_fee'])){
    header('Content-Type: application/json');
    $student_id = intval($_GET['student_id']);
    $class_id = intval($_GET['class_id']);
    $month_name = mysqli_real_escape_string($conn, $_GET['month']);

    $check_paid = mysqli_query($conn, "SELECT 1 FROM payments WHERE student_id = $student_id AND class_id = $class_id AND month = '$month_name' AND amount > 0 LIMIT 1");
    $is_paid = (mysqli_num_rows($check_paid) > 0) ? true : false;

    $class_query = mysqli_query($conn, "SELECT monthly_fee FROM classes WHERE id = $class_id LIMIT 1");
    $class_data = mysqli_fetch_assoc($class_query);
    $base_fee = floatval($class_data['monthly_fee'] ?? 0);

    $att_sql = "SELECT date, status 
                FROM attendance 
                WHERE student_id = $student_id 
                AND class_id = $class_id 
                AND (MONTHNAME(date) = '$month_name' OR DATE_FORMAT(date, '%F') = '$month_name')
                ORDER BY date ASC";
                
    $att_query = mysqli_query($conn, $att_sql);
    
    $dates = [];
    $attendance_count = 0;
    while($row = mysqli_fetch_assoc($att_query)) {
        $dates[] = [
            'date' => $row['date'],
            'status' => $row['status']
        ];
        if($row['status'] == '1' || strtolower($row['status']) == 'present') {
            $attendance_count++;
        }
    }

    $final_fee = $base_fee;
    $status_msg = "Full Payment";

    echo json_encode([
        'is_paid' => $is_paid,
        'attendance_count' => $attendance_count,
        'dates' => $dates,
        'base_fee' => $base_fee, 
        'final_fee' => $final_fee,
        'status_msg' => $status_msg
    ]);
    exit;
}

/* ============================= SEARCH STUDENT + UNPAID MONTHS ============================= */ 
if(isset($_GET['search_student'])){ 
     header('Content-Type: application/json'); 
     $name = trim(mysqli_real_escape_string($conn, $_GET['student_name'])); 
     
     $sql ="SELECT s.student_id, s.student_name, s.registered_grade, s.registered_date, 
                   c.id AS class_id, c.subject, c.monthly_fee, IFNULL(t.name, 'No Teacher Assigned') as teacher_name
            FROM students s 
            LEFT JOIN student_classes sc ON s.student_id = sc.student_id 
            LEFT JOIN classes c ON sc.class_id = c.id 
            LEFT JOIN teachers t ON c.teacher_id = t.id
            WHERE s.student_name LIKE '%$name%'"; 
           
     $res = mysqli_query($conn, $sql); 
     $subjects = []; 
     $student_info = null; 
     
     if(mysqli_num_rows($res) > 0){ 
         while($row = mysqli_fetch_assoc($res)){ 
             if(!$student_info){ 
                $student_info = [
                   'student_id' => $row['student_id'], 
                   'student_name' => $row['student_name'], 
                   'registered_grade' => $row['registered_grade'],
                   'registered_date' => $row['registered_date']
                ];
             } 
             
             if(!empty($row['class_id'])){
                 $current_loop_class_id = intval($row['class_id']);

                 $paid_months = [];
                 $pq = mysqli_query($conn,"SELECT month FROM payments WHERE student_id = {$row['student_id']} AND class_id = $current_loop_class_id AND amount > 0");
                 while($pm = mysqli_fetch_assoc($pq)){
                     $paid_months[] = $pm['month'];
                 }

                 $start = new DateTime($row['registered_date']);
                 $end = new DateTime();
                 $end->modify('first day of this month');
                 $interval = new DateInterval('P1M');
                 $period = new DatePeriod($start, $interval, $end);
                 
                 $all_months = [];
                 foreach ($period as $dt) {
                     $all_months[] = $dt->format("F");
                 }
                 $all_months[] = date("F"); 
                 $all_months = array_unique($all_months);

                 $pending_months = array_values(array_diff($all_months, $paid_months));

                 $subjects[] = [
                     'class_id' => $current_loop_class_id,
                     'subject' => $row['subject'],
                     'teacher_name' => $row['teacher_name'],
                     'fee' => $row['monthly_fee'],
                     'all_months' => $all_months,
                     'pending_months' => $pending_months,
                     'paid_months' => $paid_months
                 ];
             }
         } 
         
         $st_id = $student_info['student_id']; 
         
         $h_sql = "SELECT p.month, p.amount, p.paid_date, CONCAT(c.subject, ' (', IFNULL(t.name, 'No Teacher'), ')') as subject 
                   FROM payments p 
                   JOIN classes c ON p.class_id = c.id 
                   LEFT JOIN teachers t ON c.teacher_id = t.id
                   WHERE p.student_id = $st_id AND p.amount > 0
                   ORDER BY p.paid_date DESC, p.id DESC LIMIT 5"; 
         $h_res = mysqli_query($conn, $h_sql); 
         $history = []; 
         while($h_row = mysqli_fetch_assoc($h_res)){ $history[] = $h_row; } 
         
         echo json_encode(['student' => $student_info, 'subjects' => $subjects, 'history' => $history]); 
     } else { 
         echo json_encode(['error' => 'not_found']); 
     } 
     exit; 
} 

/* ============================= SAVE PAYMENT ============================= */ 
if(isset($_POST['add_payment'])){ 
     $st_id = intval($_POST['student_id']); 
     $month = mysqli_real_escape_string($conn, $_POST['month']); 
     if(empty($month)){ echo json_encode(['status' => 'error', 'message' => 'invalid_month']); exit; }
     $classes_data = $_POST['classes'] ?? []; 
     
     $saved_details = [];
     
     foreach($classes_data as $cls){
         $cl_id = intval($cls['id']);
         $amt = floatval($cls['amount']);

         $cls_info = mysqli_query($conn, "SELECT subject FROM classes WHERE id = $cl_id LIMIT 1");
         $cls_row = mysqli_fetch_assoc($cls_info);
         $sub_name = $cls_row['subject'] ?? 'Subject';

         $check_exist = mysqli_query($conn,"SELECT id FROM payments WHERE student_id = $st_id AND class_id = $cl_id AND month = '$month' LIMIT 1");
         if(mysqli_num_rows($check_exist) > 0){
             mysqli_query($conn,"UPDATE payments SET amount = '$amt', paid_date = '$today' WHERE student_id = $st_id AND class_id = $cl_id AND month = '$month'");
         }else{
             mysqli_query($conn,"INSERT INTO payments(student_id, class_id, month, amount, paid_date) VALUES ('$st_id', '$cl_id', '$month', '$amt', '$today')");
         }

         $saved_details[] = [
             'subject' => $sub_name,
             'amount' => $amt
         ];
     }

     $receipt_no = "REC-" . date('Ymd') . "-" . rand(1000, 9999);

     echo json_encode([
         'status' => 'success',
         'receipt_no' => $receipt_no,
         'date' => date('Y-m-d H:i:s'),
         'items' => $saved_details
     ]); 
     exit;
}

/* ============================= REPORT LOGIC (COMPLETE BACKLOG FETCH & GRADE SORT) ============================= */
if(isset($_GET['get_paid_report'])){
    header('Content-Type: application/json');
    $selected_month = mysqli_real_escape_string($conn, $_GET['month']);
    $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
    if($class_id == 0) { echo json_encode(['regular'=>[], 'backlog'=>[]]); exit; }
    
    // 🔥 තෝරාගත් මාසයේ සිදු කළ සියලුම ගෙවීම් (Regular & Backlog) එකතු කිරීම
    $sql = "SELECT s.student_id, s.student_name, s.registered_grade, 
                   p.month AS payment_for_month, p.paid_date, p.amount 
            FROM payments p 
            JOIN students s ON p.student_id = s.student_id 
            WHERE (
                MONTHNAME(p.paid_date) = '$selected_month'
                OR p.month = '$selected_month'
            )
            AND p.class_id = $class_id 
            AND p.amount > 0
            ORDER BY CAST(s.registered_grade AS UNSIGNED) ASC, s.student_name ASC";
            
    $res = mysqli_query($conn, $sql);
    
    $regular_payments = [];
    $backlog_payments = [];
    
    while($row = mysqli_fetch_assoc($res)){ 
        $paid_month_name = !empty($row['paid_date']) ? date('F', strtotime($row['paid_date'])) : '';
        
        // Regular Payment -> ගෙවීම් කළ මාසය සහ ගෙවන ලද්දේ අදාළ මාසයටම නම් පමණි
        if(strcasecmp($row['payment_for_month'], $selected_month) == 0 && strcasecmp($paid_month_name, $selected_month) == 0){
            $regular_payments[] = $row;
        } else {
            // පසුගිය මාස සඳහා මෙම මාසයේ කළ ගෙවීම් (Backlog)
            $backlog_payments[] = $row;
        }
    }
    
    echo json_encode([
        'regular' => $regular_payments,
        'backlog' => $backlog_payments
    ]); 
    exit;
}

if(isset($_GET['get_classes_list'])){
    header('Content-Type: application/json');
    $sql = "SELECT c.id, c.subject, IFNULL(t.name, 'No Teacher Assigned') as teacher_name FROM classes c LEFT JOIN teachers t ON c.teacher_id = t.id ORDER BY c.subject ASC";
    $res = mysqli_query($conn, $sql);
    $classes_list = [];
    while($row = mysqli_fetch_assoc($res)){ $classes_list[] = $row; }
    echo json_encode($classes_list); exit;
}

$ms = ["January","February","March","April","May","June","July","August","September","October","November","December"];
?>
<!DOCTYPE html> 
<html lang="en"> 
<head> 
     <meta charset="UTF-8"> 
     <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
     <title>Payment Portal | SIGMA ERP</title> 
     <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"> 
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"> 
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
     <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script> 
     <style>
        :root { 
            --bg-dark: #0a0a0c; --card-dark: #16161a; --accent-blue: #3b82f6; --electric-blue: #00d2ff; --text-gray: #94a3b8; --sidebar-width: 280px;
        }
        body { background-color: var(--bg-dark); color: #ffffff; font-family: 'Inter', sans-serif; overflow-x: hidden; min-height: 100vh; }
        
        .main-content { margin-left: var(--sidebar-width); padding: 40px; transition: all 0.3s ease; }
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0; padding: 20px; }
        }

        .card { background: var(--card-dark); border: 1px solid #222; border-radius: 24px; color: white; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        
        .search-container { background: #111115; border: 1px solid #222; border-radius: 15px; padding: 8px 8px 8px 20px; flex-wrap: wrap; }
        .search-input { background: transparent !important; border: none !important; color: white !important; box-shadow: none !important; min-width: 200px; flex: 1; }

        .subject-item { background: #111115; border: 1px solid #222; padding: 20px; border-radius: 15px; cursor: pointer; transition: 0.3s; display: flex; justify-content: space-between; align-items: center; position: relative; gap: 10px; }
        .subject-check:checked + .subject-item { background: rgba(59, 130, 246, 0.1); border-color: var(--accent-blue); box-shadow: 0 0 15px rgba(59, 130, 246, 0.2); }
        .subject-item.paid-card { background: rgba(22, 163, 74, 0.05) !important; border-color: rgba(22, 163, 74, 0.3) !important; cursor: not-allowed; opacity: 0.8; }
        .summary-box { background: linear-gradient(145deg, #1e293b, #0f172a); border-radius: 20px; padding: 30px; border: 1px solid #334155; }
        .btn-pay { background: linear-gradient(45deg, var(--accent-blue), var(--electric-blue)); color: white; border: none; border-radius: 12px; font-weight: 700; transition: 0.3s; }
        .btn-pay:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4); }
        .form-select, .form-control { background-color: #111115; border: 1px solid #222; color: white; border-radius: 10px; }
        .form-select:focus, .form-control:focus { background-color: #111115; border-color: var(--accent-blue); color: white; box-shadow: none; }
        
        .table { --bs-table-bg: transparent !important; --bs-table-color: #e2e8f0 !important; --bs-table-border-color: #222 !important; margin-bottom: 0; }
        .table thead tr { background: #111115 !important; }
        .table thead th { color: #94a3b8 !important; border-color: #222 !important; font-weight: 600; white-space: nowrap; }
        .table tbody tr { background: #16161a !important; transition: 0.3s; }
        .table tbody tr:hover { background: #1e293b !important; }
        .table td, .table th { border-color: #222 !important; vertical-align: middle; white-space: nowrap; }
        .table-responsive { background: #16161a; border-radius: 16px; overflow-x: auto; }
        
        .list-group-item { background: #111115 !important; color: white !important; border: 1px solid #222 !important; cursor: pointer; }
        .list-group-item:hover { background: #1e293b !important; }
        .report-pill { background: #111115; padding: 20px; border-radius: 15px; border: 1px solid #222; }
        
        /* 📄 FIXED PDF STYLES FOR PERFECT MULTI-PAGE OUTPUT */
        .pdf-outer-container { 
            position: fixed; 
            top: 0; 
            left: 0; 
            z-index: -9999; 
            width: 100%; 
            height: 0;
            overflow: hidden; 
            visibility: hidden;
        }
        
        #report-pdf-area { 
            width: 190mm; 
            margin: 0 auto; 
            background: #ffffff !important; 
            color: #0f172a !important; 
            padding: 10mm; 
            font-family: 'Inter', Helvetica, Arial, sans-serif;
            box-sizing: border-box;
        }

        .pdf-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .pdf-brand h1 { margin: 0; color: #1e3a8a; font-size: 20px; font-weight: 800; letter-spacing: 0.5px; }
        .pdf-brand p { margin: 2px 0 0 0; color: #64748b; font-size: 10px; font-weight: 600; text-transform: uppercase; }

        .pdf-meta-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #334155;
        }

        .pdf-grade-header {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 800;
            font-size: 11px;
            padding: 5px 8px;
            border-left: 4px solid #1e3a8a;
            margin-top: 10px;
            margin-bottom: 4px;
            page-break-after: avoid;
        }

        .pdf-section-title {
            font-size: 12px;
            font-weight: 800;
            color: #1e3a8a;
            text-transform: uppercase;
            margin: 15px 0 6px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            page-break-after: avoid;
        }

        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            page-break-inside: auto;
        }

        .pdf-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .pdf-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            padding: 6px 8px;
            text-align: left;
            text-transform: uppercase;
        }

        .pdf-table td {
            padding: 5px 8px;
            font-size: 10px;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
        }

        .pdf-table tr:nth-child(even) td { background-color: #f8fafc; }

        .pdf-summary-card {
            background: #f8fafc;
            border: 2px solid #1e3a8a;
            border-radius: 8px;
            padding: 10px 15px;
            margin-top: 15px;
            font-size: 11px;
            page-break-inside: avoid;
        }

        .pdf-summary-table { width: 100%; border-collapse: collapse; }
        .pdf-summary-table td { padding: 4px 0; color: #334155; }
        .pdf-summary-table .highlight-row td { border-top: 1px dashed #cbd5e1; padding-top: 6px; font-weight: 800; font-size: 12px; color: #0f172a; }

        .modal-content { background-color: #16161a; border: 1px solid #333; border-radius: 20px; }
        .modal-header { border-bottom: 1px solid #222; }
        .modal-footer { border-top: 1px solid #222; }
        .modal-body { color: #fff; }

        #receiptPrintArea {
            background: #ffffff;
            color: #000000;
            width: 80mm;
            padding: 15px;
            margin: 0 auto;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.3;
        }

        @media print {
            body * { visibility: hidden; }
            #receiptPrintArea, #receiptPrintArea * { visibility: visible; }
            #receiptPrintArea { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; }
        }
     </style> 
</head> 
<body> 

<?php include 'sidebar.php'; ?>
<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5 header-wrapper">
        <div>
            <h3 class="fw-bold mb-1">Payment Portal</h3>
            <p class="text-secondary small mb-0">මුදල් ගෙවීම් සහ ආදායම් වාර්තා කළමනාකරණය</p>
        </div>
        <div class="text-end">
            <h5 class="mb-0 text-info fw-bold"><?php echo date('l, F j'); ?></h5>
            <small class="text-secondary">System Date</small>
        </div>
    </div>

    <!-- Student Search & Payment Section -->
    <div class="card p-3 p-md-4 mb-5">
        <div class="row justify-content-center mb-5">
            <div class="col-md-10 position-relative">
                <div class="search-container d-flex align-items-center">
                    <i class="bi bi-search text-secondary me-2"></i>
                    <input type="text" id="student_name_input" class="form-control search-input" placeholder="🔍 ශිෂ්‍යයාගේ නම මෙතනින් සොයන්න..." onkeyup="liveSearch()" autocomplete="off">
                    <button class="btn btn-pay px-4 py-2 ms-2" onclick="searchStudent()">සොයන්න</button>
                </div>
                <div id="suggestions" class="list-group position-absolute w-100 mt-2 z-3 shadow-lg"></div>
            </div>
        </div>

        <div id="student_section" style="display:none">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center mb-4 p-3 rounded-4" style="background: rgba(255,255,255,0.03); border: 1px solid #222;">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3"><i class="bi bi-person text-primary fs-3"></i></div>
                        <div>
                            <h4 id="student_name_disp" class="fw-bold mb-0"></h4>
                            <span class="badge bg-primary rounded-pill px-3 mt-1">Grade <span id="display_grade"></span></span>
                        </div>
                    </div>
                    
                    <h6 class="fw-bold mb-3 text-secondary">විෂයයන් ලැයිස්තුව සහ තත්වය:</h6>
                    <div id="subject_list" class="row g-3"></div>
                </div>
                
                <div class="col-lg-5">
                    <div class="summary-box h-100">
                        <div id="month_dropdown_wrapper">
                            <label class="small fw-bold text-secondary mb-2">ගෙවීම් කරන මාසය</label>
                            <select id="month" class="form-select mb-4" onchange="triggerMonthAttendanceChange()"></select>
                        </div>
                        
                        <div class="mb-4">
                            <span class="small fw-bold text-secondary d-block mb-1">මුළු එකතුව (LKR)</span>
                            <h2 class="fw-800 text-info">Rs. <span id="total_amount_display">0.00</span></h2>
                        </div>
                        <button class="btn btn-pay w-100 py-3 fs-5" id="payBtn" onclick="submitPayment()">ගෙවීම තහවුරු කරන්න</button>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3 gap-2">
                    <h6 class="fw-bold text-secondary mb-0"><i class="bi bi-clock-history me-2"></i>පසුගිය ගෙවීම් වාර්තා (ළඟම මාස 5 පමණි)</h6>
                    <input type="text" id="history_search_input" class="form-control form-control-sm" style="max-width:250px" placeholder="🔍 මාසය හෝ විෂය සොයන්න..." onkeyup="filterHistoryTable()">
                </div>
                <div class="table-responsive rounded-3 border border-secondary border-opacity-25">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th class="ps-4">මාසය</th><th>විෂය</th><th>මුදල</th><th>දිනය</th><th class="text-end pe-4">තත්වය</th></tr>
                        </thead>
                        <tbody id="payment_history"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="empty_state" class="text-center py-5">
            <i class="bi bi-wallet2 text-secondary opacity-25" style="font-size: 4rem;"></i>
            <p class="text-secondary mt-3">ගෙවීම් සිදු කිරීම සඳහා ශිෂ්‍යයෙකු සොයන්න.</p>
        </div>
    </div>

    <!-- Reports Section -->
    <div class="card p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <div id="report_title_section">
                <h4 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i>ආදායම් වාර්තාව</h4>
                <small id="report_selected_meta" class="text-secondary mt-1 d-block fw-bold"></small>
            </div>
            <button id="download_btn" class="btn btn-danger btn-sm fw-bold d-none px-4 rounded-pill" onclick="downloadReportPDF()">
                <i class="bi bi-file-earmark-pdf me-1"></i> Full Report PDF බාගත කරන්න
            </button>
        </div>

        <div class="row g-3 mb-4 p-3 p-md-4 rounded-4" style="background: rgba(255,255,255,0.02); border: 1px solid #222;">
            <div class="col-md-4">
                <label class="small fw-bold mb-2">මාසය (ගෙවීම සිදු කළ මාසය)</label>
                <select id="report_month" class="form-select"><?php foreach($ms as $m){ $sel = ($m == $current_month)? 'selected':''; echo "<option value='$m' $sel>$m</option>"; } ?></select>
            </div>
            <div class="col-md-4">
                <label class="small fw-bold mb-2">විෂය (ගුරුවරයා සමඟ)</label>
                <select id="report_class" class="form-select"><option value="">පන්තිය තෝරන්න...</option></select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary w-100 py-2 fw-bold" onclick="generateReport()">වාර්තාව බලන්න</button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-3"><div class="report-pill text-center"><small class="text-secondary">මේ මාසයේ මුදල්</small><div id="sum_regular" class="fs-5 fw-bold text-white">0.00</div></div></div>
            <div class="col-sm-3"><div class="report-pill text-center"><small class="text-warning">පසුගිය මාසවල (Backlog)</small><div id="sum_backlog" class="fs-5 fw-bold text-warning">0.00</div></div></div>
            <div class="col-sm-3"><div class="report-pill text-center"><small class="text-secondary">මුළු ආදායම (Total)</small><div id="sum_total" class="fs-5 fw-bold text-info">0.00</div></div></div>
            <div class="col-sm-3"><div class="report-pill text-center"><small class="text-secondary">ගුරු භාගය (80%)</small><div id="sum_bal" class="fs-5 fw-bold text-success">0.00</div></div></div>
        </div>

        <!-- UI Tables -->
        <h6 class="fw-bold text-info mb-2"><i class="bi bi-calendar-check me-2"></i>මේ මාසයේ අය කරගත් මුදල් (Regular Month Payments)</h6>
        <div class="table-responsive rounded-3 border border-secondary border-opacity-10 mb-4">
            <table class="table table-hover mb-0">
                <thead><tr><th>ID</th><th>Grade</th><th>Student Name</th><th>Paid For</th><th>Amount</th><th>Date</th></tr></thead>
                <tbody id="report_table_regular"></tbody>
            </table>
        </div>

        <h6 class="fw-bold text-warning mb-2"><i class="bi bi-clock-history me-2"></i>පසුගිය මාස සඳහා අය කරගත් මුදල් (Backlog Payments)</h6>
        <div class="table-responsive rounded-3 border border-secondary border-opacity-10">
            <table class="table table-hover mb-0">
                <thead><tr><th>ID</th><th>Grade</th><th>Student Name</th><th>Paid For Month</th><th>Amount</th><th>Date</th></tr></thead>
                <tbody id="report_table_backlog"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Attendance Details Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-info fw-bold"><i class="bi bi-calendar-check me-2"></i>පැමිණීම් දින සහ ගාස්තු සාරාංශය</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="modal_attendance_body" class="d-flex flex-column gap-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- RECEIPT SLIP POPUP MODAL -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark border-secondary">
      <div class="modal-header border-secondary">
        <h5 class="modal-title text-success fw-bold"><i class="bi bi-receipt me-2"></i>Payment Receipt</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light p-3">
        <div id="receiptPrintArea">
            <div style="text-align: center; margin-bottom: 10px;">
                <h3 style="margin:0; font-size: 16px; font-weight: bold;">SIGMA INSTITUTE</h3>
                <p style="margin:0; font-size: 10px;">Higher Education Center</p>
                <p style="margin:0; font-size: 10px;">Tel: 077-1234567 / 011-2345678</p>
                <div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>
            </div>
            <div style="margin-bottom: 8px;">
                <div><strong>Receipt No:</strong> <span id="rec_no"></span></div>
                <div><strong>Date:</strong> <span id="rec_date"></span></div>
                <div><strong>Student ID:</strong> <span id="rec_sid"></span></div>
                <div><strong>Student Name:</strong> <span id="rec_sname"></span></div>
                <div><strong>Month:</strong> <span id="rec_month"></span></div>
            </div>
            <div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>
            <table style="width: 100%; font-size: 11px; margin-bottom: 8px;" id="rec_items_table">
                <thead>
                    <tr style="border-bottom: 1px solid #000;">
                        <th style="text-align: left;">Subject</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 13px;">
                <span>TOTAL PAID:</span>
                <span id="rec_total_amount">Rs. 0.00</span>
            </div>
            <div style="border-bottom: 1px dashed #000; margin: 8px 0;"></div>
            <div style="text-align: center; margin-top: 10px; font-size: 10px;">
                <p style="margin: 0;">Thank You & Good Luck!</p>
                <p style="margin: 0; font-style: italic;">System Generated Receipt</p>
            </div>
        </div>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success fw-bold px-4" onclick="printReceipt()"><i class="bi bi-printer me-2"></i>Print Receipt</button>
      </div>
    </div>
  </div>
</div>

<!-- 📄 HIDDEN PDF CONTAINER FOR PERFECT CLEAN RENDERING -->
<div class="pdf-outer-container" id="pdf_container_wrapper">
    <div id="report-pdf-area">
        <div class="pdf-header">
            <div class="pdf-brand">
                <h1>SIGMA EDUCATION INSTITUTE</h1>
                <p>Monthly Class Income Statement</p>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 10px; color: #64748b;">Generated On:</div>
                <div style="font-size: 11px; font-weight: 700; color: #0f172a;"><?php echo date('Y-m-d h:i A'); ?></div>
            </div>
        </div>

        <div class="pdf-meta-box">
            <div><strong>Subject:</strong> <span id="pdf_meta_subject">N/A</span></div>
            <div><strong>Teacher:</strong> <span id="pdf_meta_teacher">N/A</span></div>
            <div><strong>Collection Month:</strong> <span id="pdf_meta_month">N/A</span></div>
        </div>

        <!-- Dynamic Tables Container -->
        <div id="pdf_tables_container"></div>

        <!-- Summary Calculation Box -->
        <div class="pdf-summary-card">
            <table class="pdf-summary-table">
                <tr>
                    <td style="width: 70%;">Regular Month Income (<span id="pdf_lbl_month"></span>):</td>
                    <td style="text-align: right; font-weight: 600;" id="pdf_sum_regular">Rs. 0.00</td>
                </tr>
                <tr>
                    <td>Previous Months Arrears (Backlog Collection):</td>
                    <td style="text-align: right; font-weight: 600; color: #d97706;" id="pdf_sum_backlog">+ Rs. 0.00</td>
                </tr>
                <tr class="highlight-row">
                    <td>Gross Total Collections:</td>
                    <td style="text-align: right; color: #1e3a8a;" id="pdf_sum_gross">Rs. 0.00</td>
                </tr>
                <tr>
                    <td style="color: #dc2626;">Institute Service Fee Deduction (20%):</td>
                    <td style="text-align: right; color: #dc2626; font-weight: 600;" id="pdf_sum_inst">- Rs. 0.00</td>
                </tr>
                <tr style="border-top: 2px solid #1e3a8a;">
                    <td style="padding-top: 6px; font-size: 13px; font-weight: 800; color: #15803d;">Teacher Net Payable Amount (80%):</td>
                    <td style="padding-top: 6px; font-size: 13px; font-weight: 800; text-align: right; color: #15803d;" id="pdf_sum_net">Rs. 0.00</td>
                </tr>
            </table>
        </div>

        <div style="margin-top: 25px; display: flex; justify-content: space-between; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 6px;">
            <span>System Generated Financial Statement | SIGMA ERP</span>
            <span>Official Collection Copy</span>
        </div>
    </div>
</div>

<input type="hidden" id="student_id"> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
 let classTeachers = {};
 let currentReportData = { regular: [], backlog: [] }; 
 let studentSubjectsGlobal = [];
 let fullPaymentHistory = [];
 const currentMonthName = "<?php echo $current_month; ?>";
 const userRole = "<?php echo $_SESSION['role'] ?? 'admin'; ?>";

 function liveSearch(){
     const k=document.getElementById("student_name_input").value;
     if(k.length<2){ document.getElementById("suggestions").innerHTML=""; return; }
     fetch(`payment.php?live_search=true&student_name=${encodeURIComponent(k)}`).then(r=>r.json()).then(data=>{
         let l=""; data.forEach(s=>{ l+=`<a class="list-group-item list-group-item-action py-3 px-4 border-0" onclick="selectStudent('${s.student_name.replace(/'/g, "\'")}')">${s.student_name}</a>`; });
         document.getElementById("suggestions").innerHTML=l;
     });
 }

 function selectStudent(n){ 
    document.getElementById("student_name_input").value=n; 
    document.getElementById("suggestions").innerHTML=""; 
    searchStudent(); 
 }

 function searchStudent(){
     const n=document.getElementById("student_name_input").value;
     if(!n) return;
     fetch(`payment.php?search_student=true&student_name=${encodeURIComponent(n)}`).then(r=>r.json()).then(data=>{
         if(data.error){ alert("එවැනි ශිෂ්‍යයෙකු හමු නොවීය."); return; }
         
         document.getElementById("empty_state").style.display="none";
         document.getElementById("student_section").style.display="block";
         document.getElementById("student_name_disp").innerText=data.student.student_name;
         document.getElementById("display_grade").innerText=data.student.registered_grade;
         document.getElementById("student_id").value=data.student.student_id;
         
         studentSubjectsGlobal = data.subjects;
         fullPaymentHistory = data.history; 

         let pendingMonthsSet = new Set();
         let allPaidMonthsSet = new Set();

         if(data.subjects && data.subjects.length > 0) {
             data.subjects.forEach(s => {
                 s.pending_months.forEach(m => pendingMonthsSet.add(m));
                 s.paid_months.forEach(m => allPaidMonthsSet.add(m));
             });
         }

         if(userRole === 'superadmin'){
             allPaidMonthsSet.forEach(m => {
                 if(!pendingMonthsSet.has(m)){
                     allPaidMonthsSet.add(m); 
                 }
             });
         }

         const monthSelect = document.getElementById("month");
         monthSelect.innerHTML = "";
         
         if(pendingMonthsSet.size === 0) {
             monthSelect.innerHTML = `<option value="">සියලුම මාස ගෙවා ඇත</option>`;
             document.getElementById("payBtn").disabled = true;
         } else {
             document.getElementById("payBtn").disabled = false;
             let hasCurrentMonth = pendingMonthsSet.has(currentMonthName);
             
             pendingMonthsSet.forEach(mName => {
                 let selected = "";
                 if(hasCurrentMonth && mName === currentMonthName) { selected = "selected"; }
                 else if(!hasCurrentMonth && monthSelect.children.length === 0) { selected = "selected"; }
                 
                 monthSelect.innerHTML += `<option value="${mName}" ${selected}>${mName}</option>`;
             });
         }

         triggerMonthAttendanceChange();
         renderHistoryTable(fullPaymentHistory);
     });
 }

 function triggerMonthAttendanceChange() {
     const selectedMonth = document.getElementById("month").value;
     if(selectedMonth) {
         renderSubjectList(selectedMonth);
     } else {
         document.getElementById("subject_list").innerHTML = "";
         updateTotal();
     }
 }

 function renderSubjectList(targetMonth) {
     const studentId = document.getElementById("student_id").value;
     const sListContainer = document.getElementById("subject_list");
     
     if(!studentSubjectsGlobal || studentSubjectsGlobal.length === 0) {
         sListContainer.innerHTML = `
         <div class="col-12">
            <div class="alert alert-warning border border-warning border-opacity-25 bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>මෙම ශිෂ්‍යයා තවමත් කිසිදු පන්තියකට/විෂයකට ලියාපදිංචි වී නොමැත!
            </div>
         </div>`;
         updateTotal();
         return;
     }

     sListContainer.innerHTML = `<div class="col-12 text-secondary small"><i class="fa fa-spinner fa-spin me-2"></i>දත්ත පරීක්ෂා කරමින්...</div>`;

     let promises = studentSubjectsGlobal.map(s => {
         return fetch(`payment.php?get_attendance_fee=true&student_id=${studentId}&class_id=${s.class_id}&month=${targetMonth}`)
         .then(r => r.json())
         .then(attDetails => {
             let finalPaidStatus = attDetails.is_paid;
             let requiredFee = attDetails.final_fee;

             if(userRole === 'superadmin') {
                 requiredFee = attDetails.base_fee; 
             }

             return {
                 ...s,
                 is_paid: finalPaidStatus, 
                 attendance_count: attDetails.attendance_count,
                 dates: attDetails.dates,
                 calculated_fee: requiredFee, 
                 status_msg: attDetails.status_msg
             };
         });
     });

     Promise.all(promises).then(updatedSubjects => {
         let sList = "";
         let modalHTML = "";
         let hasUnpaidData = false;
         let visibleCheckboxesCount = 0;

         updatedSubjects.forEach(s => {
             if(s.is_paid) {
                 sList += `
                 <div class="col-md-6">
                    <input type="checkbox" class="subject-check d-none" id="sub_${s.class_id}" value="${s.class_id}" data-fee="0" disabled>
                    <div class="subject-item paid-card w-100">
                        <div>
                            <div class="fw-bold mb-0 text-secondary" style="text-decoration: line-through;">${s.subject}</div>
                            <small class="text-secondary d-block">${s.teacher_name}</small>
                            <span class="badge bg-success mt-1"><i class="bi bi-check-circle-fill me-1"></i>Paid</span>
                        </div>
                        <div class="text-muted small fw-bold">Rs. 0.00</div>
                    </div>
                 </div>`;
             } else {
                 hasUnpaidData = true;
                 visibleCheckboxesCount++;
                 
                 let badgeHTML = "";
                 if(userRole === 'superadmin') {
                     badgeHTML = `<span class="badge bg-info mt-1"><i class="bi bi-shield-check me-1"></i>Superadmin Mode</span>`;
                 } else {
                     let badgeClass = "bg-success";
                     if(s.attendance_count <= 1) badgeClass = "bg-danger";
                     else if(s.attendance_count == 2) badgeClass = "bg-warning text-dark";
                     badgeHTML = `<span class="badge ${badgeClass} mt-1">පැමිණීම්: ${s.attendance_count}</span>`;
                 }

                 sList += `
                 <div class="col-md-6">
                    <input type="checkbox" class="subject-check d-none" id="sub_${s.class_id}" value="${s.class_id}" data-fee="${s.calculated_fee}" onchange="updateTotal()" checked>
                    <label class="subject-item w-100" for="sub_${s.class_id}">
                        <div>
                            <div class="fw-bold mb-0">${s.subject}</div>
                            <small class="text-secondary d-block">${s.teacher_name}</small>
                            ${badgeHTML}
                        </div>
                        <div class="fw-bold text-info fs-5">Rs. ${parseFloat(s.calculated_fee).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                    </label>
                 </div>`;

                 if(userRole !== 'superadmin') {
                     let datesListHTML = "";
                     if(s.dates.length === 0) {
                         datesListHTML = `<div class="text-danger small mt-1">⚠️ මෙම මාසයේ පැමිණීම් වාර්තා වී නොමැත.</div>`;
                     } else {
                         s.dates.forEach(d => {
                             let stBadge = (d.status == '1' || d.status.toLowerCase() == 'present') ? '<span class="badge bg-success">Present</span>' : '<span class="badge bg-danger">Absent</span>';
                             datesListHTML += `<div class="d-flex justify-content-between align-items-center bg-dark bg-opacity-20 p-2 rounded mt-1 border border-secondary border-opacity-10"><span class="small">${d.date}</span>${stBadge}</div>`;
                         });
                     }

                     modalHTML += `
                     <div class="p-3 rounded-4 mb-2" style="background: rgba(255,255,255,0.02); border: 1px solid #333;">
                        <div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-20 pb-2 mb-2">
                            <div>
                                <h6 class="fw-bold text-white mb-0">${s.subject}</h6>
                                <small class="text-secondary">${s.teacher_name}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge ${s.attendance_count <= 1 ? 'bg-danger' : s.attendance_count == 2 ? 'bg-warning text-dark' : 'bg-success'}">${s.status_msg} (පැමිණීම්: ${s.attendance_count})</span>
                                <div class="fw-bold text-info mt-1">Rs. ${parseFloat(s.calculated_fee).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                            </div>
                        </div>
                        <div>
                            <label class="text-secondary" style="font-size:12px;">පැමිණි දින ලේඛනය:</label>
                            ${datesListHTML}
                        </div>
                     </div>`;
                 }
             }
         });

         sListContainer.innerHTML = sList;
         updateTotal();

         if(visibleCheckboxesCount === 0) {
             document.getElementById("payBtn").disabled = true;
         } else {
             document.getElementById("payBtn").disabled = false;
         }

         if(userRole !== 'superadmin' && hasUnpaidData && modalHTML !== "") {
             document.getElementById("modal_attendance_body").innerHTML = modalHTML;
             let myModal = new bootstrap.Modal(document.getElementById('attendanceModal'));
             myModal.show();
         }
     });
 }

 function updateTotal() {
     let t = 0; 
     document.querySelectorAll('.subject-check:checked').forEach(c => {
         t += parseFloat(c.getAttribute('data-fee')) || 0;
     });
     document.getElementById("total_amount_display").innerText = t.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
 }

 function submitPayment(){
     const checks = document.querySelectorAll('.subject-check:checked');
     const selectedMonth = document.getElementById("month").value;
     if(!selectedMonth || selectedMonth === "සියලුම මාස ගෙවා ඇත"){ alert("කරුණාකර වලංගු මාසයක් තෝරන්න."); return; }
     if(checks.length === 0){ alert("කරුණාකර ගෙවීම සඳහා අවම වශයෙන් එක් විෂයයක්වත් තෝරන්න."); return; }
     
     const payBtn = document.getElementById("payBtn");
     payBtn.disabled = true; payBtn.innerText = "Processing...";

     const fd = new FormData(); 
     fd.append("add_payment", true);
     fd.append("student_id", document.getElementById("student_id").value);
     fd.append("month", selectedMonth);
     checks.forEach((c, i) => { 
        fd.append(`classes[${i}][id]`, c.value); 
        fd.append(`classes[${i}][amount]`, c.getAttribute('data-fee')); 
     });

     fetch("payment.php", {method:"POST", body:fd})
     .then(r=>r.json())
     .then(res=>{ 
        if(res.status === "success"){
            document.getElementById("rec_no").innerText = res.receipt_no;
            document.getElementById("rec_date").innerText = res.date;
            document.getElementById("rec_sid").innerText = document.getElementById("student_id").value;
            document.getElementById("rec_sname").innerText = document.getElementById("student_name_disp").innerText;
            document.getElementById("rec_month").innerText = selectedMonth;

            let tableBody = "";
            let grandTotal = 0;
            res.items.forEach(item => {
                let itemAmt = parseFloat(item.amount) || 0;
                grandTotal += itemAmt;
                tableBody += `
                <tr>
                    <td style="text-align: left;">${item.subject}</td>
                    <td style="text-align: right;">Rs. ${itemAmt.toFixed(2)}</td>
                </tr>`;
            });

            document.querySelector("#rec_items_table tbody").innerHTML = tableBody;
            document.getElementById("rec_total_amount").innerText = "Rs. " + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            let receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
            receiptModal.show();

            searchStudent();
        } else {
            alert("Payment Failed: " + (res.message || "Unknown error"));
        }
        payBtn.disabled = false; payBtn.innerText = "ගෙවීම තහවුරු කරන්න";
     })
     .catch(err => {
         alert("ගෙවීමේ පද්ධති දෝෂයක් සිදුවිය.");
         payBtn.disabled = false; payBtn.innerText = "ගෙවීම තහවුරු කරන්න";
     });
 }

 function printReceipt() {
     window.print();
 }

 function renderHistoryTable(historyData) {
     let hHTML = ""; 
     if(historyData.length === 0) {
         hHTML = `<tr><td colspan="5" class="text-center text-secondary py-3">දත්ත කිසිවක් හමු නොවීය.</td></tr>`;
     } else {
         historyData.forEach(i => {
             hHTML += `<tr><td class="ps-4">${i.month}</td><td>${i.subject}</td><td class="fw-bold text-info">Rs.${parseFloat(i.amount).toLocaleString()}</td><td>${i.paid_date}</td><td class="text-end pe-4"><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Paid</span></td></tr>`;
         });
     }
     document.getElementById("payment_history").innerHTML = hHTML;
 }

 function filterHistoryTable() {
     const searchKey = document.getElementById("history_search_input").value.toLowerCase().trim();
     if(searchKey === "") { renderHistoryTable(fullPaymentHistory); return; }
     const filtered = fullPaymentHistory.filter(item => {
         return item.month.toLowerCase().includes(searchKey) || item.subject.toLowerCase().includes(searchKey);
     });
     renderHistoryTable(filtered);
 }

 function loadClasses(){
    fetch('payment.php?get_classes_list=true').then(r=>r.json()).then(data=>{
        let opt = '<option value="">විෂය තෝරන්න...</option>';
        classTeachers = {}; 
        data.forEach(c=>{ 
            opt += `<option value="${c.id}">${c.subject} (${c.teacher_name})</option>`; 
            classTeachers[c.id] = c.teacher_name; 
        });
        document.getElementById("report_class").innerHTML = opt;
    });
 }

 function generateReport(){
    const m = document.getElementById("report_month").value;
    const cid = document.getElementById("report_class").value;
    if(!cid){ alert("කරුණාකර පන්තියක් තෝරන්න."); return; }
    
    fetch(`payment.php?get_paid_report=true&month=${m}&class_id=${cid}`)
    .then(r => r.json())
    .then(data => {
        currentReportData = data; 
        const regBody = document.getElementById("report_table_regular");
        const backBody = document.getElementById("report_table_backlog");
        regBody.innerHTML = "";
        backBody.innerHTML = "";

        const reportClassSelect = document.getElementById("report_class");
        const fullText = reportClassSelect.options[reportClassSelect.selectedIndex].text;
        document.getElementById("report_selected_meta").innerHTML = `<i class="bi bi-info-circle me-1"></i> ${fullText} - ${m} Collection Report`;

        let regTotal = 0;
        let backTotal = 0;

        if(!data.regular || data.regular.length === 0){
            regBody.innerHTML = `<tr><td colspan="6" class="text-center text-secondary py-3">මෙම මාසයේ සාමාන්‍ය ගෙවීම් වාර්තා වී නොමැත.</td></tr>`;
        } else {
            data.regular.forEach(row => {
                let amt = parseFloat(row.amount) || 0;
                regTotal += amt;
                regBody.innerHTML += `<tr><td>${row.student_id}</td><td><span class="badge bg-secondary">Grade ${row.registered_grade}</span></td><td>${row.student_name}</td><td><span class="badge bg-success px-2">${row.payment_for_month}</span></td><td class="text-info fw-bold">Rs. ${amt.toLocaleString()}</td><td>${row.paid_date}</td></tr>`;
            });
        }

        if(!data.backlog || data.backlog.length === 0){
            backBody.innerHTML = `<tr><td colspan="6" class="text-center text-secondary py-3">පසුගිය මාස සඳහා ගෙවීම් වාර්තා වී නොමැත.</td></tr>`;
        } else {
            data.backlog.forEach(row => {
                let amt = parseFloat(row.amount) || 0;
                backTotal += amt;
                backBody.innerHTML += `<tr><td>${row.student_id}</td><td><span class="badge bg-secondary">Grade ${row.registered_grade}</span></td><td>${row.student_name}</td><td><span class="badge bg-warning text-dark px-2">${row.payment_for_month}</span></td><td class="text-warning fw-bold">Rs. ${amt.toLocaleString()}</td><td>${row.paid_date}</td></tr>`;
            });
        }

        const grandTotal = regTotal + backTotal;
        const bal = grandTotal * 0.80; // 80% Teacher Share

        document.getElementById("sum_regular").innerText = "Rs. " + regTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById("sum_backlog").innerText = "Rs. " + backTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById("sum_total").innerText = "Rs. " + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById("sum_bal").innerText = "Rs. " + bal.toLocaleString('en-US', {minimumFractionDigits: 2});

        if(grandTotal > 0){
            document.getElementById("download_btn").classList.remove("d-none");
        } else {
            document.getElementById("download_btn").classList.add("d-none");
        }
    });
 }

 /* =========================================================================
    📄 FIXED PDF GENERATION FUNCTION (MULTI-PAGE & ZERO OFFSET FIX)
    ========================================================================= */
 function downloadReportPDF() {
    if(!currentReportData || ((!currentReportData.regular || currentReportData.regular.length === 0) && (!currentReportData.backlog || currentReportData.backlog.length === 0))) {
        alert("බාගත කිරීමට දත්ත නොමැත.");
        return;
    }

    const reportClassSelect = document.getElementById("report_class");
    const selectedClassId = reportClassSelect.value;
    const fullClassText = reportClassSelect.options[reportClassSelect.selectedIndex].text;
    const selectedMonth = document.getElementById("report_month").value;
    const teacherName = classTeachers[selectedClassId] || "N/A";

    document.getElementById("pdf_meta_subject").innerText = fullClassText;
    document.getElementById("pdf_meta_teacher").innerText = teacherName;
    document.getElementById("pdf_meta_month").innerText = selectedMonth;
    document.getElementById("pdf_lbl_month").innerText = selectedMonth;

    let tablesContainerHTML = "";
    let regTotal = 0;
    let backTotal = 0;

    function groupByGrade(items) {
        return items.reduce((acc, obj) => {
            let key = obj.registered_grade || 'Other';
            if (!acc[key]) { acc[key] = []; }
            acc[key].push(obj);
            return acc;
        }, {});
    }

    // 1. Regular Month Payments Section
    if(currentReportData.regular && currentReportData.regular.length > 0) {
        tablesContainerHTML += `
        <div class="pdf-section-title">
            <span>1. Regular Payments (${selectedMonth})</span>
            <span style="font-size:10px; color:#64748b;">Total Students: ${currentReportData.regular.length}</span>
        </div>`;

        let groupedRegular = groupByGrade(currentReportData.regular);
        Object.keys(groupedRegular).sort((a,b)=>a-b).forEach(grade => {
            let rowsHTML = "";
            groupedRegular[grade].forEach(row => {
                let amt = parseFloat(row.amount) || 0;
                regTotal += amt;
                rowsHTML += `
                <tr>
                    <td style="text-align: center;">${row.student_id}</td>
                    <td style="font-weight: 600;">${row.student_name}</td>
                    <td style="text-align: center;"><span style="color:#16a34a; font-weight:700;">${row.payment_for_month}</span></td>
                    <td style="text-align: right; font-weight: 700;">Rs. ${amt.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    <td style="text-align: center;">${row.paid_date}</td>
                </tr>`;
            });

            tablesContainerHTML += `
            <div class="pdf-grade-header">Grade ${grade}</div>
            <table class="pdf-table">
                <thead>
                    <tr>
                        <th style="width: 12%; text-align: center;">ID</th>
                        <th style="width: 40%;">Student Name</th>
                        <th style="width: 18%; text-align: center;">Month</th>
                        <th style="width: 15%; text-align: right;">Amount</th>
                        <th style="width: 15%; text-align: center;">Paid Date</th>
                    </tr>
                </thead>
                <tbody>${rowsHTML}</tbody>
            </table>`;
        });
    }

    // 2. Backlog / Arrears Payments Section
    if(currentReportData.backlog && currentReportData.backlog.length > 0) {
        tablesContainerHTML += `
        <div class="pdf-section-title" style="color: #b45309; margin-top: 15px;">
            <span>2. Arrears & Previous Months Collection (Backlog)</span>
            <span style="font-size:10px; color:#64748b;">Total Students: ${currentReportData.backlog.length}</span>
        </div>`;

        let groupedBacklog = groupByGrade(currentReportData.backlog);
        Object.keys(groupedBacklog).sort((a,b)=>a-b).forEach(grade => {
            let rowsHTML = "";
            groupedBacklog[grade].forEach(row => {
                let amt = parseFloat(row.amount) || 0;
                backTotal += amt;
                rowsHTML += `
                <tr>
                    <td style="text-align: center;">${row.student_id}</td>
                    <td style="font-weight: 600;">${row.student_name}</td>
                    <td style="text-align: center;"><span style="color:#d97706; font-weight:700;">${row.payment_for_month}</span></td>
                    <td style="text-align: right; font-weight: 700; color:#d97706;">Rs. ${amt.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    <td style="text-align: center;">${row.paid_date}</td>
                </tr>`;
            });

            tablesContainerHTML += `
            <div class="pdf-grade-header" style="border-left-color: #b45309;">Grade ${grade}</div>
            <table class="pdf-table">
                <thead>
                    <tr style="background-color: #b45309;">
                        <th style="width: 12%; text-align: center;">ID</th>
                        <th style="width: 40%;">Student Name</th>
                        <th style="width: 18%; text-align: center;">Paid For</th>
                        <th style="width: 15%; text-align: right;">Amount</th>
                        <th style="width: 15%; text-align: center;">Paid Date</th>
                    </tr>
                </thead>
                <tbody>${rowsHTML}</tbody>
            </table>`;
        });
    }

    document.getElementById("pdf_tables_container").innerHTML = tablesContainerHTML;

    const grossTotal = regTotal + backTotal;
    const instFee = grossTotal * 0.20;
    const teacherNet = grossTotal * 0.80;

    document.getElementById("pdf_sum_regular").innerText = "Rs. " + regTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById("pdf_sum_backlog").innerText = "+ Rs. " + backTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById("pdf_sum_gross").innerText = "Rs. " + grossTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById("pdf_sum_inst").innerText = "- Rs. " + instFee.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById("pdf_sum_net").innerText = "Rs. " + teacherNet.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    // Temporary Make Container Visible for Crisp Capture
    const wrapper = document.getElementById("pdf_container_wrapper");
    wrapper.style.visibility = "visible";
    wrapper.style.height = "auto";

    const element = document.getElementById("report-pdf-area");
    const opt = {
        margin:       [6, 6, 6, 6],
        filename:     `Income_Report_${selectedMonth}_${selectedClassId}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, scrollY: 0 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:    { mode: ['css', 'legacy'] }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        wrapper.style.visibility = "hidden";
        wrapper.style.height = "0";
    }).catch(err => {
        wrapper.style.visibility = "hidden";
        wrapper.style.height = "0";
        console.error("PDF generation failed: ", err);
        alert("PDF එක සෑදීමේදී දෝෂයක් ඇති විය.");
    });
 }

 window.onload = function() {
     loadClasses();
 };
</script> 
</body> 
</html>