<?php
// Session eka start karala log wela innawada balanna
if(session_status() == PHP_SESSION_NONE){
    session_start();
}

// Student kenek nemenam login page ekata yawanna
//if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    //header("Location: login.php"); 
    //exit();
//}

include 'db.php'; // ඔයාගේ db.php එක තියෙන තැන හරියටම දාන්න

// පරණ පේළිය අයින් කරලා මේක දාන්න
$student_id = $_SESSION['student_data']['student_id'] ?? 6; 

// 1. මේ ශිෂ්‍යයාගේ ප්‍රතිඵල ලේඛනය ලබා ගැනීම
$query = "SELECT e.exam_title, e.exam_date, r.marks_obtained, c.subject 
          FROM exam_results r
          JOIN exams e ON r.exam_id = e.id
          JOIN classes c ON e.class_id = c.id
          WHERE r.student_id = ?
          ORDER BY e.exam_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

// 2. අලුතින් එකතු කරන්න: ශිෂ්‍යයාගේ Analytics (Average, Max, Total)
$stats_query = "SELECT AVG(marks_obtained) as avg_marks, MAX(marks_obtained) as max_marks, COUNT(*) as total_exams 
                FROM exam_results 
                WHERE student_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $student_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Variables අගයන් සකසා ගැනීම
$avg_marks = isset($stats['avg_marks']) ? round($stats['avg_marks'], 1) : 0;
$max_marks = $stats['max_marks'] ?? 0;
$total_exams = $stats['total_exams'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Exam Results | SIGMA ERP</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Serif+Sinhala:wght@400;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #030712;
            --card: rgba(17, 24, 39, 0.7);
            --sidebar: #0b1220;
            --border: rgba(255, 255, 255, 0.06);
            --gold: #fbbf24;
            --blue: #2563eb;
            --text: #f8fafc;
            --muted: #94a3b8;
            --purple: #8b5cf6;
            --emerald: #10b981;
            --glow: rgba(251, 191, 36, 0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: var(--bg); color: var(--text); font-family: 'Plus Jakarta Sans', sans-serif; overflow-x: hidden; }

        /* Background Art Vectors */
        .bg-glow-1 { position: fixed; top: -200px; right: -200px; width: 600px; height: 600px; background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%); z-index: -1; pointer-events: none; }
        .bg-glow-2 { position: fixed; bottom: -200px; left: -200px; width: 600px; height: 600px; background: radial-gradient(circle, rgba(139,92,246,0.08) 0%, transparent 70%); z-index: -1; pointer-events: none; }

        /* ---------------- MOBILE NAVIGATION ---------------- */
        .mobile-navbar {
            display: none;
            background: var(--sidebar);
            border-bottom: 1px solid var(--border);
            padding: 15px 20px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }

        /* ---------------- MAIN CONTAINER ---------------- */
        .main-content { margin-left: 280px; padding: 50px 40px; position: relative; z-index: 1; min-height: 100vh; transition: all 0.3s ease; }

        .welcome-badge {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
            border: 1px solid rgba(255,255,255,0.05);
            padding: 6px 16px; border-radius: 100px; display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #93c5fd; width: fit-content;
        }

        /* Nisadasa (Poem Card) Style */
        .poem-card {
            background: linear-gradient(135deg, rgba(17, 24, 39, 0.9), rgba(11, 18, 32, 0.9));
            border: 1px solid rgba(251, 191, 36, 0.15);
            border-radius: 24px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 0 20px rgba(251, 191, 36, 0.02);
        }
        .poem-card::before {
            content: '“';
            position: absolute;
            right: 30px;
            bottom: -20px;
            font-size: 150px;
            color: rgba(251, 191, 36, 0.03);
            font-family: Georgia, serif;
            line-height: 1;
        }
        .poem-title {
            font-family: 'Noto Serif Sinhala', serif;
            font-size: 1.15rem;
            color: var(--gold);
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }
        .poem-line {
            font-family: 'Noto Serif Sinhala', serif;
            font-size: 0.95rem;
            color: #cbd5e1;
            line-height: 1.8;
            margin: 0;
            font-style: italic;
        }

        /* Analytics Top Cards */
        .stat-card {
            background: var(--card); border: 1px solid var(--border); border-radius: 24px; padding: 25px;
            backdrop-filter: blur(12px); transition: all 0.4s ease; position: relative; overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); border-color: rgba(255,255,255,0.12); box-shadow: 0 15px 30px rgba(0,0,0,0.5); }
        .stat-card::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(to right, transparent, rgba(255,255,255,0.03), transparent);
            transform: skewX(-25deg); transition: 0.75s;
        }
        .stat-card:hover::after { left: 125%; }

        /* Medals & Badges Design */
        .medal-box { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 6px 14px; border-radius: 100px; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; white-space: nowrap; width: fit-content; }
        
        .medal-elite { background: linear-gradient(135deg, rgba(251, 191, 36, 0.15), rgba(217, 119, 6, 0.15)); border: 1px solid rgba(251, 191, 36, 0.3); color: #fef08a; animation: pulseGlow 2s infinite alternate; }
        .medal-merit { background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(29, 78, 216, 0.15)); border: 1px solid rgba(59, 130, 246, 0.3); color: #bfdbfe; }
        .medal-effort { background: linear-gradient(135deg, rgba(244, 63, 94, 0.1), rgba(159, 18, 57, 0.1)); border: 1px solid rgba(244, 63, 94, 0.25); color: #fecdd3; }

        @keyframes pulseGlow {
            0% { box-shadow: 0 0 10px rgba(251, 191, 36, 0.1); }
            100% { box-shadow: 0 0 20px rgba(251, 191, 36, 0.3); }
        }

        /* Glass Table Wrap */
        .glass-table-wrapper { background: var(--card); border: 1px solid var(--border); border-radius: 24px; padding: 15px; backdrop-filter: blur(20px); box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .table { margin: 0; color: #cbd5e1; }
        .table thead th { background: transparent !important; color: var(--muted) !important; font-weight: 600; text-transform: uppercase; font-size: 0.78rem; letter-spacing: 1px; padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .table tbody td { background: transparent !important; padding: 22px 20px; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .table tbody tr { transition: all 0.2s ease; }
        .table tbody tr:hover { background: rgba(255, 255, 255, 0.02) !important; transform: scale(1.005); }

        .score-glow { font-size: 1.2rem; font-weight: 800; color: #fff; text-shadow: 0 0 10px rgba(255,255,255,0.2); }

        /* ---------------- RESPONSIVE MEDIA QUERIES ---------------- */
        @media (max-width: 992px) { 
            .sidebar { display: none !important; } 
            .mobile-navbar { display: flex; justify-content: space-between; align-items: center; }
            .main-content { margin-left: 0; padding: 100px 20px 40px 20px; } 
            
            .poem-card { padding: 20px; }
            .poem-title { font-size: 1.05rem; }
            .poem-line { font-size: 0.88rem; line-height: 1.6; }
            
            .stat-card { padding: 20px; }
            .stat-card h2 { font-size: 1.5rem; }
        }

        @media (max-width: 576px) {
            .main-content h1 { font-size: 1.75rem; }
            .table thead th { padding: 12px; font-size: 0.7rem; }
            .table tbody td { padding: 14px 12px; font-size: 0.85rem; }
            .score-glow { font-size: 1rem; }
            .medal-box { padding: 4px 10px; font-size: 0.75rem; }
        }
    </style>
</head>
<body>

    <div class="bg-glow-1"></div>
    <div class="bg-glow-2"></div>

    <!-- Mobile Top Navigation Bar -->
    <div class="mobile-navbar">
        <span class="text-white fw-bold" style="letter-spacing: 0.5px;">SIGMA ERP</span>
        <button class="btn btn-outline-light btn-sm border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas">
            <i class="fa-solid fa-bars fs-4 text-warning"></i>
        </button>
    </div>

    <!-- Include original sidebar for desktop -->
    <?php include 'st_sidebar.php'; ?>

    <!-- Offcanvas Container for Sidebar on Mobile (If st_sidebar supports it or you need a toggleable side menu) -->
    <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarOffcanvas" style="width: 280px; background-color: var(--sidebar) !important; border-right: 1px solid var(--border);">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title text-warning fw-bold">SIGMA MENU</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <!-- Sidebar file eka require karala thiyෙන nisa, mobile menu ekatath e links dynamic widiyata use karanna puluwani -->
            <?php @include 'st_sidebar.php'; ?>
        </div>
    </div>

    <div class="main-content">
        <div class="container-fluid m-0 p-0">
            
            <div class="d-flex flex-column gap-2 mb-4 animate__animated animate__fadeInDown">
                <div class="welcome-badge">
                    <span class="spinner-grow spinner-grow-sm text-warning" role="status"></span>
                    <span>Secure Token Active</span>
                </div>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h1 class="m-0" style="font-weight:800; letter-spacing: -0.5px;">My Academic Progress</h1>
                    <h5 class="m-0" style="font-weight: 600; background: rgba(255,255,255,0.03); padding: 8px 18px; border-radius: 12px; border: 1px solid var(--border);">
                        Student: <span style="color: var(--gold); font-weight:700;"><?= htmlspecialchars($_SESSION['student_data']['student_name'] ?? 'Scholar'); ?></span>
                    </h5>
                </div>
            </div>

            <div class="poem-card mb-5 animate__animated animate__fadeIn">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="poem-title"><i class="fa-solid fa-feather-pointed me-2"></i>සිහින සැබෑකරන සිග්මා පියවර...</div>
                        <p class="poem-line">කඳු මුදුනක් වෙත යන ගමනේ, වෙහෙස නිවාලන මල් පිපුණාවේ,</p>
                        <p class="poem-line">ලකුණු පෙළක ඇති ජය පරාජය, හෙට දින දිනනා සවියක් වේවා!</p>
                        <p class="poem-line">නොනැවතී පෙරටම යන්න දිරියගෙන, ඔබේ ලොවම හෙට එළිය කරනු මැන.</p>
                    </div>
                    <div class="col-lg-4 text-end d-none d-lg-block">
                        <i class="fa-solid fa-graduation-cap" style="font-size: 5.5rem; color: rgba(251, 191, 36, 0.08); transform: rotate(-15deg);"></i>
                    </div>
                </div>
            </div>

            <div class="row g-3 g-md-4 mb-5 animate__animated animate__fadeIn">
                <div class="col-sm-4">
                    <div class="stat-card d-flex align-items-center justify-content-between">
                        <div>
                            <p class="small text-uppercase text-muted mb-1 fw-bold">Your Average</p>
                            <h2 class="m-0 text-white" style="font-weight:800;"><?= $avg_marks; ?><span style="font-size:1rem; color:var(--muted);"> /100</span></h2>
                        </div>
                        <div style="background: rgba(59, 130, 246, 0.1); width: 50px; height: 50px; border-radius: 14px; display:flex; align-items:center; justify-content:center;">
                            <i class="fa-solid fa-chart-line text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="stat-card d-flex align-items-center justify-content-between">
                        <div>
                            <p class="small text-uppercase text-muted mb-1 fw-bold">Highest Score</p>
                            <h2 class="m-0" style="font-weight:800; color: var(--gold);"><?= $max_marks; ?><span style="font-size:1rem; color:var(--muted);"> /100</span></h2>
                        </div>
                        <div style="background: rgba(251, 191, 36, 0.1); width: 50px; height: 50px; border-radius: 14px; display:flex; align-items:center; justify-content:center;">
                            <i class="fa-solid fa-crown text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="stat-card d-flex align-items-center justify-content-between">
                        <div>
                            <p class="small text-uppercase text-muted mb-1 fw-bold">Total Exams</p>
                            <h2 class="m-0 text-white" style="font-weight:800;"><?= $total_exams; ?><span style="font-size:1rem; color:var(--muted);"> Faced</span></h2>
                        </div>
                        <div style="background: rgba(16, 185, 129, 0.1); width: 50px; height: 50px; border-radius: 14px; display:flex; align-items:center; justify-content:center;">
                            <i class="fa-solid fa-graduation-cap text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-table-wrapper animate__animated animate__fadeInUp">
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Exam Title</th>
                                    <th>Date</th>
                                    <th>Marks</th>
                                    <th>Performance Rank</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): 
                                    $score = floatval($row['marks_obtained']);
                                ?>
                                    <tr>
                                        <td class="fw-bold text-white">
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--blue); box-shadow: 0 0 8px var(--blue); shrink: 0;"></div>
                                                <span class="text-nowrap"><?= htmlspecialchars($row['subject']); ?></span>
                                            </div>
                                        </td>
                                        <td><div style="min-width: 120px;"><?= htmlspecialchars($row['exam_title']); ?></div></td>
                                        <td class="text-muted" style="font-size: 0.9rem;"><div style="min-width: 100px;"><i class="fa-regular fa-calendar me-2"></i><?= date('Y-m-d', strtotime($row['exam_date'])); ?></div></td>
                                        <td><span class="score-glow"><?= number_format($score, 0); ?>%</span></td>
                                        
                                        <td>
                                            <?php if($score >= 75): ?>
                                                <div class="medal-box medal-elite">
                                                    <i class="fa-solid fa-medal me-1"></i> Elite Scholar
                                                </div>
                                            <?php elseif($score >= 45): ?>
                                                <div class="medal-box medal-merit">
                                                    <i class="fa-solid fa-award me-1"></i> Merit Pass
                                                </div>
                                            <?php else: ?>
                                                <div class="medal-box medal-effort animate__animated animate__headShake animate__infinite animate__slower">
                                                    <i class="fa-solid fa-bolt-lightning me-1"></i> Keep Pushing
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 my-4">
                        <div style="background: rgba(255,255,255,0.02); width:80px; height:80px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:20px; border: 1px solid var(--border);">
                            <i class="fas fa-folder-open text-muted fs-2"></i>
                        </div>
                        <h4 class="fw-bold text-white mb-2">No Records Found</h4>
                        <p class="text-muted mx-auto" style="max-width: 360px; font-size:0.9rem;">Your token has no exam logs assigned to it at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>