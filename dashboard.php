<?php
/** @var mysqli $conn */
session_start();
date_default_timezone_set("Asia/Colombo");
include "db.php"; 

// 1. ආරක්ෂක පරීක්ෂාව (අවසර ලත් සියලුම Roles පරීක්ෂා කිරීම)
$allowed_roles = ['admin', 'teacher', 'assistant','superadmin'];

if(!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)){
    header("Location: login.php");
    exit();
}

// පරිශීලකයාගේ භූමිකාව සහ නම ලබා ගැනීම
$user_role = $_SESSION['role'];
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : "User");

// --- දත්ත ලබා ගැනීම (Real-time Data Fetching) ---

// 1. මුළු ශිෂ්‍ය සංඛ්‍යාව
$resStudents = mysqli_query($conn, "SELECT COUNT(*) as total FROM students");
$totalStudents = ($resStudents) ? mysqli_fetch_assoc($resStudents)['total'] : 0;

// 2. මුළු පන්ති සංඛ්‍යාව
$resClasses = mysqli_query($conn, "SELECT COUNT(*) as total FROM classes");
$totalClasses = ($resClasses) ? mysqli_fetch_assoc($resClasses)['total'] : 0;

// 3. අද දින පැමිණීම
$today = date('Y-m-d');
$resAttendance = mysqli_query($conn, "SELECT COUNT(*) as total FROM attendance WHERE date = '$today'");
$todayAttendance = ($resAttendance) ? mysqli_fetch_assoc($resAttendance)['total'] : 0;

// 4. මාසික ආදායම (ආරක්ෂිත පියවරක්: ආදායම් විස්තර පෙන්විය යුත්තේ Admin ට පමණක් නම්)
$monthlyIncome = 0;
if($user_role === 'admin') {
    $currentMonth = date('m');
    $currentYear = date('Y');
    $resIncome = mysqli_query($conn, "SELECT SUM(amount) as total FROM payments WHERE MONTH(paid_date) = '$currentMonth' AND YEAR(paid_date) = '$currentYear'");
    $incomeRow = mysqli_fetch_assoc($resIncome);
    $monthlyIncome = ($incomeRow['total']) ? $incomeRow['total'] : 0;
}

// 5. Today's Summary සහ පන්ති ශ්‍රේණි දත්ත (Grade Breakdown)
$resTodayReg = mysqli_query($conn, "SELECT COUNT(*) as total FROM students WHERE DATE(registered_date) = '$today'");
$todayRegistrations = ($resTodayReg) ? mysqli_fetch_assoc($resTodayReg)['total'] : 0;

$resTodayPay = mysqli_query($conn, "SELECT COUNT(*) as total FROM payments WHERE DATE(paid_date) = '$today'");
$todayPayments = ($resTodayPay) ? mysqli_fetch_assoc($resTodayPay)['total'] : 0;

// ERROR FIX: gender වෙනුවට ඔයාගේ table එකේ තියෙන registered_grade එකෙන් වැඩිම සිසුන් ඉන්න පන්ති 2ක විස්තර dynamic ගන්නවා
$resGrades = mysqli_query($conn, "SELECT registered_grade, COUNT(*) as count FROM students GROUP BY registered_grade ORDER BY count DESC LIMIT 2");
$gradeData = [];
while($row = mysqli_fetch_assoc($resGrades)) {
    $gradeData[] = $row;
}
$grade1_name = isset($gradeData[0]) ? $gradeData[0]['registered_grade'] : 'N/A';
$grade1_count = isset($gradeData[0]) ? $gradeData[0]['count'] : 0;
$grade2_name = isset($gradeData[1]) ? $gradeData[1]['registered_grade'] : 'N/A';
$grade2_count = isset($gradeData[1]) ? $gradeData[1]['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SIGMA ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content { 
            margin-left: var(--sidebar-w); 
            padding: 40px; 
            flex: 1;
        }

        /* Header Clock Area */
        .header-status {
            background: var(--card-dark);
            border: 1px solid #222;
            padding: 10px 22px;
            border-radius: 50px;
        }

        /* Welcome Card - Optimized Spacing */
        .welcome-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 24px;
            padding: 35px 40px;
            color: white;
            position: relative;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.6);
        }
        .sinhala-quote {
            font-size: 0.95rem;
            color: var(--electric-blue);
            opacity: 0.9;
            margin-bottom: 8px;
            font-weight: 400;
            letter-spacing: 0.5px;
        }
        .admin-title {
            font-size: 2.3rem;
            font-weight: 800;
            margin-bottom: 6px;
            line-height: 1.2;
        }
        .welcome-desc {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0;
        }

        /* Stat Cards */
        .stat-card {
            background: var(--card-dark);
            border: 1px solid #222;
            border-radius: 24px;
            padding: 28px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .stat-card:hover { 
            transform: translateY(-10px); 
            border-color: var(--accent-blue);
            box-shadow: 0 15px 30px rgba(59, 130, 246, 0.1);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.03);
        }
        .icon-blue { color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); }
        .icon-green { color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .icon-orange { color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }
        .icon-purple { color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.3); }

        /* Quick Action Buttons */
        .quick-action-btn {
            border-radius: 16px;
            padding: 16px 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid #222;
            background: #111115;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #e2e8f0;
            margin-bottom: 12px;
        }
        .quick-action-btn:last-child {
            margin-bottom: 0;
        }
        .quick-action-btn:hover {
            background: #1c1c22;
            border-color: var(--accent-blue);
            color: var(--accent-blue);
            padding-left: 25px;
        }

        /* Insight / Control Cards */
        .insight-card {
            background: var(--card-dark);
            border-radius: 24px;
            border: 1px solid #222;
        }
        .progress-circle-box {
            background: rgba(59, 130, 246, 0.05);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            border: 1px solid rgba(59, 130, 246, 0.1);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-dark); }
        ::-webkit-scrollbar-thumb { background: #222; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #333; }

        /* Mobile View Fixes */
        @media (max-width: 992px) {
            .sidebar { width: 70px; }
            .sidebar-brand span, .nav-link span, .sinhala-quote, .logout-container span { display: none; }
            .main-content { margin-left: 70px; padding: 20px; }
            .sidebar-brand { justify-content: center; padding: 20px 0; }
            .nav-link { margin: 5px; justify-content: center; padding: 15px 0; }
            .welcome-card { padding: 25px; }
            .admin-title { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h3 class="fw-bold mb-1">Academy Overview</h3>
            <p class="text-secondary small mb-0">සජීවී දත්ත විශ්ලේෂණය මෙතැනින් පරීක්ෂා කරන්න.</p>
        </div>
        <div class="header-status d-flex align-items-center">
            <div class="text-end me-3">
                <div class="small fw-bold text-secondary" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">
                    <?php echo date("l, d F"); ?>
                </div>
                <div class="text-white fw-bold" id="liveTime" style="font-size: 1.1rem;"><?php echo date("h:i:s A"); ?></div>
            </div>
            <div class="ms-2 p-2 rounded-circle bg-primary bg-opacity-10">
                <i class="fas fa-clock text-primary"></i>
            </div>
        </div>
    </div>

    <div class="welcome-card animate__animated animate__fadeIn">
        <div class="row align-items-center">
            <div class="col-md-9">
                <div class="sinhala-quote">
                    "නැණ පහනින් ලොව එළිය කරන්නට... සිසු පරපුරේ හෙට දවස තනන්නට..."
                </div>
                <h1 class="admin-title">ආයුබෝවන්, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p class="welcome-desc">සිග්මා කළමනාකරණ පද්ධතියට ඔබ සාදරයෙන් පිළිගනිමු.</p>
            </div>
            <div class="col-md-3 text-end d-none d-md-block">
                <i class="fas fa-shield-halved" style="font-size: 90px; color: var(--accent-blue); opacity: 0.15;"></i>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-3">
            <div class="stat-card animate__animated animate__fadeInUp">
                <div class="stat-icon icon-blue"><i class="fas fa-user-graduate"></i></div>
                <div class="text-secondary small fw-bold text-uppercase">Total Students</div>
                <h2 class="fw-bold mt-2 mb-0"><?php echo number_format($totalStudents); ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                <div class="stat-icon icon-green"><i class="fas fa-chalkboard-user"></i></div>
                <div class="text-secondary small fw-bold text-uppercase">Active Classes</div>
                <h2 class="fw-bold mt-2 mb-0"><?php echo number_format($totalClasses); ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <div class="stat-icon icon-orange"><i class="fas fa-user-check"></i></div>
                <div class="text-secondary small fw-bold text-uppercase">Today Attendance</div>
                <h2 class="fw-bold mt-2 mb-0"><?php echo number_format($todayAttendance); ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                <div class="stat-icon icon-purple"><i class="fas fa-coins"></i></div>
                <div class="text-secondary small fw-bold text-uppercase">Monthly Revenue</div>
                <h2 class="fw-bold mt-2 mb-0">LKR <?php echo number_format($monthlyIncome); ?></h2>
            </div>
        </div>
    </div>

    <div class="row mt-4 mb-4">
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="insight-card p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <h5 class="fw-bold mb-4 d-flex align-items-center">
                        <span class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 me-3"><i class="fas fa-chart-line"></i></span>
                        System Analytics
                    </h5>
                    
                    <div class="progress-circle-box mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-8 text-start">
                                <h6 class="fw-bold text-white mb-1">පැමිණීමේ ප්‍රතිශතය (Attendance Rate)</h6>
                                <p class="text-secondary small mb-0">අද දින ලියාපදිංචි ශිෂ්‍ය සංඛ්‍යාවට සාපේක්ෂව පැමිණීම.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <?php 
                                    $percent = ($totalStudents > 0) ? round(($todayAttendance / $totalStudents) * 100) : 0;
                                ?>
                                <div class="display-5 fw-extrabold text-primary"><?php echo $percent; ?>%</div>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 8px; background: #222;">
                            <div class="progress-bar bg-primary" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-secondary mb-3" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">
                            <i class="fas fa-layer-group text-info me-2"></i> Live System Analytics & Statistics
                        </h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 rounded-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04);">
                                    <div class="text-secondary small mb-1" style="font-size: 0.8rem;">New Registrations</div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <h4 class="fw-bold text-white mb-0"><?php echo sprintf("%02d", $todayRegistrations); ?></h4>
                                        <span class="badge bg-success bg-opacity-10 text-success" style="font-size: 0.65rem; font-weight: 500;">+ Today</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04);">
                                    <div class="text-secondary small mb-1" style="font-size: 0.8rem;">Payments Collected</div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <h4 class="fw-bold text-white mb-0"><?php echo sprintf("%02d", $todayPayments); ?></h4>
                                        <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 0.65rem; font-weight: 500;">Invoices</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-6">
                                <div class="p-3 rounded-4" style="background: rgba(59, 130, 246, 0.02); border: 1px solid rgba(255,255,255,0.04);">
                                    <div class="text-secondary small mb-2" style="font-size: 0.8rem;"><i class="fas fa-graduation-cap me-1 text-primary"></i> Top Grades Distribution</div>
                                    <div class="d-flex justify-content-between align-items-center small text-white-50">
                                        <span><?php echo htmlspecialchars($grade1_name); ?>: <strong class="text-white"><?php echo $grade1_count; ?> Studs</strong></span>
                                        <span><?php echo htmlspecialchars($grade2_name); ?>: <strong class="text-white"><?php echo $grade2_count; ?> Studs</strong></span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="p-3 rounded-4" style="background: rgba(16, 185, 129, 0.02); border: 1px solid rgba(255,255,255,0.04);">
                                    <div class="text-secondary small mb-2" style="font-size: 0.8rem;"><i class="fas fa-server me-1 text-success"></i> Core Engine</div>
                                    <div class="d-flex align-items-center gap-2 small">
                                        <span class="d-inline-block rounded-circle bg-success animate__animated animate__flash animate__infinite" style="width: 8px; height: 8px;"></span>
                                        <span class="text-success fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">SYSTEM OPERATIONAL</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded-4 border border-info border-opacity-10 bg-info bg-opacity-10 d-flex align-items-center mt-auto">
                    <i class="fas fa-circle-info me-3 fs-5 text-info"></i>
                    <div class="small text-info">ගෙවීම් සහ අනෙකුත් වාර්තා ලබා ගැනීමට වම්පස ඇති 'Reports' මෙනුව භාවිතා කරන්න.</div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 d-flex flex-column gap-4">
            <div class="insight-card p-4 flex-grow-1">
                <h5 class="fw-bold mb-4">Quick Control</h5>
                <a href="attendance.php" class="quick-action-btn">
                    <i class="fas fa-fingerprint me-3 text-primary"></i> Mark Attendance
                </a>
                <a href="students.php" class="quick-action-btn">
                    <i class="fas fa-user-plus me-3 text-success"></i> New Registration
                </a>
                <a href="payment.php" class="quick-action-btn">
                    <i class="fas fa-receipt me-3 text-warning"></i> Collect Payments
                </a>
                <a href="reports.php" class="quick-action-btn">
                    <i class="fas fa-file-contract me-3 text-danger"></i> Generate Reports
                </a>
            </div>

            <div class="insight-card p-4 animate__animated animate__fadeIn">
                <h6 class="fw-bold mb-3 text-secondary" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-headset me-2 text-primary"></i> System Support & Contact
                </h6>
                <div class="d-flex flex-column gap-3" style="font-size: 0.85rem; color: #94a3b8;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded bg-white bg-opacity-5 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border: 1px solid rgba(255,255,255,0.05);">
                            <i class="fas fa-phone text-success" style="font-size: 0.8rem;"></i>
                        </div>
                        <span class="fw-medium text-white-50">+94 77 123 4567</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded bg-white bg-opacity-5 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border: 1px solid rgba(255,255,255,0.05);">
                            <i class="fas fa-envelope text-primary" style="font-size: 0.8rem;"></i>
                        </div>
                        <span class="fw-medium text-white-50">support@sigmaacademy.lk</span>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded bg-white bg-opacity-5 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; border: 1px solid rgba(255,255,255,0.05);">
                            <i class="fas fa-location-dot text-danger" style="font-size: 0.8rem;"></i>
                        </div>
                        <span class="fw-medium text-white-50" style="line-height: 1.4;">No. 45, Academy Road,<br>Colombo 03, Sri Lanka.</span>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top border-secondary border-opacity-10 text-center text-md-start">
                    <span class="text-secondary" style="font-size: 0.7rem;">&copy; <?php echo date('Y'); ?> SIGMA ERP v2.5 | Secure Mode</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateClock() {
        const now = new Date();
        const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
        document.getElementById('liveTime').innerText = now.toLocaleTimeString('en-US', options);
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>