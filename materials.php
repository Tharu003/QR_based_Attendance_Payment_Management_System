<?php 
include "auth_check.php";
restrictTo(['admin', 'teacher']); // Assistant ආවොත් බ්ලොක් වෙනවා!
include "db.php";
?>
<?php
// --- DELETE FUNCTION ---
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $res = $conn->query("SELECT file_url FROM class_materials WHERE id=$id");
    if ($res && $res->num_rows > 0) {
        $data = $res->fetch_assoc();
        // Server එකේ uploads/ ෆෝල්ඩර් එකෙන් ෆයිල් එක මකා දැමීම
        if(!empty($data['file_url']) && file_exists($data['file_url'])) {
            unlink($data['file_url']);
        }
    }

    // Database එකෙන් Record එක මකා දැමීම
    $conn->query("DELETE FROM class_materials WHERE id=$id");
    header("Location: materials.php");
    exit();
}

// --- UPLOAD & INSERT FUNCTION ---
if (isset($_POST['add_material'])) {
    $class_id = mysqli_real_escape_string($conn, $_POST['class_id']);
    $grade = mysqli_real_escape_string($conn, $_POST['grade']); // අලුතින් එකතු කල Grade එක
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $week_no = mysqli_real_escape_string($conn, $_POST['week_no']);
    $video_url = mysqli_real_escape_string($conn, $_POST['video_url']);
    $m_type = mysqli_real_escape_string($conn, $_POST['type']); 

    $file_name = "";
    if (!empty($_FILES['file_upload']['name'])) {
        $target_dir = "uploads/"; 
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_name = $target_dir . time() . "_" . basename($_FILES["file_upload"]["name"]);
        move_uploaded_file($_FILES["file_upload"]["tmp_name"], $file_name);
    }

    // SQL එකට 'grade' Column එක සහ Variable එක එකතු කලා
    $sql = "INSERT INTO class_materials 
            (class_id, grade, week_no, title, material_type, file_url, video_url) 
            VALUES 
            ('$class_id', '$grade', '$week_no', '$title', '$m_type', '$file_name', '$video_url')";
    
    if($conn->query($sql)) {
        echo "<script>alert('Successfully Published!'); window.location='materials.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// FETCH CLASSES
$classes_query = "SELECT c.id, c.subject, t.name AS teacher_name 
                  FROM classes c 
                  JOIN teachers t ON c.teacher_id = t.id 
                  ORDER BY c.subject ASC";
$classes_res = $conn->query($classes_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials Management | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-dark: #0a0a0c;
            --card-dark: #16161a;
            --sidebar-black: #000000;
            --accent-blue: #3b82f6;
            --electric-blue: #00d2ff;
            --text-gray: #94a3b8;
            --sidebar-w: 280px;
            --glow-blue: rgba(59, 130, 246, 0.25);
        }

        body { 
            background-color: var(--bg-dark); 
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            overflow-x: hidden;
        }

        .main-content {
            margin-left: var(--sidebar-w);
            padding: 40px;
            animation: fadeIn 0.8s ease-out;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .glass-card { 
            background: var(--card-dark); 
            border-radius: 24px; 
            border: 1px solid #1e293b;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.4s ease;
            height: 100%;
        }
        
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6), 0 0 20px var(--glow-blue);
            border-color: #334155;
        }

        .form-control, .form-select { 
            border-radius: 12px; 
            padding: 0.75rem 1rem; 
            border: 1px solid #334155;
            background: #030712;
            color: white;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background: #030712;
            color: white;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 4px var(--glow-blue);
        }

        .small.fw-bold.text-muted {
            color: #cbd5e1 !important;
            letter-spacing: 0.5px;
        }

        .custom-text{
            color: #d4e0f0;
        }

        .btn-publish {
            background: linear-gradient(135deg, #38bdf8 0%, #1d4ed8 100%);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 700;
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
            box-shadow: 0 4px 15px rgba(29, 78, 216, 0.3);
        }

        .btn-publish:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(56, 189, 248, 0.5);
            color: white;
        }

        .week-badge {
            background: #030712;
            color: var(--accent-blue);
            width: 42px; height: 42px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px; font-weight: 800; border: 1px solid #1e293b;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.1);
            transition: all 0.3s ease;
        }

        .table tr:hover .week-badge {
            background: var(--accent-blue);
            color: white;
            border-color: var(--accent-blue);
            transform: scale(1.1) rotate(5deg);
        }

        .cat-tag {
            padding: 6px 14px; border-radius: 10px; font-size: 0.75rem; font-weight: 700;
            display: inline-flex; align-items: center; gap: 6px; text-transform: uppercase;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        .tag-tute { background: rgba(56, 189, 248, 0.12); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.2); }
        .tag-quiz { background: rgba(244, 63, 94, 0.12); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.2); }
        .tag-video { background: rgba(34, 197, 94, 0.12); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.2); }
        .tag-assignment { background: rgba(168, 85, 247, 0.12); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.2); }

        .action-btn {
            width: 36px; height: 36px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            text-decoration: none; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .btn-view { background: rgba(56, 189, 248, 0.1); color: #38bdf8; }
        .btn-view:hover { background: #38bdf8; color: black; transform: scale(1.15); }
        .btn-quiz-link { background: rgba(244, 63, 94, 0.1); color: #fb7185; width: auto; padding: 0 12px; font-size: 0.8rem; font-weight: 700; }
        .btn-quiz-link:hover { background: #fb7185; color: #000; transform: scale(1.05); }
        .btn-del { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .btn-del:hover { background: #ef4444; color: white; transform: scale(1.15); }

        .table {
            --bs-table-bg: transparent !important;
            --bs-table-color: #ffffff !important;
            --bs-table-border-color: #1e293b !important;
        }

        .table tbody tr {
            background: #16161a;
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: #0c1524 !important;
        }

        .teacher-badge {
            font-size: 0.75rem; background: #1e293b; color: #94a3b8;
            padding: 2px 8px; border-radius: 6px; display: inline-block; margin-top: 2px;
            white-space: nowrap;
        }
        
        .grade-badge {
            font-size: 0.75rem; background: rgba(59, 130, 246, 0.2); color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 2px 8px; border-radius: 6px; display: inline-block; margin-top: 2px;
            font-weight: 600;
            white-space: nowrap;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- RESPONSIVE MEDIA QUERIES --- */
        @media (max-width: 1200px) {
            .main-content {
                padding: 30px 20px;
            }
        }

        @media (max-width: 992px) {
            .main-content { 
                margin-left: 0; 
                padding: 20px 15px; 
            }
            .glass-card {
                height: auto;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px 10px;
            }
            .header-section {
                flex-direction: column;
                align-items: start !important;
                gap: 15px;
            }
            .week-badge {
                width: 35px;
                height: 35px;
                font-size: 0.85rem;
            }
            .table th, .table td {
                padding: 10px 8px !important;
            }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main-content">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-md-5 header-section">
        <div>
            <h2 class="fw-800 text-white mb-1" style="font-weight: 800; font-size: calc(1.3rem + 0.6vw);">Resource Hub</h2>
            <p class="mb-0 custom-text small">Manage and publish learning materials.</p>
        </div>
        <div class="text-start text-md-end">
            <div class="small fw-bold text-uppercase text-muted">System Date</div>
            <div class="fw-bold text-white small"><?php echo date('d M, Y'); ?></div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Input Form Block -->
        <div class="col-12 col-xl-4">
            <div class="glass-card p-4">
                <h5 class="fw-bold text-white mb-4"><i class="fas fa-plus-circle text-info me-2"></i> New Resource</h5>
                <form method="POST" enctype="multipart/form-data">
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-2">TARGET GRADE (ශ්‍රේණිය)</label>
                        <select name="grade" class="form-select" required>
                            <option value="">Select Grade...</option>
                            <?php for($i=1; $i<=13; $i++): ?>
                                <option value="Grade <?= $i ?>">Grade <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-2">TARGET CLASS</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">Select Class...</option>
                            <?php 
                            if($classes_res) {
                                $classes_res->data_seek(0); 
                                while($row = $classes_res->fetch_assoc()): ?>
                                    <option value="<?= $row['id'] ?>">
                                        <?= htmlspecialchars($row['subject']) ?> (<?= htmlspecialchars($row['teacher_name']) ?>)
                                    </option>
                                <?php endwhile; 
                            } ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-2">RESOURCE TITLE</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-7">
                            <label class="small fw-bold text-muted mb-2">CATEGORY</label>
                            <select name="type" class="form-select" required>
                                <option value="tute">PDF Lesson</option>
                                <option value="quiz">Weekly Quiz</option>
                                <option value="video">Video Lesson</option>
                                <option value="assignment">Assignment</option>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="small fw-bold text-muted mb-2">WEEK</label>
                            <input type="number" name="week_no" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-2">UPLOAD FILE</label>
                        <input type="file" name="file_upload" class="form-control">
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-muted mb-2">VIDEO URL</label>
                        <input type="url" name="video_url" class="form-control">
                    </div>

                    <button type="submit" name="add_material" class="btn btn-publish w-100">Publish Resource</button>
                </form>
            </div>
        </div>

        <!-- Data Table Block -->
        <div class="col-12 col-xl-8">
            <div class="glass-card overflow-hidden">
                <div class="p-4 border-bottom border-secondary border-opacity-25">
                    <h5 class="fw-bold m-0 text-white"><i class="fas fa-layer-group text-primary me-2"></i> Live Materials</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-white" style="min-width: 600px;">
                        <thead>
                            <tr class="text-muted small">
                                <th class="ps-4" style="width: 10%;">WK</th>
                                <th style="width: 50%;">RESOURCE DETAILS</th>
                                <th style="width: 20%;">TYPE</th>
                                <th class="text-end pe-4" style="width: 20%;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT m.*, c.subject, t.name AS teacher_name 
                                    FROM class_materials m 
                                    JOIN classes c ON m.class_id = c.id 
                                    JOIN teachers t ON c.teacher_id = t.id
                                    ORDER BY m.week_no DESC, m.id DESC";
                            $result = $conn->query($sql);
                            if($result && $result->num_rows > 0):
                                while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="week-badge"><?= $row['week_no'] ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-white mb-1" style="word-break: break-word; max-width: 300px;"><?= htmlspecialchars($row['title']) ?></div>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <span class="grade-badge"><i class="fas fa-graduation-cap me-1"></i> <?= htmlspecialchars($row['grade']) ?></span>
                                            <span class="text-white-50 small"><?= htmlspecialchars($row['subject']) ?></span>
                                            <span class="teacher-badge"><i class="fas fa-user-tie me-1"></i> <?= htmlspecialchars($row['teacher_name']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                            $m_type = strtolower($row['material_type']);
                                            $tagClass = "tag-tute"; $icon = "fa-file-pdf";
                                            if($m_type == 'quiz') { $tagClass = "tag-quiz"; $icon = "fa-stopwatch"; }
                                            if($m_type == 'video') { $tagClass = "tag-video"; $icon = "fa-play-circle"; }
                                            if($m_type == 'assignment') { $tagClass = "tag-assignment"; $icon = "fa-tasks"; }
                                        ?>
                                        <div class="cat-tag <?= $tagClass ?>"><i class="fas <?= $icon ?>"></i> <?= $m_type ?></div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2 align-items-center">
                                            <?php if(!empty($row['file_url'])): ?>
                                                <a href="<?= $row['file_url'] ?>" target="_blank" class="action-btn btn-view" title="Download File">
                                                    <i class="fas fa-file-download"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="?delete=<?= $row['id'] ?>" class="action-btn btn-del" onclick="return confirm('Are you sure you want to delete this resource? All local linked files will be cleared permanently.')" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; 
                            else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-folder-open fa-2x mb-3 d-block text-secondary"></i> No materials published yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>