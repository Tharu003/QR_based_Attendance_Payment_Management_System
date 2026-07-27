<?php 
session_start();
include 'db.php';

// Check if student is logged in
if(!isset($_SESSION['student_data'])) {
    header("Location: st_login.php");
    exit();
}

$s = $_SESSION['student_data'];
$sid = $s['student_id'];
$sid = mysqli_real_escape_string($conn, $sid);

/* ==========================================
   1. Overall Stats Calculation
   ========================================== */
$stats_sql = "SELECT 
                COUNT(*) as total_days,
                SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_days
              FROM attendance 
              WHERE student_id = '$sid'";
$stats_res = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_res);

$total_days = $stats['total_days'] ?? 0;
$present_days = $stats['present_days'] ?? 0;
$absent_days = $stats['absent_days'] ?? 0;

$attendance_percentage = $total_days > 0 ? round(($present_days / $total_days) * 100) : 0;

/* ==========================================
   2. Subject-wise Stats Calculation
   ========================================== */
$subject_stats_sql = "SELECT 
                        c.id as class_id,
                        c.subject,
                        COUNT(a.id) as total_classes,
                        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count
                      FROM student_classes sc
                      JOIN classes c ON sc.class_id = c.id
                      LEFT JOIN attendance a ON c.id = a.class_id AND a.student_id = '$sid'
                      WHERE sc.student_id = '$sid'
                      GROUP BY c.id";
$subject_stats_res = mysqli_query($conn, $subject_stats_sql);

// Build subjects list array for filter dropdown
$subjects_list = [];
$subject_stats_res_copy = mysqli_query($conn, $subject_stats_sql);
while($row = mysqli_fetch_assoc($subject_stats_res_copy)) {
    $subjects_list[] = $row['subject'];
}

/* ==========================================
   3. Detailed History Log
   ========================================== */
$history_sql = "SELECT 
                    a.date,
                    a.time,
                    a.status,
                    c.subject
                FROM attendance a
                JOIN classes c ON a.class_id = c.id
                WHERE a.student_id = '$sid'
                ORDER BY a.date DESC, a.time DESC";
$history_res = mysqli_query($conn, $history_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sigma Institute | Attendance Portal</title>
    
    <!-- Fonts & Essentials -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Round">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --bg: #02040a;
            --card-glass: rgba(13, 20, 38, 0.6);
            --sidebar: #060b18;
            --border-glass: rgba(255, 255, 255, 0.06);
            --gold: #f59e0b;
            --blue: #3b82f6;
            --mint: #10b981;
            --rose: #f43f5e;
            --text: #f9fafb;
            --muted: #9ca3af;
            --input-bg: rgba(22, 32, 59, 0.6);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', 'Noto Sans Sinhala', sans-serif;
            overflow-x: hidden;
            background-image: radial-gradient(circle at 80% 20%, rgba(245, 158, 11, 0.05) 0%, transparent 40%),
                              radial-gradient(circle at 20% 80%, rgba(59, 130, 246, 0.06) 0%, transparent 45%);
            background-attachment: fixed;
        }

        /* MAIN LAYOUT RESPONSIVE */
        .main-content { 
            padding: 25px 20px; 
            max-width: 1500px; 
            transition: all 0.3s ease;
        }

        @media (min-width: 1025px) {
            .main-content { 
                margin-left: 280px; 
                padding: 50px 60px; 
            }
        }

        /* HEADER RESPONSIVE */
        .page-header h1 { font-size: calc(1.8rem + 1vw); font-weight: 800; color: #fff; margin-bottom: 6px; letter-spacing: -0.5px; }
        .page-header p { color: #9ca3af; font-weight: 500; font-size: 0.95rem; }

        /* MOTIVATIONAL NISADASA BANNER */
        .nisadasa-card {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(6, 11, 24, 0.8));
            border: 1px solid rgba(245, 158, 11, 0.15);
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3), inset 0 0 20px rgba(245, 158, 11, 0.05);
        }
        @media (min-width: 768px) {
            .nisadasa-card { border-radius: 30px; padding: 35px; margin-bottom: 40px; }
        }
        .nisadasa-card::before {
            content: ""; position: absolute; top: -50%; right: -20%; width: 300px; height: 300px;
            background: var(--gold); filter: blur(120px); opacity: 0.12; pointer-events: none;
        }
        .nisadasa-title { font-family: 'Noto Sans Sinhala', sans-serif; font-weight: 800; font-size: 1.3rem; color: var(--gold); text-shadow: 0 0 15px rgba(245, 158, 11, 0.3); margin-bottom: 12px; }
        .nisadasa-content { font-family: 'Noto Sans Sinhala', sans-serif; font-size: calc(0.9rem + 0.3vw); line-height: 1.8; color: #e2e8f0; font-weight: 600; font-style: italic; letter-spacing: 0.3px; }
        .nisadasa-icon { position: absolute; bottom: 15px; right: 20px; font-size: 4rem; color: rgba(245, 158, 11, 0.05); pointer-events: none; }

        /* ANALYTICS STAT CARDS */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        @media (min-width: 768px) { .stat-grid { gap: 24px; margin-bottom: 40px; } }
        
        .stat-card { background: var(--card-glass); border: 1px solid var(--border-glass); border-radius: 20px; padding: 24px; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); display: flex; align-items: center; justify-content: space-between; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px) scale(1.01); border-color: rgba(255,255,255,0.12); box-shadow: 0 15px 35px rgba(0,0,0,0.4); }
        .stat-icon { width: 54px; height: 54px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 26px; transition: transform 0.3s; flex-shrink: 0; }
        .stat-card:hover .stat-icon { transform: rotate(10deg) scale(1.1); }
        .stat-value { font-size: 2rem; font-weight: 800; color: #fff; line-height: 1.2; }
        .stat-label { font-size: 0.8rem; color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; }

        /* PANEL CARD CONTAINER */
        .panel-card { background: var(--card-glass); border: 1px solid var(--border-glass); border-radius: 24px; padding: 24px; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); margin-bottom: 30px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); }
        @media (min-width: 768px) { .panel-card { border-radius: 30px; padding: 35px; margin-bottom: 40px; } }
        
        .panel-title { font-size: 1.25rem; font-weight: 800; color: #fff; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* BREAKDOWN PROGRESS BAR */
        .subject-progress-item { margin-bottom: 20px; background: rgba(255,255,255,0.01); padding: 18px 20px; border-radius: 18px; border: 1px solid rgba(255,255,255,0.02); transition: all 0.3s ease; }
        .subject-progress-item:hover { border-color: rgba(245, 158, 11, 0.15); background: rgba(245, 158, 11, 0.01); transform: translateX(5px); }
        .progress { height: 10px; background: rgba(255, 255, 255, 0.04); border-radius: 12px; overflow: visible; position: relative; }
        
        /* FILTER CONTROLS */
        .filter-wrapper { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-box { position: relative; flex: 1; min-width: 200px; }
        .filter-box .material-icons-round { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--gold); pointer-events: none; font-size: 20px; }
        .form-select-custom { width: 100%; background: var(--input-bg); border: 1px solid var(--border-glass); color: var(--text); padding: 14px 18px 14px 52px; border-radius: 16px; font-weight: 600; appearance: none; cursor: pointer; transition: all 0.3s; font-size: 0.9rem; }
        .form-select-custom:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 20px rgba(245, 158, 11, 0.2); background-color: rgba(13, 20, 38, 0.9); }
        .filter-box::after { content: '\e5cf'; font-family: 'Material Icons Round'; position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: var(--muted); pointer-events: none; }

        /* DATA TABLE GRID */
        .table-responsive { border-radius: 20px; overflow-x: auto; border: 1px solid var(--border-glass); box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        .custom-table { width: 100%; margin-bottom: 0; color: #e5e7eb; vertical-align: middle; background: transparent; min-width: 600px; }
        .custom-table th { background: rgba(6, 11, 24, 0.9); color: #9ca3af; font-weight: 800; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1.2px; padding: 18px 22px; border: none; }
        .custom-table td { padding: 16px 22px; border-bottom: 1px solid rgba(255,255,255,0.01); background: transparent; transition: all 0.3s; }
        .custom-table tbody tr:hover td { background: rgba(245, 158, 11, 0.02); color: #fff; }

        /* STATUS BADGES */
        .status-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 0.7rem; font-weight: 800; padding: 6px 14px; border-radius: 30px; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-present { background: rgba(16, 185, 129, 0.08); color: var(--mint); border: 1px solid rgba(16, 185, 129, 0.2); box-shadow: 0 0 15px rgba(16, 185, 129, 0.1); }
        .status-absent { background: rgba(244, 63, 94, 0.08); color: var(--rose); border: 1px solid rgba(244, 63, 94, 0.2); box-shadow: 0 0 15px rgba(244, 63, 94, 0.1); }

        .no-records-row { display: none; }

        /* HIDE SIDEBAR LOGIC COMPATIBILITY FOR TABLETS/MOBILES */
        @media (max-width: 1024px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>

<body>
<?php include 'st_sidebar.php'; ?>

    <!-- MAIN BODY CONTENT -->
    <main class="main-content">
        
        <!-- HEADER -->
        <header class="page-header d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4 animate__animated animate__fadeIn">
            <div>
                <h1>Attendance Analytics</h1>
                <p class="m-0">Track your academic consistency and breakdown reports parameters.</p>
            </div>
            <span class="material-icons-round text-warning d-none d-md-block" style="font-size: 3rem; opacity: 0.3;">auto_stories</span>
        </header>

        <!-- MOTIVATIONAL NISADASA BANNER -->
        <div class="nisadasa-card animate__animated animate__fadeInDown">
            <div class="nisadasa-title"><span class="material-icons-round align-middle me-1">auto_awesome</span> දිනන හෙටකට...</div>
            <div class="nisadasa-content">
                "අකුරු කරන්නට පියනගනා හැම වාරයක්ම...<br>
                හෙට දින දිනන லෝකෙට තබනා පියවරක්ම...<br>
                නොනැවතී ඇවිත් සිප්සතර සොයා යන ගමනක්ම...<br>
                නුඹේ සිහින සැබෑ කරනා රන් මාවතක්ම..."
            </div>
            <span class="material-icons-round nisadasa-icon">school</span>
        </div>

        <!-- COUNTER GRID -->
        <div class="stat-grid animate__animated animate__fadeInUp">
            <div class="stat-card">
                <div>
                    <div class="stat-value"><?php echo $attendance_percentage; ?>%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.08); color: var(--gold); box-shadow: 0 0 15px rgba(245, 158, 11, 0.1);">
                    <span class="material-icons-round">donut_large</span>
                </div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-value"><?php echo $present_days; ?></div>
                    <div class="stat-label">Days Present</div>
                </div>
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.08); color: var(--mint); box-shadow: 0 0 15px rgba(16, 185, 129, 0.1);">
                    <span class="material-icons-round">check_circle</span>
                </div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-value"><?php echo $absent_days; ?></div>
                    <div class="stat-label">Days Absent</div>
                </div>
                <div class="stat-icon" style="background: rgba(244, 63, 94, 0.08); color: var(--rose); box-shadow: 0 0 15px rgba(244, 63, 94, 0.1);">
                    <span class="material-icons-round">cancel</span>
                </div>
            </div>
        </div>

        <!-- SUBJECT BREAKDOWN -->
        <div class="panel-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
            <div class="panel-title">
                <span class="material-icons-round" style="color: var(--gold);">analytics</span>
                Subject-wise Breakdown
            </div>
            
            <div class="row">
                <?php while($sub = mysqli_fetch_assoc($subject_stats_res)): 
                    $sub_total = $sub['total_classes'] ?? 0;
                    $sub_present = $sub['present_count'] ?? 0;
                    $sub_percentage = $sub_total > 0 ? round(($sub_present / $sub_total) * 100) : 0;
                    
                    $bar_color = 'var(--mint)';
                    if($sub_percentage < 50) { $bar_color = 'var(--rose)'; }
                    elseif($sub_percentage < 75) { $bar_color = 'var(--gold)'; }
                ?>
                    <div class="col-xl-6 col-lg-12">
                        <div class="subject-progress-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-white"><?php echo htmlspecialchars($sub['subject']); ?></span>
                                <span class="fw-bold" style="color: <?php echo $bar_color; ?>;"><?php echo $sub_percentage; ?>%</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $sub_percentage; ?>%; background: <?php echo $bar_color; ?>; border-radius: 12px; box-shadow: 0 0 12px <?php echo $bar_color; ?>;"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2" style="font-size: 0.75rem; color: var(--muted);">
                                <span>Total Classes: <?php echo $sub_total; ?></span>
                                <span>Attended: <?php echo $sub_present; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- DETAILED HISTORY + LIVE FILTER SYSTEM -->
        <div class="panel-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            <div class="panel-title">
                <span class="material-icons-round" style="color: var(--blue);">history</span>
                Attendance Log History
            </div>

            <!-- FILTERS -->
            <div class="filter-wrapper">
                <div class="filter-box">
                    <span class="material-icons-round">menu_book</span>
                    <select id="subjectFilter" class="form-select-custom">
                        <option value="ALL">All Subjects</option>
                        <?php foreach($subjects_list as $sub_name): ?>
                            <option value="<?php echo htmlspecialchars($sub_name); ?>"><?php echo htmlspecialchars($sub_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-box">
                    <span class="material-icons-round">flaky</span>
                    <select id="statusFilter" class="form-select-custom">
                        <option value="ALL">All Status</option>
                        <option value="Present">Present Only</option>
                        <option value="Absent">Absent Only</option>
                    </select>
                </div>
            </div>

            <!-- TABLE RESPONSIVE WRAPPER -->
            <div class="table-responsive">
                <table class="table custom-table" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Class / Subject</th>
                            <th>Date</th>
                            <th>Punch-in Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($history_res) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($history_res)): ?>
                                <tr class="attendance-row" data-subject="<?php echo htmlspecialchars($row['subject']); ?>" data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                    <td class="fw-semibold text-white"><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="material-icons-round text-muted" style="font-size: 16px;">calendar_today</span>
                                            <?php echo date('M d, Y', strtotime($row['date'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="material-icons-round text-muted" style="font-size: 16px;">schedule</span>
                                            <?php echo date('h:i A', strtotime($row['time'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($row['status'] === 'Present'): ?>
                                            <span class="status-badge status-present"><span class="material-icons-round" style="font-size: 12px;">fiber_manual_record</span>Present</span>
                                        <?php else: ?>
                                            <span class="status-badge status-absent"><span class="material-icons-round" style="font-size: 12px;">fiber_manual_record</span>Absent</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                        
                        <tr id="noRecordsRow" class="no-records-row">
                            <td colspan="4" class="text-center py-5 text-muted">
                                <span class="material-icons-round d-block mb-2" style="font-size: 2.5rem; opacity: 0.3;">search_off</span>
                                No matching logs found for the selected filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- LIVE FILTER SCRIPT -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const subjectFilter = document.getElementById("subjectFilter");
            const statusFilter = document.getElementById("statusFilter");
            const rows = document.querySelectorAll(".attendance-row");
            const noRecordsRow = document.getElementById("noRecordsRow");

            function filterTable() {
                const selectedSubject = subjectFilter.value;
                const selectedStatus = statusFilter.value;
                let visibleCount = 0;

                rows.forEach(row => {
                    const rowSubject = row.getAttribute("data-subject");
                    const rowStatus = row.getAttribute("data-status");

                    const matchSubject = (selectedSubject === "ALL" || rowSubject === selectedSubject);
                    const matchStatus = (selectedStatus === "ALL" || rowStatus === selectedStatus);

                    if (matchSubject && matchStatus) {
                        row.style.display = "";
                        visibleCount++;
                    } else {
                        row.style.display = "none";
                    }
                });

                if (visibleCount === 0) {
                    noRecordsRow.style.display = "table-row";
                } else {
                    noRecordsRow.style.display = "none";
                }
            }

            subjectFilter.addEventListener("change", filterTable);
            statusFilter.addEventListener("change", filterTable);
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>