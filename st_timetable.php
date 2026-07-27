<?php 
session_start();
include 'db.php'; // Your Database Connection File

// Check if student is logged in
if(!isset($_SESSION['student_data'])) {
    header("Location: st_login.php");
    exit();
}

$s = $_SESSION['student_data'];
$sid = $s['student_id'];

/* ==========================================
   1. Fetch Enrolled Classes & Timetable Details
   ========================================== */
$timetable_sql = "SELECT 
                    c.id as class_id, 
                    c.subject, 
                    t.name as teacher_name, 
                    ct.day_of_week as class_day, 
                    TIME_FORMAT(ct.start_time, '%h:%i %p') as start_time, 
                    TIME_FORMAT(ct.end_time, '%h:%i %p') as end_time, 
                    ct.hall_name, 
                    ct.zoom_link
                  FROM student_classes sc
                  JOIN classes c ON sc.class_id = c.id
                  JOIN teachers t ON c.teacher_id = t.id
                  JOIN class_timetable ct ON c.id = ct.class_id
                  WHERE sc.student_id = ?
                  ORDER BY FIELD(ct.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ct.start_time";

$stmt = mysqli_prepare($conn, $timetable_sql);
mysqli_stmt_bind_param($stmt, "s", $sid);
mysqli_stmt_execute($stmt);
$timetable_res = mysqli_stmt_get_result($stmt);

$schedule = [];
if ($timetable_res) {
    while($row = mysqli_fetch_assoc($timetable_res)) {
        $schedule[$row['class_day']][] = $row;
    }
}

/* ==========================================
   2. Handle Teacher Notification / Message
   ========================================== */
$msg_success = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_msg'])) {
    $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
    $message = mysqli_real_escape_string($conn, $_POST['message_body']);
    
    $notif_title = "New Inquiry from Student (ID: " . $sid . ")";
    
    $insert_msg = "INSERT INTO student_notifications (student_id, title, message, is_read, created_at) 
                   VALUES (?, ?, ?, 0, NOW())";
    
    $stmt_insert = mysqli_prepare($conn, $insert_msg);
    mysqli_stmt_bind_param($stmt_insert, "sss", $sid, $notif_title, $message);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        $msg_success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sigma Institute | Premium Timetable Portal</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Abhaya+Libre:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Round">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --bg: #030712;
            --card: rgba(163, 163, 163, 0.7);
            --sidebar: #0b1220;
            --border: rgba(255, 255, 255, 0.06);
            --gold: #fbbf24;
            --blue: #2563eb;
            --text: #f8fafc;
            --muted: #94a3b8;
            --purple: #8b5cf6;
            --emerald: #10b981;
            --glow: rgba(251, 191, 36, 0.15);
            --card-glass: rgba(255, 255, 255, 0.03);
            --border-glass: rgba(255, 255, 255, 0.08);
            --input-bg: rgba(20, 26, 46, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }
       
        /* MAIN CONTENTS RESPONSIVE */
        .main-content { 
            padding: 25px 16px; 
            max-width: 1500px; 
            transition: all 0.3s ease;
        }

        @media (min-width: 1025px) {
            .main-content { 
                margin-left: 280px; 
                padding: 50px 55px; 
            }
        }

        /* NISADAS BANNER */
        .nisadas-banner {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.1), rgba(236, 72, 153, 0.05));
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 35px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }
        @media (min-width: 768px) {
            .nisadas-banner { border-radius: 30px; padding: 35px; margin-bottom: 45px; }
        }
        .nisadas-banner::before {
            content: '“'; position: absolute; font-size: 140px; color: rgba(168, 85, 247, 0.06); font-family: serif; top: -40px; left: 15px; line-height: 1;
        }
        .nisadas-text {
            font-family: 'Abhaya Libre', serif;
            font-size: calc(1.1rem + 0.3vw);
            font-weight: 700;
            line-height: 1.8;
            color: #f1f5f9;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
            position: relative;
            z-index: 1;
        }
        .nisadas-author {
            font-size: 0.8rem;
            color: #f472b6;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 15px;
            display: block;
            position: relative;
            z-index: 1;
        }

        /* TIMETABLE SECTIONS */
        .day-section { margin-bottom: 40px; }
        .day-title { font-size: 1.25rem; font-weight: 800; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; letter-spacing: 0.5px; text-transform: uppercase; }
        .day-title span.indicator { width: 8px; height: 22px; background: linear-gradient(to bottom, #ec4899, var(--purple)); border-radius: 6px; display: inline-block; box-shadow: 0 0 15px var(--purple); }

        /* PREMIUM GLASS CARDS */
        .class-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 20px; }
        @media (min-width: 768px) { .class-grid { grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 25px; } }
        
        .class-card { background: var(--card-glass); border: 1px solid var(--border-glass); border-radius: 24px; padding: 25px; backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; display: flex; flex-direction: column; height: 100%; }
        .class-card:hover { transform: translateY(-5px); border-color: rgba(236, 72, 153, 0.3); box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        .class-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--blue), var(--purple), #ec4899); opacity: 0.8; }

        .subject-name { font-size: 1.35rem; font-weight: 800; color: #fff; margin-bottom: 6px; letter-spacing: -0.5px; padding-right: 90px; }
        .teacher-name { font-size: 0.9rem; color: #cbd5e1; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        
        .meta-info { background: rgba(255,255,255,0.01); border-radius: 16px; padding: 14px; border: 1px solid rgba(255,255,255,0.03); margin-bottom: 20px; margin-top: auto; }
        .meta-item { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; color: #d1d5db; margin-bottom: 10px; }
        .meta-item:last-child { margin-bottom: 0; }
        .meta-item .material-icons-round { font-size: 18px; color: var(--blue); }

        /* BUTTONS */
        .btn-msg-teacher { background: rgba(255, 255, 255, 0.02); color: #fff; border: 1px solid var(--border-glass); padding: 12px; border-radius: 14px; width: 100%; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s; text-decoration: none; }
        .btn-msg-teacher:hover { background: linear-gradient(135deg, rgba(168, 85, 247, 0.15), rgba(236, 72, 153, 0.15)); border-color: rgba(236, 72, 153, 0.4); color: #f472b6; }

        .btn-zoom { background: linear-gradient(135deg, #2563eb, #a855f7); border: none; padding: 12px; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2); transition: all 0.3s ease; font-weight: 700; border-radius: 14px; font-size: 0.85rem; }
        .btn-zoom:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4); background: linear-gradient(135deg, #3b82f6, #ec4899); }

        /* NEON BADGES */
        .type-badge { position: absolute; top: 25px; right: 25px; font-size: 0.65rem; font-weight: 800; padding: 5px 12px; border-radius: 10px; text-transform: uppercase; letter-spacing: 0.8px; z-index: 2; }
        .badge-online { background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .badge-physical { background: rgba(236, 72, 153, 0.1); color: #f472b6; border: 1px solid rgba(236, 72, 153, 0.3); }

        /* MODAL GLASS EFFECTS */
        .premium-modal .modal-content { background: #0a0f1e; border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 24px; padding: 15px; color: #fff; box-shadow: 0 25px 50px rgba(0,0,0,0.6); }
        .msg-textarea { background: var(--input-bg); border: 1px solid var(--border-glass); color: #fff; border-radius: 14px; padding: 12px; width: 100%; height: 130px; resize: none; font-size: 0.9rem; transition: all 0.3s; }
        .msg-textarea:focus { outline: none; border-color: var(--purple); box-shadow: 0 0 15px rgba(168, 85, 247, 0.2); }
        .btn-send-now { background: linear-gradient(135deg, var(--blue), #ec4899); color: white; border: none; padding: 14px; border-radius: 14px; font-weight: 800; width: 100%; transition: all 0.3s; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: var(--card-glass); border: 1px solid var(--border-glass); border-radius: 24px; }

        /* SIDEBAR TOGGLE COMPATIBILITY FOR TABLETS/MOBILES */
        @media (max-width: 1024px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
<?php include 'st_sidebar.php'; ?>
   
    <main class="main-content">
        
        <header class="page-header d-flex justify-content-between align-items-center mb-4 animate__animated animate__fadeIn">
            <div>
                <h1 style="font-weight: 800; letter-spacing: -1.5px; background: linear-gradient(to right, #fff, #9ca3af); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: calc(1.6rem + 1vw);">My Class Schedule</h1>
                <p style="color: var(--muted); font-weight: 500; margin: 0; font-size: 0.9rem;">Manage and review your weekly enrolled lecture schedules here.</p>
            </div>
            <span class="material-icons-round d-none d-md-block shadow-lg" style="font-size: 3rem; color: var(--purple); opacity: 0.3;">auto_awesome</span>
        </header>

        <div class="nisadas-banner animate__animated animate__fadeInDown">
            <div class="nisadas-text">
                නොනැවතී ඉදිරියටම යන ගමනක් මැද, <br>
                හෙට දින දිනන්නට වෙහෙසෙන නෙතට, <br>
                කාලයේ වටිනාකම මතක් කර දෙමින්... <br>
                මෙන්න ඔබේ סיහින දේශන මාලාව!
            </div>
            <span class="nisadas-author">SIGMA INSTITUTE • EDUCATION FOR FUTURE</span>
        </div>

        <?php if($msg_success): ?>
            <div class="alert alert-success border-0 rounded-4 p-3 mb-4 animate__animated animate__bounceIn" style="background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2) !important;">
                <div class="d-flex align-items-center gap-2">
                    <span class="material-icons-round">check_circle</span>
                    <span class="fw-semibold">Your inquiry message has been successfully sent to the teacher!</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if(!empty($schedule)): ?>
            <?php foreach($schedule as $day => $classes): ?>
                <section class="day-section animate__animated animate__fadeInUp">
                    <div class="day-title">
                        <span class="indicator"></span>
                        <?php echo htmlspecialchars($day); ?>
                    </div>
                    
                    <div class="class-grid">
                        <?php foreach($classes as $class): 
                            $is_online = (!empty($class['zoom_link'])) ? true : false;
                            $badge_text = $is_online ? "Online" : "Physical";
                            $badge_class = $is_online ? "badge-online" : "badge-physical";
                        ?>
                            <div class="class-card">
                                <span class="type-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                
                                <div class="subject-name text-truncate" title="<?php echo htmlspecialchars($class['subject']); ?>"><?php echo htmlspecialchars($class['subject']); ?></div>
                                <div class="teacher-name">
                                    <span class="material-icons-round" style="font-size: 16px; color: #f472b6;">school</span>
                                    <?php echo htmlspecialchars($class['teacher_name']); ?>
                                </div>

                                <div class="meta-info">
                                    <div class="meta-item">
                                        <span class="material-icons-round">access_time</span>
                                        <span><?php echo $class['start_time'] . " - " . $class['end_time']; ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="material-icons-round">place</span>
                                        <span class="text-truncate"><?php echo $is_online ? "Zoom Web Portal" : "Hall: " . htmlspecialchars($class['hall_name']); ?></span>
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <?php if($is_online): ?>
                                        <a href="<?php echo htmlspecialchars($class['zoom_link']); ?>" target="_blank" class="btn btn-primary btn-zoom w-100 rounded-3 mb-2 p-2 d-flex align-items-center justify-content-center gap-2 text-white">
                                            <span class="material-icons-round" style="font-size: 18px;">bolt</span> Join Live Zoom
                                        </a>
                                    <?php endif; ?>

                                    <button class="btn-msg-teacher" onclick="openMessageModal('<?php echo htmlspecialchars($class['teacher_name'], ENT_QUOTES); ?>', '<?php echo $class['class_id']; ?>', '<?php echo htmlspecialchars($class['subject'], ENT_QUOTES); ?>')">
                                        <span class="material-icons-round" style="font-size: 16px;">chat_bubble_outline</span>
                                        Message Teacher
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state animate__animated animate__fadeIn">
                <span class="material-icons-round text-muted mb-3" style="font-size: 3.5rem; opacity: 0.2; color: #f472b6 !important;">calendar_today</span>
                <h4 class="text-white fw-bold">No Classes Found</h4>
                <p class="text-muted m-0 small">It looks like you are not enrolled in any active schedules for this week.</p>
            </div>
        <?php endif; ?>

    </main>

    <div class="modal fade premium-modal" id="msgModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalHeading"><span class="material-icons-round text-purple align-middle me-2">forum</span>Send Inquiry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="class_id" id="modalClassId">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">RECIPIENT TEACHER</label>
                            <input type="text" id="modalTeacherName" class="form-control text-white" style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-glass); border-radius: 12px; font-weight: 600; padding: 10px;" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">YOUR INQUIRY MESSAGE</label>
                            <textarea name="message_body" class="msg-textarea" placeholder="Type your question or issue here for the teacher..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="submit" name="send_msg" class="btn-send-now">
                            <span class="material-icons-round align-middle me-2" style="font-size: 18px;">send</span>Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openMessageModal(teacherName, classId, subject) {
            document.getElementById('modalTeacherName').value = teacherName;
            document.getElementById('modalClassId').value = classId;
            document.getElementById('modalHeading').innerHTML = `<span class="material-icons-round text-primary align-middle me-2">chat</span>Inquiry: ${subject}`;
            
            var myModal = new bootstrap.Modal(document.getElementById('msgModal'));
            myModal.show();
        }
    </script>
</body>
</html>