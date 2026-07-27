<?php
session_start();

// 1. අවසර ලත් Roles ලැයිස්තුව සහ පරීක්ෂාව
$allowed_roles = ['admin', 'teacher', 'assistant']; 

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: dashboard.php?error=unauthorized");
    exit();
}

// 2. Database සම්බන්ධතාවය
$conn = new mysqli("localhost", "root", "", "attendence");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}
$conn->set_charset("utf8mb4"); // සිංහල අකුරු සඳහා

$message = "";

// Redirect එකෙන් පස්සේ පණිවිඩය පෙන්වීමට Session එක පරීක්ෂා කිරීම
if (isset($_SESSION['success_msg'])) {
    $message = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
if (isset($_SESSION['error_msg'])) {
    $message = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

// ======================================================
// 3. Form එක Submit වූ පසු ක්‍රියාත්මක වන කොටස (With Fixed Class Grades Lookup)
// ======================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $notice_type = mysqli_real_escape_string($conn, $_POST['notice_type']);
    $notice_date = mysqli_real_escape_string($conn, $_POST['notice_date']); 
    
    $selected_classes = isset($_POST['class_ids']) ? array_unique($_POST['class_ids']) : [];

    if (empty($selected_classes)) {
        $message = "<div class='alert alert-danger border-0 text-white animate__animated animate__bounceIn' style='background: rgba(239, 68, 68, 0.25); border-left: 4px solid #ef4444 !important; backdrop-filter: blur(10px);'>❌ Please select at least one Class/Grade!</div>";
    } else {
        $success_count = 0;
        $error_occurred = false;

        // 1. notices ටේබල් එකට grade එකත් ඇතුලත් වන සේ INSERT Query එක සකස් කිරීම
        $sql = "INSERT INTO notices (class_id, grade, notice_date, title, content, notice_type) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            foreach ($selected_classes as $class_id) {
                $insert_class_id = intval($class_id);
                
                // 💡 වැදගත්ම වෙනස: class_grades ටේබල් එකෙන් අදාළ පන්තියේ grade එක සොයා ගැනීම
                $grade_find_sql = "SELECT grade FROM class_grades WHERE class_id = ? LIMIT 1";
                $stmt_grade = $conn->prepare($grade_find_sql);
                $stmt_grade->bind_param("i", $insert_class_id);
                $stmt_grade->execute();
                $res_grade = $stmt_grade->get_result();
                
                $detected_grade = "";
                if($row_grade = $res_grade->fetch_assoc()) {
                    $detected_grade = $row_grade['grade']; // class_grades ටේබල් එකේ තියෙන grade එක ගනී
                }
                $stmt_grade->close();

                // 2. සොයාගත් Grade එකත් සමඟම notice එක සාර්ථකව සේව් කිරීම
                $stmt->bind_param("isssss", $insert_class_id, $detected_grade, $notice_date, $title, $content, $notice_type);
                
                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_occurred = true;
                }
            }
            $stmt->close();
        } else {
            $error_occurred = true;
        }

        if ($success_count > 0 && !$error_occurred) {
            $_SESSION['success_msg'] = "<div class='alert alert-success border-0 text-white animate__animated animate__pulse' style='background: rgba(16, 185, 129, 0.25); border-left: 4px solid #10b981 !important; backdrop-filter: blur(10px);'>✨ Notice posted successfully to $success_count target class(es)!</div>";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['error_msg'] = "<div class='alert alert-danger border-0 text-white animate__animated animate__shakeX' style='background: rgba(239, 68, 68, 0.25); border-left: 4px solid #ef4444 !important; backdrop-filter: blur(10px);'>❌ Error occurred while saving notice.</div>";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// 4. Dropdown එක සඳහා ගුරුවරුන්ගේ ලැයිස්තුව ලබා ගැනීම
$teachers_result = $conn->query("SELECT id, name FROM teachers ORDER BY name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Announcement | SIGMA ERP</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root { 
            --bg-dark: #09090e;
            --book-cover: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            --book-inside: #15151e;
            --accent-glow: linear-gradient(135deg, #00f2fe 0%, #4facfe 100%);
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

        /* Creative Illustrated Background Shapes */
        .bg-illustration {
            position: absolute;
            top: 15%; right: 5%;
            width: 350px; height: 350px;
            background: url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=600&auto=format&fit=crop') no-repeat center;
            background-size: cover;
            filter: grayscale(30%) brightness(0.6) contrast(1.2);
            mix-blend-mode: lighten;
            opacity: 0.25;
            z-index: 0;
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            animation: morphing 15s infinite alternate ease-in-out;
        }

        @keyframes morphing {
            0% { border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%; }
            50% { border-radius: 50% 50% 30% 70% / 50% 60% 40% 60%; }
            100% { border-radius: 60% 40% 60% 40% / 40% 50% 50% 60%; }
        }

        /* Responsive Main Content Layout */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            position: relative;
            z-index: 1;
            transition: margin-left 0.3s ease;
        }

        /* --- 3D BOOK ANIMATION STRUCTURE --- */
        .book-perspective {
            perspective: 1500px;
            width: 100%;
            max-width: 720px;
        }

        .book-container {
            background: var(--book-inside);
            border: 1px solid var(--border-glass);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.5), inset 0 1px 2px rgba(255,255,255,0.05);
            position: relative;
            transform-style: preserve-3d;
            transition: transform 1s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.5s ease;
        }

        .book-container.open {
            box-shadow: -20px 30px 80px rgba(0, 0, 0, 0.7), 10px 10px 30px rgba(59, 130, 246, 0.1);
        }

        /* Magic Bookmark Accent */
        .bookmark {
            position: absolute;
            top: -15px; right: 40px;
            width: 26px; height: 60px;
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            clip-path: polygon(0 0, 100% 0, 100% 100%, 50% 80%, 0 100%);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
            z-index: 10;
            transition: transform 0.3s ease;
        }
        .book-container:hover .bookmark {
            transform: translateY(10px);
        }

        .book-container h2 {
            font-weight: 800;
            font-size: 2rem;
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #ffffff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- Page Turning Overlay Effect --- */
        .page-flip-layer {
            position: absolute;
            top: 0; left: 0; width: 50%; height: 100%;
            background: linear-gradient(to left, rgba(0,0,0,0.4) 0%, rgba(255,255,255,0.02) 100%);
            transform-origin: left center;
            transition: transform 1s ease-in-out;
            pointer-events: none;
            z-index: 5;
            border-top-left-radius: 24px;
            border-bottom-left-radius: 24px;
        }

        .book-container.turning .page-flip-layer {
            transform: rotateY(-140deg);
            background: linear-gradient(to right, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0) 100%);
        }

        /* Form styling */
        .form-label {
            color: #cbd5e1;
            font-weight: 600;
            font-size: 0.88rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-label i {
            color: #38bdf8;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            background: rgba(0, 0, 0, 0.4) !important;
            border: 1px solid var(--border-glass) !important;
            color: #f8fafc !important;
            border-radius: 14px;
            padding: 13px 16px;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control:focus, .form-select:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.15) !important;
            background: rgba(0, 0, 0, 0.55) !important;
        }

        .form-select option {
            background-color: #111116;
            color: #f8fafc;
        }

        /* Class Multiselect List Design */
        .form-select[multiple] {
            height: 180px;
            padding: 8px;
        }

        .form-select[multiple] option {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 6px;
            border-left: 4px solid transparent;
            font-weight: 500;
            white-space: normal; /* Mobile වලදී text එක කැපෙන්නේ නැතුව ඊළඟ පේලියට යන්න */
        }

        .form-select[multiple] option:checked {
            background: linear-gradient(135deg, #0284c7 0%, #6366f1 100%) !important;
            color: white !important;
            border-left: 4px solid #00f2fe;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.25);
        }

        /* Fancy Submit Button */
        .btn-submit-premium {
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            color: white;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            border-radius: 14px;
            padding: 15px;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(124, 58, 237, 0.3);
        }

        .btn-submit-premium:hover {
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 15px 30px rgba(124, 58, 237, 0.45);
        }

        /* Hidden elements that appear on book open */
        .hidden-fields {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height 0.8s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.8s ease;
        }

        .hidden-fields.show {
            max-height: 1500px; /* Mobile වලදී content එක දිග නිසා max-height එක වැඩි කළා */
            opacity: 1;
        }

        /* --- MEDIA QUERIES FOR MOBILE, IPHONE & TABLETS --- */
        @media (max-width: 992px) {
            .main-content { 
                margin-left: 0; /* Sidebar එක mobile වලදී hide වෙනවා නම් ඉඩ ඉතුරු කරගන්න */
                padding: 30px 15px; 
            }
            .bg-illustration { display: none; }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 20px 10px;
                align-items: flex-start; /* කුඩා screens වලදී top එකට flex කරලා scroll කරන්න ලෙහෙසි කරනවා */
            }
            .book-container {
                padding: 24px 16px; /* Mobile සහ iPhones වලදී ඇතුලත ඉඩ (padding) අඩු කළා */
                border-radius: 16px;
            }
            .book-container h2 {
                font-size: 1.5rem; /* Heading size එක mobile වලට ගැළපෙන්න කුඩා කළා */
            }
            .bookmark {
                right: 20px;
                width: 20px;
                height: 45px;
            }
            /* Mobile වලදී 3D Flip එක නිසා text කැපෙන්න පුළුවන් නිසා 3D ආචරණය සරල කළා */
            .book-container.turning .page-flip-layer {
                transform: rotateY(-20deg); 
            }
        }
    </style>
</head>
<body>

   <?php include 'sidebar.php'; ?>
   
    <div class="bg-illustration"></div>

    <main class="main-content">
        <div class="book-perspective">
            <div class="book-container animate__animated animate__zoomIn" id="noticeBook">
                <div class="page-flip-layer"></div>
                <div class="bookmark"></div>

                <div class="d-flex align-items-center gap-2 gap-sm-3 mb-2">
                    <div style="background: rgba(56, 189, 248, 0.1); width: 40px; height: 40px; min-width: 40px; border-radius: 12px; display:flex; align-items:center; justify-content:center;">
                        <i class="fa-solid fa-book-open text-info fs-5"></i>
                    </div>
                    <h2>Publish Class Notice</h2>
                </div>
                <p style="color: var(--text-muted);" class="small mb-4">Unfold the digital registry log to stream custom notifications directly to targeted student scopes.</p>
                
                <?php echo $message; ?>
                
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fa-solid fa-fingerprint"></i> Notification Type</label>
                            <select name="notice_type" class="form-select" required>
                                <option value="general">✨ General Notice</option>
                                <option value="class_cancelled">🚫 Class Cancelled</option>
                                <option value="tute_uploaded">📚 New Tute Uploaded</option>
                                <option value="exam_notice">📝 Exam Notice</option>
                                <option value="holiday">🎉 Holiday Notice</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fa-solid fa-clock"></i> Broadcast Schedule</label>
                            <input type="date" name="notice_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><i class="fa-solid fa-user-tie"></i> Authorized Educator / Instructor</label>
                        <select id="teacher_select" class="form-select" required>
                            <option value="">-- Click to choose teacher & open book --</option>
                            <?php 
                            if ($teachers_result && $teachers_result->num_rows > 0) {
                                while($row = $teachers_result->fetch_assoc()) {
                                    echo "<option value='".$row['id']."'>".$row['name']."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="hidden-fields" id="bookPages">
                        <div class="mb-3">
                            <label class="form-label d-flex flex-wrap align-items-center justify-content-between gap-1">
                                <span><i class="fa-solid fa-layer-group"></i> Target Dynamic Classes</span>
                                <span class="text-warning small" style="font-size: 0.75rem; font-weight: 400;"><i class="fa-solid fa-keyboard"></i> Hold Ctrl/Cmd for multiple</span>
                            </label>
                            <select id="class_select" name="class_ids[]" class="form-select" multiple required>
                                <option disabled selected>Awaiting page initialization...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="fa-solid fa-feather"></i> Notice Header Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Type a catchy informative title..." required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="fa-solid fa-pen-nib"></i> Main Detailed Content</label>
                            <textarea name="content" class="form-control" rows="4" placeholder="Write full bulletin instructions here..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-submit-premium w-100">
                            <i class="fas fa-paper-plane me-2"></i> Transmit Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
    document.getElementById('teacher_select').addEventListener('change', function() {
        const teacherId = this.value; 
        const bookContainer = document.getElementById('noticeBook');
        const hiddenFields = document.getElementById('bookPages');
        const classSelect = document.getElementById('class_select');
        
        if(teacherId === "") {
            bookContainer.classList.remove('open', 'turning');
            hiddenFields.classList.remove('show');
            return;
        }

        bookContainer.classList.add('turning');
        
        setTimeout(() => {
            bookContainer.classList.remove('turning');
            bookContainer.classList.add('open');
            hiddenFields.classList.add('show');
        }, 600);

        classSelect.innerHTML = '<option disabled selected>🔄 Turning pages & fetching logs...</option>';

        fetch('get_teacher_classes.php?teacher_id=' + teacherId)
            .then(response => response.json())
            .then(data => {
                classSelect.innerHTML = '';
                
                if(!data || data.length === 0) {
                    classSelect.innerHTML = '<option disabled selected>❌ No active classes bound to this educator.</option>';
                    return;
                }
                
                data.forEach(cls => {
                    const option = document.createElement('option');
                    option.value = cls.class_id; 
                    option.textContent = `${cls.grade} - ${cls.subject} (${cls.teacher_name})`; 
                    classSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                classSelect.innerHTML = '<option disabled selected>❌ Systems Error loading records.</option>';
            });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>