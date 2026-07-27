<?php
session_start();

// 1. Database සම්බන්ධතාවය
$conn = new mysqli("localhost", "root", "", "attendence");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}
$conn->set_charset("utf8mb4");

$message = "";

// 2. ලකුණු Submit වූ පසු exam_results ටේබල් එකට Save වන කොටස
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_marks'])) {
    $exam_id = intval($_POST['exam_id']);
    $marks_array = $_POST['marks'];

    foreach ($marks_array as $student_id => $marks_value) {
        $student_id = intval($student_id);
        $marks_obtained = floatval($marks_value);
        
        $check = $conn->query("SELECT result_id FROM exam_results WHERE exam_id = $exam_id AND student_id = $student_id");
        
        if ($check->num_rows > 0) {
            $conn->query("UPDATE exam_results SET marks_obtained = $marks_obtained WHERE exam_id = $exam_id AND student_id = $student_id");
        } else {
            $conn->query("INSERT INTO exam_results (exam_id, student_id, marks_obtained) VALUES ($exam_id, $student_id, $marks_obtained)");
        }
    }
    $message = "<div class='alert alert-success border-0 text-white mb-4' style='background: rgba(16, 185, 129, 0.25); border-left: 4px solid #10b981 !important;'>✨ Exam marks successfully synchronized!</div>";
}

// 3. Exams ටේබල් එකෙන් දත්ත ලබා ගැනීම
$exams_result = $conn->query("
    SELECT e.id, e.exam_title, e.grade, e.exam_date, c.subject 
    FROM exams e
    INNER JOIN classes c ON e.class_id = c.id
    ORDER BY e.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registry Desk | SIGMA ERP</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { 
            --bg-dark: #09090e;
            --book-inside: #15151e;
            --text-muted: #94a3b8;
            --sidebar-width: 280px;
            --border-glass: rgba(255, 255, 255, 0.08);
        }

        body { 
            background: radial-gradient(circle at 80% 20%, #2e1065 0%, #0f172a 60%, var(--bg-dark) 100%);
            color: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        .sidebar-container { 
            width: var(--sidebar-width); 
            position: fixed; 
            top: 0; 
            left: 0; 
            height: 100vh; 
            z-index: 1040; 
            transition: transform 0.3s ease; 
        }
        
        .main-content {
            margin-left: var(--sidebar-width); 
            padding: 50px 20px;
            display: flex; 
            justify-content: center; 
            align-items: center;
            min-height: 100vh; 
            position: relative; 
            z-index: 1;
            transition: margin-left 0.3s ease;
        }

        .mobile-header { 
            display: none; 
            background: #15151e; 
            border-bottom: 1px solid var(--border-glass); 
            padding: 15px 20px; 
            position: fixed; 
            top: 0; 
            left: 0; 
            right: 0; 
            z-index: 1050; 
        }

        .book-container {
            background: var(--book-inside); 
            border: 1px solid var(--border-glass);
            border-radius: 24px; 
            padding: 40px; 
            width: 100%; 
            max-width: 760px;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.6); 
            position: relative;
        }

        .bookmark {
            position: absolute; top: -15px; right: 50px; width: 30px; height: 70px;
            background: linear-gradient(135deg, #38bdf8 0%, #0369a1 100%);
            clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%);
        }

        .form-label { color: #cbd5e1; font-weight: 600; font-size: 0.88rem; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .form-label i { color: #38bdf8; }

        .form-control, .form-select {
            background: rgba(0, 0, 0, 0.4) !important; 
            border: 1px solid var(--border-glass) !important;
            color: #f8fafc !important; 
            border-radius: 14px; 
            padding: 13px 16px; 
            font-size: 0.95rem;
        }

        .glass-table-card {
            background: rgba(21, 21, 30, 0.95) !important;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 20px;
        }

        .glass-table-card .table {
            background: transparent !important;
            color: #e2e8f0 !important;
            margin-bottom: 0;
        }

        .glass-table-card .table thead {
            background: rgba(56, 189, 248, 0.08) !important;
        }

        .glass-table-card .table th {
            background: transparent !important;
            color: #38bdf8 !important;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding: 15px;
        }

        .glass-table-card .table td {
            background: transparent !important;
            color: #f8fafc !important;
            border-color: rgba(255,255,255,0.05);
            padding: 15px;
        }

        .table-mark-input {
            width: 110px;
            padding: 8px 12px;
            text-align: center;
            font-weight: 700;
            color: #00f2fe !important;
            background: rgba(0,0,0,0.5) !important;
            border: 1px solid rgba(56, 189, 248, 0.2) !important;
            border-radius: 10px;
        }

        .btn-submit-premium {
            background: linear-gradient(135deg, #8b5cf6 0%, #3b82f6 50%, #06b6d4 100%);
            border: none; color: #fff; font-weight: 800; letter-spacing: .5px;
            padding: 16px; width: 100%; border-radius: 18px; transition: .35s;
            box-shadow: 0 10px 30px rgba(139,92,246,.35), 0 0 20px rgba(59,130,246,.20);
        }

        .btn-submit-premium:hover {
            transform: translateY(-4px); color: #fff;
            box-shadow: 0 20px 40px rgba(139,92,246,.45), 0 0 35px rgba(59,130,246,.30);
        }

        @media (max-width: 991.98px) {
            .sidebar-container { transform: translateX(-100%); }
            .sidebar-container.show { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 100px 16px 40px 16px; }
            .mobile-header { display: flex; align-items: center; justify-content: space-between; }
            .book-container { padding: 30px 20px; border-radius: 20px; }
            .bookmark { right: 25px; width: 25px; height: 55px; }
        }

        @media (max-width: 575.98px) {
            .book-container h2 { font-size: 1.4rem; }
            .table-mark-input { width: 90px; padding: 6px 8px; }
            .glass-table-card .table th, .glass-table-card .table td { padding: 10px 8px; font-size: 0.85rem; }
            .select-container-responsive { flex-direction: column; }
            .select-container-responsive button { width: 100%; }
        }

        .sidebar-overlay { 
            display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
            background: rgba(0,0,0,0.6); z-index: 1030; backdrop-filter: blur(4px); 
        }
        .sidebar-overlay.show { display: block; }
    </style>
</head>
<body>

    <div class="mobile-header">
        <span class="fw-bold text-white fs-5">SIGMA ERP</span>
        <button class="btn btn-outline-light btn-sm px-3" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar-container" id="sidebarContainer">
        <?php include 'sidebar.php'; ?>
    </div>
   
    <main class="main-content">
        <div class="book-container">
            <div class="bookmark"></div>

            <div class="d-flex align-items-center gap-3 mb-2">
                <div style="background: rgba(56, 189, 248, 0.1); width: 45px; height: 45px; border-radius: 12px; display:flex; align-items:center; justify-content:center; flex-shrink: 0;">
                    <i class="fa-solid fa-graduation-cap text-info fs-4"></i>
                </div>
                <h2 class="m-0" style="font-weight:800; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Academic Score Ledger</h2>
            </div>
            <p style="color: var(--text-muted);" class="small mb-4">Select the exact created exam scope below to manage and input student results.</p>
            
            <?php echo $message; ?>
            
            <form method="GET" action="">
                <div class="mb-4">
                    <label class="form-label"><i class="fa-solid fa-file-signature"></i> Select Active Exam</label>
                    <div class="d-flex gap-2 select-container-responsive">
                        <select name="select_exam_id" class="form-select" required>
                            <option value="">-- Choose Created Exam --</option>
                            <?php if($exams_result): ?>
                                <?php while($ex = $exams_result->fetch_assoc()): ?>
                                    <option value="<?php echo $ex['id']; ?>" <?php echo (isset($_GET['select_exam_id']) && $_GET['select_exam_id'] == $ex['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ex['grade'] . " - " . $ex['subject'] . " [" . $ex['exam_title'] . "]"); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                        <button type="submit" class="btn btn-primary fw-bold px-4 rounded-3" style="background: linear-gradient(135deg, #0284c7, #3b82f6); border:none;">Fetch</button>
                    </div>
                </div>
            </form>

            <?php
            if (isset($_GET['select_exam_id']) && !empty($_GET['select_exam_id'])) {
                $selected_exam_id = intval($_GET['select_exam_id']);
                
                $students_sql = "
                    SELECT s.student_id, s.student_name, er.marks_obtained 
                    FROM students s
                    INNER JOIN student_classes sc ON s.student_id = sc.student_id
                    INNER JOIN exams e ON sc.class_id = e.class_id
                    LEFT JOIN exam_results er ON er.exam_id = e.id AND er.student_id = s.student_id
                    WHERE e.id = ?
                ";
                
                $stmt = $conn->prepare($students_sql);
                
                if(!$stmt) {
                    echo "<div class='alert alert-danger'>⚠️ SQL Error: " . $conn->error . "</div>";
                } else {
                    $stmt->bind_param("i", $selected_exam_id);
                    $stmt->execute();
                    $students_result = $stmt->get_result();
                    ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="exam_id" value="<?php echo $selected_exam_id; ?>">
                        
                        <div class="glass-table-card table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Index</th>
                                        <th>Full Name</th>
                                        <th class="text-center">Score (100)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($students_result->num_rows > 0): ?>
                                        <?php while ($st = $students_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="fw-bold text-secondary">#<?php echo $st['student_id']; ?></td>
                                            <td class="fw-semibold text-white">
                                                <?php echo htmlspecialchars($st['student_name'] ?? 'No Name'); ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center">
                                                    <input type="number" step="0.01" name="marks[<?php echo $st['student_id']; ?>]"  
                                                           class="form-control table-mark-input" min="0" max="100" required 
                                                           value="<?php echo isset($st['marks_obtained']) ? $st['marks_obtained'] : ''; ?>" placeholder="0.00">
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-muted py-4">No active students enrolled in this class scope.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" name="save_marks" class="btn btn-submit-premium mt-4 fw-bold">
                            <i class="fas fa-shield-halved me-2"></i> Synchronize Ledger Records
                        </button>
                    </form>
                <?php 
                }
            } 
            ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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