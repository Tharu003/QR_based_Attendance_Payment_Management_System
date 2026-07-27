<?php
session_start();
include "db.php";

// Admin Access Validation Check
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit();
}

date_default_timezone_set("Asia/Colombo");

$message = "";

/* ======================================================
    1. SAVE TIMETABLE SLOT (WITH CONFLICT CHECKS)
====================================================== */
if(isset($_POST['save_timetable'])){

    $class_input = isset($_POST['class_input']) ? trim($_POST['class_input']) : '';
    $day         = isset($_POST['day_of_week']) ? trim($_POST['day_of_week']) : '';
    $start_time  = isset($_POST['start_time']) ? $_POST['start_time'] : '';
    $end_time    = isset($_POST['end_time']) ? $_POST['end_time'] : '';
    $hall_name   = isset($_POST['hall_name']) ? trim($_POST['hall_name']) : '';
    $zoom_link   = isset($_POST['zoom_link']) ? trim($_POST['zoom_link']) : '';

    if(empty($class_input) || empty($day) || empty($start_time) || empty($end_time)){
        $message = "<div class='alert alert-danger border-0 shadow-sm d-flex align-items-center bg-danger bg-opacity-10 text-danger'><i class='fas fa-exclamation-circle me-2 fs-5'></i> Please fill all required fields.</div>";
    } elseif(strtotime($end_time) <= strtotime($start_time)){
        $message = "<div class='alert alert-danger border-0 shadow-sm d-flex align-items-center bg-danger bg-opacity-10 text-danger'><i class='fas fa-clock me-2 fs-5'></i> End time must be greater than start time.</div>";
    } else {
        $parts = explode('|', $class_input);
        $class_id = intval($parts[0]);
        $selected_grade = isset($parts[1]) ? trim($parts[1]) : '';

        /* GET TEACHER FOR SPECIFIC CLASS SUBJECT */
        $teacher_q = $conn->prepare("SELECT teacher_id FROM classes WHERE id=?");
        $teacher_q->bind_param("i", $class_id);
        $teacher_q->execute();
        $teacher_res = $teacher_q->get_result();
        $teacher = $teacher_res->fetch_assoc();
        $teacher_id = $teacher['teacher_id'] ?? null;

        $has_teacher_conflict = false;

        /* ADVANCED TEACHER CONFLICT CHECK */
        if($teacher_id) {
            $teacher_check = $conn->prepare("
                SELECT tt.id FROM class_timetable tt
                JOIN classes c ON tt.class_id = c.id
                WHERE c.teacher_id = ? AND tt.day_of_week = ? AND (? < tt.end_time AND ? > tt.start_time)
            ");
            $teacher_check->bind_param("isss", $teacher_id, $day, $start_time, $end_time);
            $teacher_check->execute();
            
            if($teacher_check->get_result()->num_rows > 0){
                $has_teacher_conflict = true;
                $message = "<div class='alert alert-warning border-0 shadow-sm d-flex align-items-center bg-warning bg-opacity-10 text-warning'><i class='fas fa-user-xmark me-2 fs-5'></i> This specific teacher already has another session scheduled during this time slot.</div>";
            }
        }

        /* HALL CONFLICT CHECK */
        if(!$has_teacher_conflict) {
            if(!empty($hall_name)) {
                $hall_check = $conn->prepare("
                    SELECT id FROM class_timetable WHERE hall_name = ? AND day_of_week = ? AND (? < end_time AND ? > start_time)
                ");
                $hall_check->bind_param("ssss", $hall_name, $day, $start_time, $end_time);
                $hall_check->execute();
                
                if($hall_check->get_result()->num_rows > 0){
                    $message = "<div class='alert alert-warning border-0 shadow-sm d-flex align-items-center bg-warning bg-opacity-10 text-warning'><i class='fas fa-building-circle-xmark me-2 fs-5'></i> This venue/hall is already booked for the selected time slot.</div>";
                } else {
                    /* INSERT DATA WITH HALL */
                    $insert = $conn->prepare("
                        INSERT INTO class_timetable (class_id, grade, day_of_week, start_time, end_time, hall_name, zoom_link) VALUES (?,?,?,?,?,?,?)
                    ");
                    $insert->bind_param("issssss", $class_id, $selected_grade, $day, $start_time, $end_time, $hall_name, $zoom_link);
                    if($insert->execute()){
                        $message = "<div class='alert alert-success border-0 shadow-sm d-flex align-items-center bg-success bg-opacity-10 text-success'><i class='fas fa-check-circle me-2 fs-5'></i> Timetable slot allocated successfully!</div>";
                    } else {
                        $message = "<div class='alert alert-danger border-0 shadow-sm d-flex align-items-center bg-danger bg-opacity-10 text-danger'><i class='fas fa-exclamation-triangle me-2 fs-5'></i> Database Error: " . htmlspecialchars($conn->error) . "</div>";
                    }
                }
            } else {
                /* INSERT DATA WITHOUT HALL */
                $insert = $conn->prepare("
                    INSERT INTO class_timetable (class_id, grade, day_of_week, start_time, end_time, hall_name, zoom_link) VALUES (?,?,?,?,?,?,?)
                ");
                $insert->bind_param("issssss", $class_id, $selected_grade, $day, $start_time, $end_time, $hall_name, $zoom_link);
                if($insert->execute()){
                    $message = "<div class='alert alert-success border-0 shadow-sm d-flex align-items-center bg-success bg-opacity-10 text-success'><i class='fas fa-check-circle me-2 fs-5'></i> Timetable slot allocated successfully!</div>";
                } else {
                    $message = "<div class='alert alert-danger border-0 shadow-sm d-flex align-items-center bg-danger bg-opacity-10 text-danger'><i class='fas fa-exclamation-triangle me-2 fs-5'></i> Database Error: " . htmlspecialchars($conn->error) . "</div>";
                }
            }
        }
    }
}

/* ======================================================
    2. DELETE TIMETABLE ENTRY
====================================================== */
if(isset($_GET['delete'])){
    $delete_id = intval($_GET['delete']);
    $del = $conn->prepare("DELETE FROM class_timetable WHERE id=?");
    $del->bind_param("i", $delete_id);
    $del->execute();
    header("Location: timetable.php");
    exit();
}

/* ======================================================
    3. FETCH DROPDOWN DATA (SUBJECT + GRADE + TEACHER)
====================================================== */
$classes_dropdown = mysqli_query($conn, "
    SELECT c.id AS class_id, c.subject, t.name AS teacher, cg.grade 
    FROM classes c
    JOIN class_grades cg ON c.id = cg.class_id
    LEFT JOIN teachers t ON c.teacher_id = t.id
    ORDER BY cg.grade ASC, c.subject ASC
");

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
$current_day_name = date("l"); 

/* ======================================================
    4. FETCH ALL TIMETABLE ENTRIES
====================================================== */
$timetable = [];
$tt = mysqli_query($conn, "
    SELECT tt.id, tt.class_id, tt.grade AS specific_grade, tt.day_of_week, tt.start_time, tt.end_time, tt.hall_name, tt.zoom_link, c.subject, t.name AS teacher
    FROM class_timetable tt 
    JOIN classes c ON tt.class_id = c.id 
    LEFT JOIN teachers t ON c.teacher_id = t.id
    ORDER BY tt.start_time ASC
");

if($tt) {
    while($row = mysqli_fetch_assoc($tt)){
        if(isset($row['day_of_week'])) {
            $timetable[$row['day_of_week']][] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Dashboard | Weekly Timetable</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #09090b;
            --card-bg: #141417;
            --card-inner: #1c1c21;
            --accent-primary: #3b82f6;
            --accent-gradient: linear-gradient(135deg, #3b82f6, #1d4ed8);
            --text-muted: #a1a1aa;
            --border-color: rgba(255, 255, 255, 0.06);
            --sidebar-w: 280px;
        }

        body { 
            background-color: var(--bg-main); 
            color: #f4f4f5;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            overflow-x: hidden;
        }

        /* Layout Structure */
        .main-content {
            margin-left: var(--sidebar-w);
            padding: 2.5rem 1.5rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            background: linear-gradient(to right, #ffffff, #a1a1aa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Glassmorphism Container Card */
        .custom-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        /* Modernized Form Styling */
        .form-control, .form-select {
            background: rgba(24, 24, 27, 0.8) !important;
            border: 1px solid var(--border-color) !important;
            color: #fafafa !important;
            height: 54px;
            border-radius: 14px;
            padding: 0 16px;
            transition: all 0.2s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }

        .btn-modern {
            background: var(--accent-gradient);
            color: white; 
            border: none; 
            height: 52px; 
            border-radius: 14px; 
            font-weight: 700;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            color: #fff;
        }

        /* Day Selection Tabs */
        .tabs-container {
            overflow-x: auto;
            scrollbar-width: none;
            margin-bottom: 1.5rem;
            padding-bottom: 5px;
        }
        .tabs-container::-webkit-scrollbar {
            display: none;
        }

        .nav-pills-custom {
            background: #141417;
            padding: 8px;
            border-radius: 20px;
            border: 1px solid var(--border-color);
            gap: 6px;
            display: flex;
            flex-wrap: nowrap;
            width: max-content;
        }
        
        @media (min-width: 992px) {
            .nav-pills-custom {
                flex-wrap: wrap;
                width: 100%;
                justify-content: center;
            }
        }

        .nav-pills-custom .nav-link {
            color: var(--text-muted);
            font-weight: 600;
            padding: 12px 20px;
            border-radius: 14px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .nav-pills-custom .nav-link.active {
            background: var(--accent-gradient) !important;
            color: #fff !important;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .nav-pills-custom .nav-link:hover:not(.active) {
            background: rgba(255, 255, 255, 0.03);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.05);
        }

        /* Timetable Grid Cards */
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        @media (max-width: 480px) {
            .classes-grid {
                grid-template-columns: 1fr;
            }
        }

        .class-item {
            background: var(--card-inner);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 20px;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .class-item:hover {
            transform: translateY(-5px);
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }
        .class-item::before {
            content: ''; 
            position: absolute; 
            top: 20px; 
            left: 0; 
            width: 4px; 
            height: 40px;
            background: var(--accent-primary); 
            border-top-right-radius: 4px; 
            border-bottom-right-radius: 4px;
        }

        .grade-badge {
            display: inline-block; 
            background: rgba(168, 85, 247, 0.1); 
            color: #d8b4fe;
            padding: 5px 12px; 
            font-size: 0.75rem; 
            font-weight: 700; 
            border-radius: 8px; 
            margin-bottom: 12px;
            border: 1px solid rgba(168, 85, 247, 0.2);
            width: fit-content;
        }

        .subject { font-weight: 700; font-size: 1.25rem; color: #fafafa; letter-spacing: -0.02em; }
        .teacher { color: var(--text-muted); font-size: 0.95rem; margin: 6px 0 16px 0; display: flex; align-items: center; gap: 8px; }
        
        .time-box {
            display: inline-flex; 
            align-items: center; 
            gap: 8px;
            background: rgba(59, 130, 246, 0.08); 
            color: #93c5fd;
            padding: 8px 14px; 
            border-radius: 12px; 
            font-size: 0.85rem; 
            font-weight: 600;
            border: 1px solid rgba(59, 130, 246, 0.15);
            width: fit-content;
        }
        
        .hall { margin-top: 14px; font-size: 0.9rem; color: #34d399; font-weight: 600; display: flex; align-items: center; gap: 6px; }

        .zoom-link-btn {
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px;
            background: rgba(239, 68, 68, 0.08); 
            color: #fca5a5; 
            text-decoration: none;
            padding: 10px; 
            border-radius: 12px; 
            font-size: 0.85rem; 
            font-weight: 600;
            margin-top: 14px;
            border: 1px solid rgba(239, 68, 68, 0.15);
            transition: all 0.2s ease;
        }
        .zoom-link-btn:hover {
            background: #ef4444;
            color: white;
        }

        .action-buttons {
            display: flex; 
            justify-content: flex-end; 
            margin-top: 16px;
            border-top: 1px solid var(--border-color); 
            padding-top: 12px;
        }
        .btn-delete { color: #71717a; background: none; border: none; font-size: 0.88rem; text-decoration:none; transition: color 0.2s; }
        .btn-delete:hover { color: #ef4444; }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }

        /* RESPONSIVE MEDIA QUERIES */
        @media (max-width: 991.98px) {
            .main-content { 
                margin-left: 0 !important; 
                padding: 1.5rem 1rem; 
            }
            .page-title { font-size: 2rem; }
        }

        @media (max-width: 576px) {
            .header-section {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 16px;
            }
            .btn-modern {
                width: 100%;
            }
            .page-title { font-size: 1.75rem; }
        }
    </style>
</head>
<body>

<?php 
if(file_exists('sidebar.php')){
    include 'sidebar.php'; 
}
?>

<div class="main-content">
    <div class="container-fluid p-0">
        
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4 mb-md-5 header-section flex-wrap">
            <div>
                <div class="page-title">
                    <i class="fas fa-calendar-days me-2 me-sm-3" style="color: var(--accent-primary);"></i>Weekly Master Schedule
                </div>
                <div class="text-muted mt-1" style="font-size: 0.9rem;">Smart scheduling logs with dynamic room allocation and conflict oversight.</div>
            </div>
            <button class="btn btn-modern px-4" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus me-2"></i>Create New Slot
            </button>
        </div>

        <?php if(!empty($message)) echo '<div class="mb-4">'.$message.'</div>'; ?>

        <!-- Scrollable Day Tabs Filter -->
        <div class="tabs-container">
            <ul class="nav nav-pills nav-pills-custom" id="pills-tab" role="tablist">
                <?php foreach($days as $day): 
                    $isActive = ($day === $current_day_name) ? 'active' : '';
                ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $isActive; ?>" id="pill-<?php echo $day; ?>-tab" data-bs-toggle="pill" data-bs-target="#pill-<?php echo $day; ?>" type="button" role="tab">
                            <i class="far fa-calendar-check me-2"></i><?php echo $day; ?>
                            <?php if(isset($timetable[$day])): ?>
                                <span class="badge bg-dark border border-secondary ms-2 text-white"><?php echo count($timetable[$day]); ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Main Timetable Content Grid Area -->
        <div class="tab-content custom-card p-3 p-sm-4" id="pills-tabContent">
            <?php foreach($days as $day): 
                $isActive = ($day === $current_day_name) ? 'show active' : '';
            ?>
                <div class="tab-pane fade <?php echo $isActive; ?>" id="pill-<?php echo $day; ?>" role="tabpanel">
                    
                    <?php if(isset($timetable[$day]) && count($timetable[$day]) > 0): ?>
                        <div class="classes-grid">
                            <?php foreach($timetable[$day] as $row): ?>
                                <div class="class-item">
                                    <div>
                                        <div class="grade-badge"><i class="fas fa-graduation-cap me-1"></i><?php echo htmlspecialchars($row['specific_grade']); ?></div>
                                        <div class="subject"><?php echo htmlspecialchars($row['subject']); ?></div>
                                        <div class="teacher"><i class="fas fa-user-tie opacity-50"></i><?php echo htmlspecialchars($row['teacher'] ?? 'No Teacher Assigned'); ?></div>
                                    </div>
                                    
                                    <div>
                                        <div class="time-box"><i class="far fa-clock"></i><?php echo date("h:i A", strtotime($row['start_time'])); ?> - <?php echo date("h:i A", strtotime($row['end_time'])); ?></div>
                                        <div class="hall"><i class="fas fa-location-dot opacity-70"></i><?php echo !empty($row['hall_name']) ? htmlspecialchars($row['hall_name']) : 'Virtual / External'; ?></div>
                                        
                                        <?php if(!empty($row['zoom_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($row['zoom_link']); ?>" target="_blank" class="zoom-link-btn"><i class="fas fa-video"></i> Launch Virtual Class</a>
                                        <?php endif; ?>

                                        <div class="action-buttons">
                                            <a href="timetable.php?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to remove this slot?')"><i class="fas fa-trash-alt me-1"></i> Remove Slot</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-xmark fa-4x mb-3 opacity-20"></i>
                            <h5 class="fw-bold text-white">No Sessions Scheduled</h5>
                            <p class="text-muted mb-0">There are no educational sessions configured for this weekday.</p>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add Timetable Input Modal Component -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered px-3">
        <div class="modal-content custom-card border-0 p-2 p-sm-3">
            <div class="modal-header border-0">
                <h4 class="fw-bold text-white mb-0">Schedule Session Slot</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="timetable.php">
                    <div class="row g-3 g-sm-4">
                        <div class="col-md-8 col-12">
                            <label class="text-white-50 mb-2">Course, Subject & Teacher</label>
                            <select name="class_input" class="form-select" required>
                                <option value="">Select Target Class, Subject & Teacher Context</option>
                                <?php if($classes_dropdown): ?>
                                    <?php while($c = mysqli_fetch_assoc($classes_dropdown)): ?>
                                        <option value="<?php echo $c['class_id'].'|'.$c['grade']; ?>">
                                            <?php echo htmlspecialchars("[" . $c['grade'] . "] " . $c['subject'] . " — " . ($c['teacher'] ?? 'No Teacher')); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4 col-12">
                            <label class="text-white-50 mb-2">Assigned Day</label>
                            <select name="day_of_week" class="form-select" required>
                                <?php foreach($days as $d): ?>
                                    <option value="<?php echo $d; ?>" <?php echo ($d === $current_day_name) ? 'selected' : ''; ?>><?php echo $d; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 col-6">
                            <label class="text-white-50 mb-2">Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-4 col-6">
                            <label class="text-white-50 mb-2">End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                        <div class="col-md-4 col-12">
                            <label class="text-white-50 mb-2">Venue / Classroom Hall</label>
                            <input type="text" name="hall_name" class="form-control" placeholder="e.g. Hall A">
                        </div>
                        <div class="col-12">
                            <label class="text-white-50 mb-2">Zoom Broadcast Link (Optional)</label>
                            <input type="url" name="zoom_link" class="form-control" placeholder="https://zoom.us/j/...">
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" name="save_timetable" class="btn btn-modern w-100">Commit Schedule Row</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>