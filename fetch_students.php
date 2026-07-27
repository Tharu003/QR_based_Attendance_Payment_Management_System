<?php
/** @var mysqli $conn */
session_start();
include "db.php";

// ======================================================
// LOGIN CHECK
// ======================================================
if(!isset($_SESSION['role'])){
    echo "
    <div class='alert alert-danger m-4'>
        Session expired. Please login again.
    </div>
    ";
    exit;
}

$user_role = $_SESSION['role']; 

// ======================================================
// GET DATA
// ======================================================
$class_id = intval($_GET['class_id'] ?? 0);
$grade = trim(mysqli_real_escape_string($conn, $_GET['grade'] ?? ''));

date_default_timezone_set("Asia/Colombo");
$today = date('Y-m-d');
$selected_date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : $today;
$is_past_date = (strtotime($selected_date) < strtotime($today));

$current_month_name = date('F', strtotime($selected_date));     
$current_month_full = date('F Y', strtotime($selected_date));   

// ======================================================
// ACCESS CONTROL (SUPERADMIN ONLY FOR PAST ATTENDANCE)
// ======================================================
$can_mark_attendance = false;
if ($user_role === 'superadmin') {
    $can_mark_attendance = true;
} else {
    if (!$is_past_date && in_array($user_role, ['admin', 'teacher', 'assistant'])) {
        $can_mark_attendance = true;
    }
}

// ======================================================
// CLASS CHECK
// ======================================================
$class_check = mysqli_query($conn,"
    SELECT c.subject, cg.grade
    FROM classes c
    INNER JOIN class_grades cg ON c.id = cg.class_id
    WHERE c.id = '$class_id' AND TRIM(cg.grade) = TRIM('$grade')
    LIMIT 1
");

if(mysqli_num_rows($class_check) == 0){
    echo '
    <div class="empty-box p-5 text-center">
        <i class="fas fa-ban fa-3x text-danger mb-3"></i>
        <h5 class="text-light fw-bold">Invalid Class / Grade</h5>
        <p class="text-secondary">Selected subject does not belong to this grade.</p>
    </div>
    ';
    exit;
}

$class_data = mysqli_fetch_assoc($class_check);
$subject_name = $class_data['subject'];

// ======================================================
// MAIN QUERY (FIXED: Filter students strictly by registered grade)
// ======================================================
$sql = "
SELECT 
    s.student_id, 
    s.student_name, 
    s.photo, 
    s.registered_grade,
    (
        SELECT a.id 
        FROM attendance a 
        WHERE a.student_id = s.student_id 
        AND a.class_id = '$class_id' 
        AND a.date = '$selected_date' 
        LIMIT 1
    ) AS att_id,
    (
        SELECT p.id 
        FROM payments p 
        WHERE p.student_id = s.student_id 
        AND p.class_id = '$class_id' 
        AND (LOWER(p.month) = LOWER('$current_month_name') OR LOWER(p.month) = LOWER('$current_month_full'))
        LIMIT 1
    ) AS pay_id
FROM students s
WHERE TRIM(s.registered_grade) = TRIM('$grade')
GROUP BY s.student_id
ORDER BY s.student_name ASC
";

$res = mysqli_query($conn, $sql);
?>

<style>
.dark-card{ background:#16161a; border:1px solid #222; border-radius:20px; overflow:hidden; }
.dark-header{ background:#111115; border-bottom:1px solid #222; }
.dark-header h6{ color:#f1f5f9; }
.dark-header small{ color:#94a3b8; }
#attendanceTable{ margin-bottom:0; background:#16161a; }
#attendanceTable thead{ background:#111115; }
#attendanceTable thead th{ color:#94a3b8; border-bottom:1px solid #222; padding:16px; font-size:12px; letter-spacing:1px; text-transform:uppercase; background:#111115; }
#attendanceTable tbody td{ background:#16161a; color:#e2e8f0; border-bottom:1px solid #222; padding:16px; vertical-align:middle; }
#attendanceTable tbody tr:hover td{ background:#1b1c20; }
.student-name{ color:#f8fafc; font-size:14px; font-weight:700; }
.student-id{ color:#94a3b8; font-size:11px; }
.btn-dark-custom{ background:#1b1c20; color:#cbd5e1; border:1px solid #333; }
.btn-dark-custom:hover{ background:#22252b; color:#fff; }
.empty-box{ background:#16161a; border:1px solid #222; border-radius:20px; }
.badge-present{ background:rgba(25,135,84,.2); color:#22c55e; border:1px solid rgba(25,135,84,.4); }
.badge-absent{ background:rgba(220,53,69,.2); color:#ef4444; border:1px solid rgba(220,53,69,.4); }
.student-avatar{ width:45px; height:45px; border-radius:50%; object-fit:cover; border:2px solid #333; }
</style>

<?php if(mysqli_num_rows($res) > 0){ ?>
<div class="dark-card animate__animated animate__fadeIn">
    <div class="dark-header p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h6 class="fw-bold mb-1"><i class="fas fa-history me-2 text-info"></i>Attendance Report</h6>
            <small><?php echo $grade; ?> | <?php echo $subject_name; ?> | <?php echo $selected_date; ?> | <?php echo mysqli_num_rows($res); ?> Students</small>
        </div>
        <div class="btn-group">
            <button onclick="generatePDF('full')" class="btn btn-dark-custom btn-sm"><i class="fas fa-file-pdf text-danger me-1"></i>Full List</button>
            <button onclick="generatePDF('present')" class="btn btn-success btn-sm">Present Only</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-dark align-middle" id="attendanceTable">
            <thead>
                <tr>
                    <th class="ps-4">Student</th>
                    <th class="text-center">Payment<br><small style="font-size: 10px; color: #94a3b8; text-transform: none;">(<?php echo date('F Y', strtotime($selected_date)); ?>)</small></th>
                    <th class="text-center">Attendance</th>
                    <?php if($can_mark_attendance){ ?><th class="text-end pe-4 action-cell">Action</th><?php } ?>
                </tr>
            </thead>
            <tbody>
            <?php
            while($row = mysqli_fetch_assoc($res)){
                $is_present = !empty($row['att_id']);
                $is_paid = !empty($row['pay_id']);

                $pay_badge = $is_paid 
                ? '<span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 rounded-pill px-3 py-2"><i class="fas fa-check me-1"></i>PAID</span>'
                : '<span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-2"><i class="fas fa-times me-1"></i>NOT PAID</span>';

                $attendance_badge = $is_present 
                ? '<span class="badge badge-present rounded-pill px-3 py-2 fw-bold"><i class="fas fa-check-circle me-1"></i>PRESENT</span>'
                : '<span class="badge badge-absent rounded-pill px-3 py-2 fw-bold"><i class="fas fa-times-circle me-1"></i>ABSENT</span>';
            ?>
            <tr>
                <td class="ps-4">
                    <div class="d-flex align-items-center gap-3">
                        <img src="<?php echo !empty($row['photo']) ? $row['photo'] : 'https://ui-avatars.com/api/?name='.urlencode($row['student_name']); ?>" class="student-avatar">
                        <div>
                            <div class="student-name"><?php echo $row['student_name']; ?></div>
                            <div class="student-id">SIG-<?php echo $row['student_id']; ?></div>
                        </div>
                    </div>
                </td>
                <td class="text-center"><?php echo $pay_badge; ?></td>
                <td class="text-center"><?php echo $attendance_badge; ?></td>
                <?php if($can_mark_attendance){ ?>
                <td class="text-end pe-4 action-cell">
                    <button id="btn-<?php echo $row['student_id']; ?>" onclick="toggleAttendance(<?php echo $row['student_id']; ?>, <?php echo $class_id; ?>)" class="btn btn-sm <?php echo $is_present ? 'btn-success' : 'btn-dark-custom'; ?> rounded-3 px-3 fw-bold">
                        <?php echo $is_present ? '<i class="fas fa-check"></i> Present' : '<i class="fas fa-plus"></i> Mark'; ?>
                    </button>
                </td>
                <?php } ?>
            </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<?php } else { ?>
<div class="empty-box p-5 text-center animate__animated animate__fadeIn">
    <i class="fas fa-user-slash fa-3x text-secondary mb-3"></i>
    <h5 class="text-light fw-bold">No Students Found</h5>
    <p class="text-secondary small mb-0">No students registered for this grade.</p>
</div>
<?php } ?>