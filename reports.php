<?php
include "db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$months = ["January","February","March","April","May","June","July","August","September","October","November","December"];

// Subject ලැයිස්තුව ලබා ගැනීම
$sub_query = mysqli_query($conn,"SELECT DISTINCT subject FROM classes");
$subjects = [];
if($sub_query) {
    while($s = mysqli_fetch_assoc($sub_query)){
        $subjects[] = $s['subject'];
    }
}

$month = $_GET['month'] ?? "";
$subject = $_GET['subject'] ?? "";

/* ---------- Main Query ---------- */
$sql = "SELECT p.student_id, s.student_name, p.month, p.amount, p.paid_date, c.subject 
        FROM payments p 
        JOIN students s ON p.student_id=s.student_id 
        JOIN classes c ON p.class_id=c.id 
        WHERE 1 ";

if($month!=""){
    $sql.=" AND p.month='".mysqli_real_escape_string($conn,$month)."'";
}

if($subject!=""){
    $sql.=" AND c.subject='".mysqli_real_escape_string($conn,$subject)."'";
}

$sql.=" ORDER BY p.paid_date DESC";
$result = mysqli_query($conn,$sql);

// Statistics ගණනය කිරීම්
$total_records = mysqli_num_rows($result);
$total_revenue = 0;
$records_array = [];
while($row = mysqli_fetch_assoc($result)) {
    $total_revenue += $row['amount'];
    $records_array[] = $row; 
}

/* ---------- Bar Chart Query (Monthly) ---------- */
$chart_query = mysqli_query($conn,
"SELECT month, SUM(amount) total 
 FROM payments 
 GROUP BY month"
);

$chart_labels=[];
$chart_values=[];
while($row=@mysqli_fetch_assoc($chart_query)){
    $chart_labels[]=$row['month'];
    $chart_values[]=$row['total'];
}

/* ---------- Pie Chart Query (Subject Wise) ---------- */
$pie_query = mysqli_query($conn,
"SELECT c.subject, SUM(p.amount) total 
 FROM payments p
 JOIN classes c ON p.class_id=c.id
 GROUP BY c.subject"
);

$pie_labels=[];
$pie_values=[];
while($row=@mysqli_fetch_assoc($pie_query)){
    $pie_labels[]=$row['subject'];
    $pie_values[]=$row['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Portal | Financial Studio Pro</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Serif+Sinhala:wght@400;700&display=swap" rel="stylesheet">

    <style>
       :root { 
            --bg-dark: #070a13;
            --panel-glass: rgba(13, 20, 38, 0.45);
            --border-neon: rgba(96, 165, 250, 0.15);
            --accent-cyan: #06b6d4;
            --accent-blue: #3b82f6;
            --accent-purple: #a855f7;
            --accent-pink: #f43f5e;
            --text-glow: 0 0 12px rgba(6, 182, 212, 0.4);
        }

        body { 
            background-color: var(--bg-dark); 
            color: #f4f4f5;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            min-height: 100vh;
            overflow-x: hidden;
            background-image: radial-gradient(circle at 10% 10%, rgba(96, 165, 250, 0.06) 0%, transparent 40%),
                              radial-gradient(circle at 90% 80%, rgba(168, 85, 247, 0.05) 0%, transparent 40%),
                              radial-gradient(circle at 50% 50%, rgba(6, 182, 212, 0.03) 0%, transparent 50%);
            background-attachment: fixed;
        }

        .main-content { 
            margin-left: 280px; 
            padding: 3rem 2.5rem; 
            transition: all 0.3s ease; 
        }

        /* Premium Glassmorphic Card */
        .glass-card { 
            background: var(--panel-glass); 
            border-radius: 24px; 
            border: 1px solid var(--border-neon); 
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4); 
            backdrop-filter: blur(25px); 
            -webkit-backdrop-filter: blur(25px);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }
        .glass-card::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.03), transparent);
            transition: 0.5s;
        }
        .glass-card:hover::before { left: 100%; }
        .glass-card:hover { 
            transform: translateY(-5px); 
            border-color: rgba(6, 182, 212, 0.3);
            box-shadow: 0 30px 60px rgba(6, 182, 212, 0.1);
        }

        /* Title Style */
        .dashboard-title {
            font-size: 2.2rem; font-weight: 800;
            background: linear-gradient(135deg, #ffffff 30%, #94a3b8 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .sinhala-sub { font-family: 'Noto Serif Sinhala', serif; color: #64748b; font-size: 0.95rem; font-weight: 400; }

        /* Premium Inputs */
        .form-select { 
            border-radius: 16px; padding: 0.9rem 1.2rem; border: 1px solid var(--border-neon); 
            background: rgba(8, 14, 27, 0.9); color: #e4e4e7; font-weight: 500;
            cursor: pointer; transition: all 0.3s ease;
        }
        .form-select:focus { 
            background: #080e1b; color: white; border-color: var(--accent-cyan); 
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.25); 
        }

        .custom-table{
            width:100%;
            color:#e2e8f0 !important;
            border-collapse:separate;
            border-spacing:0 10px;
        }

        .custom-table thead tr{ background:transparent; }

        .custom-table th{
            color:#94a3b8 !important;
            font-size:.75rem;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:1px;
            border:none !important;
            padding:16px;
        }

        .custom-table tbody tr{
            background:rgba(15,23,42,.75) !important;
            transition:.3s;
        }

        .custom-table tbody tr:hover{
            background:rgba(30,41,59,.95) !important;
            transform:translateY(-2px);
        }

        .custom-table td{
            color:#e2e8f0 !important;
            border-top:1px solid rgba(59,130,246,.15) !important;
            border-bottom:1px solid rgba(59,130,246,.15) !important;
            padding:16px;
            vertical-align:middle;
            background:transparent !important;
        }

        .custom-table td:first-child{
            border-left:1px solid rgba(59,130,246,.15) !important;
            border-radius:14px 0 0 14px;
        }

        .custom-table td:last-child{
            border-right:1px solid rgba(59,130,246,.15) !important;
            border-radius:0 14px 14px 0;
        }

        .table-responsive{ background:transparent !important; }

        .table {
            --bs-table-bg: transparent !important;
            --bs-table-striped-bg: transparent !important;
            --bs-table-hover-bg: transparent !important;
            --bs-table-color: #e2e8f0 !important;
            margin-bottom:0;
        }

        .table > :not(caption) > * > *{
            background:transparent !important;
            box-shadow:none !important;
        }

        .text-muted{ color:#94a3b8 !important; }
       
        /* Neon Pill Badges */
        .badge-pill {
            background: rgba(168, 85, 247, 0.12); color: #c084fc; 
            padding: 0.45rem 1.1rem; border-radius: 30px; font-size: 0.75rem; font-weight: 600;
            border: 1px solid rgba(168, 85, 247, 0.2); text-shadow: 0 0 10px rgba(168, 85, 247, 0.3);
        }
        
        /* Counters Style */
        .stat-number { font-size: 2.2rem; font-weight: 800; letter-spacing: -0.5px; }
        
        /* Rounded Icon Glow Container */
        .icon-box {
            width: 56px; height: 56px; display: flex; align-items: center; justify-content: center;
            border-radius: 18px; font-size: 1.4rem; flex-shrink: 0;
        }
        .box-cyan { background: rgba(6, 182, 212, 0.1); color: var(--accent-cyan); border: 1px solid rgba(6, 182, 212, 0.2); }
        .box-purple { background: rgba(168, 85, 247, 0.1); color: var(--accent-purple); border: 1px solid rgba(168, 85, 247, 0.2); }
        .box-pink { background: rgba(244, 63, 94, 0.1); color: var(--accent-pink); border: 1px solid rgba(244, 63, 94, 0.2); }

        .table-responsive::-webkit-scrollbar{ height:8px; }
        .table-responsive::-webkit-scrollbar-track{ background:#0f172a; }
        .table-responsive::-webkit-scrollbar-thumb{ background:#06b6d4; border-radius:20px; }

        /* ================= Responsive Media Queries ================= */
        @media (max-width: 992px) { 
            .main-content { margin-left: 0; padding: 2rem 1.2rem; }
            .dashboard-title { font-size: 1.8rem; }
        }

        @media (max-width: 768px) {
            .dashboard-title { font-size: 1.6rem; }
            .stat-number { font-size: 1.8rem; }
            .chart-container { height: 280px !important; }
        }

        @media (max-width: 576px) {
            .glass-card { padding: 1.25rem !important; border-radius: 16px; }
            .icon-box { width: 45px; height: 45px; font-size: 1.1rem; border-radius: 12px; }
            .dashboard-title { font-size: 1.4rem; }
            .stat-number { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <?php if(file_exists('sidebar.php')) { include 'sidebar.php'; } ?>

    <main class="main-content">
        <div class="container-fluid px-0">
            
            <!-- Header Grid -->
            <div class="row align-items-center mb-4 mb-md-5 g-4 mx-0">
                <div class="col-md-7 px-2">
                    <h2 class="dashboard-title mb-1"><i class="fas fa-chart-line text-info me-2 me-sm-3"></i>Financial Analytics Studio</h2>
                    <p class="sinhala-sub mb-0">ආයතනික මූල්‍ය ප්‍රවාහය සහ විෂය අනුව ආදායම් බෙදීයාම තත්‍ය කාලීනව නිරීක්ෂණය කරන්න.</p>
                </div>
                <div class="col-md-5 d-flex justify-content-md-end px-2">
                    <div class="glass-card px-3 px-sm-4 py-3 border border-info border-opacity-25 d-flex align-items-center gap-3 w-100 w-md-auto">
                        <div class="icon-box box-cyan"><i class="fas fa-shield-alt"></i></div>
                        <div>
                            <div class="text-muted small fw-semibold">SYSTEM STATUS</div>
                            <div class="small text-success fw-bold"><i class="fas fa-circle-notch fa-spin me-1"></i> Secured Node Online</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Stats Grid -->
            <div class="row g-4 mb-4 mb-md-5 mx-0">
                <div class="col-sm-6 col-lg-4 px-2">
                    <div class="glass-card p-4 h-100 d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <span class="text-muted small fw-bold tracking-wider">TOTAL REVENUE</span>
                            <div class="stat-number mt-2 text-white fw-bold" style="text-shadow: var(--text-glow); color: var(--accent-cyan) !important;">
                                Rs. <?= number_format($total_revenue, 2) ?>
                            </div>
                        </div>
                        <div class="icon-box box-cyan"><i class="fas fa-vault"></i></div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 px-2">
                    <div class="glass-card p-4 h-100 d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <span class="text-muted small fw-bold">TOTAL TRANSACTIONS</span>
                            <div class="stat-number mt-2 text-white"><?= $total_records ?></div>
                        </div>
                        <div class="icon-box box-purple"><i class="fas fa-receipt"></i></div>
                    </div>
                </div>
                <div class="col-12 col-lg-4 px-2">
                    <div class="glass-card p-4 h-100 d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <span class="text-muted small fw-bold">FILTER SCOPE</span>
                            <div class="stat-number mt-2" style="font-size:1.1rem; font-weight:700; color:var(--accent-pink);">
                                <?= ($month != "" || $subject != "") ? "⚡ Filtering Matrix" : "🪐 Complete Global Scope" ?>
                            </div>
                        </div>
                        <div class="icon-box box-pink"><i class="fas fa-sliders-h"></i></div>
                    </div>
                </div>
            </div>

            <!-- Filters Area -->
            <div class="glass-card p-4 mb-4 mb-md-5 mx-2">
                <form method="GET" class="row g-3 g-md-4">
                    <div class="col-sm-6">
                        <label class="form-label text-muted small fw-bold mb-2"><i class="fas fa-calendar-minus text-info me-2"></i> MONTH TIMELINE</label>
                        <select name="month" class="form-select" onchange="this.form.submit()">
                            <option value="">All Temporal Months</option>
                            <?php foreach($months as $m){
                                echo "<option value='$m' ".($month==$m?'selected':'').">$m</option>";
                            }?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-muted small fw-bold mb-2"><i class="fas fa-atom text-purple me-2"></i> ACADEMIC SUBJECT</label>
                        <select name="subject" class="form-select" onchange="this.form.submit()">
                            <option value="">All Registered Streams</option>
                            <?php foreach($subjects as $s){
                                echo "<option value='$s' ".($subject==$s?'selected':'').">$s</option>";
                            }?>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Charts Grid -->
            <div class="row g-4 mb-4 mb-md-5 mx-0">
                <div class="col-lg-8 px-2">
                    <div class="glass-card p-4">
                        <h6 class="text-white fw-bold mb-4 uppercase tracking-wider small"><i class="fas fa-chart-bar me-2 text-info"></i> Periodic Cash Inflow</h6>
                        <div class="chart-container" style="height: 320px; position: relative;">
                            <canvas id="chart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 px-2">
                    <div class="glass-card p-4">
                        <h6 class="text-white fw-bold mb-4 uppercase tracking-wider small"><i class="fas fa-chart-pie me-2 text-purple"></i> Revenue Share</h6>
                        <div class="chart-container" style="height: 320px; position: relative;">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table Container -->
            <div class="glass-card p-3 p-sm-4 mx-2">
                <div class="mb-4">
                    <h6 class="text-white fw-bold mb-1 small"><i class="fas fa-database me-2 text-warning"></i> ARCHIVED FINANCIAL STATEMENT</h6>
                    <p class="text-muted small mb-0">Tamper-proof institution transaction ledger records.</p>
                </div>
                <div class="table-responsive">
                   <table class="table custom-table align-middle">
                        <thead>
                            <tr>
                                <th>Student Scholar</th>
                                <th>Subject Stream</th>
                                <th>Target Ledger Month</th>
                                <th>Settled Amount</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($records_array) > 0): ?>
                                <?php foreach($records_array as $row){ ?>
                                    <tr>
                                       <td class="fw-bold text-white text-nowrap">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="icon-box box-cyan" style="width:32px; height:32px; font-size:0.9rem; border-radius:10px;"><i class="fas fa-user"></i></div>
                                                <?= htmlspecialchars($row['student_name']) ?>
                                            </div>
                                        </td>
                                        <td style="color:#cbd5e1;" class="text-nowrap"><?= htmlspecialchars($row['subject']) ?></td>
                                        <td><span class="badge-pill"><?= htmlspecialchars($row['month']) ?></span></td>
                                        <td class="fw-bold text-info text-nowrap">Rs. <?= number_format($row['amount'],2) ?></td>
                                        <td class="text-muted small text-nowrap"><?= htmlspecialchars($row['paid_date']) ?></td>
                                    </tr>
                                <?php } ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5 fw-semibold"><i class="fas fa-folder-open d-block fs-2 mb-3 opacity-25"></i>No matrix data lines mapped out on current filter constraints.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            // Neon Chart Defaults Global Override
            Chart.defaults.color = '#64748b';
            Chart.defaults.font.family = 'Plus Jakarta Sans';

            // 1. NEON BAR CHART
            const ctxBar = document.getElementById("chart").getContext("2d");
            const barGradient = ctxBar.createLinearGradient(0, 0, 0, 300);
            barGradient.addColorStop(0, 'rgba(6, 182, 212, 0.8)');
            barGradient.addColorStop(1, 'rgba(59, 130, 246, 0.1)');

            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chart_labels) ?>,
                    datasets: [{
                        label: "Revenue Stream",
                        data: <?= json_encode($chart_values) ?>,
                        backgroundColor: barGradient,
                        borderColor: '#06b6d4',
                        borderWidth: 2,
                        borderRadius: 12,
                        borderSkipped: false,
                        barThickness: window.innerWidth < 576 ? 14 : 26
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: 'rgba(255,255,255,0.03)' },
                            ticks: { font: { size: 11, weight: '600' } }
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { font: { size: 11, weight: '600' } }
                        }
                    }
                }
            });

            // 2. LUXURY GLOW DOUGHNUT CHART
            const ctxPie = document.getElementById("pieChart").getContext("2d");
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($pie_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($pie_values) ?>,
                        backgroundColor: ['#a855f7', '#06b6d4', '#eab308', '#f43f5e', '#10b981'],
                        borderWidth: 4,
                        borderColor: '#0d1426',
                        hoverOffset: 12
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 12, font: { size: 11, weight: '600' } }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>