<?php
/** @var mysqli $conn */
session_start();
date_default_timezone_set("Asia/Colombo");
include "db.php"; 

// 1. ආරක්ෂක පරීක්ෂාව
$allowed_roles = ['admin', 'teacher', 'assistant'];
if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)){
    header("Location: login.php");
    exit();
}

$status_message = "";
$status_type = "";

// 2. Form එක Submit කළ පසු දත්ත එකතු කිරීමේ Logic එක
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_exam'])) {
    $class_id = intval($_POST['class_id']);
    $selected_grade = mysqli_real_escape_string($conn, $_POST['exam_grade']); // තෝරාගත් නිශ්චිත Grade එක
    $exam_title = mysqli_real_escape_string($conn, $_POST['exam_title']);
    $exam_type = mysqli_real_escape_string($conn, $_POST['exam_type']);
    $exam_date = mysqli_real_escape_string($conn, $_POST['exam_date']);
    $duration = intval($_POST['duration_minutes']);
    
    if ($exam_type === 'physical') {
        $location_or_link = mysqli_real_escape_string($conn, $_POST['hall_number']);
    } else {
        $location_or_link = mysqli_real_escape_string($conn, $_POST['online_link']);
    }

    // 💡 නිවැරදි කිරීම: grade එකත් INSERT කරනවා
    $query = "INSERT INTO exams (class_id, grade, exam_title, exam_type, exam_location_or_link, exam_date, duration_minutes) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssssi", $class_id, $selected_grade, $exam_title, $exam_type, $location_or_link, $exam_date, $duration);
        
        if (mysqli_stmt_execute($stmt)) {
            $status_message = "විභාගය සාර්ථකව පද්ධතියට ඇතුළත් කරන ලදී! (Exam Created Successfully)";
            $status_type = "success";

            // =========================================================================
            // 🚀 --- ස්වයංක්‍රීයව EXAM NOTICE එකක් ඇතුළත් කිරීම ---
            // =========================================================================
            $notice_title = "📝 නව විභාග දැනුම්දීමයි (" . $selected_grade . "): " . $exam_title;
            
            $display_type = ($exam_type === 'physical') ? "Physical (පන්තියේදී)" : "Online (අන්තර්ජාලයෙන්)";
            $display_loc = ($exam_type === 'physical') ? "🏛️ විභාග ශාලාව: " : "🔗 Portal/Zoom Link: ";

            $notice_content = "ඔබේ " . $selected_grade . " පන්තිය සඳහා අලුතින් විභාග කාලසටහනක් එකතු කර ඇත.\n\n" .
                             "📅 දිනය සහ වේලාව: " . $exam_date . "\n" .
                             "⏳ කාලය: " . $duration . " Minutes\n" .
                             "🧭 විභාග ක්‍රමය: " . $display_type . "\n" .
                             $display_loc . $location_or_link . "\n\n" .
                             "💬 කරුණාකර නියමිත වේලාවට විභාගය සඳහා සූදානම් වන්න.";
            
            $notice_type = "exam_notice";
            $current_date = date('Y-m-d');

            $notice_sql = "INSERT INTO notices (class_id, notice_date, title, content, notice_type) VALUES (?, ?, ?, ?, ?)";
            $notice_stmt = $conn->prepare($notice_sql);

            if ($notice_stmt) {
                $notice_stmt->bind_param("issss", $class_id, $current_date, $notice_title, $notice_content, $notice_type);
                $notice_stmt->execute();
                $notice_stmt->close();
                
                $status_message .= " සහ සිසුන් සඳහා නිවේදනයක් (Notice) නිකුත් කරන ලදී.";
            }
        } else {
            $status_message = "දත්ත ඇතුළත් කිරීමේදී දෝෂයක් සිදුවිය: " . mysqli_error($conn);
            $status_type = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}

// 3. Dropdown එකට සහ JavaScript Array එක සෑදීමට Data ලබා ගැනීම
$classes_query = "SELECT c.id, c.subject, t.name AS teacher_name, 
                         GROUP_CONCAT(cg.grade SEPARATOR ', ') AS grades
                  FROM classes c 
                  LEFT JOIN teachers t ON c.teacher_id = t.id 
                  LEFT JOIN class_grades cg ON c.id = cg.class_id
                  GROUP BY c.id, c.subject, t.name
                  ORDER BY c.subject ASC";
$classes_res = mysqli_query($conn, $classes_query);

// JavaScript එකට පන්ති සහ Grade ටික Pass කරන්න Array එකක් හදාගන්නවා
$class_mapping = [];
if($classes_res) {
    while($row = mysqli_fetch_assoc($classes_res)) {
        $class_mapping[$row['id']] = [
            'subject' => $row['subject'],
            'teacher' => $row['teacher_name'] ?? 'No Teacher',
            'grades' => !empty($row['grades']) ? explode(', ', $row['grades']) : []
        ];
    }
    // Loop එක මුලට reset කරනවා HTML dropdown එක පිරවීමට
    mysqli_data_seek($classes_res, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exam | SIGMA ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #09090b;
            --card-dark: rgba(22, 22, 26, 0.7);
            --card-border: rgba(255, 255, 255, 0.05);
            --accent-blue: #3b82f6;
            --text-gray: #94a3b8;
            --sidebar-w: 280px;
        }
        body { background-color: var(--bg-dark); color: #ffffff; font-family: 'Inter', sans-serif; min-height: 100vh; overflow-x: hidden; }
        
        /* Sidebar Wrapper & Responsive Fixes */
        .sidebar-container { width: var(--sidebar-w); position: fixed; top: 0; left: 0; height: 100vh; z-index: 1040; transition: transform 0.3s ease; }
        .main-content { margin-left: var(--sidebar-w); padding: 40px; min-height: 100vh; transition: margin 0.3s ease; }
        
        /* Mobile Top Navbar */
        .mobile-header { display: none; background: #16161a; border-bottom: 1px solid var(--card-border); padding: 15px 20px; position: fixed; top: 0; left: 0; right: 0; z-index: 1050; }
        
        .glass-card { background: var(--card-dark); backdrop-filter: blur(12px); border: 1px solid var(--card-border); border-radius: 24px; padding: 35px; }
        .form-label { font-weight: 500; color: #cbd5e1; font-size: 0.9rem; margin-bottom: 8px; text-transform: uppercase; }
        .form-control, .form-select { background-color: rgba(255, 255, 255, 0.03) !important; border: 1px solid rgba(255, 255, 255, 0.08) !important; color: #ffffff !important; border-radius: 12px; padding: 12px 16px; }
        .form-select option { background-color: #16161a; color: #fff; }
        .btn-submit { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border: none; color: white; padding: 14px 28px; border-radius: 12px; font-weight: 600; }
        .type-selector-box { border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 14px; padding: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
        .form-check-input:checked + .type-selector-box { border-color: var(--accent-blue); background: rgba(59, 130, 246, 0.08); }
        
        /* Responsive Media Queries */
        @media (max-width: 991.98px) {
            .sidebar-container { transform: translateX(-100%); }
            .sidebar-container.show { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 100px 20px 40px 20px; }
            .mobile-header { display: flex; align-items: center; justify-content: space-between; }
            .glass-card { padding: 20px; border-radius: 16px; }
            .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1030; }
            .sidebar-overlay.show { display: block; }
        }
    </style>
</head>
<body>

<!-- Mobile Header Toggle (කුඩා Screen වලදී පමණක් පෙනේ) -->
<div class="mobile-header">
    <span class="fw-bold text-white fs-5">SIGMA ERP</span>
    <button class="btn btn-outline-light btn-sm" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Wrapper -->
<div class="sidebar-container" id="sidebarContainer">
    <?php include 'sidebar.php'; ?>
</div>

<div class="main-content">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-5">
        <div>
            <h3 class="fw-bold mb-1">Exam Management</h3>
            <p class="text-secondary small mb-0">නිශ්චිත පන්තියක් සහ ශ්‍රේණියක් තෝරා විභාග ඇතුළත් කරන්න.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 text-white-50 align-self-sm-center">Back</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-11 w-100" style="max-width: 950px;">
            
            <?php if(!empty($status_message)): ?>
                <div class="alert alert-<?php echo $status_type; ?> rounded-4 p-3 mb-4">
                    <span class="small d-block text-break"><?php echo $status_message; ?></span>
                </div>
            <?php endif; ?>

            <div class="glass-card">
                <h5 class="fw-bold mb-4 text-white">Create Grade-Specific Exam</h5>
                <hr style="border-color: rgba(255,255,255,0.1); margin-bottom: 30px;">

                <form action="" method="POST">
                    <div class="row g-3 g-md-4">
                        <div class="col-12">
                            <label class="form-label">Exam Title / විභාගයේ නම</label>
                            <input type="text" name="exam_title" class="form-control" placeholder="e.g., Term Test - Unit 01" required>
                        </div>

                        <!-- 1. පන්තිය තෝරන Dropdown එක -->
                        <div class="col-12 col-md-6">
                            <label class="form-label">Select Class / විෂය පන්තිය</label>
                            <select name="class_id" id="class_select" class="form-select" onchange="updateGradesDropdown()" required>
                                <option value="" disabled selected>පන්තිය තෝරන්න...</option>
                                <?php if($classes_res): ?>
                                    <?php mysqli_data_seek($classes_res, 0); ?>
                                    <?php while($row = mysqli_fetch_assoc($classes_res)): ?>
                                        <option value="<?php echo $row['id']; ?>">
                                            <?php echo htmlspecialchars($row['subject'] . " - " . ($row['teacher_name'] ?? 'No Teacher')); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- 2. තෝරාගත් පන්තිය අනුව විතරක් Grades පෙන්වන Dropdown එක -->
                        <div class="col-12 col-md-6">
                            <label class="form-label">Select Grade / ශ්‍රේණිය</label>
                            <select name="exam_grade" id="grade_select" class="form-select" required disabled>
                                <option value="" disabled selected>පළමුව පන්තියක් තෝරන්න...</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Duration (Minutes) / කාලය (මිනිත්තු)</label>
                            <input type="number" name="duration_minutes" class="form-control" placeholder="120" min="1" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Exam Date & Time / දිනය සහ වේලාව</label>
                            <input type="datetime-local" name="exam_date" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Exam Type / විභාග ක්‍රමය</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="exam_type" id="type_physical" value="physical" checked onclick="toggleTypeFields()">
                                    <label class="type-selector-box w-100" for="type_physical">
                                        <i class="fas fa-users text-primary me-2 me-sm-3 fs-5"></i>
                                        <span class="fw-bold text-white small">Physical</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="exam_type" id="type_online" value="online" onclick="toggleTypeFields()">
                                    <label class="type-selector-box w-100" for="type_online">
                                        <i class="fas fa-globe text-info me-2 me-sm-3 fs-5"></i>
                                        <span class="fw-bold text-white small">Online</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12" id="physical_fields">
                            <label class="form-label">Hall Number / විභාග ශාලාව</label>
                            <input type="text" name="hall_number" id="hall_input" class="form-control" placeholder="Main Hall">
                        </div>

                        <div class="col-12 d-none" id="online_fields">
                            <label class="form-label">Exam Link or Portal URL</label>
                            <input type="url" name="online_link" id="link_input" class="form-control" placeholder="https://...">
                        </div>

                        <div class="col-12 text-end mt-4">
                            <button type="submit" name="create_exam" class="btn btn-submit w-100 w-sm-auto">Schedule Exam</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // PHP වල තියෙන Class සහ Grade mapping එක JavaScript Object එකකට ගන්නවා
    const classGradeMap = <?php echo json_encode($class_mapping); ?>;

    // පන්තිය වෙනස් කරන කොට Grade Dropdown එක Update කරන Function එක
    function updateGradesDropdown() {
        const classSelect = document.getElementById('class_select');
        const gradeSelect = document.getElementById('grade_select');
        const selectedClassId = classSelect.value;

        // මුලින්ම පරණ Option අයින් කරනවා
        gradeSelect.innerHTML = '<option value="" disabled selected>ශ්‍රේණිය තෝරන්න...</option>';

        if (selectedClassId && classGradeMap[selectedClassId]) {
            const classData = classGradeMap[selectedClassId];
            
            if(classData.grades.length > 0) {
                // පන්තියට අදාළ හැම Grade එකක්ම Option එකක් විදිහට එකතු කරනවා
                classData.grades.forEach(function(grade) {
                    if(grade.trim() !== "") {
                        const option = document.createElement('option');
                        option.value = grade;
                        option.textContent = grade;
                        gradeSelect.appendChild(option);
                    }
                });
                gradeSelect.disabled = false; // Dropdown එක Enable කරනවා
            } else {
                gradeSelect.innerHTML = '<option value="" disabled>මෙම පන්තියට ශ්‍රේණි ඇතුළත් කර නැත.</option>';
                gradeSelect.disabled = true;
            }
        }
    }

    function toggleTypeFields() {
        const physicalRadio = document.getElementById('type_physical');
        const physicalFields = document.getElementById('physical_fields');
        const onlineFields = document.getElementById('online_fields');
        if (physicalRadio.checked) {
            physicalFields.classList.remove('d-none');
            onlineFields.classList.add('d-none');
        } else {
            onlineFields.classList.remove('d-none');
            physicalFields.classList.add('d-none');
        }
    }

    // Mobile Sidebar Toggle Logic
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarContainer = document.getElementById('sidebarContainer');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if(sidebarToggle && sidebarContainer && sidebarOverlay) {
        sidebarToggle.addEventListener('click', function() {
            sidebarContainer.classList.add('show');
            sidebarOverlay.classList.add('show');
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebarContainer.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }
</script>
</body>
</html>