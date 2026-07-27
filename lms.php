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

// Classes ලැයිස්තුව ලබා ගැනීම
$class_sql = "SELECT c.*, COUNT(cm.id) AS material_count 
              FROM classes c
              JOIN student_classes sc ON c.id = sc.class_id
              LEFT JOIN class_materials cm ON c.id = cm.class_id
              WHERE sc.student_id='$sid'
              GROUP BY c.id
              ORDER BY material_count DESC, c.subject ASC";

$class_result = mysqli_query($conn, $class_sql);

// වත්මන් කාල විස්තර
$current_day_num = (int)date('j'); 
$current_month_name = date('F'); 
$is_first_week = ($current_day_num <= 7);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sigma Institute | Ultimate LMS Portal</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Round">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --bg: #040814;
            --card-glass: rgba(11, 19, 43, 0.45);
            --sidebar: #0b1329;
            --border-glass: rgba(255, 255, 255, 0.04);
            --gold: #f59e0b;
            --blue: #3b82f6;
            --text: #f8fafc;
            --muted: #475569;
            --neon-cyan: #06b6d4;
            --neon-purple: #d946ef;
            --danger: #ef4444;
            --success: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', 'Noto Sans Sinhala', sans-serif;
            overflow-x: hidden;
            background-image: radial-gradient(circle at 80% 10%, rgba(59, 130, 246, 0.08) 0%, transparent 40%),
                              radial-gradient(circle at 10% 80%, rgba(217, 70, 239, 0.05) 0%, transparent 40%);
            background-attachment: fixed;
        }

        .main-content { padding: 20px; max-width: 1500px; transition: all 0.3s ease; }

        @media (min-width: 1025px) {
            .main-content { margin-left: 280px; padding: 50px 60px; }
        }

        /* PREMIUM HERO BANNER */
        .hero-card {
            background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
            border-radius: 24px; padding: 30px 25px; position: relative; overflow: hidden;
            border: 1px solid var(--border-glass); margin-bottom: 20px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
            background-image: url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1200&auto=format&fit=crop');
            background-size: cover; background-position: center;
        }
        @media (min-width: 768px) { .hero-card { border-radius: 32px; padding: 45px 50px; margin-bottom: 25px; } }
        .hero-card::before { content: ''; position: absolute; inset: 0; background: linear-gradient(to right, rgba(2, 6, 23, 0.95) 40%, rgba(2, 6, 23, 0.6)); z-index: 1; }
        .hero-container { position: relative; z-index: 2; }
        .nisadasa-text { font-family: 'Noto Sans Sinhala', sans-serif; font-size: calc(1.1rem + 0.8vw); font-weight: 700; line-height: 1.6; color: #fff; margin-bottom: 15px; text-shadow: 0 4px 20px rgba(0,0,0,0.6); }

        /* MONTH & ACCESS STATUS CARD */
        .month-status-card {
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 20px; padding: 20px; margin-bottom: 35px;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;
        }
        .month-badge-box { display: flex; align-items: center; gap: 15px; }
        .calendar-icon-box { width: 50px; height: 50px; background: linear-gradient(135deg, var(--blue), var(--neon-cyan)); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #fff; }
        .month-info-text h3 { font-size: 1.2rem; font-weight: 800; margin: 0; color: #fff; }
        .month-info-text p { font-size: 0.8rem; margin: 0; color: #94a3b8; font-weight: 500; }
        .access-tag { padding: 8px 16px; border-radius: 30px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; text-transform: uppercase; }
        .access-free { background: rgba(16, 185, 129, 0.12); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
        .access-conditional { background: rgba(245, 158, 11, 0.12); color: var(--gold); border: 1px solid rgba(245, 158, 11, 0.2); }

        /* PAYMENT ALERT BOX */
        .payment-lock-alert {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.12) 0%, rgba(220, 38, 38, 0.03) 100%);
            border: 1px dashed rgba(239, 68, 68, 0.25); border-radius: 16px; padding: 16px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 15px; color: #fca5a5;
        }
        .payment-lock-alert .lock-icon { color: var(--danger); font-size: 2rem; }
        .payment-lock-alert-text h4 { margin: 0 0 4px 0; font-size: 0.95rem; font-weight: 700; color: #fff; font-family: 'Noto Sans Sinhala', sans-serif; }
        .payment-lock-alert-text p { margin: 0; font-size: 0.85rem; color: #fca5a5; font-family: 'Noto Sans Sinhala', sans-serif; opacity: 0.85; }

        /* SUBJECT CONTAINER */
        .subject-container { margin-bottom: 45px; }
        .subject-header-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
        .subject-title-box { display: flex; align-items: center; gap: 15px; }
        .subject-icon { width: 44px; height: 44px; background: rgba(59, 130, 246, 0.08); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--blue); border: 1px solid rgba(59, 130, 246, 0.15); }
        .subject-title-box h2 { font-size: 1.4rem; font-weight: 800; margin: 0; color: #fff; letter-spacing: -0.3px; }

        /* CREATIVE ARCHIVE SELECTOR */
        .btn-archive-select {
            background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08);
            color: #cbd5e1; padding: 8px 16px; border-radius: 12px; font-size: 0.85rem; font-weight: 600;
            display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;
        }
        .btn-archive-select:hover { background: rgba(255, 255, 255, 0.07); border-color: rgba(255, 255, 255, 0.15); color: #fff; }

        /* RESOURCE CONTAINER */
        .material-section-wrapper {
            background: rgba(11, 19, 43, 0.2); border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.02); padding: 5px;
        }
        .month-header-divider {
            font-size: 0.8rem; font-weight: 700; color: var(--blue); text-transform: uppercase;
            letter-spacing: 1.5px; padding: 12px 15px 5px 15px; display: flex; align-items: center; gap: 10px;
        }
        .month-header-divider::after { content: ''; flex-grow: 1; height: 1px; background: linear-gradient(90deg, rgba(59, 130, 246, 0.2), transparent); }

        /* RESPONSIVE AUTO-FILL GRID */
        .material-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; padding: 15px; }
        @media (min-width: 576px) { .material-grid { grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 20px; } }

        .material-card {
            background: var(--card-glass); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--border-glass); border-radius: 18px; padding: 18px;
            display: flex; flex-direction: row; align-items: center; justify-content: space-between; gap: 12px;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); position: relative;
        }
        .material-card:hover:not(.card-locked) { border-color: rgba(255, 255, 255, 0.1); background: rgba(15, 23, 42, 0.7); transform: translateY(-4px); box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3); }
        .material-card.card-locked { opacity: 0.6; border-color: rgba(239, 68, 68, 0.12); }

        /* SYSTEM BADGES */
        .week-circle { width: 48px; height: 48px; background: rgba(255, 255, 255, 0.02); border-radius: 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 1px solid var(--border-glass); flex-shrink: 0; }
        .week-circle span:first-child { font-size: 0.55rem; text-transform: uppercase; color: var(--gold); font-weight: 800; }
        .week-circle span:last-child { font-size: 1.1rem; font-weight: 800; color: #fff; }

        .mat-info { margin-left: 10px; flex-grow: 1; min-width: 0; }
        .mat-name { font-weight: 700; font-size: 0.9rem; color: #f1f5f9; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .quiz-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 0.65rem; font-weight: 700; padding: 3px 8px; border-radius: 30px; text-transform: uppercase; }
        .badge-ai { background: rgba(168, 85, 247, 0.12); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.15); }
        .badge-manual { background: rgba(6, 182, 212, 0.12); color: #22d3ee; border: 1px solid rgba(6, 182, 212, 0.15); }
        .badge-gform { background: rgba(16, 185, 129, 0.12); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.15); }
        .badge-generic { background: rgba(255,255,255,0.04); color: #94a3b8; }
        .badge-locked { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.2); }
        .badge-attempt { background: rgba(245, 158, 11, 0.12); color: var(--gold); border: 1px solid rgba(245, 158, 11, 0.2); }
        .badge-attempt-max { background: rgba(239, 68, 110, 0.12); color: var(--danger); border: 1px solid rgba(239, 68, 110, 0.2); }

        /* ACTIONS */
        .btn-stack { display: flex; gap: 6px; flex-shrink: 0; }
        .action-btn { width: 38px; height: 38px; border-radius: 11px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .btn-ai-quiz { background: rgba(168, 85, 247, 0.12); color: #a855f7; }
        .btn-manual-quiz { background: rgba(6, 182, 212, 0.12); color: #06b6d4; }
        .btn-gform-quiz { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .btn-tute { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }
        .btn-video { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
        .btn-locked { background: rgba(239, 68, 68, 0.1); color: #fca5a5; cursor: not-allowed; }
        .action-btn:hover:not(.disabled):not(.btn-locked) { transform: scale(1.08) translateY(-2px); background: #fff; color: #000; }
        .action-btn.disabled { background: rgba(255, 255, 255, 0.02) !important; color: #475569 !important; cursor: not-allowed; pointer-events: none; }

        /* PLACEHOLDER */
        .futuristic-placeholder { background: linear-gradient(135deg, rgba(6, 182, 212, 0.01) 0%, rgba(59, 130, 246, 0.01) 100%) !important; border: 1px dashed rgba(6, 182, 212, 0.15) !important; padding: 15px !important; margin: 15px; border-radius: 16px; }
        .radar-box { width: 44px; height: 44px; background: rgba(6, 182, 212, 0.04); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--neon-cyan); border: 1px solid rgba(6, 182, 212, 0.08); }
        .cyber-tag { font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 8px; background: rgba(6, 182, 212, 0.06); color: var(--neon-cyan); text-transform: uppercase; display: inline-flex; align-items: center; gap: 5px; margin-top: 4px; }
        .pulse-core { width: 5px; height: 5px; background: var(--neon-cyan); border-radius: 50%; animation: coreGlow 1.5s infinite ease-in-out; }
        @keyframes coreGlow { 0%, 100% { transform: scale(0.8); opacity: 0.5; } 50% { transform: scale(1.3); opacity: 1; } }

        @media (max-width: 1024px) { .sidebar { display: none; } .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>

<body>

    <?php include 'st_sidebar.php'; ?>
    
    <main class="main-content">
        
        <!-- HERO BANNER -->
        <header class="hero-card animate__animated animate__fadeIn">
            <div class="hero-container row align-items-center">
                <div class="col-lg-9 col-md-8">
                    <div class="nisadasa-text">
                        "අඳුර දුරලන දැණුමේ ආලෝකය,<br>
                        සිග්මා අභිමනයි - හෙට දින ජයග්‍රහණය."
                    </div>
                    <p class="text-white-50 fw-medium m-0" style="font-size: 0.85rem; letter-spacing: 0.3px;">Welcome back, Student! Your automated smart-archived timeline is active below.</p>
                </div>
                <div class="col-lg-3 col-md-4 text-end d-none d-md-block">
                    <span class="material-icons-round animate__animated animate__pulse animate__infinite" style="font-size: 5rem; opacity: 0.2; color: var(--gold);">auto_awesome</span>
                </div>
            </div>
        </header>

        <!-- MONTH STATUS TOP CARD -->
        <section class="month-status-card animate__animated animate__fadeInDown">
            <div class="month-badge-box">
                <div class="calendar-icon-box">
                    <span class="material-icons-round">calendar_month</span>
                </div>
                <div class="month-info-text">
                    <h3><?php echo $current_month_name; ?> LMS Portal</h3>
                    <p>Server Timeline: Day <?php echo $current_day_num; ?> of the month</p>
                </div>
            </div>
            <div>
                <?php if($is_first_week): ?>
                    <span class="access-tag access-free">
                        <span class="spinner-grow spinner-grow-sm text-success" role="status"></span>
                        1st Week Grace Period Active
                    </span>
                <?php else: ?>
                    <span class="access-tag access-conditional">
                        <span class="material-icons-round" style="font-size: 16px;">verified_user</span>
                        Secure Vault Mode
                    </span>
                <?php endif; ?>
            </div>
        </section>

        <!-- SUBJECT MODULES LOOP -->
        <?php while($class = mysqli_fetch_assoc($class_result)): ?>
            
            <?php
            $class_id = $class['id'];
            
            // 1. ვත්මන් මාසයේ ගෙවීම් තත්ත්වය පරීක්ෂාව
            $current_month_paid = false;
            if (!$is_first_week) {
                $pay_sql = "SELECT id FROM payments WHERE student_id='$sid' AND class_id='$class_id' AND month='$current_month_name' LIMIT 1";
                $pay_res = mysqli_query($conn, $pay_sql);
                if (mysqli_num_rows($pay_res) > 0) { $current_month_paid = true; }
            } else {
                $current_month_paid = true; 
            }

            // 2. මෙම සිසුවා කලින් ගෙවීම් කර ඇති සියලුම මාසයන් payments වගුවෙන් සොයා ගැනීම (Archive Filter එක සඳහා)
            $archive_months = [];
            $history_sql = "SELECT DISTINCT month FROM payments WHERE student_id='$sid' AND class_id='$class_id' AND month != '$current_month_name'";
            $history_res = mysqli_query($conn, $history_sql);
            while($h_row = mysqli_fetch_assoc($history_res)) {
                $archive_months[] = $h_row['month'];
            }
            ?>

            <div class="subject-container animate__animated animate__fadeInUp">
                
                <div class="subject-header-row">
                    <div class="subject-title-box">
                        <div class="subject-icon"><span class="material-icons-round">auto_stories</span></div>
                        <h2><?php echo htmlspecialchars($class['subject']); ?></h2>
                    </div>

                    <!-- ARCHIVE MONTH SELECTOR (Dropdown) -->
                    <?php if(!empty($archive_months)): ?>
                        <div class="archive-dropdown-container">
                            <div class="dropdown">
                                <button class="btn btn-archive-select dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="material-icons-round" style="font-size:16px; color:var(--neon-purple);">history_toggle_off</span>
                                    Browse Paid Vaults
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" style="background: #0b1329; border: 1px solid rgba(255,255,255,0.08); border-radius:12px;">
                                    <li><h6 class="dropdown-header text-white-50">Unlocked Past Months</h6></li>
                                    <?php foreach($archive_months as $m_name): ?>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center justify-content-between" href="#" onclick="toggleArchiveMonth(event, '<?php echo $class_id; ?>', '<?php echo $m_name; ?>')">
                                                <span><?php echo $m_name; ?></span>
                                                <span class="material-icons-round text-success" style="font-size:14px;">check_circle</span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- වත්මන් මාසයේ ගෙවීම් පැහැර හැර ඇත්නම් පෙන්වන Alert එක -->
                <?php if (!$current_month_paid): ?>
                    <div class="payment-lock-alert animate__animated animate__shakeX">
                        <span class="material-icons-round lock-icon">lock_person</span>
                        <div class="payment-lock-alert-text">
                            <h4>වත්මන් මාසය අගුලු දමා ඇත (Current Month Locked)</h4>
                            <p>මෙම පන්තිය සඳහා <?php echo $current_month_name; ?> මාසයේ ගෙවීම් හමුනොවීය. (නමුත් ඔබ කලින් ගෙවූ මාස ඇත්නම් ඒවා ඉහත "Browse Paid Vaults" හරහා නැරඹිය හැක).</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="material-section-wrapper">
                    
                    <!-- CURRENT MONTH SECTION: ඩේටාබේස් එකේ තියෙන created_at එකෙන් වත්මන් මාසය ස්වයංක්‍රීයව හඳුනා ගනී -->
                    <div class="month-header-divider">Active Month: <?php echo $current_month_name; ?></div>
                    <div class="material-grid">
                        <?php
                        // MONTHNAME(created_at) මඟින් වත්මන් මාසයට ගැලපෙන ද්‍රව්‍ය පමණක් පෙරා ගනී
                        $mat_sql = "SELECT id, title, week_no, material_type, file_url, video_url, generated_by FROM class_materials WHERE class_id='$class_id' AND MONTHNAME(created_at) = '$current_month_name' ORDER BY week_no ASC";
                        $mat_res = mysqli_query($conn, $mat_sql);

                        if(mysqli_num_rows($mat_res) > 0):
                            while($m = mysqli_fetch_assoc($mat_res)):
                                $attempts_count = 0;
                                $is_quiz = ($m['material_type'] == 'quiz');
                                if($is_quiz) {
                                    $mat_id = $m['id'];
                                    $attempt_sql = "SELECT COUNT(*) AS total_attempts FROM student_attempts WHERE user_id = '$sid' AND material_id = '$mat_id'";
                                    $attempt_res = mysqli_query($conn, $attempt_sql);
                                    if($attempt_res) { $attempts_count = (int)mysqli_fetch_assoc($attempt_res)['total_attempts']; }
                                }
                        ?>
                                <div class="material-card <?php echo (!$current_month_paid) ? 'card-locked' : ''; ?>">
                                    <div class="d-flex align-items-center min-w-0 flex-grow-1">
                                        <div class="week-circle"><span>WK</span><span><?php echo sprintf("%02d", $m['week_no']); ?></span></div>
                                        <div class="mat-info">
                                            <div class="mat-name" title="<?php echo htmlspecialchars($m['title']); ?>"><?php echo htmlspecialchars($m['title']); ?></div>
                                            <div class="d-flex flex-wrap">
                                                <?php if (!$current_month_paid): ?>
                                                    <span class="quiz-badge badge-locked"><span class="material-icons-round" style="font-size:10px;">lock</span>Locked</span>
                                                <?php else: ?>
                                                    <?php if($is_quiz): 
                                                        $type = isset($m['generated_by']) ? $m['generated_by'] : 'manual';
                                                        if($type === 'ai'): echo '<span class="quiz-badge badge-ai"><span class="material-icons-round" style="font-size:10px;">bolt</span>AI Smart</span>';
                                                        elseif($type === 'google_form'): echo '<span class="quiz-badge badge-gform"><span class="material-icons-round" style="font-size:10px;">assignment</span>G Form</span>';
                                                        else: echo '<span class="quiz-badge badge-manual"><span class="material-icons-round" style="font-size:10px;">layers</span>Standard</span>'; endif;
                                                        echo ($attempts_count >= 3) ? '<span class="quiz-badge badge-attempt-max">3/3 Maxed</span>' : '<span class="quiz-badge badge-attempt">Try '.$attempts_count.'/3</span>';
                                                    else: echo '<span class="quiz-badge badge-generic">Resource</span>'; endif;
                                                ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="btn-stack">
                                        <?php if (!$current_month_paid): ?>
                                            <button class="action-btn btn-locked" disabled><span class="material-icons-round">lock</span></button>
                                        <?php else: ?>
                                            <?php if($is_quiz): 
                                                $mode = isset($m['generated_by']) ? $m['generated_by'] : 'manual';
                                                $disable = ($attempts_count >= 3) ? 'disabled' : '';
                                                if($mode === 'google_form'): echo '<a href="'.(($attempts_count >= 3) ? '#' : $m['file_url']).'" target="_blank" class="action-btn btn-gform-quiz '.$disable.'"><span class="material-icons-round">launch</span></a>';
                                                elseif($mode === 'ai'): echo '<a href="'.(($attempts_count >= 3) ? '#' : "take_quiz.php?mid=".$m['id']."&mode=ai").'" class="action-btn btn-ai-quiz '.$disable.'"><span class="material-icons-round">psychology</span></a>';
                                                else: echo '<a href="'.(($attempts_count >= 3) ? '#' : "take_quiz.php?mid=".$m['id']."&mode=manual").'" class="action-btn btn-manual-quiz '.$disable.'"><span class="material-icons-round">quiz</span></a>'; endif;
                                            endif;
                                            if($m['material_type'] == 'tute' || $m['material_type'] == 'note') echo '<a href="'.htmlspecialchars($m['file_url']).'" class="action-btn btn-tute" download><span class="material-icons-round">description</span></a>';
                                            if(!empty($m['video_url'])) echo '<a href="'.htmlspecialchars($m['video_url']).'" target="_blank" class="action-btn btn-video"><span class="material-icons-round">play_arrow</span></a>';
                                        endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="material-card futuristic-placeholder w-100"><div class="d-flex align-items-center"><div class="radar-box"><span class="material-icons-round animate__animated animate__spin animate__infinite" style="animation-duration: 4s;">published_with_changes</span></div><div class="mat-info"><div class="mat-name" style="color:#94a3b8;">Syllabus Node Synchronizing...</div><span class="cyber-tag"><span class="pulse-core"></span> Next Unit Launching Soon</span></div></div></div>
                        <?php endif; ?>
                    </div>

                    <!-- ARCHIVE MONTHS CONTAINER: කලින් ගෙවූ මාස වල ද්‍රව්‍ය පෙන්වන කොටස -->
                    <?php if(!empty($archive_months)): ?>
                        <?php foreach($archive_months as $m_name): ?>
                            <div class="archive-month-block d-none" id="archive-<?php echo $class_id; ?>-<?php echo $m_name; ?>">
                                <div class="month-header-divider text-success" style="color: var(--success) !important;">
                                    <span class="material-icons-round" style="font-size:16px;">folder_open</span> 
                                    Unlocked Vault: <?php echo $m_name; ?>
                                </div>
                                <div class="material-grid">
                                    <?php
                                    // MONTHNAME(created_at) මඟින් අදාළ පැරණි මාසයට අදාළ ද්‍රව්‍ය ස්වයංක්‍රීයව ෆිල්ටර් කර ගනී
                                    $arc_mat_sql = "SELECT id, title, week_no, material_type, file_url, video_url, generated_by FROM class_materials WHERE class_id='$class_id' AND MONTHNAME(created_at) = '$m_name' ORDER BY week_no ASC";
                                    $arc_mat_res = mysqli_query($conn, $arc_mat_sql);

                                    if(mysqli_num_rows($arc_mat_res) > 0):
                                        while($am = mysqli_fetch_assoc($arc_mat_res)):
                                            $am_quiz = ($am['material_type'] == 'quiz');
                                            $am_attempts = 0;
                                            if($am_quiz) {
                                                $amid = $am['id'];
                                                $am_att_sql = "SELECT COUNT(*) AS total_attempts FROM student_attempts WHERE user_id = '$sid' AND material_id = '$amid'";
                                                $am_att_res = mysqli_query($conn, $am_att_sql);
                                                if($am_att_res) { $am_attempts = (int)mysqli_fetch_assoc($am_att_res)['total_attempts']; }
                                            }
                                    ?>
                                            <div class="material-card animate__animated animate__fadeIn">
                                                <div class="d-flex align-items-center min-w-0 flex-grow-1">
                                                    <div class="week-circle" style="border-color: rgba(16, 185, 129, 0.2);"><span>WK</span><span><?php echo sprintf("%02d", $am['week_no']); ?></span></div>
                                                    <div class="mat-info">
                                                        <div class="mat-name" title="<?php echo htmlspecialchars($am['title']); ?>"><?php echo htmlspecialchars($am['title']); ?></div>
                                                        <div class="d-flex flex-wrap">
                                                            <?php if($am_quiz): 
                                                                $am_type = isset($am['generated_by']) ? $am['generated_by'] : 'manual';
                                                                if($am_type === 'ai'): echo '<span class="quiz-badge badge-ai">AI Smart</span>';
                                                                elseif($am_type === 'google_form'): echo '<span class="quiz-badge badge-gform">G Form</span>';
                                                                else: echo '<span class="quiz-badge badge-manual">Standard</span>'; endif;
                                                                echo ($am_attempts >= 3) ? '<span class="quiz-badge badge-attempt-max">3/3 Maxed</span>' : '<span class="quiz-badge badge-attempt">Try '.$am_attempts.'/3</span>';
                                                            else: echo '<span class="quiz-badge badge-generic">Archive</span>'; endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="btn-stack">
                                                    <?php if($am_quiz): 
                                                        $am_mode = isset($am['generated_by']) ? $am['generated_by'] : 'manual';
                                                        $am_disable = ($am_attempts >= 3) ? 'disabled' : '';
                                                        if($am_mode === 'google_form'): echo '<a href="'.(($am_attempts >= 3) ? '#' : $am['file_url']).'" target="_blank" class="action-btn btn-gform-quiz '.$am_disable.'"><span class="material-icons-round">launch</span></a>';
                                                        elseif($am_mode === 'ai'): echo '<a href="'.(($am_attempts >= 3) ? '#' : "take_quiz.php?mid=".$am['id']."&mode=ai").'" class="action-btn btn-ai-quiz '.$am_disable.'"><span class="material-icons-round">psychology</span></a>';
                                                        else: echo '<a href="'.(($am_attempts >= 3) ? '#' : "take_quiz.php?mid=".$am['id']."&mode=manual").'" class="action-btn btn-manual-quiz '.$am_disable.'"><span class="material-icons-round">quiz</span></a>'; endif;
                                                    endif; 
                                                    if($am['material_type'] == 'tute' || $am['material_type'] == 'note') echo '<a href="'.htmlspecialchars($am['file_url']).'" class="action-btn btn-tute" download><span class="material-icons-round">description</span></a>';
                                                    if(!empty($am['video_url'])) echo '<a href="'.htmlspecialchars($am['video_url']).'" target="_blank" class="action-btn btn-video"><span class="material-icons-round">play_arrow</span></a>'; ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="p-4 text-white-50 text-center fs-7 italic opacity-50 w-100">No active documents or quizzes inside this month's database block.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>

        <?php endwhile; ?>

    </main>

    <!-- JavaScript For Creative Dropdown Toggling -->
    <script>
        function toggleArchiveMonth(event, classId, monthName) {
            event.preventDefault();
            
            const siblings = document.querySelectorAll(`[id^="archive-${classId}-"]`);
            siblings.forEach(el => {
                if(el.id !== `archive-${classId}-${monthName}`) {
                    el.classList.add('d-none');
                }
            });

            const targetBlock = document.getElementById(`archive-${classId}-${monthName}`);
            if(targetBlock) {
                targetBlock.classList.toggle('d-none');
                if(!targetBlock.classList.contains('d-none')) {
                    targetBlock.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>