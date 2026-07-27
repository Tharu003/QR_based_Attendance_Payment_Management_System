<?php
session_start();
include 'db.php';

// ======================================================
// LOGIN CHECK
// ======================================================
if (!isset($_SESSION['student_data'])) {
    header("Location: st_login.php");
    exit();
}

$student = $_SESSION['student_data'];
$sid = intval($student['student_id']);
$today_date = date('Y-m-d'); 
$current_datetime = date('Y-m-d H:i:s');

// ======================================================
// PROGRESS CHART DATA FETCH (From exam_results Table)
// ======================================================
$progress_subjects = [];
$progress_marks = [];

$chart_sql = "
    SELECT c.subject, AVG(er.marks_obtained) as avg_marks
    FROM exam_results er
    INNER JOIN exams e ON er.exam_id = e.id
    INNER JOIN classes c ON e.class_id = c.id
    WHERE er.student_id = ?
    GROUP BY c.subject
";
$stmt_chart = mysqli_prepare($conn, $chart_sql);
mysqli_stmt_bind_param($stmt_chart, "i", $sid);
mysqli_stmt_execute($stmt_chart);
$chart_result = mysqli_stmt_get_result($stmt_chart);

while ($c_row = mysqli_fetch_assoc($chart_result)) {
    $progress_subjects[] = $c_row['subject']; 
    $progress_marks[] = round($c_row['avg_marks'], 2); 
}

// Default dummy data if no marks recorded yet
if(empty($progress_subjects)) {
    $progress_subjects = ['Mathematics', 'Science', 'History', 'Sinhala', 'English'];
    $progress_marks = [75, 68, 82, 70, 85]; 
}

// ======================================================
// NOTICE MODAL CHECK
// ======================================================
$show_notice_modal = false;
if (!isset($_SESSION['notice_seen'])) {
    $_SESSION['notice_seen'] = true;
    $show_notice_modal = true;
}

// ======================================================
// NOTICE QUERY
// ======================================================
$highlight_sql = "
    SELECT DISTINCT n.*, c.subject
    FROM notices n
    INNER JOIN student_classes sc ON n.class_id = sc.class_id
    INNER JOIN classes c ON n.class_id = c.id
    WHERE sc.student_id = ? 
      AND n.notice_date = ?
      AND n.grade = ?
    ORDER BY n.id DESC LIMIT 1
";
$stmt = mysqli_prepare($conn, $highlight_sql);
mysqli_stmt_bind_param($stmt, "iss", $sid, $today_date, $student['registered_grade']);
mysqli_stmt_execute($stmt);
$highlight_result = mysqli_stmt_get_result($stmt);

$has_notice = false;
$notice = null;
if ($row = mysqli_fetch_assoc($highlight_result)) {
    $has_notice = true;
    $notice = $row;
}

// ======================================================
// UPCOMING CLASS COUNTDOWN FETCH
// ======================================================
$current_day_name = date('l'); 
$current_time = date('H:i:s');

$upcoming_class_sql = "
    SELECT 
        c.subject, 
        t.day_of_week, 
        t.start_time,
        DATE_FORMAT(
            DATE_ADD(CURDATE(), INTERVAL 
                IF(
                    FIELD(t.day_of_week, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') = FIELD(?, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') AND t.start_time > ?,
                    0,
                    (7 + FIELD(t.day_of_week, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') - FIELD(?, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')) % 7
                ) DAY
            ), 
            '%Y-%m-%d'
        ) AS calculated_date
    FROM class_timetable t
    INNER JOIN classes c ON t.class_id = c.id
    INNER JOIN student_classes sc ON c.id = sc.class_id
    WHERE sc.student_id = ?
    ORDER BY 
        IF(
            FIELD(t.day_of_week, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') = FIELD(?, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') AND t.start_time > ?,
            0,
            (7 + FIELD(t.day_of_week, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') - FIELD(?, 'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday')) % 7
        ) ASC,
        t.start_time ASC 
    LIMIT 1;
";

$stmt_class = mysqli_prepare($conn, $upcoming_class_sql);

// Query එකේ ප්‍රශ්නාර්ථ (?) 7ක් ඇති නිසා:
// Types: s (string), s (string), s (string), i (int), s (string), s (string), s (string) => "sssisss"
mysqli_stmt_bind_param(
    $stmt_class, 
    "sssisss", 
    $current_day_name, // 1. FIELD(?)
    $current_time,     // 2. t.start_time > ?
    $current_day_name, // 3. FIELD(?)
    $sid,              // 4. sc.student_id = ?
    $current_day_name, // 5. FIELD(?)
    $current_time,     // 6. t.start_time > ?
    $current_day_name  // 7. FIELD(?)
);

mysqli_stmt_execute($stmt_class);
$class_result = mysqli_stmt_get_result($stmt_class);
$upcoming_class = mysqli_fetch_assoc($class_result);

$countdown_target = "";
if ($upcoming_class) {
    $countdown_target = $upcoming_class['calculated_date'] . ' ' . $upcoming_class['start_time'];
}
// ======================================================
// UPCOMING EXAMS FETCH
// ======================================================
$exams_query = "
    SELECT DISTINCT e.*, c.subject 
    FROM exams e
    INNER JOIN classes c ON e.class_id = c.id
    INNER JOIN student_classes sc ON c.id = sc.class_id
    WHERE sc.student_id = ? 
      AND e.grade = ? 
      AND e.exam_date >= NOW()
    ORDER BY e.exam_date ASC 
    LIMIT 3
";
$stmt_exams = mysqli_prepare($conn, $exams_query);
mysqli_stmt_bind_param($stmt_exams, "is", $sid, $student['registered_grade']);
mysqli_stmt_execute($stmt_exams);
$exams_result = mysqli_stmt_get_result($stmt_exams);

// ======================================================
// SUBJECT-WISE PAYMENT CHECK & ALERT LOGIC
// ======================================================
$current_month_name = date('F');
$next_month_name = date('F', strtotime('+1 month'));
$current_day = (int)date('d');

$unpaid_subjects_sql = "
    SELECT c.subject 
    FROM student_classes sc
    INNER JOIN classes c ON sc.class_id = c.id
    LEFT JOIN payments p ON sc.student_id = p.student_id 
                        AND p.month = ? 
                        AND p.amount > 0 
                        AND (p.class_id = sc.class_id OR p.class_id IS NULL) 
    WHERE sc.student_id = ? AND p.id IS NULL
";

$stmt_unpaid = mysqli_prepare($conn, $unpaid_subjects_sql);
mysqli_stmt_bind_param($stmt_unpaid, "si", $current_month_name, $sid);
mysqli_stmt_execute($stmt_unpaid);
$unpaid_result = mysqli_stmt_get_result($stmt_unpaid);

$unpaid_subjects_list = [];
while ($unpaid_row = mysqli_fetch_assoc($unpaid_result)) {
    $unpaid_subjects_list[] = $unpaid_row['subject'];
}

$show_payment_alert = (!empty($unpaid_subjects_list) && $current_day <= 30);

// ======================================================
// RECENT PAYMENT TRANSFER NOTIFICATION LOGIC (1 Month Limit)
// ======================================================
$has_new_transfer = false;
$transfer_details = null;

// මාසයක් (30 Days) ඇතුළත සිදු වූ Approved transfer එකක් තිබේදැයි බලයි
$transfer_check_sql = "
    SELECT * FROM payment_transfers 
    WHERE student_id = ? 
      AND status = 'Approved' 
      AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) 
    ORDER BY id DESC LIMIT 1
";
$stmt_t = mysqli_prepare($conn, $transfer_check_sql);
mysqli_stmt_bind_param($stmt_t, "i", $sid);
mysqli_stmt_execute($stmt_t);
$t_result = mysqli_stmt_get_result($stmt_t);

if (mysqli_num_rows($t_result) > 0) {
    $has_new_transfer = true;
    $transfer_details = mysqli_fetch_assoc($t_result);
}

// ======================================================
// RECENT PAYMENT HISTORY FETCH
// ======================================================
$payments_history_sql = "
    SELECT p.*, c.subject 
    FROM payments p
    LEFT JOIN classes c ON p.class_id = c.id
    WHERE p.student_id = ?
    ORDER BY p.id DESC
    LIMIT 5
";
$stmt_pay_hist = mysqli_prepare($conn, $payments_history_sql);
mysqli_stmt_bind_param($stmt_pay_hist, "i", $sid);
mysqli_stmt_execute($stmt_pay_hist);
$payments_history_result = mysqli_stmt_get_result($stmt_pay_hist);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sigma Hub | Student Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Round">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;500;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg: #0b0f19;
            --card: rgba(20, 27, 45, 0.7);
            --border: rgba(255, 255, 255, 0.08);
            --gold: #f59e0b;
            --gold-glow: rgba(245, 158, 11, 0.2);
            --blue: #3b82f6;
            --purple: #8b5cf6;
            --emerald: #10b981;
            --text: #f8fafc;
            --muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            background: radial-gradient(circle at 50% 0%, #17153a 0%, var(--bg) 75%); 
            color: var(--text); 
            font-family: 'Plus Jakarta Sans', 'Noto Sans Sinhala', sans-serif; 
            overflow-x: hidden; 
        }

        .main { margin-left: 280px; padding: 40px; min-height: 100vh; }
        
        .glass { 
            background: var(--card); 
            border: 1px solid var(--border); 
            border-radius: 24px; 
            padding: 30px; 
            backdrop-filter: blur(20px); 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5); 
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); 
        }
        .glass:hover { 
            transform: translateY(-5px); 
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7); 
        }

        .stat-card { display: flex; align-items: center; gap: 20px; padding: 25px; }
        .stat-icon { 
            width: 60px; 
            height: 60px; 
            border-radius: 18px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 28px;
            box-shadow: inset 0 2px 8px rgba(255,255,255,0.05);
        }

        .profile-img { 
            width: 120px; 
            height: 120px; 
            object-fit: cover; 
            border-radius: 50%; 
            border: 4px solid transparent; 
            background: linear-gradient(var(--card), var(--card)) padding-box, 
                        linear-gradient(135deg, var(--gold), var(--purple)) border-box;
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.25); 
        }

        .notice-item { 
            padding: 20px; 
            margin-bottom: 15px; 
            background: rgba(255, 255, 255, 0.02); 
            border-radius: 16px; 
            border-left: 4px solid var(--purple);
            border-top: 1px solid var(--border);
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .btn-premium { 
            background: linear-gradient(135deg, #f59e0b, #d97706); 
            color: #000; 
            border: none; 
            padding: 14px 24px; 
            border-radius: 16px; 
            font-weight: 700; 
            text-decoration: none; 
            display: block; 
            text-align: center; 
            box-shadow: 0 8px 24px var(--gold-glow); 
            transition: all 0.3s ease; 
        }
        .btn-premium:hover { 
            transform: scale(1.02); 
            box-shadow: 0 12px 30px rgba(245, 158, 11, 0.4); 
            color: #000; 
        }

        .countdown-box { 
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05)); 
            border: 1px solid rgba(139, 92, 246, 0.2); 
            text-align: center; 
        }
        .countdown-timer { 
            font-size: 24px; 
            font-weight: 800; 
            letter-spacing: 0.5px; 
            color: #38bdf8; 
            text-shadow: 0 0 15px rgba(56, 189, 248, 0.3); 
        }
        
        .quote-card { 
            background: linear-gradient(135deg, rgba(30, 27, 75, 0.3), rgba(15, 23, 42, 0.5)); 
            border-top: 3px solid var(--gold);
        }

        .table-responsive { border-radius: 16px; overflow: hidden; border: 1px solid var(--border); }
        .table { --bs-table-bg: #111827; margin-bottom: 0; color: var(--text); }
        .table thead { background: #1f2937; }
        .table th { padding: 16px; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; color: var(--muted); border-bottom: 1px solid var(--border); }
        .table td { padding: 18px 16px; border-bottom: 1px solid var(--border); background: rgba(20, 27, 45, 0.4); }

        .pay-badge-verified { background: rgba(16, 185, 129, 0.12); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }

        /* Robotic Futuristic Chatbot Widget */
        #chat-circle { position: fixed; right: 40px; bottom: 40px; width: 100px; height: 120px; background: transparent; cursor: pointer; z-index: 9999; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); animation: roboFloat 4s ease-in-out infinite; }
        #chat-circle:hover { transform: scale(1.15) rotate(3deg); }
        .robo-antenna { width: 6px; height: 18px; background: linear-gradient(to top, #3b82f6, #60a5fa); position: relative; margin: 0 auto; border-radius: 3px; }
        .robo-antenna::after { content: ''; position: absolute; top: -8px; left: -5px; width: 16px; height: 16px; background: #f59e0b; border-radius: 50%; box-shadow: 0 0 15px #f59e0b; animation: energyPulse 1.5s infinite alternate; }
        .robo-head { width: 85px; height: 65px; background: linear-gradient(135deg, #1e40af, #3b82f6); border: 3px solid #60a5fa; border-radius: 24px 24px 18px 18px; position: relative; margin: 0 auto; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3); }
        .robo-ear { width: 10px; height: 22px; background: #60a5fa; position: absolute; top: 20px; border-radius: 5px; }
        .robo-ear.left { left: -8px; }
        .robo-ear.right { right: -8px; }
        .robo-screen { width: 65px; height: 35px; background: #090d16; border-radius: 14px; position: absolute; top: 12px; left: 50%; transform: translateX(-50%); display: flex; justify-content: center; align-items: center; gap: 12px; }
        .eye { width: 12px; height: 12px; background: #00f2fe; border-radius: 50%; box-shadow: 0 0 12px #00f2fe; transition: all 0.3s ease; }
        .robo-body-base { width: 55px; height: 22px; background: linear-gradient(135deg, #1d4ed8, #1e3a8a); border: 3px solid #3b82f6; border-top: none; border-radius: 0 0 14px 14px; margin: -2px auto 0; }
        
        .chat-box { position: fixed; right: 40px; bottom: 165px; width: 400px; max-width: 92vw; height: 580px; background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(25px); border-radius: 30px; display: none; flex-direction: column; overflow: hidden; border: 1px solid var(--border); z-index: 9999; box-shadow: 0 25px 60px rgba(0,0,0,0.7); }
        .chat-header { background: linear-gradient(135deg, #2563eb, #7c3aed); color: white; padding: 20px; font-weight: 700; display: flex; align-items: center; justify-content: space-between; }
        .chat-body { flex: 1; padding: 20px; overflow-y: auto; background: rgba(0,0,0,0.2); }
        .chat-msg { margin-bottom: 20px; display: flex; }
        .chat-msg.user { justify-content: flex-end; }
        .cm-msg-text { padding: 14px 20px; border-radius: 20px; max-width: 85%; font-size: 14.5px; line-height: 1.6; }
        .chat-msg.user .cm-msg-text { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; border-bottom-right-radius: 4px; }
        .chat-msg.assistant .cm-msg-text { background: #1e293b; color: #f1f5f9; border-bottom-left-radius: 4px; border: 1px solid rgba(255,255,255,0.04); }
        .chat-input-box { display: flex; gap: 10px; padding: 16px; background: #0b0f19; border-top: 1px solid var(--border); }
        #chat-input { flex: 1; border: 1px solid var(--border); outline: none; background: #1e293b; color: white; border-radius: 14px; padding: 14px; }
        #chat-submit { border: none; background: linear-gradient(135deg, #2563eb, #7c3aed); color: white; border-radius: 14px; padding: 0 24px; font-weight: 600; cursor: pointer; }

        @keyframes roboFloat { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-12px); } }
        @keyframes energyPulse { 0% { opacity: 0.6; } 100% { opacity: 1; } }

        .swal2-popup-dark {
            background: #141b2d !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 24px !important;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6) !important;
        }
        .swal2-title-custom {
            color: #ffffff !important;
            font-family: 'Plus Jakarta Sans', 'Noto Sans Sinhala', sans-serif !important;
            font-weight: 700 !important;
            font-size: 22px !important;
        }
        .swal2-html-custom {
            color: #cbd5e1 !important;
            font-family: 'Plus Jakarta Sans', 'Noto Sans Sinhala', sans-serif !important;
            line-height: 1.7 !important;
            font-size: 15px !important;
        }
        .swal2-confirm-custom {
            background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
            padding: 12px 32px !important;
            font-size: 15px !important;
            font-weight: 600 !important;
            border-radius: 14px !important;
            color: white !important;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3) !important;
        }

        @media(max-width: 1024px) {
            .main { margin-left: 0; padding: 20px; padding-top: 80px; }
        }
    </style>
</head>

<body>
    <!-- Modal Notification Layout -->
    <?php if ($has_notice && $show_notice_modal && $notice): ?>
    <div class="modal fade" id="noticeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white border-0 rounded-4 shadow-lg" style="background: #0f172a !important; border: 1px solid var(--border) !important;">
                <div class="modal-body p-4 text-center">
                    <div class="d-inline-flex p-3 bg-warning bg-opacity-10 text-warning rounded-circle mb-3">
                        <span class="material-icons-round" style="font-size:48px;">campaign</span>
                    </div>
                    <h5 class="text-warning fw-bold mb-1"><?php echo htmlspecialchars($notice['subject']); ?> Class</h5>
                    <h3 class="fw-bold mb-3"><?php echo htmlspecialchars($notice['title']); ?></h3>
                    <div class="p-3 rounded-3 bg-black bg-opacity-40 text-start border border-secondary border-opacity-10 mb-4" style="font-size: 14.5px; line-height:1.6;">
                        <?php echo nl2br(htmlspecialchars($notice['content'])); ?>
                    </div>
                    <button class="btn btn-warning w-100 py-3 fw-bold rounded-3" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'st_sidebar.php'; ?>
    <main class="main">
        
        <!-- Payment Status Notification Area -->
        <?php if ($show_payment_alert): ?>
            <div class="alert alert-warning alert-dismissible fade show shadow-lg mb-4 border-0 text-white" role="alert" style="border-radius: 20px; background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(239, 68, 68, 0.1)); border: 1px solid rgba(245, 158, 11, 0.2) !important;">
                <div class="d-flex align-items-center gap-3 p-2">
                    <div style="background: linear-gradient(135deg, #f59e0b, #d97706); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #000;">
                        <span class="material-icons-round">auto_awesome</span>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold text-warning mb-1" style="font-size: 16px;">ඉගෙනීමේ ගමන බාධාවකින් තොරව ඉදිරියටම යමු! 🚀</h5>
                        <p class="m-0 text-secondary" style="font-size: 14px; line-height: 1.5;">
                            ඔබගේ <strong><?php echo $current_month_name; ?></strong> මාසයට අදාළව පහත විෂයයන්හි සියලුම අංග සක්‍රීයව තබා ගැනීමට පන්ති ගාස්තු පළමු සති දෙක ඇතුළත නිම කරන්න.
                        </p>
                        <div class="mt-2 d-flex flex-wrap gap-2">
                            <?php foreach ($unpaid_subjects_list as $sub): ?>
                                <span class="badge px-3 py-2 d-flex align-items-center gap-1" style="font-size: 12px; background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 8px;">
                                    <span class="material-icons-round" style="font-size: 14px;">pending_actions</span> <?php echo htmlspecialchars($sub); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close" style="top: 15px; right: 15px;"></button>
            </div>
        <?php endif; ?>

        <!-- Welcome Banner Segment -->
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h1 class="fw-extrabold" style="letter-spacing: -0.5px;">ආයුබෝවන්, <?php $name_parts = explode(' ', $student['student_name']); echo htmlspecialchars($name_parts[0]); ?> ✨</h1>
                <p class="text-secondary mt-1" style="font-size:14px;"><span class="material-icons-round align-bottom" style="font-size:18px;">calendar_today</span> <?php echo date('l, d F Y'); ?></p>
            </div>
        </div>

      <!-- Quick Info Blocks -->
<div class="row g-4 mb-4">
    <!-- Overall Attendance Block -->
    <div class="col-md-4">
        <div class="glass stat-card">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--emerald);">
                <span class="material-icons-round">task_alt</span>
            </div>
            <div>
                <small class="text-white opacity-75 d-block uppercase tracking-wider mb-1" style="font-size:12px; font-weight: 600;">
                    Overall Attendance
                </small>
                <h3 class="fw-bold m-0 text-white">88%</h3>
            </div>
        </div>
    </div>

    <!-- Payment Status Block -->
    <div class="col-md-4">
        <div class="glass stat-card">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--blue);">
                <span class="material-icons-round">credit_card</span>
            </div>
            <div>
                <small class="text-white opacity-75 d-block uppercase tracking-wider mb-1" style="font-size:12px; font-weight: 600;">
                    Payment Status (<?php echo $current_month_name; ?>)
                </small>
                <?php if (empty($unpaid_subjects_list)): ?>
                    <h4 class="fw-bold m-0 text-success" style="font-size:18px;">All Paid ✓</h4>
                <?php else: ?>
                    <h4 class="fw-bold m-0 text-warning" style="font-size:18px;"><?php echo count($unpaid_subjects_list); ?> Pending ⏳</h4>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Enrolled Classes Block -->
    <div class="col-md-4">
        <div class="glass stat-card">
            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: var(--purple);">
                <span class="material-icons-round">school</span>
            </div>
            <div>
                <small class="text-white opacity-75 d-block uppercase tracking-wider mb-1" style="font-size:12px; font-weight: 600;">
                    Enrolled Classes
                </small>
                <h3 class="fw-bold m-0 text-white">03 Registered</h3>
            </div>
        </div>
    </div>
</div>

        <div class="row g-4">
            <!-- Sidebar Widgets Grid Left -->
            <div class="col-lg-4">
                <div class="glass text-center mb-4">
                    <?php
                    $profile_photo = 'assets/default-user.png';
                    if (!empty($student['photo'])) {
                        $photoPath = $student['photo'];
                        if (file_exists(__DIR__ . "/" . $photoPath)) { $profile_photo = $photoPath; }
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($profile_photo); ?>?v=<?php echo time(); ?>" class="profile-img mb-3" alt="Profile">
                    <h4 class="fw-bold m-0 text-white"><?php echo htmlspecialchars($student['student_name']); ?></h4>
                    <span class="badge bg-secondary bg-opacity-25 text-secondary px-3 py-1.5 mt-2 rounded-pill" style="font-size: 12px; border: 1px solid rgba(255,255,255,0.05);"><?php echo htmlspecialchars($student['registered_grade']); ?></span>
                    <div class="my-4 p-3 rounded-4 bg-black bg-opacity-30 border border-secondary border-opacity-10">
                        <small class="text-muted d-block">Student ID</small>
                        <h4 class="fw-bold m-0 text-warning mt-1">#<?php echo $sid; ?></h4>
                    </div>
                    <a href="profile.php" class="btn-premium">View Full Profile</a>
                </div>

                <div class="glass countdown-box text-center mb-4">
                    <div class="d-inline-flex p-2.5 bg-primary bg-opacity-10 text-primary rounded-3 mb-2">
                        <span class="material-icons-round" style="font-size: 28px;">hourglass_top</span>
                    </div>
                    <h5 class="fw-bold text-white mb-1">Next Live Class</h5>
                    <?php if ($upcoming_class): ?>
                        <p class="text-warning small m-0 mb-3 fw-bold"><?php echo htmlspecialchars($upcoming_class['subject']); ?></p>
                        <div id="countdown" class="countdown-timer">Calculating...</div>
                    <?php else: ?>
                        <p class="text-muted small m-0 mt-2">No upcoming classes found.</p>
                    <?php endif; ?>
                </div>

                <div class="glass quote-card text-center">
                    <span class="material-icons-round text-warning" style="font-size: 32px; filter: drop-shadow(0 4px 8px var(--gold-glow));">auto_stories</span>
                    <h5 class="fw-bold mt-2 mb-3 text-white">අද දවසේ සිතුවිල්ල</h5>
                    <p class="fst-italic" style="line-height: 1.8; color: #cbd5e1; font-size: 14.5px;">
                        "කටුකයි තමයි යන මේ පාර,<br>එනමුත් දිනක ඔබ වෙයි වීර.<br>අද මහන්සි වී කරන මේ කැපවීම,<br>හෙට දිනේ මල් පල දරනවා සත්තයි!"
                    </p>
                    <small class="text-muted d-block mt-3">— සුභ අනාගතයකට පියවරක් —</small>
                </div>
            </div>

            <!-- Content Panel Grid Right -->
            <div class="col-lg-8">
                <!-- Payment Transfers logs Grid Element -->
                <div class="glass mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="material-icons-round text-success">receipt_long</span>
                            <h5 class="fw-bold m-0">Recent Payment Transfers</h5>
                        </div>
                        <a href="payments.php" class="btn btn-outline-warning btn-sm rounded-pill px-3 py-1.5 fw-semibold" style="font-size:12px;">Make Payment 💳</a>
                    </div>

                    <?php if (mysqli_num_rows($payments_history_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr class="text-secondary small">
                                        <th>Month & Subject</th>
                                        <th>Amount</th>
                                        <th>Method / Ref</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($pay = mysqli_fetch_assoc($payments_history_result)): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-white"><?php echo htmlspecialchars($pay['month']); ?></div>
                                                <small class="text-muted" style="font-size:12px;"><?php echo htmlspecialchars($pay['subject'] ?? 'General Fee'); ?></small>
                                            </td>
                                            <td class="fw-bold text-warning">
                                                Rs. <?php echo number_format($pay['amount'], 2); ?>
                                            </td>
                                            <td>
                                                <div class="small text-white"><?php echo ucfirst(htmlspecialchars($pay['payment_method'] ?? 'Online Slip')); ?></div>
                                                <small class="text-muted" style="font-size:11px;"><?php echo htmlspecialchars($pay['reference_no'] ?? 'N/A'); ?></small>
                                            </td>
                                            <td class="small text-secondary">
                                                <?php echo date('Y-m-d', strtotime($pay['created_at'] ?? $pay['payment_date'] ?? 'now')); ?>
                                            </td>
                                            <td>
                                                <span class="badge pay-badge-verified rounded-pill px-3 py-1.5 fw-medium">
                                                    Verified ✓
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <span class="material-icons-round opacity-20" style="font-size:48px;">payments</span>
                            <p class="small mb-0 mt-2">තවම ගෙවීම් වාර්තා වී නැත.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Academic Performance Chart Grid Element -->
                <div class="glass mb-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <span class="material-icons-round text-primary">trending_up</span>
                        <h5 class="fw-bold m-0">Academic Performance Analysis</h5>
                    </div>
                    <div style="height: 300px; display: flex; justify-content: center; position: relative;">
                        <canvas id="progressChart"></canvas>
                    </div>
                </div>

                <!-- Notice Dashboard Board Grid Element -->
                <div class="glass mb-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <span class="material-icons-round text-danger">campaign</span>
                        <h5 class="fw-bold m-0">Notice Board (Today)</h5>
                    </div>
                    <?php
                    $notice_sql = "
                        SELECT DISTINCT n.*, c.subject 
                        FROM notices n
                        INNER JOIN student_classes sc ON n.class_id = sc.class_id
                        INNER JOIN classes c ON n.class_id = c.id
                        WHERE sc.student_id = ? 
                          AND n.notice_date = ?
                          AND n.grade = ?
                        ORDER BY n.id DESC 
                        LIMIT 5
                    ";
                    
                    $stmt_board = mysqli_prepare($conn, $notice_sql);
                    mysqli_stmt_bind_param($stmt_board, "iss", $sid, $today_date, $student['registered_grade']);
                    mysqli_stmt_execute($stmt_board);
                    $notice_query = mysqli_stmt_get_result($stmt_board);

                    if (mysqli_num_rows($notice_query) > 0) {
                        while ($n = mysqli_fetch_assoc($notice_query)) {
                            ?>
                            <div class="notice-item">
                                <span class="badge bg-primary bg-opacity-20 text-primary px-3 py-1.5 mb-2.5 rounded-3" style="font-size: 11.5px; border: 1px solid rgba(59, 130, 246, 0.2);"><?php echo htmlspecialchars($n['subject']); ?></span>
                                <h6 class="fw-bold text-white mb-2" style="font-size:15px;"><?php echo htmlspecialchars($n['title']); ?></h6>
                                <p class="small text-secondary mb-2" style="line-height:1.6;"><?php echo nl2br(htmlspecialchars($n['content'])); ?></p>
                                <small class="text-muted d-flex align-items-center gap-1" style="font-size:11px;"><span class="material-icons-round" style="font-size:14px;">event</span> Target Date: <?php echo date('Y-m-d', strtotime($n['notice_date'])); ?></small>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<div class='p-3 text-center text-muted small border border-secondary border-opacity-10 rounded-3 bg-black bg-opacity-10'>ඔබේ ශ්‍රේණිය සඳහා අද දිනට නිවේදන නොමැත.</div>";
                    }
                    ?>
                </div>

                <!-- Upcoming Exams Table Grid Element -->
                <div class="glass">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <span class="material-icons-round text-warning">quiz</span>
                        <h5 class="fw-bold m-0">Upcoming Exams</h5>
                    </div>
                    
                    <?php if (mysqli_num_rows($exams_result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr class="text-secondary small">
                                        <th>Subject</th>
                                        <th>Exam Title</th>
                                        <th>Date & Time</th>
                                        <th>Type & Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($exam = mysqli_fetch_assoc($exams_result)): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 border border-primary border-opacity-25" style="font-size:12px;">
                                                    <?php echo htmlspecialchars($exam['subject']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-white"><?php echo htmlspecialchars($exam['exam_title']); ?></div>
                                                <small class="text-muted">⏳ <?php echo $exam['duration_minutes'] ?? 'N/A'; ?> Mins</small>
                                            </td>
                                            <td>
                                                <div class="text-info small fw-semibold">
                                                    <?php echo date('Y-m-d', strtotime($exam['exam_date'])); ?>
                                                </div>
                                                <small class="text-secondary"><?php echo date('h:i A', strtotime($exam['exam_date'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($exam['exam_type'] === 'physical'): ?>
                                                    <span class="badge bg-success text-white px-2 py-1 mb-1" style="font-size:11px;">Physical</span>
                                                    <div class="small text-muted" style="font-size: 11px;">🏛️ <?php echo htmlspecialchars($exam['exam_location_or_link']); ?></div>
                                                <?php else: ?>
                                                    <span class="badge bg-info text-dark px-2 py-1 mb-1" style="font-size:11px;">Online</span>
                                                    <div><a href="<?php echo htmlspecialchars($exam['exam_location_or_link']); ?>" target="_blank" class="text-info small text-decoration-none d-flex align-items-center gap-0.5">🔗 Join Exam</a></div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-3 text-center text-muted small border border-secondary border-opacity-10 rounded-3 bg-black bg-opacity-10">ඔබේ ශ්‍රේණිය සඳහා නියමිත ඉදිරි විභාග කිසිවක් නැත.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Robotic AI Chatbot Interface Structure -->
    <div id="chat-circle" onclick="toggleChat()">
        <div class="robo-antenna"></div>
        <div class="robo-head">
            <div class="robo-ear left"></div>
            <div class="robo-screen">
                <div class="eye left-eye"></div>
                <div class="eye right-eye"></div>
            </div>
            <div class="robo-ear right"></div>
        </div>
        <div class="robo-body-base"></div>
    </div>

    <div class="chat-box" id="chat-box">
        <div class="chat-header">
            <div class="d-flex align-items-center gap-2">
                <span class="material-icons-round" style="color: #60a5fa;">smart_toy</span>
                <span style="letter-spacing: 0.5px; font-weight:700;">Sigma AI Assistant</span>
            </div>
            <span style="cursor:pointer; display: flex; align-items: center;" onclick="toggleChat()">
                <span class="material-icons-round">close</span>
            </span>
        </div>
        <div class="chat-body" id="chat-logs">
            <div class="chat-msg assistant">
                <div class="cm-msg-text">ආයුබෝවන් 😊<br>මම ඔයාගේ AI Assistant. ඔබට අද මගෙන් කුමන උදව්වක්ද අවශ්‍ය?</div>
            </div>
        </div>
        <div class="chat-input-box">
            <input type="text" id="chat-input" placeholder="ඔබට දැනගන්නට අවශ්‍ය දේ මෙතන ලියන්න...">
            <button id="chat-submit" onclick="sendQuery()">Send</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- SweetAlert2 for Transfer Alert (Persists for 1 Month) -->
    <?php if ($has_new_transfer && $transfer_details): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: 'ගෙවීම් මාරු කිරීම සාර්ථකයි! 🔄',
                html: `ඔබගේ ගෙවීම් මාරු කිරීම තහවුරු කර ඇත.<br><br>
                       <b>පැරණි මාසය:</b> ${"<?php echo htmlspecialchars($transfer_details['from_month']); ?>"}<br>
                       <b>නව මාසය:</b> ${"<?php echo htmlspecialchars($transfer_details['to_month']); ?>"}<br>
                       <b>මුදල:</b> Rs. ${"<?php echo number_format($transfer_details['amount'], 2); ?>"}`,
                icon: 'success',
                confirmButtonText: 'හරි',
                customClass: {
                    popup: 'swal2-popup-dark',
                    title: 'swal2-title-custom',
                    htmlContainer: 'swal2-html-custom',
                    confirmButton: 'swal2-confirm-custom'
                }
            });
        });
    </script>
    <?php endif; ?>

    <script>
        const STUDENT_ID = <?php echo json_encode($sid); ?>;

        document.addEventListener("DOMContentLoaded", function() {
            // Notice Modal Trigger
            <?php if (isset($has_notice) && $has_notice && isset($show_notice_modal) && $show_notice_modal && isset($notice)): ?>
                var myModal = new bootstrap.Modal(document.getElementById('noticeModal'), { keyboard: false, backdrop: 'static' });
                myModal.show();
            <?php endif; ?>

            // Confetti Blast Effect
            confetti({ particleCount: 30, spread: 60, origin: { y: 0.8 } });

            // 1. Radar Academic Performance Analysis Chart
            const chartCanvas = document.getElementById('progressChart');
            if (chartCanvas) {
                const ctx = chartCanvas.getContext('2d');
                new Chart(ctx, {
                    type: 'radar', 
                    data: {
                        labels: <?php echo json_encode($progress_subjects); ?>,
                        datasets: [{
                            label: 'Marks Obtained (%)',
                            data: <?php echo json_encode($progress_marks); ?>,
                            backgroundColor: 'rgba(139, 92, 246, 0.25)',
                            borderColor: '#8b5cf6',
                            pointBackgroundColor: '#f59e0b',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#8b5cf6',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                min: 0,
                                max: 100,
                                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                                angleLines: { color: 'rgba(255, 255, 255, 0.1)' },
                                ticks: { display: false, stepSize: 20 },
                                pointLabels: { 
                                    color: '#cbd5e1', 
                                    font: { family: 'Plus Jakarta Sans', size: 12, weight: '600' } 
                                }
                            }
                        },
                        plugins: {
                            legend: { labels: { color: '#f8fafc', font: { family: 'Plus Jakarta Sans', weight: '600' } } }
                        }
                    }
                });
            }

            // 2. Countdown Timer Logic
            const targetString = "<?php echo $countdown_target; ?>";
            if (targetString !== "") {
                const countDownDate = new Date(targetString.replace(' ', 'T')).getTime();
                
                const timerInterval = setInterval(function() {
                    const now = new Date().getTime();
                    const distance = countDownDate - now;

                    if (distance <= 0) {
                        clearInterval(timerInterval);
                        const countdownEl = document.getElementById("countdown");
                        if(countdownEl) {
                            countdownEl.innerHTML = "CLASS STARTED 🔴";
                            countdownEl.style.color = "#ef4444";
                        }
                        return;
                    }

                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    const countdownEl = document.getElementById("countdown");
                    if (countdownEl) {
                        countdownEl.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                    }
                }, 1000);
            } else {
                const countdownEl = document.getElementById("countdown");
                if (countdownEl) countdownEl.innerHTML = "No upcoming classes";
            }
        });

        // Chatbot logic
        function toggleChat() {
            let chatBox = document.getElementById("chat-box");
            chatBox.style.display = (chatBox.style.display === "none" || chatBox.style.display === "") ? "flex" : "none";
        }

        async function sendQuery() {
            const input = document.getElementById("chat-input");
            const query = input.value.trim();
            if (!query) return;

            appendMessage(query, "user");
            input.value = "";
            appendMessage("Typing...", "assistant", "typing-loader");

            try {
                const response = await fetch("http://127.0.0.1:8000/api/chat", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ student_id: STUDENT_ID, message: query })
                });
                
                const loader = document.getElementById("typing-loader");
                if (loader) loader.remove();
                const data = await response.json();
                
                if (response.ok) { 
                    appendMessage(data.reply, "assistant"); 
                } else { 
                    appendMessage(data.detail || "Server Error", "assistant"); 
                }
            } catch (error) {
                const loader = document.getElementById("typing-loader");
                if (loader) loader.remove();
                appendMessage("Cannot connect to AI server.", "assistant");
            }
        }

        function appendMessage(text, sender, id = null) {
            const logs = document.getElementById("chat-logs");
            const div = document.createElement("div");
            div.classList.add("chat-msg", sender);
            if (id) div.id = id;
            div.innerHTML = `<div class="cm-msg-text">${text}</div>`;
            logs.appendChild(div);
            logs.scrollTop = logs.scrollHeight;
        }

        document.getElementById('chat-input')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { sendQuery(); }
        });
    </script>
</body>
</html>