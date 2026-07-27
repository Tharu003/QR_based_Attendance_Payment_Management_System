<?php 
session_start();
include 'db.php';

// 1. Session Check - ලොග් වී නොමැති නම් Login පිටුවට යොමු කිරීම
if(!isset($_SESSION['student_data'])) {
    header("Location: st_login.php");
    exit();
}

// Session එකෙන් ලොග් වූ ශිෂ්‍යයාගේ ID එක ලබා ගැනීම
$s = $_SESSION['student_data'];
$student_id = mysqli_real_escape_string($conn, $s['student_id']); 

// 2. Student Details ලබා ගැනීම
$student_query = "SELECT * FROM students WHERE student_id = '$student_id'";
$student_result = mysqli_query($conn, $student_query);
$student = mysqli_fetch_assoc($student_result);

if (!$student) {
    die("<div style='text-align:center; padding:50px; color:#fff; font-family:sans-serif;'>Student record not found. Please log in again.</div>");
}

// 3. Log වී සිටින Student ට අදාළ Missing Tutes ලබා ගැනීමේ Query එක
$missing_tutes_query = "
    SELECT 
        cm.id AS material_id,
        cm.title,
        cm.week_no,
        cm.grade,
        cm.material_type,
        cm.file_url,
        cm.created_at,
        cm.class_id,
        c.subject,
        MONTHNAME(cm.created_at) AS tute_month,
        YEAR(cm.created_at) AS tute_year,
        (SELECT COUNT(*) 
         FROM payments p 
         WHERE p.student_id = sc.student_id 
           AND p.class_id = cm.class_id 
           AND (
               p.month = MONTHNAME(cm.created_at) 
               OR MONTH(p.paid_date) = MONTH(cm.created_at)
           )
           AND YEAR(p.paid_date) = YEAR(cm.created_at)
        ) AS is_month_paid
    FROM class_materials cm
    JOIN student_classes sc ON cm.class_id = sc.class_id
    JOIN classes c ON cm.class_id = c.id
    LEFT JOIN student_attempts sa ON cm.id = sa.material_id AND sa.user_id = '$student_id'
    WHERE sc.student_id = '$student_id' 
      AND cm.material_type = 'tute'
      AND sa.id IS NULL
    ORDER BY cm.created_at DESC
";

$missing_tutes_result = mysqli_query($conn, $missing_tutes_query);

// Tute එකක් වෙනම මිළදී ගැනීමේ සාමාන්‍ය ගාස්තුව
$tute_price = 200.00; 

// Distinct Months සොයා ගැනීම
$months_list = [];
$tute_list = [];

if ($missing_tutes_result && mysqli_num_rows($missing_tutes_result) > 0) {
    while ($row = mysqli_fetch_assoc($missing_tutes_result)) {
        $m_key = $row['tute_month'] . ' ' . $row['tute_year'];
        if (!in_array($m_key, $months_list)) {
            $months_list[] = $m_key;
        }
        $tute_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Tutes Vault | Sigma Institute</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;500;600;700&family=Caveat:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Round">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            --bg-dark: #030712;
            --card-bg: rgba(15, 23, 42, 0.65);
            --card-border: rgba(255, 255, 255, 0.08);
            --neon-blue: #3b82f6;
            --neon-purple: #c084fc;
            --neon-pink: #f43f5e;
            --neon-cyan: #06b6d4;
            --gold: #fbbf24;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --success: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', 'Noto Sans Sinhala', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(59, 130, 246, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 85% 75%, rgba(192, 132, 252, 0.1) 0%, transparent 40%);
            background-attachment: fixed;
        }

        .main-content { 
            padding: 20px; 
            max-width: 1400px; 
            margin: 0 auto; 
            transition: all 0.3s ease; 
        }

        @media (min-width: 1025px) {
            .main-content { margin-left: 280px; padding: 40px 50px; }
        }

        /* HERO BANNER */
        .hero-banner {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 27, 75, 0.7) 100%);
            border-radius: 24px;
            padding: 35px;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--card-border);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
            margin-bottom: 30px;
            backdrop-filter: blur(12px);
        }

        .nisadasa-wrapper {
            position: relative;
            z-index: 2;
            padding-left: 20px;
            border-left: 4px solid;
            border-image: linear-gradient(to bottom, var(--neon-blue), var(--neon-purple)) 1;
        }

        .nisadasa-title {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.8;
            background: linear-gradient(90deg, #ffffff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .nisadasa-sub {
            font-family: 'Caveat', cursive;
            font-size: 1.35rem;
            color: var(--neon-purple);
        }

        .user-badge {
            background: rgba(255, 255, 255, 0.05);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.82rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 15px;
        }

        /* ---------------------------------------------------- */
        /* 📖 REALISTIC 3D PAGE TURNING FLIP BOOK ANIMATION */
        /* ---------------------------------------------------- */
        .book-container {
            width: 120px;
            height: 140px;
            perspective: 1000px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .real-3d-book {
            width: 90px;
            height: 120px;
            position: relative;
            transform-style: preserve-3d;
            transform: rotateY(-20deg) rotateX(10deg);
            transition: transform 0.5s ease;
            animation: float3D 4s ease-in-out infinite;
        }

        .real-3d-book:hover {
            transform: rotateY(-5deg) rotateX(0deg) scale(1.05);
        }

        .book-cover-back {
            position: absolute;
            width: 100%;
            height: 100%;
            background: #1e1b4b;
            border-radius: 4px 10px 10px 4px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.8), 0 0 20px rgba(168, 85, 247, 0.3);
            transform: translateZ(-10px);
        }

        .book-spine {
            position: absolute;
            left: 0;
            width: 16px;
            height: 100%;
            background: linear-gradient(to right, #0f172a, #3b82f6);
            transform: rotateY(-90deg) translateZ(8px);
            border-radius: 3px;
        }

        .book-page-static {
            position: absolute;
            right: 4px;
            top: 4px;
            width: 80px;
            height: 112px;
            background: #f8fafc;
            border-radius: 2px 6px 6px 2px;
            box-shadow: inset -3px 0 5px rgba(0,0,0,0.15);
        }

        .book-page-turning {
            position: absolute;
            right: 4px;
            top: 4px;
            width: 80px;
            height: 112px;
            background: #ffffff;
            border-radius: 2px 6px 6px 2px;
            transform-origin: left center;
            animation: flipPage 2.8s cubic-bezier(0.645, 0.045, 0.355, 1) infinite;
            box-shadow: inset -2px 0 4px rgba(0,0,0,0.1);
        }

        .book-cover-front {
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #3b82f6 0%, #a855f7 100%);
            border-radius: 4px 10px 10px 4px;
            transform-origin: left center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-left: 4px solid #1e1b4b;
            color: #fff;
            animation: coverOpen 2.8s cubic-bezier(0.645, 0.045, 0.355, 1) infinite;
            box-shadow: inset 0 0 10px rgba(255,255,255,0.3);
        }

        @keyframes flipPage {
            0% { transform: rotateY(0deg); }
            40%, 100% { transform: rotateY(-160deg); }
        }

        @keyframes coverOpen {
            0% { transform: rotateY(0deg); }
            35%, 100% { transform: rotateY(-35deg); }
        }

        @keyframes float3D {
            0%, 100% { transform: rotateY(-20deg) rotateX(10deg) translateY(0px); }
            50% { transform: rotateY(-15deg) rotateX(5deg) translateY(-10px); }
        }

        /* FILTER & CARDS */
        .filter-section {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 20px 24px;
            margin-bottom: 30px;
        }

        .filter-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        
        .search-box { position: relative; flex-grow: 1; max-width: 300px; }
        .search-box input {
            width: 100%; background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px; padding: 8px 15px 8px 38px;
            color: #fff; font-size: 0.88rem; outline: none;
        }
        .search-box .material-icons-round { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-sub); }

        .month-list-wrapper { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 6px; }
        .month-btn {
            background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-sub); padding: 8px 18px; border-radius: 30px;
            font-size: 0.85rem; font-weight: 600; white-space: nowrap; cursor: pointer;
            display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s;
        }
        .month-btn.active {
            background: linear-gradient(135deg, var(--neon-blue), var(--neon-purple));
            color: #fff; border-color: transparent;
        }

        .tute-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 22px; }
        
        .tute-card {
            background: var(--card-bg); backdrop-filter: blur(16px);
            border: 1px solid var(--card-border); border-radius: 20px;
            padding: 22px; display: flex; flex-direction: column; justify-content: space-between;
            transition: all 0.35s ease;
        }
        .tute-card:hover { transform: translateY(-6px); border-color: rgba(192, 132, 252, 0.3); }

        .month-badge {
            font-size: 0.72rem; font-weight: 700; padding: 5px 12px; border-radius: 20px;
            background: rgba(59, 130, 246, 0.12); color: var(--neon-cyan);
            border: 1px solid rgba(6, 182, 212, 0.2); display: inline-flex; align-items: center; gap: 4px;
        }
        .week-badge { font-size: 0.75rem; font-weight: 800; color: var(--gold); float: right; }
        .subject-tag { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: var(--neon-purple); margin-top: 12px; display: block; }
        .tute-title { font-size: 1.05rem; font-weight: 700; color: #fff; margin: 6px 0 16px 0; line-height: 1.45; }

        .status-box {
            padding: 10px 14px; border-radius: 12px; font-size: 0.8rem; font-weight: 700;
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;
        }
        .status-paid { background: rgba(16, 185, 129, 0.12); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.25); }
        .status-unpaid { background: rgba(251, 191, 36, 0.12); color: var(--gold); border: 1px solid rgba(251, 191, 36, 0.25); }

        .btn-action {
            width: 100%; padding: 11px; border-radius: 12px; font-size: 0.88rem; font-weight: 700;
            text-decoration: none; display: flex; align-items: center; justify-content: center;
            gap: 8px; border: none; cursor: pointer; transition: all 0.3s;
        }
        .btn-download { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; }
        .btn-unlock { background: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%); color: #fff; }

        /* MODAL CUSTOM STYLES FOR DUMMY PAYMENT */
        .modal-content {
            background: #0f172a; border: 1px solid var(--card-border);
            border-radius: 20px; color: #fff;
        }
        .payment-option-card {
            border: 1px solid rgba(255,255,255,0.1); border-radius: 12px;
            padding: 12px; cursor: pointer; transition: all 0.3s;
            background: rgba(255,255,255,0.02);
        }
        .payment-option-card:hover, .payment-option-card.selected {
            border-color: var(--neon-blue); background: rgba(59, 130, 246, 0.15);
        }
    </style>
</head>

<body>

    <?php include 'st_sidebar.php'; ?>
    
    <main class="main-content">
        
        <!-- HERO BANNER WITH NISADASA & 3D BOOK -->
        <header class="hero-banner animate__animated animate__fadeIn">
            <div class="row align-items-center">
                <div class="col-lg-9 col-md-8">
                    <div class="nisadasa-wrapper">
                        <div class="nisadasa-title">
                            "නොනැවතී ඉදිරියටම යන ගමනේ, අතපසු වූ පාඩම් නැවත අතට...<br>
                            දැනුමෙන් අනාගතය සරසා, හෙට දින ජයග්‍රහණය උදෙසා!"
                        </div>
                        <div class="nisadasa-sub">~ Keep Learning, Keep Growing ~</div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <span class="user-badge">
                            <span class="material-icons-round text-info" style="font-size:16px;">account_circle</span>
                            <?php echo htmlspecialchars($student['student_name']); ?>
                        </span>
                        <span class="user-badge">
                            <span class="material-icons-round text-warning" style="font-size:16px;">badge</span>
                            ID: <?php echo htmlspecialchars($student['student_id']); ?>
                        </span>
                    </div>
                </div>
                
                <!-- 3D PAGE TURNING FLIP BOOK -->
                <div class="col-lg-3 col-md-4 text-center text-md-end mt-4 mt-md-0 d-flex justify-content-center justify-content-md-end">
                    <div class="book-container">
                        <div class="real-3d-book">
                            <div class="book-cover-back"></div>
                            <div class="book-page-static"></div>
                            <div class="book-page-turning"></div>
                            <div class="book-cover-front">
                                <span class="material-icons-round" style="font-size:36px;">menu_book</span>
                                <small style="font-size: 8px; margin-top:4px; font-weight:700;">SIGMA</small>
                            </div>
                            <div class="book-spine"></div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- MONTH FILTER LIST SECTION -->
        <div class="filter-section animate__animated animate__fadeInDown">
            <div class="filter-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="material-icons-round text-info">calendar_month</span>
                    <span class="fw-bold text-white" style="font-size: 0.95rem;">Filter by Month</span>
                    <span class="badge bg-primary rounded-pill ms-1" id="tuteBadgeCount"><?php echo count($tute_list); ?></span>
                </div>
                
                <div class="search-box">
                    <span class="material-icons-round">search</span>
                    <input type="text" id="searchInput" onkeyup="filterTutes()" placeholder="Search tute or subject...">
                </div>
            </div>

            <div class="month-list-wrapper" id="monthListWrapper">
                <button class="month-btn active" onclick="selectMonth('ALL', this)">
                    <span class="material-icons-round" style="font-size:15px;">grid_view</span> All Months
                </button>
                <?php foreach($months_list as $m_opt): ?>
                    <button class="month-btn" onclick="selectMonth('<?php echo htmlspecialchars($m_opt); ?>', this)">
                        <span class="material-icons-round" style="font-size:15px;">event</span>
                        <?php echo htmlspecialchars($m_opt); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TUTE CARDS SECTION -->
        <section class="animate__animated animate__fadeInUp">
            <?php if (!empty($tute_list)): ?>
                
                <div class="tute-grid" id="tuteContainer">
                    <?php foreach ($tute_list as $tute): ?>
                        <?php 
                            $is_paid = ($tute['is_month_paid'] > 0);
                            $month_year_group = $tute['tute_month'] . ' ' . $tute['tute_year'];
                            $card_id = "tute_card_" . $tute['material_id'];
                        ?>
                        <div class="tute-card tute-item-card" id="<?php echo $card_id; ?>" data-month="<?php echo htmlspecialchars($month_year_group); ?>" data-search="<?php echo strtolower(htmlspecialchars($tute['title'] . ' ' . $tute['subject'])); ?>">
                            <div>
                                <div>
                                    <span class="month-badge">
                                        <span class="material-icons-round" style="font-size:13px;">calendar_today</span>
                                        <?php echo htmlspecialchars($month_year_group); ?>
                                    </span>
                                    <span class="week-badge">Week <?php echo sprintf("%02d", $tute['week_no']); ?></span>
                                </div>

                                <span class="subject-tag"><?php echo htmlspecialchars($tute['subject']); ?></span>
                                
                                <h3 class="tute-title" title="<?php echo htmlspecialchars($tute['title']); ?>">
                                    <?php echo htmlspecialchars($tute['title']); ?>
                                </h3>
                            </div>

                            <div>
                                <!-- Payment Status Strip -->
                                <div class="status-container">
                                    <?php if ($is_paid): ?>
                                        <div class="status-box status-paid">
                                            <span class="d-flex align-items-center gap-1">
                                                <span class="material-icons-round" style="font-size:16px;">verified</span> Class Fee Paid
                                            </span>
                                            <span class="fw-bold">FREE</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="status-box status-unpaid">
                                            <span class="d-flex align-items-center gap-1">
                                                <span class="material-icons-round" style="font-size:16px;">lock</span> Fee Unpaid
                                            </span>
                                            <span>Rs. <?php echo number_format($tute_price, 0); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Button -->
                                <div class="action-btn-container">
                                    <?php if (!empty($tute['file_url'])): ?>
                                        <?php if ($is_paid): ?>
                                            <a href="<?php echo htmlspecialchars($tute['file_url']); ?>" class="btn-action btn-download" download>
                                                <span class="material-icons-round" style="font-size:18px;">cloud_download</span> Download Tute
                                            </a>
                                        <?php else: ?>
                                            <button class="btn-action btn-unlock" onclick="openDummyPayModal('<?php echo $tute['material_id']; ?>', '<?php echo addslashes($tute['title']); ?>', '<?php echo number_format($tute_price, 2); ?>', '<?php echo htmlspecialchars($tute['file_url']); ?>')">
                                                <span class="material-icons-round" style="font-size:18px;">shopping_bag</span> Unlock Tute
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn-action text-muted" style="background:rgba(255,255,255,0.03); cursor:not-allowed;" disabled>
                                            <span class="material-icons-round" style="font-size:18px;">block</span> File Unavailable
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="text-center py-5">
                    <span class="material-icons-round text-success mb-3" style="font-size: 4rem;">task_alt</span>
                    <h3 class="fw-bold text-white mb-2">Your Tute Vault is Clear!</h3>
                    <p class="text-white-50 m-0">ඔබට ලබා ගැනීමට හිඟ (Pending) Tutes කිසිවක් නොමැත.</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- DUMMY PAYMENT PROCESS MODAL -->
    <div class="modal fade" id="dummyPayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom border-secondary">
                    <h5 class="modal-title d-flex align-items-center gap-2 text-white">
                        <span class="material-icons-round text-primary">verified_user</span> Quick Simulation Payment
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3 text-center">
                        <small class="text-muted d-block mb-1">SELECTED TUTE</small>
                        <h6 id="modalTuteTitle" class="text-warning fw-bold">Sample Tute Title</h6>
                        <h3 class="text-success fw-bold my-2">Rs. <span id="modalTuteAmount">200.00</span></h3>
                    </div>

                    <label class="form-label text-white-50 small">Select Demo Payment Method:</label>
                    <div class="d-flex flex-column gap-2 mb-4">
                        <div class="payment-option-card selected" onclick="selectPayOption(this)">
                            <div class="d-flex align-items-center gap-3">
                                <span class="material-icons-round text-info">credit_card</span>
                                <div>
                                    <div class="fw-bold text-white" style="font-size:0.9rem;">Online Card (Simulation)</div>
                                    <small class="text-muted" style="font-size:0.75rem;">Instant Access without real bank transaction</small>
                                </div>
                            </div>
                        </div>
                        <div class="payment-option-card" onclick="selectPayOption(this)">
                            <div class="d-flex align-items-center gap-3">
                                <span class="material-icons-round text-warning">qr_code_scanner</span>
                                <div>
                                    <div class="fw-bold text-white" style="font-size:0.9rem;">LANKAQR / Digital Wallet</div>
                                    <small class="text-muted" style="font-size:0.75rem;">Simulate QR Scan Payment</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button id="payProcessBtn" class="btn btn-primary w-100 py-2 fw-bold d-flex align-items-center justify-content-center gap-2" onclick="processDummyPayment()">
                        <span class="material-icons-round">lock_open</span> Pay Now & Unlock Instant Access
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedMonthVal = 'ALL';
        let activePayMaterialId = null;
        let activeFileUrl = '';
        let dummyModalObj = null;

        document.addEventListener('DOMContentLoaded', function() {
            dummyModalObj = new bootstrap.Modal(document.getElementById('dummyPayModal'));
        });

        function selectMonth(month, element) {
            selectedMonthVal = month;
            document.querySelectorAll('.month-btn').forEach(btn => btn.classList.remove('active'));
            element.classList.add('active');
            filterTutes();
        }

        function filterTutes() {
            const searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.tute-item-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const cardMonth = card.getAttribute('data-month');
                const cardSearchData = card.getAttribute('data-search');

                const matchesMonth = (selectedMonthVal === 'ALL' || cardMonth === selectedMonthVal);
                const matchesSearch = (searchQuery === '' || cardSearchData.includes(searchQuery));

                if (matchesMonth && matchesSearch) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const badgeCount = document.getElementById('tuteBadgeCount');
            if(badgeCount) badgeCount.textContent = visibleCount;
        }

        // DUMMY PAYMENT PROCESSORS
        function openDummyPayModal(materialId, title, amount, fileUrl) {
            activePayMaterialId = materialId;
            activeFileUrl = fileUrl;
            document.getElementById('modalTuteTitle').textContent = title;
            document.getElementById('modalTuteAmount').textContent = amount;
            dummyModalObj.show();
        }

        function selectPayOption(element) {
            document.querySelectorAll('.payment-option-card').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
        }

        function processDummyPayment() {
            const btn = document.getElementById('payProcessBtn');
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> Processing Payment...`;

            // SIMULATE API/BANK DELAY (1.5 SECONDS)
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = `<span class="material-icons-round">lock_open</span> Pay Now & Unlock Instant Access`;
                dummyModalObj.hide();

                // UPDATE UI DYNAMICALLY (INSTANT UNLOCK DEMO)
                const targetCard = document.getElementById('tute_card_' + activePayMaterialId);
                if (targetCard) {
                    // Update status badge
                    const statusContainer = targetCard.querySelector('.status-container');
                    statusContainer.innerHTML = `
                        <div class="status-box status-paid animate__animated animate__flipInX">
                            <span class="d-flex align-items-center gap-1">
                                <span class="material-icons-round" style="font-size:16px;">verified</span> Unlocked (Paid)
                            </span>
                            <span class="fw-bold">SUCCESS</span>
                        </div>
                    `;

                    // Update action button to Download
                    const btnContainer = targetCard.querySelector('.action-btn-container');
                    btnContainer.innerHTML = `
                        <a href="${activeFileUrl}" class="btn-action btn-download animate__animated animate__bounceIn" download>
                            <span class="material-icons-round" style="font-size:18px;">cloud_download</span> Download Tute
                        </a>
                    `;
                }

                alert("🎉 Payment Successful! Your Tute has been unlocked and is ready for download.");
            }, 1500);
        }
    </script>
</body>
</html>