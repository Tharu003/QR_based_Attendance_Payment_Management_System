<?php
/** @var mysqli $conn */
session_start();
include "db.php";

if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit();
}

$message = "";
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

$edit_mode = false;
$edit_id = "";
$u_grades = [];
$u_subject = "";
$u_teacher_id = "";
$u_fee = "1000";

/* =========================
   CONFIGURATION
========================= */

$grade_list = [
    "Grade 1","Grade 2","Grade 3","Grade 4","Grade 5",
    "Grade 6","Grade 7","Grade 8","Grade 9",
    "Grade 10","Grade 11"
];

$subjects = [
    "ගණිතය",
    "විද්‍යාව",
    "සිංහල",
    "ඉංග්‍රීසි",
    "තොරතුරු තාක්ෂණය",
    "නර්තනය",
    "වාණිජ්‍යය",
    "සංගීතය",
    "දෙමළ",
    "1-2 සිංහල",
    "3,4,5 Paper පන්තිය",
    "3,4,5 Theory පන්තිය"
];

$subject_keywords = [
    "ගණිතය" => "ganithaya maths mathematics math",
    "විද්‍යාව" => "widyawa vidyawa science sci",
    "සිංහල" => "sinhala sinhala",
    "ඉංග්‍රීසි" => "ingrisi english eng",
    "තොරතුරු තාක්ෂණය" => "thorathuru thakshanya ict it information technology",
    "නර්තනය" => "narthanaya dancing dance",
    "වාණිජ්‍යය" => "wanijaya commerce com",
    "සංගීතය" => "sangithaya music",
    "දෙමළ" => "demala tamil",
    "1-2 සිංහල" => "1-2 sinhala",
    "3,4,5 Paper පන්තිය" => "3 4 5 paper panthiya class",
    "3,4,5 Theory පන්තිය" => "3 4 5 theory panthiya class"
];

/* =========================
   TEACHERS
========================= */

$teachers_res = mysqli_query($conn, "SELECT id, name FROM teachers ORDER BY name ASC");
$teachers_list = mysqli_fetch_all($teachers_res, MYSQLI_ASSOC);

/* =========================
   SAVE / UPDATE
========================= */

if(isset($_POST['save_dataset'])){

    $selected_grades = $_POST['grades'] ?? [];
    $subject = trim($_POST['subject']);
    $teacher_id = intval($_POST['teacher_id']);
    $fee = intval($_POST['monthly_fee']);
    $update_id = $_POST['update_id'] ?? '';

    if(!empty($selected_grades) && !empty($subject) && !empty($teacher_id)){

        $duplicate_found = false;
        $duplicate_grade = "";

        // 1. ඩුප්ලිකේට් චෙක් කිරීම (Edit Mode එකේදී හැර)
        if(empty($update_id)) {
            foreach($selected_grades as $g){
                $check = $conn->prepare("
                    SELECT c.id
                    FROM classes c
                    JOIN class_grades cg ON c.id = cg.class_id
                    WHERE c.subject = ? AND cg.grade = ? AND c.teacher_id = ?
                ");
                $check->bind_param("ssi", $subject, $g, $teacher_id);
                $check->execute();
                $result = $check->get_result();

                if($result->num_rows > 0){
                    $duplicate_found = true;
                    $duplicate_grade = $g;
                    break;
                }
            }
        }

        if($duplicate_found){
            $t_name = "";
            foreach($teachers_list as $t) { if($t['id'] == $teacher_id) $t_name = $t['name']; }

            $_SESSION['flash_message'] = "
            <div class='alert alert-danger border-0'>
                <b>$duplicate_grade</b> සඳහා <b>$subject ($t_name)</b> class එක already added!
            </div>";
            header("Location: classes.php");
            exit();
        } else {

            mysqli_begin_transaction($conn);

            try {
                if(!empty($update_id)){
                    // EDIT MODE: දැනට තියෙන පන්තිය අප්ඩේට් කිරීම
                    $stmt = $conn->prepare("
                        UPDATE classes
                        SET subject=?, teacher_id=?, monthly_fee=?
                        WHERE id=?
                    ");
                    $stmt->bind_param("siii", $subject, $teacher_id, $fee, $update_id);
                    $stmt->execute();

                    mysqli_query($conn, "DELETE FROM class_grades WHERE class_id = $update_id");
                    
                    $stmt_grade = $conn->prepare("INSERT INTO class_grades (class_id, grade) VALUES (?, ?)");
                    foreach($selected_grades as $g){
                        $stmt_grade->bind_param("is", $update_id, $g);
                        $stmt_grade->execute();
                    }
                    $msg_text = "updated";

                } else {
                    // NEW INSERT MODE: හැම ශ්‍රේණියකටම වෙන වෙනම class_id එකක් සහිතව සෑදීම!
                    foreach($selected_grades as $g){
                        // විෂය නාමයට ශ්‍රේණිය එකතු කර වඩාත් පැහැදිලි නමක් සාදයි (උදා: "Grade 8 ගණිතය")
                        $specific_subject = $g . " " . $subject; 

                        $stmt = $conn->prepare("
                            INSERT INTO classes (subject, teacher_id, monthly_fee)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->bind_param("sii", $specific_subject, $teacher_id, $fee);
                        $stmt->execute();

                        $new_class_id = $conn->insert_id;

                        $stmt_grade = $conn->prepare("INSERT INTO class_grades (class_id, grade) VALUES (?, ?)");
                        $stmt_grade->bind_param("is", $new_class_id, $g);
                        $stmt_grade->execute();
                    }
                    $msg_text = "added";
                }

                mysqli_commit($conn);

                $_SESSION['flash_message'] = "
                <div class='alert alert-success border-0 bg-success bg-opacity-10 text-success'>
                    Class datasets <b>$msg_text</b> successfully as separate classes!
                </div>";

                header("refresh:1;url=classes.php");

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $_SESSION['flash_message'] = "
                <div class='alert alert-danger'>
                    Error : ".$e->getMessage()."
                </div>";
                header("Location: classes.php" . (!empty($update_id) ? "?edit=$update_id" : ""));
                exit();
            }
        }

    } else {
        $_SESSION['flash_message'] = "
        <div class='alert alert-warning'>
            Please fill all fields.
        </div>";
        header("Location: classes.php" . (!empty($update_id) ? "?edit=$update_id" : ""));
        exit();
    }
}

/* =========================
   EDIT FETCH
========================= */

if(isset($_GET['edit'])){
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);

    $res = mysqli_query($conn, "
        SELECT c.*, GROUP_CONCAT(cg.grade SEPARATOR ', ') as grades
        FROM classes c
        LEFT JOIN class_grades cg ON c.id = cg.class_id
        WHERE c.id = $edit_id
        GROUP BY c.id
    ");

    if($row = mysqli_fetch_assoc($res)){
        $u_grades = explode(", ", $row['grades']);
        
        // Edit කිරීමේදී "Grade X " කොටස ඉවත් කර මුල් විෂය නම පෙන්වීමට
        $u_subject = $row['subject'];
        foreach($grade_list as $gl) {
            if(strpos($u_subject, $gl . " ") === 0) {
                $u_subject = str_replace($gl . " ", "", $u_subject);
                break;
            }
        }
        
        $u_teacher_id = $row['teacher_id'];
        $u_fee = $row['monthly_fee'];
    }
}

/* =========================
   DELETE
========================= */

if(isset($_GET['delete'])){
    $del_id = intval($_GET['delete']);

    mysqli_query($conn, "DELETE FROM class_grades WHERE class_id = '$del_id'");
    mysqli_query($conn, "DELETE FROM classes WHERE id = '$del_id'");

    header("Location: classes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes | SIGMA ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <style>
         :root {
            --bg-dark: #0a0a0c;
            --card-dark: #16161a;
            --sidebar-black: #000000;
            --accent-blue: #3b82f6;
            --electric-blue: #00d2ff;
            --text-gray: #94a3b8;
            --sidebar-w: 280px;
        }

        body { 
            background-color: var(--bg-dark); 
            color: #ffffff;
            font-family: 'Inter', sans-serif; 
            overflow-x: hidden;
        }

        .main-content{ margin-left: var(--sidebar-w); padding: 40px; transition: all 0.3s ease; }
        .card{ background: var(--card-dark); border: 1px solid #222; border-radius: 20px; }
        .form-control, .form-select{ background: #111115; border: 1px solid #333; color: white; padding: 12px; border-radius: 12px; }
        .form-control:focus, .form-select:focus{ background: #111115; color: white; border-color: var(--accent-blue); box-shadow: 0 0 8px rgba(59, 130, 246, 0.2); }
        
        .grade-pill-container{ display: flex; flex-wrap: wrap; gap: 8px; }
        .grade-check{ display: none; }
        .grade-label{ padding: 8px 14px; border-radius: 10px; border: 1px solid #27272a; cursor: pointer; background: #111115; color: #a1a1aa; transition: 0.2s; font-size: 0.85rem; font-weight: 500; }
        .grade-check:checked + .grade-label{ background: var(--accent-blue); color: white; border-color: var(--accent-blue); box-shadow: 0 0 10px rgba(59, 130, 246, 0.3); }

        .btn-primary{ background: linear-gradient(135deg, var(--accent-blue), #2563eb); border: none; padding: 12px; border-radius: 12px; font-weight: 600; }
        .table thead th{ background: #111115 !important; color: white !important; border: none; padding: 16px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; }
        .table tbody td{ background: #16161a !important; color: #ddd !important; border-bottom: 1px solid #222; padding: 16px; }
        .class-card { background: #111115; padding: 15px; border-radius: 15px; border: 1px solid #2a2a2a; }

        @media (max-width: 992px) { .main-content { margin-left: 0; padding: 20px; } }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 header-title-area">
        <div>
            <h2 class="fw-bold mb-1">Class Management</h2>
            <small class="text-secondary">සියලුම පන්ති දත්ත මෙතනින් කළමනාකරණය කරන්න (ස්වාධීන පන්ති ලෙස සැකසේ)</small>
        </div>
    </div>

    <div class="card p-4 mb-4">
        <h4 class="mb-4 text-white fw-bold">
            <i class="fas <?php echo $edit_mode ? 'fa-edit text-warning' : 'fa-plus-circle text-primary'; ?> me-2"></i>
            <?php echo $edit_mode ? 'Update Class Dataset' : 'Add New Class Dataset'; ?>
        </h4>
        <?php echo $message; ?>

        <form method="POST">
            <input type="hidden" name="update_id" id="update_id" value="<?php echo $edit_id; ?>">

            <div class="mb-4">
                <label class="mb-2 fw-bold text-white small text-secondary">Select Grades (ශ්‍රේණි තෝරන්න)</label>
                <div class="grade-pill-container">
                    <?php foreach($grade_list as $g): ?>
                        <input type="checkbox" name="grades[]" value="<?php echo $g; ?>" id="<?php echo $g; ?>" class="grade-check" <?php echo in_array($g, $u_grades) ? 'checked' : ''; ?>>
                        <label for="<?php echo $g; ?>" class="grade-label"><?php echo $g; ?></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-4 col-md-6 col-12">
                    <label class="mb-2 text-white fw-semibold small text-secondary">Subject (විෂය)</label>
                    <select name="subject" id="subject_select" class="form-select" required>
                        <option value="">Select Subject</option>
                        <?php foreach($subjects as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo ($u_subject == $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-4 col-md-6 col-12">
                   <label class="mb-2 text-white fw-semibold small text-secondary">Teacher (ගුරුවරයා)</label>
                    <select name="teacher_id" class="form-select" required>
                        <option value="">Select Teacher</option>
                        <?php foreach($teachers_list as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo ($u_teacher_id == $t['id']) ? 'selected' : ''; ?>><?php echo $t['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6 col-12">
                   <label class="mb-2 text-white fw-semibold small text-secondary">Monthly Fee (ගාස්තුව)</label>
                    <input type="number" name="monthly_fee" class="form-control" value="<?php echo $u_fee; ?>" required>
                </div>

                <div class="col-lg-2 col-md-6 col-12 d-flex align-items-end">
                    <button type="submit" name="save_dataset" class="btn btn-primary w-100">
                        <?php echo $edit_mode ? 'Update Class' : 'Create Class'; ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="card overflow-hidden">
        <div class="p-3 bg-dark bg-opacity-25 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center class-list-header">
            <h5 class="mb-0 text-secondary fw-semibold"><i class="fas fa-list me-2 text-info"></i>Class List</h5>
            <div class="position-relative" style="max-width: 300px; width: 100%;">
                <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-secondary">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="table_search" class="form-control ps-5" placeholder="Type to search...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="class_table">
                <thead>
                    <tr>
                        <th class="ps-4" style="min-width: 110px;">Grade</th>
                        <th style="min-width: 250px;">Class Details</th>
                        <th class="text-center" style="min-width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "
                    SELECT c.id, c.subject, c.monthly_fee, t.name as teacher_name, cg.grade 
                    FROM class_grades cg 
                    JOIN classes c ON cg.class_id = c.id 
                    LEFT JOIN teachers t ON c.teacher_id = t.id 
                    ORDER BY cg.grade ASC, c.subject ASC
                ";
                $res = mysqli_query($conn, $sql);
                while($row = mysqli_fetch_assoc($res)){
                    // සෙවීම් කටයුතු පහසු කිරීමට Keywords සැකසීම
                    $clean_sub = str_replace($row['grade']." ", "", $row['subject']);
                    $keywords = isset($subject_keywords[$clean_sub]) ? $subject_keywords[$clean_sub] : "";
                ?>
                    <tr data-keywords="<?php echo htmlspecialchars($keywords . " " . strtolower($row['subject'])); ?>">
                        <td class="ps-4"><b><?php echo $row['grade']; ?></b></td>
                        <td>
                            <div class="class-card">
                                <div class="fw-bold text-info mb-2 class-subject"><?php echo $row['subject']; ?></div>
                                <div class="small text-secondary mb-1 class-teacher">
                                    <i class="fas fa-user-tie me-1"></i> <?php echo $row['teacher_name']; ?>
                                </div>
                                <div class="small text-success">
                                    Rs. <?php echo number_format($row['monthly_fee']); ?>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="classes.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning px-3" style="border-radius: 8px;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="classes.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger px-3" style="border-radius: 8px;" onclick="return confirm('Delete this class?')">
                                    <i class="fas fa-trash"></i>
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById('table_search');
    const tableRows = document.querySelectorAll('#class_table tbody tr');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();

        tableRows.forEach(row => {
            const gradeText = row.querySelector('td:first-child').textContent.toLowerCase();
            const subjectText = row.querySelector('.class-subject').textContent.toLowerCase();
            const teacherText = row.querySelector('.class-teacher').textContent.toLowerCase();
            const englishKeywords = row.getAttribute('data-keywords').toLowerCase();

            if (
                gradeText.includes(query) || 
                subjectText.includes(query) || 
                teacherText.includes(query) || 
                englishKeywords.includes(query)
            ) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>