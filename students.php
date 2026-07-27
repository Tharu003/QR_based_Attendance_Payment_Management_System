<?php 
/** @var mysqli $conn */
session_start();
date_default_timezone_set("Asia/Colombo");
include "db.php"; 

$allowed_roles = ['admin', 'teacher', 'assistant'];
if(!isset($_SESSION['role']) || !in_array(strtolower(trim($_SESSION['role'])), $allowed_roles)){
    header("Location: login.php");
    exit();
}

$message = "";
$edit_mode = false;
$s_id = $s_name = $s_phone = $s_address = $s_grade = $s_photo = "";
$s_class_ids = []; // ශිෂ්‍යයාට අදාළ පන්ති ID ලැයිස්තුව

// 1. DROP SINGLE SUBJECT
if(isset($_GET['drop_subject']) && isset($_GET['student_id'])){
    $class_id = intval($_GET['drop_subject']);
    $student_id = intval($_GET['student_id']);
    mysqli_query($conn, "DELETE FROM student_classes WHERE student_id = $student_id AND class_id = $class_id");
    $message = "<div class='alert alert-warning border-0 bg-warning bg-opacity-10 text-warning animate__animated animate__fadeInDown'>ශිෂ්‍යයා අදාළ විෂයෙන් ඉවත් කරන ලදී.</div>";
    header("refresh:1;url=students.php");
}

// 2. DELETE COMPLETE STUDENT
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']); 
    mysqli_query($conn, "DELETE FROM student_classes WHERE student_id = $id");
    mysqli_query($conn, "DELETE FROM students WHERE student_id = $id");
    header("Location: students.php");
    exit();
}

// 3. FETCH DATA FOR EDIT
if(isset($_GET['edit'])){
    $edit_mode = true;
    $id = intval($_GET['edit']);
    $sql = "SELECT * FROM students WHERE student_id = $id LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if($row = mysqli_fetch_assoc($res)){
        $s_id       = $row['student_id'];
        $s_name     = $row['student_name'];
        $s_phone    = $row['phone'];
        $s_address  = $row['address'];
        $s_grade    = $row['registered_grade'];
        $s_photo    = $row['photo']; 
        
        $c_res = mysqli_query($conn, "SELECT class_id FROM student_classes WHERE student_id = $id");
        while($c_row = mysqli_fetch_assoc($c_res)){
            $s_class_ids[] = $c_row['class_id'];
        }
    }
}

// 4. ADD / UPDATE LOGIC
if(isset($_POST['save_student'])){
    $student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    $name       = mysqli_real_escape_string($conn, trim($_POST['name']));
    $phone      = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address    = mysqli_real_escape_string($conn, $_POST['address']);
    $grade      = $_POST['selected_grade']; 
    $chosen_classes = isset($_POST['class_ids']) ? $_POST['class_ids'] : [];

    if(!empty($name) && !empty($grade) && !empty($chosen_classes)){

        $photo_path = "";
        if($student_id > 0) {
            $res_p = mysqli_query($conn, "SELECT photo FROM students WHERE student_id=$student_id");
            $row_p = mysqli_fetch_assoc($res_p);
            $photo_path = $row_p['photo'] ?? "";
        }

        if(isset($_FILES['photo']) && $_FILES['photo']['name'] != ""){
            $folder = "uploads/";
            if(!is_dir($folder)) mkdir($folder, 0777, true);
            $file_name = time() . "_" . basename($_FILES['photo']['name']);
            $target = $folder . $file_name;
            if(move_uploaded_file($_FILES['photo']['tmp_name'], $target)){
                $photo_path = $target;
            }
        }

        if($student_id > 0){
            // UPDATE
            $check_dup_user = $conn->prepare("SELECT student_id FROM students WHERE student_name = ? AND phone = ? AND student_id != ?");
            $check_dup_user->bind_param("ssi", $name, $phone, $student_id);
            $check_dup_user->execute();
            $check_dup_user->store_result();

            if($check_dup_user->num_rows > 0) {
                $message = "<div class='alert alert-danger border-0 bg-danger bg-opacity-10 text-danger animate__animated animate__shakeX'>Error! මෙම නම සහ දුරකථන අංකය සහිත වෙනත් ශිෂ්‍යයෙකු දැනටමත් සිටී.</div>";
            } else {
                mysqli_query($conn, "UPDATE students SET student_name='$name', registered_grade='$grade', phone='$phone', address='$address', photo='$photo_path' WHERE student_id=$student_id");
                
                mysqli_query($conn, "DELETE FROM student_classes WHERE student_id=$student_id");
                foreach($chosen_classes as $cid){
                    $cid = intval($cid);
                    mysqli_query($conn, "INSERT INTO student_classes (student_id, class_id) VALUES ($student_id, $cid)");
                }
                
                $message = "<div class='alert alert-success border-0 bg-success bg-opacity-10 text-success animate__animated animate__fadeInDown'>Success! Student updated successfully.</div>";
                header("refresh:1;url=students.php");
            }
            $check_dup_user->close();

        } else {
            // INSERT
            $check_dup_user = $conn->prepare("SELECT student_id FROM students WHERE student_name = ? AND phone = ?");
            $check_dup_user->bind_param("ss", $name, $phone);
            $check_dup_user->execute();
            $check_dup_user->store_result();

            if($check_dup_user->num_rows > 0) {
                $check_dup_user->bind_result($existing_student_id);
                $check_dup_user->fetch();

                foreach($chosen_classes as $cid){
                    $cid = intval($cid);
                    $chk = mysqli_query($conn, "SELECT id FROM student_classes WHERE student_id=$existing_student_id AND class_id=$cid");
                    if(mysqli_num_rows($chk) == 0){
                        mysqli_query($conn, "INSERT INTO student_classes (student_id, class_id) VALUES ($existing_student_id, $cid)");
                    }
                }
                $message = "<div class='alert alert-success border-0 bg-success bg-opacity-10 text-success animate__animated animate__fadeInDown'>Success! Added to the new subject classes.</div>";
                header("refresh:1;url=students.php");

            } else {
                mysqli_query($conn, "INSERT INTO students (student_name, registered_grade, phone, address, photo) VALUES ('$name', '$grade', '$phone', '$address', '$photo_path')");
                $new_student_id = $conn->insert_id;
                
                $qr_token = "STU_" . str_pad($new_student_id, 5, "0", STR_PAD_LEFT); 
                mysqli_query($conn, "UPDATE students SET qr_token='$qr_token' WHERE student_id='$new_student_id'");
                
                foreach($chosen_classes as $cid){
                    $cid = intval($cid);
                    mysqli_query($conn, "INSERT INTO student_classes (student_id, class_id) VALUES ($new_student_id, $cid)");
                }

                $message = "<div class='alert alert-success border-0 bg-success bg-opacity-10 text-success animate__animated animate__fadeInDown'>Success! Student registered successfully.</div>";
                header("refresh:1;url=students.php");
            }
            $check_dup_user->close();
        }
    } else {
        if(isset($_POST['save_student'])){
            $message = "<div class='alert alert-danger border-0 bg-danger bg-opacity-10 text-danger'>Error! කරුණාකර නම, ශ්‍රේණිය සහ අවම වශයෙන් එක් විෂයයක්වත් තෝරන්න.</div>";
        }
    }
}

// DROPDOWN DATA FETCHING
$all_data = [];
$sql_classes = "SELECT c.id, c.subject, cg.grade, t.name as teacher_name 
                FROM classes c 
                LEFT JOIN class_grades cg ON c.id = cg.class_id
                LEFT JOIN teachers t ON c.teacher_id = t.id";
$resClasses = mysqli_query($conn, $sql_classes);
while($row = mysqli_fetch_assoc($resClasses)){
    $grades_array = explode(", ", $row['grade']);
    foreach($grades_array as $single_grade){
        if(!empty($single_grade)){
            $all_data[] = [ 
                'id' => $row['id'], 
                'grade' => trim($single_grade), 
                'subject' => $row['subject'],
                'teacher' => $row['teacher_name'] ?? 'No Teacher'
            ];
        }
    }
}
$unique_grades = array_unique(array_column($all_data, 'grade'));
sort($unique_grades);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | SIGMA ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
        :root {
            --bg-dark: #09090b;
            --card-dark: #141417;
            --accent-blue: #3b82f6;
            --border-color: #27272a;
            --sidebar-w: 280px;
        }
        body { background-color: var(--bg-dark); color: #f4f4f5; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .main-content { margin-left: var(--sidebar-w); padding: 40px; transition: all 0.3s ease; }
        .card { background: var(--card-dark); border: 1px solid var(--border-color); border-radius: 20px; color: white; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2); }
        .form-control, .form-select { background-color: #09090b; border: 1px solid var(--border-color); color: #f4f4f5; border-radius: 12px; padding: 12px; }
        .form-control:focus, .form-select:focus { background-color: #09090b; border-color: var(--accent-blue); color: white; box-shadow: 0 0 10px rgba(59, 130, 246, 0.15); }
        .table { background: var(--card-dark) !important; border-radius: 14px; overflow: hidden; border: none; }
        .table, .table tbody, .table tr, .table td, .table th { color: #e4e4e7 !important; }
        .table thead th { background: #18181b !important; color: #ffffff !important; border-bottom: 1px solid var(--border-color) !important; text-transform: uppercase; font-size: 0.72rem; padding: 16px; font-weight: 600; white-space: nowrap; }
        .table tbody td { border-bottom: 1px solid var(--border-color) !important; padding: 16px; vertical-align: middle; background: var(--card-dark) !important; }
        .btn-primary { background: linear-gradient(135deg, var(--accent-blue), #2563eb); border: none; border-radius: 12px; padding: 12px; font-weight: 600; }
        .action-btn { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; text-decoration: none; }
        .badge-grade { background: rgba(59, 130, 246, 0.08); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.15); padding: 5px 12px; border-radius: 8px; font-weight: 600; font-size: 0.8rem; }
        .badge-subject { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); padding: 6px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; margin: 4px; white-space: nowrap; }
        .drop-sub-btn { color: #ef4444; text-decoration: none; font-weight: bold; font-size: 14px; padding: 0 4px; border-radius: 3px; transition: 0.2s; }
        .drop-sub-btn:hover { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        .student-photo { width: 52px; height: 52px; object-fit: cover; border-radius: 12px; border: 2px solid #27272a; }
        .class-checkbox-container { max-height: 180px; overflow-y: auto; background: #09090b; border: 1px solid var(--border-color); padding: 12px; border-radius: 12px; }
        .search-wrapper { position: relative; }
        .search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #a1a1aa; }
        .search-wrapper .form-control { padding-left: 45px; }

        @media (max-width: 992px) { .main-content { margin-left: 0; padding: 20px; } }
        @media (max-width: 768px) { .main-content { padding: 15px; margin-top: 20px; } .table tbody td { padding: 12px 8px; font-size: 0.85rem; } }
        @media (max-width: 576px) { .header-title-area { flex-direction: column; align-items: start !important; gap: 15px; } .header-title-area a { width: 100%; text-align: center; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 mb-md-5 header-title-area">
        <div>
            <h3 class="fw-bold mb-1 fs-4 fs-md-2">Student Management</h3>
            <p class="text-secondary small mb-0">ශිෂ්‍ය දත්ත පද්ධතිය කළමනාකරණය කරන්න.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4 text-light btn-sm btn-md-normal">Back to Home</a>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5 col-12">
            <div class="card p-4 animate__animated animate__fadeInLeft">
                <h5 class="fw-bold mb-4">
                    <i class="fas <?php echo $edit_mode ? 'fa-user-edit' : 'fa-user-plus'; ?> me-2 text-primary"></i>
                    <?php echo $edit_mode ? "සංස්කරණය" : "නව ලියාපදිංචිය"; ?>
                </h5>
                
                <?php echo $message; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="student_id" value="<?php echo $s_id; ?>">
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-secondary mb-2">සම්පූර්ණ නම</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $s_name; ?>" required placeholder="Student Name">
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-secondary mb-2">ශ්‍රේණිය</label>
                        <select name="selected_grade" id="grade_dropdown" class="form-select" onchange="loadSubjects()" required>
                            <option value="">Select Grade</option>
                            <?php foreach($unique_grades as $g): ?>
                                <option value="<?php echo $g; ?>" <?php if($s_grade == $g) echo 'selected'; ?>><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-secondary mb-2">පන්ති සහ විෂයයන් (එකකට වඩා තෝරාගත හැක)</label>
                        <div class="class-checkbox-container" id="checkbox_wrapper">
                            <p class="text-muted small mb-0 p-1"><i class="fas fa-arrow-up me-2"></i>කරුණාකර පළමුව ශ්‍රේණිය තෝරන්න...</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-secondary mb-2">ඡායාරූපය</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-secondary mb-2">දුරකථනය</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo $s_phone; ?>" required placeholder="07x xxxxxxx">
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-secondary mb-2">ලිපිනය</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Address..."><?php echo $s_address; ?></textarea>
                    </div>

                    <button type="submit" name="save_student" class="btn btn-primary w-100 py-3 mb-2">
                        <?php echo $edit_mode ? "Update Student" : "Register / Add Subjects"; ?>
                    </button>
                    
                    <?php if($edit_mode): ?>
                        <a href="students.php" class="btn btn-dark w-100 border-secondary">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7 col-12">
            <div class="card p-0 animate__animated animate__fadeInRight" style="overflow: hidden;">
                
                <!-- 🔍 FILTER SYSTEM & DISPLAY LIMIT -->
                <div class="p-3 border-bottom" style="border-color: var(--border-color) !important; background: #18181b;">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <div class="search-wrapper">
                                <i class="fas fa-search"></i>
                                <input type="text" id="student_search" class="form-control" placeholder="Search Name or Phone...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select id="filter_grade" class="form-select">
                                <option value="">All Grades</option>
                                <?php foreach($unique_grades as $g): ?>
                                    <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="filter_subject" class="form-select">
                                <option value="">All Subjects</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                        <small class="text-secondary" id="display_info">Showing recent students</small>
                        <small class="text-primary" style="cursor:pointer;" id="reset_filters_btn" onclick="resetFilters()"><i class="fas fa-sync-alt me-1"></i> Reset Filters</small>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="student_table">
                        <thead>
                            <tr>
                                <th class="text-center" style="min-width: 140px;">Student</th>
                                <th style="min-width: 220px;">Registered Subjects</th>
                                <th class="text-center" style="min-width: 100px;">QR Code</th>
                                <th class="text-center" style="min-width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // සියලු දත්ත ගෙන එයි, නමුත් JS මගින් මුලින් 10ක් පමණක් Display කරනු ලබයි
                            $sql = "SELECT s.*, GROUP_CONCAT(CONCAT(c.id, ':', c.subject, ' (', IFNULL(t.name, 'No Teacher'), ')') SEPARATOR '|||') AS all_subjects 
                                    FROM students s 
                                    LEFT JOIN student_classes sc ON s.student_id = sc.student_id 
                                    LEFT JOIN classes c ON sc.class_id = c.id 
                                    LEFT JOIN teachers t ON c.teacher_id = t.id
                                    GROUP BY s.student_id
                                    ORDER BY s.student_id DESC";
                                    
                            $resList = mysqli_query($conn, $sql);
                            while($row = mysqli_fetch_assoc($resList)){
                                $qr_data = !empty($row['qr_token']) ? $row['qr_token'] : "N/A";
                                $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . $qr_data . "&bgcolor=ffffff";
                                ?>
                                <tr class="student-row">
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center">
                                            <?php if(!empty($row['photo']) && file_exists($row['photo'])) { ?>
                                                <img src="<?php echo $row['photo']; ?>" class="student-photo mb-2">
                                            <?php } else { ?>
                                                <div class="student-photo bg-dark d-flex align-items-center justify-content-center text-secondary mb-2">
                                                    <i class="fas fa-user fa-lg"></i>
                                                </div>
                                            <?php } ?>
                                            <div class="fw-bold small text-light student-name"><?php echo $row['student_name']; ?></div>
                                            <div class="text-secondary student-phone" style="font-size: 11px;"><?php echo $row['phone']; ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mb-2">
                                            <span class="badge-grade student-grade"><?php echo $row['registered_grade']; ?></span>
                                        </div>
                                        <div class="student-subjects d-flex flex-wrap">
                                            <?php 
                                            if(!empty($row['all_subjects'])){
                                                $subjects_arr = explode("|||", $row['all_subjects']);
                                                foreach($subjects_arr as $sub_data){
                                                    if(strpos($sub_data, ':') !== false) {
                                                        list($c_id, $sub_name) = explode(":", $sub_data, 2);
                                                        echo "<span class='badge-subject'>
                                                                <i class='fas fa-book me-1'></i>$sub_name 
                                                                <a href='students.php?drop_subject=$c_id&student_id={$row['student_id']}' 
                                                                   class='drop-sub-btn' 
                                                                   onclick=\"return confirm('මෙම ශිෂ්‍යයාව $sub_name පන්තියෙන් පමණක් ඉවත් කරන්නද?')\" 
                                                                   title='Drop Subject'>×</a>
                                                              </span>";
                                                    }
                                                }
                                            } else {
                                                echo "<span class='text-secondary small ps-1'>No classes assigned</span>";
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center">
                                            <img width="50" src="<?php echo $qr_url; ?>" class="rounded bg-white p-1 mb-2">
                                            <div class="text-secondary mb-1" style="font-size: 9px; font-family: monospace;"><?php echo $qr_data; ?></div>
                                            <a href="<?php echo $qr_url; ?>" target="_blank" class="btn btn-sm btn-dark border-secondary py-1 px-2" style="font-size: 10px;">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="students.php?edit=<?php echo $row['student_id']; ?>" class="action-btn bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" title="Edit">
                                                <i class="fas fa-pen fa-sm"></i>
                                            </a>
                                            <a href="students.php?delete=<?php echo $row['student_id']; ?>" class="action-btn bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" onclick="return confirm('මෙම ශිෂ්‍යයාව සම්පූර්ණයෙන්ම පද්ධතියෙන් මකා දමන්නද?')" title="Delete Complete Student">
                                                <i class="fas fa-trash fa-sm"></i>
                                            </a>
                                            <a href="id_card.php?id=<?php echo $row['student_id']; ?>" target="_blank" class="action-btn bg-info bg-opacity-10 text-info border border-info border-opacity-25" title="ID Card">
                                                <i class="fas fa-id-card fa-sm"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const classData = <?php echo json_encode($all_data); ?>;
const currentClassIDs = <?php echo json_encode($s_class_ids); ?>;
const DEFAULT_LIMIT = 10; // මුලින්ම පෙන්වන ශිෂ්‍යයන් ගණන (Default limit)

// Registration Form Subject Loader
function loadSubjects() {
    const selectedGrade = document.getElementById('grade_dropdown').value;
    const checkboxWrapper = document.getElementById('checkbox_wrapper');
    checkboxWrapper.innerHTML = '';

    if (selectedGrade === "") {
        checkboxWrapper.innerHTML = '<p class="text-muted small mb-0 p-1"><i class="fas fa-arrow-up me-2"></i>කරුණාකර පළමුව ශ්‍රේණිය තෝරන්න...</p>';
        return;
    }

    const filteredClasses = classData.filter(item => item.grade === selectedGrade);

    if (filteredClasses.length === 0) {
        checkboxWrapper.innerHTML = '<p class="text-danger small mb-0 p-1"><i class="fas fa-exclamation-triangle me-2"></i>මෙම ශ්‍රේණිය සඳහා පන්ති හමු නොවීය!</p>';
        return;
    }

    filteredClasses.forEach(item => {
        const isChecked = currentClassIDs.includes(item.id.toString()) || currentClassIDs.includes(parseInt(item.id)) ? 'checked' : '';
        
        const div = document.createElement('div');
        div.className = 'form-check mb-2';
        div.innerHTML = `
            <input class="form-check-input" type="checkbox" name="class_ids[]" value="${item.id}" id="class_chk_${item.id}" ${isChecked}>
            <label class="form-check-label text-light small" for="class_chk_${item.id}">
                <strong>${item.subject}</strong> - ${item.teacher}
            </label>
        `;
        checkboxWrapper.appendChild(div);
    });
}

window.onload = function() { 
    if(document.getElementById('grade_dropdown').value !== "") loadSubjects(); 
    applyTableFilters(); // Initial load with limit
};

// ⚡ REAL-TIME FILTER SYSTEM WITH SMART LIMIT LOGIC
function applyTableFilters() {
    const searchVal = document.getElementById('student_search').value.toLowerCase().trim();
    const selectedGrade = document.getElementById('filter_grade').value.toLowerCase().trim();
    const selectedSubject = document.getElementById('filter_subject').value.toLowerCase().trim();
    
    const rows = document.querySelectorAll('#student_table .student-row');
    const isFilteringActive = (searchVal !== "" || selectedGrade !== "" || selectedSubject !== "");

    let countShown = 0;
    let matchCount = 0;

    rows.forEach(row => {
        const name = row.querySelector('.student-name').textContent.toLowerCase();
        const phone = row.querySelector('.student-phone').textContent.toLowerCase();
        const grade = row.querySelector('.student-grade').textContent.toLowerCase().trim();
        const subjects = row.querySelector('.student-subjects').textContent.toLowerCase();

        const matchSearch = name.includes(searchVal) || phone.includes(searchVal);
        const matchGrade = (selectedGrade === "") || (grade === selectedGrade);
        const matchSubject = (selectedSubject === "") || (subjects.includes(selectedSubject));

        if (matchSearch && matchGrade && matchSubject) {
            matchCount++;
            // Filter නොකරන විට (Default Mode) පළමු ශිෂ්‍යයන් 10 දෙනා පමණක් පෙන්වයි.
            // Filter/Search කරන විට ගැළපෙන සියලු ශිෂ්‍යයන් පෙන්වයි.
            if (!isFilteringActive && countShown >= DEFAULT_LIMIT) {
                row.style.display = 'none';
            } else {
                row.style.display = '';
                countShown++;
            }
        } else {
            row.style.display = 'none'; 
        }
    });

    // Info Label Update
    const infoLabel = document.getElementById('display_info');
    if(isFilteringActive) {
        infoLabel.innerHTML = `Found <strong>${matchCount}</strong> student(s) matching your filter.`;
    } else {
        infoLabel.innerHTML = `Showing latest <strong>${countShown}</strong> registered students. (Use filter/search to find others)`;
    }
}

function resetFilters() {
    document.getElementById('student_search').value = "";
    document.getElementById('filter_grade').value = "";
    document.getElementById('filter_subject').value = "";
    document.getElementById('filter_subject').innerHTML = '<option value="">All Subjects</option>';
    applyTableFilters();
}

// Populate Subjects Filter dropdown based on Grade selection
document.getElementById('filter_grade').addEventListener('change', function() {
    const selectedGrade = this.value;
    const subjectSelect = document.getElementById('filter_subject');
    
    subjectSelect.innerHTML = '<option value="">All Subjects</option>';
    
    if (selectedGrade !== "") {
        const availableSubjects = [...new Set(
            classData
                .filter(item => item.grade === selectedGrade)
                .map(item => item.subject)
        )];
        
        availableSubjects.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub;
            opt.textContent = sub;
            subjectSelect.appendChild(opt);
        });
    }
    
    applyTableFilters();
});

document.getElementById('student_search').addEventListener('keyup', applyTableFilters);
document.getElementById('filter_subject').addEventListener('change', applyTableFilters);

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>