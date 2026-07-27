<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}

include 'db.php'; 

$message = "";

// ---- 1. ADD TEACHER ----
if (isset($_POST['add_teacher'])) {
    $id_num = trim($_POST['id_num']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']); 
    
    $conn->begin_transaction();
    try {
        $check_id = "SELECT id FROM teachers WHERE id_num = ?";
        $chk_stmt = $conn->prepare($check_id);
        $chk_stmt->bind_param("s", $id_num);
        $chk_stmt->execute();
        $res = $chk_stmt->get_result();
        
        if($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $teacher_id = $row['id'];
            
            $check_class = "SELECT id FROM classes WHERE teacher_id = ? AND subject = ?";
            $c_stmt = $conn->prepare($check_class);
            $c_stmt->bind_param("is", $teacher_id, $subject);
            $c_stmt->execute();
            if($c_stmt->get_result()->num_rows > 0) {
                throw new Exception("This instructor is already assigned to this discipline.");
            }
        } else {
            $insert_teacher = "INSERT INTO teachers (id_num, name, email, phone) VALUES (?, ?, ?, ?)";
            $stmt1 = $conn->prepare($insert_teacher);
            $stmt1->bind_param("ssss", $id_num, $name, $email, $phone);
            $stmt1->execute();
            $teacher_id = $conn->insert_id;
        }

        $check_subj = "SELECT id FROM classes WHERE subject = ? AND (teacher_id IS NULL OR teacher_id = 0)";
        $s_stmt = $conn->prepare($check_subj);
        $s_stmt->bind_param("s", $subject);
        $s_stmt->execute();
        $s_res = $s_stmt->get_result();

        if($s_res->num_rows > 0) {
            $class_row = $s_res->fetch_assoc();
            $update_class = "UPDATE classes SET teacher_id = ? WHERE id = ?";
            $up_c_stmt = $conn->prepare($update_class);
            $up_c_stmt->bind_param("ii", $teacher_id, $class_row['id']);
            $up_c_stmt->execute();
        } else {
            $insert_class = "INSERT INTO classes (teacher_id, subject, monthly_fee) VALUES (?, ?, 1000.00)";
            $stmt2 = $conn->prepare($insert_class);
            $stmt2->bind_param("is", $teacher_id, $subject);
            $stmt2->execute();
        }

        $conn->commit();
        $message = "<script>window.addEventListener('DOMContentLoaded', function() { Swal.fire({icon: 'success', title: 'Profile Synchronized', text: 'Instructor matrix successfully updated.', background: '#09090f', color: '#fff', confirmButtonColor: '#8b5cf6'}); });</script>";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert text-white animate__animated animate__fadeInDown' style='background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); border-radius:16px;'>❌ System Conflict: " . $e->getMessage() . "</div>";
    }
}

// ---- 2. UPDATE TEACHER ----
if (isset($_POST['update_teacher'])) {
    $t_id = intval($_POST['teacher_id']);
    $id_num = trim($_POST['id_num']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    
    $conn->begin_transaction();
    try {
        $check_id = "SELECT id FROM teachers WHERE id_num = ? AND id != ?";
        $chk_stmt = $conn->prepare($check_id);
        $chk_stmt->bind_param("si", $id_num, $t_id);
        $chk_stmt->execute();
        if($chk_stmt->get_result()->num_rows > 0) {
            throw new Exception("This ID Number is utilized by another data node.");
        }

        $update_t = "UPDATE teachers SET id_num = ?, name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = $conn->prepare($update_t);
        $stmt->bind_param("ssssi", $id_num, $name, $email, $phone, $t_id);
        $stmt->execute();
        
        if(!empty($subject)) {
            $check_c = "SELECT id FROM classes WHERE teacher_id = ? AND subject = ?";
            $chk_c_stmt = $conn->prepare($check_c);
            $chk_c_stmt->bind_param("is", $t_id, $subject);
            $chk_c_stmt->execute();
            
            if($chk_c_stmt->get_result()->num_rows == 0) {
                $check_exist = "SELECT id FROM classes WHERE subject = ? AND (teacher_id IS NULL OR teacher_id = 0)";
                $ex_stmt = $conn->prepare($check_exist);
                $ex_stmt->bind_param("s", $subject);
                $ex_stmt->execute();
                $ex_res = $ex_stmt->get_result();

                if($ex_res->num_rows > 0) {
                    $c_id = $ex_res->fetch_assoc()['id'];
                    $up_c = "UPDATE classes SET teacher_id = ? WHERE id = ?";
                    $up_c_st = $conn->prepare($up_c);
                    $up_c_st->bind_param("ii", $t_id, $c_id);
                    $up_c_st->execute();
                } else {
                    $insert_c = "INSERT INTO classes (teacher_id, subject, monthly_fee) VALUES (?, ?, 1000.00)";
                    $stmt2 = $conn->prepare($insert_c);
                    $stmt2->bind_param("is", $t_id, $subject);
                    $stmt2->execute();
                }
            }
        }
        
        $conn->commit();
        $message = "<script>window.addEventListener('DOMContentLoaded', function() { Swal.fire({icon: 'success', title: 'Matrix Optimised', text: 'Dataset fully updated.', background: '#09090f', color: '#fff', confirmButtonColor: '#6366f1'}); });</script>";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert text-white animate__animated animate__fadeInDown' style='background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); border-radius:16px;'>❌ Optimization Error: " . $e->getMessage() . "</div>";
    }
}

// ---- 3. DELETE TEACHER ----
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $conn->begin_transaction();
    try {
        $del_c = "UPDATE classes SET teacher_id = NULL WHERE teacher_id = ?";
        $stmt1 = $conn->prepare($del_c);
        $stmt1->bind_param("i", $delete_id);
        $stmt1->execute();
        
        $del_t = "DELETE FROM teachers WHERE id = ?";
        $stmt2 = $conn->prepare($del_t);
        $stmt2->bind_param("i", $delete_id);
        $stmt2->execute();
        
        $conn->commit();
        header("Location: teachers.php?deleted=1");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = "<div class='alert text-white' style='background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2);'>❌ Purge Error: " . $e->getMessage() . "</div>";
    }
}

if(isset($_GET['deleted'])){
    $message = "<script>window.addEventListener('DOMContentLoaded', function() { Swal.fire({icon: 'info', title: 'Node Terminated', text: 'Instructor vector completely wiped.', background: '#09090f', color: '#fff', confirmButtonColor: '#3b82f6'}); });</script>";
}

$query = "SELECT t.id, t.id_num, t.name, t.email, t.phone, 
          GROUP_CONCAT(DISTINCT c.subject SEPARATOR ', ') AS subjects
          FROM teachers t
          LEFT JOIN classes c ON t.id = c.teacher_id
          GROUP BY t.id, t.id_num, t.name, t.email, t.phone
          ORDER BY t.id DESC";
$result = $conn->query($query);

$subjects_list = [];
$sub_q = "SELECT DISTINCT subject FROM classes ORDER BY subject ASC";
$sub_res = $conn->query($sub_q);
if($sub_res) {
    while($s_row = $sub_res->fetch_assoc()) {
        $subjects_list[] = $s_row['subject'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantum Registry | Premium Studio Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-dark: #05050a;
            --card-glass: rgba(11, 11, 21, 0.65);
            --input-bg: #080811;
            --border-glow: rgba(139, 92, 246, 0.2);
            --gradient-accent: linear-gradient(135deg, #a78bfa 0%, #6366f1 100%);
            --text-white: #f8fafc;
            --text-slate: #94a3b8;
            --neon-purple: #8b5cf6;
            --sidebar-w: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.08) 0px, transparent 40%),
                radial-gradient(at 100% 0%, rgba(167, 139, 250, 0.06) 0px, transparent 40%);
            background-attachment: fixed;
            color: var(--text-white); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Desktop Mode Layout */
        .main-content { margin-left: var(--sidebar-w); padding: 40px; transition: all 0.3s ease; }
        .dashboard-header-block { border-bottom: 1px solid rgba(255, 255, 255, 0.06); padding-bottom: 25px; margin-bottom: 35px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; }
        
        .search-container { position: relative; width: 100%; max-width: 400px; }
        .search-control {
            padding-left: 52px !important; background: #080812 !important; border: 1px solid rgba(167, 139, 250, 0.2) !important;
            height: 54px; border-radius: 16px !important; color: #ffffff !important; transition: all 0.3s ease;
        }
        .search-control:focus { border-color: #a78bfa !important; box-shadow: 0 0 25px rgba(167, 139, 250, 0.25) !important; }
        .search-icon-inside { position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: #a78bfa; }

        .premium-glass-card { 
            background: var(--card-glass); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.04); border-radius: 24px; padding: 35px; 
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.6);
        }

        .form-label { font-family: 'Space Grotesk', sans-serif; font-size: 0.75rem; font-weight: 700; color: #94a3b8; letter-spacing: 0.8px; text-transform: uppercase; margin-bottom: 8px; }
        .form-control, .form-select {
            background: var(--input-bg) !important; border: 1px solid rgba(255, 255, 255, 0.05) !important;
            color: #fff !important; border-radius: 14px; padding: 14px 18px; font-size: 0.92rem; transition: all 0.25s ease;
        }
        .form-control:focus, .form-select:focus { border-color: #a78bfa !important; box-shadow: 0 0 20px rgba(167, 139, 250, 0.15) !important; }
        .form-select option { background: #0f0f1a; color: #fff; }

        .btn-modern-luxe {
            background: var(--gradient-accent); color: #fff !important; font-weight: 700; font-size: 0.9rem; font-family: 'Space Grotesk', sans-serif;
            border: none; border-radius: 14px; padding: 15px; transition: all 0.3s ease; box-shadow: 0 10px 25px rgba(99, 102, 241, 0.2);
        }
        .btn-modern-luxe:hover { filter: brightness(1.15); transform: translateY(-2px); box-shadow: 0 12px 30px rgba(99, 102, 241, 0.35); }

        .table thead th { 
            background: transparent !important; color: var(--text-slate) !important; 
            font-weight: 600; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 1px; font-family: 'Space Grotesk', sans-serif;
            padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.06); 
        }
        .table tbody tr { transition: all 0.2s ease; }
        .table tbody tr:hover { background: rgba(255,255,255,0.01) !important; }
        .table tbody td { background: transparent !important; padding: 22px 20px; border-bottom: 1px solid rgba(255,255,255,0.02); color: #cbd5e1; }

        .designer-avatar { 
            width: 46px; height: 46px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(167, 139, 250, 0.15) 100%); 
            color: #c084fc; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.92rem; border: 1px solid rgba(139, 92, 246, 0.2); flex-shrink: 0;
        }

        .luxe-pill-badge { 
            background: rgba(99, 102, 241, 0.08); color: #a5b4fc; padding: 6px 14px; border-radius: 10px; font-size: 0.8rem; font-weight: 600; border: 1px solid rgba(99, 102, 241, 0.2); display: inline-block; margin: 4px 2px; white-space: nowrap;
        }

        .action-icon-circle { 
            width: 38px; height: 38px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; 
            border: 1px solid rgba(255,255,255,0.05); color: #94a3b8; text-decoration: none; background: #080811; transition: all 0.25s ease;
        }
        .action-icon-circle:hover { border-color: #a78bfa; color: #fff; background: rgba(139,92,246,0.1); }

        .modal-content { background: linear-gradient(150deg, #0d0d18 0%, #05050a 100%) !important; border: 1px solid rgba(167, 139, 250, 0.2) !important; border-radius: 26px; color: #fff; }
        .searching-active-row { background: rgba(99, 102, 241, 0.03) !important; border-left: 3px solid var(--neon-purple); }
        
        /* ========================================================
           💻 📱 ULTIMATE RESPONSIVE MEDIA QUERIES (MOBILE, TAB, IPHONE)
           ======================================================== */
        @media (max-width: 992px) { 
            .main-content { margin-left: 0; padding: 24px; } 
            .dashboard-header-block { flex-direction: column; align-items: flex-start; gap: 15px; margin-bottom: 25px; } 
            .search-container { max-width: 100%; }
            .premium-glass-card { padding: 25px; border-radius: 20px; }
        }
        @media (max-width: 768px) {
            .main-content { padding: 16px; }
            .table tbody td { padding: 16px 12px; font-size: 0.88rem; }
            .action-icon-circle { width: 34px; height: 34px; border-radius: 10px; }
        }
        @media (max-width: 576px) {
            .header-title-area h1 { font-size: 1.8rem !important; }
            .table-responsive { border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; }
        }
    </style>
</head>
<body>

    <?= $message; ?>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid m-0 p-0">
            
            <!-- Dashboard Top Responsive Header -->
            <div class="dashboard-header-block animate__animated animate__fadeIn">
                <div class="header-title-area">
                    <h1 class="m-0 mb-1" style="font-weight: 800; font-size: 2.2rem; letter-spacing: -0.8px; background: linear-gradient(90deg, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Instructors Matrix</h1>
                    <p class="m-0" style="font-size:0.92rem; color: #94a3b8;">Unified center for specialized educational faculties & profiles.</p>
                </div>
                
                <div class="search-container">
                    <input type="text" id="omniSearchEngine" class="form-control search-control" placeholder="Search instructor or discipline..." autocomplete="off">
                    <i class="fa-solid fa-magnifying-glass search-icon-inside"></i>
                </div>
            </div>

            <div class="row g-4">
                <!-- Register Profile Block (Folds on Tablet/Mobile) -->
                <div class="col-xl-4 col-lg-5 col-12 animate__animated animate__fadeInLeft">
                    <div class="premium-glass-card">
                        <h5 class="fw-bold text-white mb-4" style="font-size:1.15rem; font-family:'Space Grotesk'; letter-spacing: -0.3px;">
                            <i class="fa-solid fa-user-plus me-2" style="color:#a78bfa;"></i>Register Profile
                        </h5>
                        <form action="" method="POST" onsubmit="return validateTeacherForm(this);">
                            <div class="mb-3">
                                <label class="form-label">ID Number / NIC</label>
                                <input type="text" name="id_num" class="form-control" placeholder="e.g. 199512345678 or 951234567V" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Sumudu Priyashantha" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email Handle</label>
                                <input type="email" name="email" class="form-control" placeholder="example@sigma.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Link</label>
                                <input type="text" name="phone" class="form-control" placeholder="e.g. 0771234567">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Discipline (Subject)</label>
                                <select name="subject" class="form-select" required>
                                    <option value="" disabled selected>Select assigned discipline...</option>
                                    <?php foreach($subjects_list as $sub): ?>
                                        <option value="<?= htmlspecialchars($sub) ?>"><?= htmlspecialchars($sub) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="add_teacher" class="btn btn-modern-luxe w-100">
                                <i class="fa-solid fa-cube me-2"></i> Deploy Data Node
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Active Faculty Grid Block -->
                <div class="col-xl-8 col-lg-7 col-12 animate__animated animate__fadeInRight">
                    <div class="premium-glass-card h-100">
                        <h5 class="fw-bold text-white mb-4" style="font-size:1.15rem; font-family:'Space Grotesk'; letter-spacing: -0.3px;">
                            <i class="fa-solid fa-network-wired me-2" style="color:#6366f1;"></i>Active Faculty Matrix
                        </h5>
                        
                        <?php if ($result && $result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table align-middle" id="facultyTableData">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 220px;">Faculty Node</th>
                                            <th style="min-width: 180px;">Assigned Fields</th>
                                            <th style="min-width: 180px;">Contact Metric</th>
                                            <th class="text-end" style="min-width: 100px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): 
                                            $initials = strtoupper(substr($row['name'], 0, 2));
                                        ?>
                                            <tr class="teacher-data-row">
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="designer-avatar"><?= $initials ?></div>
                                                        <div>
                                                            <h6 class="m-0 fw-bold text-white target-search-name" style="font-size:0.95rem;"><?= htmlspecialchars($row['name']); ?></h6>
                                                            <span class="text-muted" style="font-size:11px; font-family: monospace;">NIC: <?= htmlspecialchars($row['id_num']); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap">
                                                        <?php 
                                                        if(!empty($row['subjects'])) {
                                                            $splitted_subs = explode(', ', $row['subjects']);
                                                            foreach($splitted_subs as $sb) {
                                                                echo '<span class="luxe-pill-badge">'.htmlspecialchars($sb).'</span>';
                                                            }
                                                        } else {
                                                            echo '<span class="text-muted small" style="font-style: italic;">Unassigned</span>';
                                                        }
                                                        ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="font-size: 13px; color:#e2e8f0; white-space: nowrap;"><i class="fa-regular fa-envelope text-muted me-2" style="font-size:11px;"></i><?= htmlspecialchars($row['email'] ?: '-'); ?></div>
                                                    <div class="text-muted mt-1" style="font-size: 12px; white-space: nowrap;"><i class="fa-solid fa-phone text-muted me-2" style="font-size:11px;"></i><?= htmlspecialchars($row['phone'] ?: '-'); ?></div>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <a href="javascript:void(0)" class="action-icon-circle data-intercept-trigger" 
                                                           data-id="<?= $row['id']; ?>"
                                                           data-nic="<?= htmlspecialchars($row['id_num']); ?>"
                                                           data-name="<?= htmlspecialchars($row['name']); ?>"
                                                           data-email="<?= htmlspecialchars($row['email']); ?>"
                                                           data-phone="<?= htmlspecialchars($row['phone']); ?>">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </a>
                                                        <a href="javascript:void(0)" class="action-icon-circle" onclick="triggerProfileRemoval(<?= $row['id']; ?>)">
                                                            <i class="fa-solid fa-trash-can"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div id="matrixSearchFallback" class="text-center py-5 d-none animate__animated animate__fadeIn">
                                <i class="fa-solid fa-ban text-muted mb-3 fs-3" style="opacity:0.3;"></i>
                                <h6 class="text-muted fw-semibold">No instructors matched that query string.</h6>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fa-solid fa-folder-open text-muted mb-3 fs-3" style="opacity:0.3;"></i>
                                <h6 class="text-muted fw-semibold">No active data streams found inside tables.</h6>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Responsive Edit Modal -->
    <div class="modal fade" id="glowingEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered px-3">
            <div class="modal-content">
                <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <h5 class="modal-title fw-bold text-white" style="letter-spacing:-0.4px; font-family:'Space Grotesk';">
                        <i class="fa-solid fa-pen-to-square text-purple me-2" style="color: #a78bfa;"></i> Modify Faculty Node
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="teachers.php" method="POST" onsubmit="return validateTeacherForm(this);">
                    <div class="modal-body">
                        <input type="hidden" name="teacher_id" id="edit_teacher_id">
                        <div class="mb-3">
                            <label class="form-label">ID Number / NIC</label>
                            <input type="text" name="id_num" id="edit_nic" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Channel</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Assign Additional Discipline</label>
                            <select name="subject" id="edit_subject" class="form-select">
                                <option value="">Keep current assignments / Add no new discipline</option>
                                <?php foreach($subjects_list as $sub): ?>
                                    <option value="<?= htmlspecialchars($sub) ?>"><?= htmlspecialchars($sub) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05);">
                        <button type="button" class="btn btn-link text-muted text-decoration-none small fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_teacher" class="btn btn-modern-luxe px-4" style="padding:11px 24px !important;">Commit Synchronization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateTeacherForm(form) {
            let idNum = form.id_num.value.trim();
            let phone = form.phone.value.trim();
            
            let nicPattern = /^([0-9]{9}[xXvV]|[0-9]{12})$/;
            if (!nicPattern.test(idNum)) {
                Swal.fire({ icon: 'error', title: 'Invalid Identifier', text: 'Please provide a standard Sri Lankan NIC format.', background: '#0b0b14', color: '#fff', confirmButtonColor: '#6366f1' });
                return false;
            }

            if(phone !== "") {
                let phonePattern = /^[0-9]{10}$/;
                if(!phonePattern.test(phone)) {
                    Swal.fire({ icon: 'error', title: 'Telemetry Mismatch', text: 'Contact metrics must span exactly 10 digits.', background: '#0b0b14', color: '#fff', confirmButtonColor: '#6366f1' });
                    return false;
                }
            }
            return true;
        }

        $(document).ready(function() {
            $(document).on("click", ".data-intercept-trigger", function (e) {
                e.preventDefault();
                $("#edit_teacher_id").val($(this).attr('data-id'));
                $("#edit_nic").val($(this).attr('data-nic'));
                $("#edit_name").val($(this).attr('data-name'));
                $("#edit_email").val($(this).attr('data-email'));
                $("#edit_phone").val($(this).attr('data-phone'));
                $("#edit_subject").val(""); 

                $('#glowingEditModal').modal('show');
            });

            $("#omniSearchEngine").on("keyup", function() {
                var searchRawValue = $(this).val().toLowerCase();
                var matchTrackCounter = 0;

                $(".teacher-data-row").each(function() {
                    var fullContent = $(this).text().toLowerCase();

                    if (fullContent.indexOf(searchRawValue) > -1) {
                        $(this).removeClass("d-none");
                        if(searchRawValue.trim() !== "") { $(this).addClass("searching-active-row"); } 
                        else { $(this).removeClass("searching-active-row"); }
                        matchTrackCounter++;
                    } else {
                        $(this).addClass("d-none").removeClass("searching-active-row");
                    }
                });

                if (matchTrackCounter === 0) {
                    $("#matrixSearchFallback").removeClass("d-none");
                    $("#facultyTableData").addClass("d-none");
                } else {
                    $("#matrixSearchFallback").addClass("d-none");
                    $("#facultyTableData").removeClass("d-none");
                }
            });
        });

        function triggerProfileRemoval(id) {
            Swal.fire({
                title: 'Terminate Node?',
                text: "Erase this profile registry? Assigned classes will be unlinked.",
                icon: 'warning',
                showCancelButton: true,
                background: '#0c0c14',
                color: '#fff',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: 'transparent',
                confirmButtonText: 'Yes, Terminate',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "teachers.php?delete_id=" + id;
                }
            });
        }
    </script>
</body>
</html>