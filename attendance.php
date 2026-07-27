<?php 
/** @var mysqli $conn */
session_start();
include "db.php"; 

// 1. පරිශීලකයා පද්ධතියට ඇතුළු වී ඇත්දැයි සහ භූමිකාව (Role) පරීක්ෂාව
$allowed_roles = ['superadmin', 'admin', 'teacher', 'assistant'];

if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)){
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role']; 
$user_id   = $_SESSION['user_id'] ?? 0;
$is_superadmin = ($user_role === 'superadmin'); // Superadmin පමණක් හඳුනා ගැනීම

date_default_timezone_set("Asia/Colombo");
$today = date('Y-m-d');
$time  = date('H:i:s');

// Filter කිරීමට අවශ්‍ය දිනය ලබා ගැනීම (Default: අද දිනය)
$selected_date = isset($_REQUEST['attendance_date']) ? trim($_REQUEST['attendance_date']) : $today;
$is_past_date = (strtotime($selected_date) < strtotime($today));

/*
|--------------------------------------------------------------------------
| ACCESS CONTROL (Superadmin ට පමණක් Past Attendance Mark කළ හැක)
|--------------------------------------------------------------------------
*/
$can_view_attendance = true; 
$can_mark_attendance = false;

if ($is_superadmin) {
    // Superadmin ට ඕනෑම දිනයක Attendance mark කළ හැක
    $can_mark_attendance = true;
} else {
    // අනෙකුත් සියලුම Roles සඳහා Past Dates Block වේ (අද දිනයට පමණක් අවසර ඇත)
    if (!$is_past_date && in_array($user_role, ['admin', 'teacher', 'assistant'])) {
        $can_mark_attendance = true;
    }
}

// MONTH FORMAT FIX: ප්‍රධාන පිටුව සඳහා මාස ආකෘති සකසා ගැනීම
$current_month_name = date('F', strtotime($selected_date));     
$current_month_full = date('F Y', strtotime($selected_date));   
$prev_month_name    = date('F', strtotime("$selected_date -1 month"));
$prev_month_full    = date('F Y', strtotime("$selected_date -1 month"));

$current_month_ym = date('Y-m', strtotime($selected_date));
$prev_month_ym    = date('Y-m', strtotime("$selected_date -1 month"));


/* ========================================================
   🎯 AUDIT LOG HELPER FUNCTION
   ======================================================== */
function logSuperadminAction($conn, $user_id, $user_role, $action, $student_id, $old_val, $new_val) {
    if ($user_role === 'superadmin') {
        $stmt = $conn->prepare("INSERT INTO system_logs (user_id, user_role, action, student_id, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ississ", $user_id, $user_role, $action, $student_id, $old_val, $new_val);
            $stmt->execute();
            $stmt->close();
        }
    }
}


/* ========================================================
   🎯 HELPER FUNCTION: TIMETABLE & EXTENDED TIME SLOT VALIDATION
   ======================================================== */
function validateClassTimeSlot($conn, $cl_id, $att_date, $is_superadmin = false) {
    if ($is_superadmin) {
        return ["valid" => true, "reason" => "ok", "message" => "Superadmin override active"];
    }

    $day_of_week = date('l', strtotime($att_date));
    $current_timestamp = time();

    $class_res = mysqli_query($conn, "SELECT subject FROM classes WHERE id = '$cl_id'");
    $class_row = mysqli_fetch_assoc($class_res);
    $subject_name = $class_row['subject'] ?? 'Selected Class';

    $tt_query = mysqli_query($conn, "
        SELECT start_time, end_time, day_of_week 
        FROM class_timetable 
        WHERE class_id = '$cl_id' AND LOWER(day_of_week) = LOWER('$day_of_week')
    ");

    if (mysqli_num_rows($tt_query) == 0) {
        $all_days_query = mysqli_query($conn, "SELECT DISTINCT day_of_week FROM class_timetable WHERE class_id = '$cl_id'");
        $scheduled_days = [];
        while($d = mysqli_fetch_assoc($all_days_query)) {
            $scheduled_days[] = $d['day_of_week'];
        }
        $days_str = !empty($scheduled_days) ? implode(", ", $scheduled_days) : "කිසිදු දිනයක් වෙන්කර නැත";

        return [
            "valid" => false,
            "reason" => "not_scheduled",
            "message" => "<b>{$subject_name}</b> පන්තිය මෙම දිනයේදී ({$day_of_week}) පැවැත්වෙන්නේ නැත.<br><small class='text-warning'>මෙම පන්තිය පැවැත්වෙන නිවැරදි දිනයන්: {$days_str}</small>"
        ];
    }

    $tt_row = mysqli_fetch_assoc($tt_query);
    $start_time = $tt_row['start_time']; 

    $ext_stmt = $conn->prepare("SELECT SUM(extended_minutes) as total_ext FROM attendance_extensions WHERE class_id = ? AND DATE(created_at) = ?");
    $ext_stmt->bind_param("is", $cl_id, $att_date);
    $ext_stmt->execute();
    $ext_res = $ext_stmt->get_result()->fetch_assoc();
    $extra_seconds = ($ext_res['total_ext'] ? (int)$ext_res['total_ext'] : 0) * 60;
    $ext_stmt->close();

    $class_start_timestamp = strtotime("$att_date $start_time");
    $window_start = $class_start_timestamp - 3600;
    $window_end = $class_start_timestamp + 1800 + $extra_seconds;

    $formatted_start_time = date('h:i A', $class_start_timestamp);
    $formatted_window_end = date('h:i A', $window_end);

    if ($current_timestamp < $window_start) {
        return [
            "valid" => false,
            "reason" => "too_early",
            "message" => "<b>{$subject_name}</b> පන්තිය ආරම්භ වන්නේ <b>{$formatted_start_time}</b> ට ය.<br><small class='text-info'>Attendance mark කිරීමට හැකි වන්නේ පන්තිය ආරම්භ වීමට පැයකට පෙර සිට පමණි.</small>"
        ];
    }

    if ($current_timestamp > $window_end) {
        return [
            "valid" => false,
            "reason" => "time_expired",
            "message" => "<b>{$subject_name}</b> පන්තියේ Attendance mark කිරීමේ කාලය අවසන් වී ඇත.<br><small class='text-danger'>පන්තිය ආරම්භ වී පැය භාගයකට පසු ({$formatted_window_end} න් පසු) Attendance ස්වයංක්‍රීයව අගුලු වැටේ.</small>"
        ];
    }

    return ["valid" => true, "reason" => "ok", "message" => "Valid date and time slot"];
}


/* ========================================================
   C. ACTION: EXTEND ATTENDANCE TIME SLOT (AJAX)
   ======================================================== */
if (isset($_POST['action']) && $_POST['action'] === 'extend_time') {
    if (!in_array($user_role, ['superadmin', 'admin', 'teacher'])) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized operation.']);
        exit;
    }

    $class_id = intval($_POST['class_id']);
    $minutes  = intval($_POST['minutes']);
    
    if ($class_id > 0 && $minutes > 0) {
        $stmt = $conn->prepare("INSERT INTO attendance_extensions (class_id, extended_minutes, extended_by) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $class_id, $minutes, $user_id);
        
        if ($stmt->execute()) {
            logSuperadminAction($conn, $user_id, $user_role, "Extended Attendance Lock Time", null, "Class ID: $class_id", "Added $minutes mins");
            echo json_encode(['status' => 'success', 'message' => "Attendance marking window successfully extended by {$minutes} minutes."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to execute database statement.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid parameters provided.']);
    }
    exit;
}


/* ========================================================
   A. CHECK ELIGIBILITY VIA AJAX
   ======================================================== */
if(isset($_POST['check_eligibility'])) {
    $st_id = intval($_POST['student_id']);
    $cl_id = intval($_POST['class_id']);
    
    $ajax_date = isset($_POST['attendance_date']) ? trim($_POST['attendance_date']) : date('Y-m-d');

    $time_check = validateClassTimeSlot($conn, $cl_id, $ajax_date, $is_superadmin);
    if(!$time_check['valid']) {
        echo json_encode([
            "status" => "time_error",
            "allow" => false,
            "reason" => $time_check['reason'],
            "message" => $time_check['message']
        ]);
        exit;
    }

    $ajax_current_month_ym = date('Y-m', strtotime($ajax_date));
    $ajax_prev_month_ym    = date('Y-m', strtotime("$ajax_date -1 month"));

    $ajax_curr_month_name  = date('F', strtotime($ajax_date));
    $ajax_curr_month_full  = date('F Y', strtotime($ajax_date));
    $ajax_prev_month_name  = date('F', strtotime("$ajax_date -1 month"));
    $ajax_prev_month_full  = date('F Y', strtotime("$ajax_date -1 month"));

    $curr_weeks_res = mysqli_query($conn, "SELECT COUNT(DISTINCT WEEK(date)) as weeks FROM attendance WHERE student_id='$st_id' AND class_id='$cl_id' AND DATE_FORMAT(date, '%Y-%m') = '$ajax_current_month_ym'");
    $curr_weeks_row = mysqli_fetch_assoc($curr_weeks_res);
    $current_weeks_attended = intval($curr_weeks_row['weeks'] ?? 0);

    $prev_weeks_res = mysqli_query($conn, "SELECT COUNT(DISTINCT WEEK(date)) as weeks FROM attendance WHERE student_id='$st_id' AND class_id='$cl_id' AND DATE_FORMAT(date, '%Y-%m') = '$ajax_prev_month_ym'");
    $prev_weeks_row = mysqli_fetch_assoc($prev_weeks_res);
    $prev_weeks_attended = intval($prev_weeks_row['weeks'] ?? 0);

    $pay_check = mysqli_query($conn, "
        SELECT id FROM payments 
        WHERE student_id='$st_id' 
        AND class_id='$cl_id' 
        AND (LOWER(month)=LOWER('$ajax_prev_month_name') OR LOWER(month)=LOWER('$ajax_prev_month_full'))
    ");
    $is_prev_paid = (mysqli_num_rows($pay_check) > 0);

    $curr_pay_check = mysqli_query($conn, "
        SELECT id FROM payments 
        WHERE student_id='$st_id' 
        AND class_id='$cl_id' 
        AND (LOWER(month)=LOWER('$ajax_curr_month_name') OR LOWER(month)=LOWER('$ajax_curr_month_full'))
    ");
    $is_current_paid = (mysqli_num_rows($curr_pay_check) > 0);

    $allow = true;
    $reason = "allowed";
    $fee_status = "Full Fee Required";

    if ($prev_weeks_attended == 2 && !$is_prev_paid) {
        $fee_status = "Half Fee Required for Previous Month";
    } elseif ($prev_weeks_attended <= 1) {
        $fee_status = "Free/No payment required for Previous Month";
    }

    if($prev_weeks_attended == 0 && $current_weeks_attended >= 2 && !$is_current_paid) {
        $allow = false;
        $reason = "consecutive_absent_block";
    }
    elseif($prev_weeks_attended > 1 && $is_prev_paid && $current_weeks_attended >= 2 && !$is_current_paid) {
        $allow = false;
        $reason = "third_week_unpaid";
    }
    elseif($prev_weeks_attended > 1 && !$is_prev_paid && !$is_current_paid) {
        $allow = false;
        $reason = "prev_month_unpaid";
    }

    echo json_encode([
        "status" => "success",
        "allow" => $allow,
        "reason" => $reason,
        "prev_weeks" => $prev_weeks_attended,
        "current_weeks" => $current_weeks_attended,
        "prev_paid" => $is_prev_paid ? "Paid" : "Not Paid",
        "current_paid" => $is_current_paid ? "Paid" : "Not Paid",
        "fee_suggestion" => $fee_status
    ]);
    exit;
}

/* ========================================================
   B. CONFIRM AND EXECUTE MARKING
   ======================================================== */
if(isset($_POST['execute_marking'])){
    if(!$can_mark_attendance){
        echo "unauthorized";
        exit;
    }

    $st_id = intval($_POST['student_id']);
    $cl_id = intval($_POST['class_id']);
    $att_date = mysqli_real_escape_string($conn, $_POST['attendance_date']);

    $time_check = validateClassTimeSlot($conn, $cl_id, $att_date, $is_superadmin);
    if(!$time_check['valid']) {
        echo "time_restricted";
        exit;
    }

    $check = mysqli_query($conn,"SELECT id FROM attendance WHERE student_id='$st_id' AND class_id='$cl_id' AND date='$att_date'");

    if(mysqli_num_rows($check) > 0){
        mysqli_query($conn,"DELETE FROM attendance WHERE student_id='$st_id' AND class_id='$cl_id' AND date='$att_date'");
        if ($is_superadmin && $is_past_date) {
            logSuperadminAction($conn, $user_id, $user_role, "Removed Past Attendance", $st_id, "Present on $att_date", "Marked Absent");
        }
        echo "absent";
    } else {
        mysqli_query($conn,"INSERT INTO attendance(student_id,class_id,date,time) VALUES('$st_id','$cl_id','$att_date','$time')");
        if ($is_superadmin && $is_past_date) {
            logSuperadminAction($conn, $user_id, $user_role, "Marked Past Attendance", $st_id, "Absent on $att_date", "Marked Present");
        }
        echo "present";
    }
    exit;
}


/* ========================================================
   3. LOAD CLASSES & GRADES WITH TIMETABLE DETAILS
======================================================== */
$resClasses = mysqli_query($conn,"
    SELECT c.id, c.subject, cg.grade, t.name AS teacher_name,
           ct.day_of_week, ct.start_time, ct.end_time 
    FROM classes c
    JOIN class_grades cg ON c.id = cg.class_id
    LEFT JOIN teachers t ON c.teacher_id = t.id
    LEFT JOIN class_timetable ct ON c.id = ct.class_id
    ORDER BY cg.grade ASC, c.subject ASC
");

$classes_data = [];
$unique_grades = [];

while($row=mysqli_fetch_assoc($resClasses)){
    $s_time = $row['start_time'] ? date("h:i A", strtotime($row['start_time'])) : '';
    $e_time = $row['end_time'] ? date("h:i A", strtotime($row['end_time'])) : '';
    $time_str = ($s_time && $e_time) ? "{$s_time} - {$e_time}" : "Not Scheduled";

    $classes_data[] = [
        'id'           => $row['id'],
        'grade'        => $row['grade'],
        'subject'      => $row['subject'],
        'teacher_name' => $row['teacher_name'] ?? 'No Teacher Assigned',
        'day_of_week'  => $row['day_of_week'] ?? 'Not Set',
        'schedule'     => $time_str
    ];
    $unique_grades[] = $row['grade'];
}

$unique_grades = array_unique($unique_grades);
sort($unique_grades);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Attendance | SIGMA ERP</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        :root {
            --bg-dark: #090a0f;
            --card-dark: #12141c;
            --input-bg: #1a1d29;
            --accent-blue: #3b82f6;
            --electric-blue: #00d2ff;
            --text-gray: #94a3b8;
            --sidebar-w: 280px;
        }

        body { 
            background-color: var(--bg-dark); 
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            overflow-x: hidden;
            min-height: 100vh;
        }

        .main-content { 
            margin-left: var(--sidebar-w); 
            padding: 40px; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card {
            background: var(--card-dark);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            color: white;
            box-shadow: 0 20px 40px rgba(0,0,0,0.6);
            backdrop-filter: blur(10px);
        }

        .form-select, .form-control {
            background-color: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 14px;
            padding: 12px 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .form-select:focus, .form-control:focus {
            background-color: var(--input-bg);
            border-color: var(--electric-blue);
            color: white;
            box-shadow: 0 0 15px rgba(0, 210, 255, 0.2);
        }

        .action-btn-group {
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(0, 210, 255, 0.15));
            border: 1px solid rgba(0, 210, 255, 0.3);
            border-radius: 16px;
            padding: 4px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .action-btn-group:hover {
            border-color: rgba(0, 210, 255, 0.6);
            box-shadow: 0 8px 30px rgba(0, 210, 255, 0.25);
        }

        .btn-scan-main {
            background: linear-gradient(135deg, #2563eb, #00d2ff);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            flex-grow: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .btn-scan-main:hover:not(:disabled) {
            background: linear-gradient(135deg, #1d4ed8, #00b4d8);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 210, 255, 0.3);
            color: #fff;
        }

        .btn-extend-sub {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            font-weight: 800;
            font-size: 0.85rem;
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 12px;
            padding: 10px 14px;
            margin-left: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.3s ease;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-extend-sub:hover {
            background: #f59e0b;
            color: #000;
            border-color: #f59e0b;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.4);
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center; padding: 60px 20px; background: rgba(255, 255, 255, 0.015); border-radius: 20px; border: 2px dashed rgba(255, 255, 255, 0.08);
        }
        
        #reader-container { 
            background: var(--input-bg); 
            padding: 20px; 
            border-radius: 20px; 
            border: 1px solid rgba(255,255,255,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        
        #reader video {
            border-radius: 12px;
            width: 100% !important;
            height: auto !important;
        }
        
        hr { border-top: 1px solid rgba(255, 255, 255, 0.08); opacity: 1; }

        .search-wrapper {
            position: relative;
        }
        .search-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-gray);
        }
        .search-wrapper .form-control {
            padding-left: 42px;
        }

        @media (max-width: 1200px) {
            .main-content { padding: 30px 20px; }
        }

        @media (max-width: 991.98px) {
            .main-content { margin-left: 0; padding: 24px 16px; }
            .card { padding: 24px !important; border-radius: 16px; }
        }

        @media (max-width: 768px) {
            .header-flex { flex-direction: column; align-items: flex-start !important; }
            .date-picker-wrapper { width: 100%; text-align: left !important; }
            .date-picker-wrapper input { text-align: left !important; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-4 header-flex">
        <div>
            <h3 class="fw-bold mb-1">Smart Attendance</h3>
            <p class="text-secondary small mb-0">පැමිණීම සටහන් කිරීමේ සහ කළමනාකරණය කිරීමේ ද්වාරය</p>
            <div class="mt-2">
                <?php if($is_past_date): ?>
                    <?php if($is_superadmin): ?>
                        <span class="badge bg-danger text-white">
                            <i class="fas fa-unlock me-1"></i> Superadmin Override - Past Attendance Marking Enabled
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-lock me-1"></i> Read Only Mode - Past Attendance Locked
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge bg-success text-white">
                        <i class="fas fa-edit me-1"></i> Live Mode - Today Attendance Active
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="text-md-end date-picker-wrapper">
            <label class="small fw-bold text-secondary mb-1 text-uppercase">Select Attendance Date</label>
            <input type="date" id="attendance_date" class="form-control text-md-center text-info fw-bold" value="<?php echo $selected_date; ?>" onchange="filterByDate()">
        </div>
    </div>

    <div class="card p-4 p-md-5 animate__animated animate__fadeIn">
        <div class="row g-3 mb-4 align-items-start">
            <div class="col-12 col-md-3">
                <label class="small fw-bold text-secondary mb-2 text-uppercase">Academic Grade</label>
                <select id="grade_select" class="form-select shadow-sm" onchange="filterSubjects()">
                    <option value="">Select Grade...</option>
                    <?php foreach($unique_grades as $g){ ?>
                        <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                    <?php } ?>
                </select>
            </div>
            
            <div class="col-12 col-md-3">
                <label class="small fw-bold text-secondary mb-2 text-uppercase">Subject & Teacher Module</label>
                <select id="subject_select" class="form-select shadow-sm" onchange="loadStudentList()">
                    <option value="">Select Subject Module...</option>
                </select>
            </div>
            
            <div class="col-12 col-md-3">
                <label class="small fw-bold text-secondary mb-2 text-uppercase">Live Table Filter</label>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" id="table_search" class="form-control shadow-sm" placeholder="Search by Student Name, ID..." onkeyup="liveTableFilter()" disabled>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <label class="small fw-bold text-secondary mb-2 text-uppercase d-none d-md-block">&nbsp;</label>
                <div class="action-btn-group">
                    <button id="scan_btn" onclick="openScanner()" class="btn-scan-main" <?php echo (!$can_mark_attendance ? 'disabled title="පසුගිය දිනවල පැමිණීම සටහන් කිරීම අගුලු දමා ඇත."' : ''); ?>>
                        <i class="fas fa-qrcode"></i> SCAN QR
                    </button>
                    <?php if(in_array($user_role, ['superadmin', 'admin', 'teacher'])): ?>
                        <button type="button" onclick="extendAttendanceTime()" class="btn-extend-sub" title="Extend Lock Time by 15 mins">
                            <i class="fas fa-stopwatch"></i> +15m
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="reader-container" class="mt-4 mb-4 animate__animated animate__zoomIn" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-bold text-white"><i class="fas fa-camera me-2 text-info"></i>Live QR Scanner</h6>
                <button class="btn btn-dark btn-sm rounded-circle" onclick="closeScanner()"><i class="fas fa-times"></i></button>
            </div>
            <div id="reader"></div>
        </div>

        <hr>

        <div id="student_list_container">
            <div class="empty-state">
                <i class="fas fa-user-check fa-3x mb-3 text-secondary"></i>
                <h5 class="text-white">පන්තිය තෝරන තෙක් රැඳී සිටින්න</h5>
                <p class="text-secondary small mb-0">පැමිණීමේ සටහන් බැලීම හෝ ඇතුලත් කිරීම සඳහා ඉහළින් ශ්‍රේණිය සහ අදාළ ගුරුවරයා සහිත විෂය තෝරන්න.</p>
            </div>
        </div>
    </div>
</div>

<script>
const classes = <?php echo json_encode($classes_data); ?>;
let canMarkAttendance = <?php echo $can_mark_attendance ? 'true' : 'false'; ?>;

function filterByDate() {
    let dateVal = document.getElementById("attendance_date").value;
    window.location.href = "attendance.php?attendance_date=" + dateVal;
}

function filterSubjects(){
    let grade = document.getElementById("grade_select").value;
    let sub   = document.getElementById("subject_select");
    sub.innerHTML = '<option value="">Select Subject Module...</option>';
    
    classes.forEach(c => {
        if(c.grade === grade){
            sub.innerHTML += `<option value="${c.id}">${c.subject} — (${c.teacher_name})</option>`;
        }
    });
    loadStudentList();
}

function loadStudentList(){
    let classId = document.getElementById("subject_select").value;
    let grade   = document.getElementById("grade_select").value;
    let date    = document.getElementById("attendance_date").value;
    let searchBox = document.getElementById("table_search");

    if(classId == "" || grade == "") {
        searchBox.value = "";
        searchBox.disabled = true;
        document.getElementById("student_list_container").innerHTML = `
            <div class="empty-state">
                <i class="fas fa-user-check fa-3x mb-3 text-secondary"></i>
                <h5 class="text-white">පන්තිය තෝරන තෙක් රැඳී සිටින්න</h5>
            </div>`;
        return;
    }

    let selectedClassObj = classes.find(c => c.id == classId);
    if(selectedClassObj) {
        Swal.fire({
            title: '<span style="color:#00d2ff;"><i class="fas fa-calendar-alt me-2"></i>Class Schedule</span>',
            html: `
                <div style="font-size: 15px; color: #cbd5e1; text-align: center; margin-top: 10px;">
                    <p class="mb-2"><strong style="color: #fff;">Subject:</strong> ${selectedClassObj.subject}</p>
                    <p class="mb-2"><strong style="color: #fff;">Teacher:</strong> ${selectedClassObj.teacher_name}</p>
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 12px 0;">
                    <p class="mb-1"><i class="fas fa-calendar-day text-warning me-2"></i><strong>Day:</strong> <span class="text-warning">${selectedClassObj.day_of_week}</span></p>
                    <p class="mb-0"><i class="fas fa-clock text-info me-2"></i><strong>Time:</strong> <span class="text-info">${selectedClassObj.schedule}</span></p>
                </div>
            `,
            background: '#12141c',
            color: '#fff',
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Got it!',
            timer: 4000,
            timerProgressBar: true
        });
    }

    document.getElementById("student_list_container").innerHTML = '<div class="text-center py-5"><div class="spinner-border text-info" role="status"></div><p class="mt-2 text-secondary">Loading students...</p></div>';
    
    fetch("fetch_students.php?class_id="+classId+"&grade="+grade+"&date="+date)
    .then(res=>res.text())
    .then(data=>{
        document.getElementById("student_list_container").innerHTML = data;
        searchBox.disabled = false;
        searchBox.value = ""; 
    });
}

function toggleAttendance(studentId, classId) {
    if(!canMarkAttendance) {
        Swal.fire({
            icon: 'error',
            title: 'Locked',
            text: 'පසුගිය දිනවල Attendance සලකුණු කළ හැක්කේ Superadmin හට පමණි.',
            background: '#12141c',
            color: '#fff'
        });
        return;
    }

    let selectedDate = $('#attendance_date').val() || new Date().toISOString().slice(0, 10); 

    $.ajax({
        url: 'attendance.php',
        type: 'POST',
        data: {
            check_eligibility: true,
            student_id: studentId,
            class_id: classId,
            attendance_date: selectedDate
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'time_error') {
                Swal.fire({
                    icon: 'warning',
                    title: '<span style="color:#ef4444;"><i class="fas fa-exclamation-triangle me-2"></i>Access Restricted</span>',
                    html: `<div class="p-2 text-start" style="color:#cbd5e1;">${response.message}</div>`,
                    background: '#12141c',
                    color: '#fff',
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'OK, Close'
                });
                return;
            }

            if(response.status === 'success') {
                showEligibilityPopup(response, studentId, classId, selectedDate);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Eligibility check failed.', background: '#12141c', color: '#fff' });
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong while checking eligibility.', background: '#12141c', color: '#fff' });
        }
    });
}

function showEligibilityPopup(data, studentId, classId, date) {
    let htmlContent = `
        <div style="text-align: left; font-size: 14px; color: #b1b5c0; line-height: 1.6;">
            <p><i class="fas fa-history me-2 text-warning"></i> <strong>Prev Month Attended:</strong> <span class="text-warning">${data.prev_weeks} Weeks</span></p>
            <p><i class="fas fa-money-check-alt me-2 text-warning"></i> <strong>Prev Month Payment:</strong> <span class="badge ${data.prev_paid === 'Paid' ? 'bg-success' : 'bg-danger'}">${data.prev_paid}</span></p>
            <hr style="border-color:#333; margin: 10px 0;">
            <p><i class="fas fa-calendar-check me-2 text-info"></i> <strong>Current Month Attended:</strong> <span class="text-info">${data.current_weeks} Weeks</span></p>
            <p><i class="fas fa-hand-holding-usd me-2 text-info"></i> <strong>Current Month Payment:</strong> <span class="badge ${data.current_paid === 'Paid' ? 'bg-success' : 'bg-danger'}">${data.current_paid}</span></p>
            <hr style="border-color:#333; margin: 10px 0;">
            <p style="font-weight:bold; color:#00d2ff;" class="mb-0"><i class="fas fa-info-circle me-1"></i> Fee Status: ${data.fee_suggestion}</p>
        </div>
    `;

    if(!data.allow) {
        let errorMsg = "පැමිණීම සටහන් කළ නොහැක!";
        if(data.reason === "third_week_unpaid") errorMsg = "මෙම සිසුවා සති 2ක් පැමිණ ඇත. 3වන සතියේ සිට පැමිණීමට නම් මේ මාසය ගෙවා තිබිය යුතුය.";
        if(data.reason === "prev_month_unpaid") errorMsg = "පසුගිය මාසයේ සති 2කට වඩා පැමිණ ඇතත් මුදල් ගෙවා නොමැත.";
        if(data.reason === "consecutive_absent_block") errorMsg = "පසුගිය මාසයේ පැමිණ නැත, මෙම මාසයේද සති 2 සීමාව ඉක්මවා ඇත. පද්ධතිය මඟින් අගුලු දමා ඇත.";

        Swal.fire({
            icon: 'error',
            title: 'Attendance Blocked',
            html: htmlContent + `<div class="alert alert-danger mt-3 small text-start">${errorMsg}<br><b>කරුණාකර Admin වෙත යොමු කරන්න.</b></div>`,
            background: '#12141c',
            color: '#fff',
            showCancelButton: true,
            showConfirmButton: false,
            cancelButtonText: 'Close'
        });
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Student Verification',
            html: htmlContent,
            background: '#12141c',
            color: '#fff',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            confirmButtonText: 'Confirm & Mark Attendance',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                executeMarking(studentId, classId, date);
            }
        });
    }
}

function executeMarking(studentId, classId, date) {
    let fd = new FormData();
    fd.append("execute_marking", true);
    fd.append("student_id", studentId);
    fd.append("class_id", classId);
    fd.append("attendance_date", date);

    fetch("attendance.php", { method: "POST", body: fd })
    .then(res => res.text())
    .then(msg => {
        if(msg === "present" || msg === "absent" || msg === "success") {
            Swal.fire({ icon: 'success', title: 'Recorded Successfully', timer: 1000, showConfirmButton: false, background: '#12141c', color: '#fff' });
            new Audio('https://www.soundjay.com/buttons/beep-07a.mp3').play();
            loadStudentList();
        } else if(msg === "time_restricted") {
            Swal.fire({ icon: 'error', title: 'Restricted', text: 'මෙම දිනය හෝ වේලාව තුළ Attendance ඇතුළත් කිරීමට නොහැක.', background: '#12141c', color: '#fff' });
        } else if(msg === "unauthorized") {
            Swal.fire({ icon: 'error', title: 'Unauthorized', text: 'ඔබට මෙම ක්‍රියාව සඳහා අවසර නැත.', background: '#12141c', color: '#fff' });
        }
    });
}

function extendAttendanceTime() {
    let classId = document.getElementById("subject_select").value;
    if(classId == "") {
        Swal.fire({ icon: 'warning', title: 'අවධානය!', text: 'කාලය දීර්ඝ කිරීමට ප්‍රථමයෙන් Class Module එක තෝරන්න.', background: '#12141c', color: '#fff' });
        return;
    }

    Swal.fire({
        title: 'Extend Attendance Slot',
        text: "මෙම පන්තියේ Lock වන කාල සීමාව තවත් විනාඩි 15කින් දීර්ඝ කිරීමට අවශ්‍යද?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#333',
        confirmButtonText: 'Yes, Extend 15 Mins',
        background: '#12141c',
        color: '#fff'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'attendance.php',
                type: 'POST',
                data: {
                    action: 'extend_time',
                    class_id: classId,
                    minutes: 15
                },
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Extended!', text: res.message, background: '#12141c', color: '#fff' });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Failed', text: res.message, background: '#12141c', color: '#fff' });
                    }
                }
            });
        }
    });
}

function openScanner(){
    if(!canMarkAttendance) {
        Swal.fire({ icon: 'error', title: 'Locked', text: 'පසුගිය දිනවල පැමිණීම සටහන් කිරීම අගුලු දමා ඇත.', background: '#12141c', color: '#fff' });
        return;
    }

    let classId = document.getElementById("subject_select").value;
    if(classId == ""){
        Swal.fire({ icon: 'info', title: 'අවධානය!', text: 'ස්කෑන් කිරීමට පෙර විෂය තෝරන්න.', background: '#12141c', color: '#eee5e5' });
        return;
    }
    
    document.getElementById("reader-container").style.display="block";
    scanner = new Html5QrcodeScanner("reader", { fps: 15, qrbox: function(viewfinderWidth, viewfinderHeight) {
        let minEdgePercentage = 0.70; 
        let minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
        let qrboxSize = Math.floor(minEdgeSize * minEdgePercentage);
        if (qrboxSize < 200) { qrboxSize = 200; }
        return { width: qrboxSize, height: qrboxSize };
    }});
    
    scanner.render(function(decodedText){
        fetch("fetch_student_by_qr.php?qr="+encodeURIComponent(decodedText)+"&class_id="+classId)
        .then(res => res.json())
        .then(stuData => {
            if(stuData.success) {
                closeScanner();
                toggleAttendance(stuData.student_id, classId);
            } else {
                Swal.fire({ icon: 'error', title: 'Access Denied', text: stuData.message, background: '#12141c', color: '#fff' });
            }
        });
    });
}

function closeScanner(){ if(typeof scanner !== 'undefined'){ scanner.clear(); document.getElementById("reader-container").style.display="none"; } }

function liveTableFilter() {
    let input = document.getElementById("table_search");
    let filter = input.value.toLowerCase();
    let table = document.getElementById("attendanceTable");
    if (!table) return;
    let tr = table.getElementsByTagName("tr");
    for (let i = 1; i < tr.length; i++) {
        let rowContainsFilter = false;
        let td = tr[i].getElementsByTagName("td");
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                if (td[j].innerText.toLowerCase().indexOf(filter) > -1) {
                    rowContainsFilter = true;
                    break;
                }
            }
        }
        tr[i].style.display = rowContainsFilter ? "" : "none";
    }
}

/* ========================================================
   🎯 PDF GENERATION FUNCTION (Full List / Present Only)
   ======================================================== */
function generatePDF(type) {
    const { jsPDF } = window.jspdf;
    
    let table = document.getElementById("attendanceTable");
    if (!table) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'දත්ත පද්‍ධතියේ Table එක හමු නොවීය.',
            background: '#12141c',
            color: '#fff'
        });
        return;
    }

    // Processing Alert
    Swal.fire({
        title: 'Generating PDF...',
        text: 'කරුණාකර මඳ වේලාවක් රැඳී සිටින්න.',
        background: '#12141c',
        color: '#fff',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let clonedTable = table.cloneNode(true);
    
    // PDF එකට අනවශ්‍ය Action button column එක ඉවත් කිරීම
    let actionCells = clonedTable.querySelectorAll('.action-cell, th:nth-child(4), td:nth-child(4)');
    actionCells.forEach(cell => cell.remove());

    // Present Only තෝරා ඇත්නම් ABSENT සිසුන් ඉවත් කිරීම
    if (type === 'present') {
        let rows = clonedTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (row.innerText.includes('ABSENT')) {
                row.remove();
            }
        });
    }

    // PDF එක සඳහා Styling සකස් කිරීම
    let pdfContainer = document.createElement('div');
    pdfContainer.style.padding = '25px';
    pdfContainer.style.background = '#12141c';
    pdfContainer.style.color = '#ffffff';
    pdfContainer.style.position = 'absolute';
    pdfContainer.style.left = '-9999px';
    
    let titleText = (type === 'present') ? 'PRESENT STUDENTS LIST' : 'FULL ATTENDANCE LIST';
    let selectedDate = document.getElementById("attendance_date") ? document.getElementById("attendance_date").value : '';
    let gradeVal = document.getElementById("grade_select") ? document.getElementById("grade_select").value : '';
    let subjectText = document.getElementById("subject_select") ? document.getElementById("subject_select").options[document.getElementById("subject_select").selectedIndex].text : '';

    pdfContainer.innerHTML = `
        <div style="text-align: center; margin-bottom: 20px; font-family: sans-serif;">
            <h2 style="color: #00d2ff; margin-bottom: 5px; font-weight: bold;">SIGMA ERP - ATTENDANCE REPORT</h2>
            <h4 style="color: #f1f5f9; margin-top: 0; font-weight: 600;">${titleText}</h4>
            <p style="color: #94a3b8; font-size: 13px; margin-bottom: 0;">
                <strong>Grade:</strong> ${gradeVal} | <strong>Subject:</strong> ${subjectText} | <strong>Date:</strong> ${selectedDate}
            </p>
        </div>
    `;
    pdfContainer.appendChild(clonedTable);
    document.body.appendChild(pdfContainer);

    html2canvas(pdfContainer, {
        backgroundColor: '#12141c',
        scale: 2
    }).then(canvas => {
        const doc = new jsPDF('p', 'pt', 'a4');
        let imgData = canvas.toDataURL('image/png');
        let imgWidth = 595.28;
        let pageHeight = 841.89;
        let imgHeight = (canvas.height * imgWidth) / canvas.width;
        let heightLeft = imgHeight;
        let position = 0;

        doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        while (heightLeft >= 0) {
            position = heightLeft - imgHeight;
            doc.addPage();
            doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        doc.save(`${type}_attendance_${selectedDate}.pdf`);
        document.body.removeChild(pdfContainer);
        Swal.close();
    }).catch(err => {
        console.error(err);
        if (document.body.contains(pdfContainer)) {
            document.body.removeChild(pdfContainer);
        }
        Swal.fire({
            icon: 'error',
            title: 'Failed',
            text: 'PDF එක සාදා ගැනීමට නොහැකි විය.',
            background: '#12141c',
            color: '#fff'
        });
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>