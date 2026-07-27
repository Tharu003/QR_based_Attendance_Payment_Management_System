<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}

$role = $_SESSION['role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);

$permissions = [
    // ADMIN ACCESS (සියලුම පිටු බැලිය හැක - Payment Transfer ද ඇතුළුව)
    'admin' => [
        'dashboard', 'students', 'classes', 'teachers', 'timetable', 'attendance', 
        'payment', 'payment_transfer', 'materials', 'quiz', 'reports', 'notices', 'create_exams', 'enter_marks'
    ],
    // TEACHER ACCESS (විභාග සහ ලකුණු ඇතුළත් කිරීම් සහිතයි)
    'teacher' => [
        'dashboard', 'students', 'classes', 'timetable', 'attendance', 'materials', 'quiz', 'notices', 'create_exams', 'enter_marks'
    ],
    // ASSISTANT ACCESS (සීමිත පිටු කිහිපයක් පමණි)
    'assistant' => [
        'dashboard', 'students', 'attendance'
    ],
    // SUPERADMIN ACCESS (සියලුම පිටු බැලිය හැක)
    'superadmin' => [
        'dashboard', 'students', 'classes', 'teachers', 'timetable', 'attendance', 
        'payment', 'payment_transfer', 'materials', 'quiz', 'reports', 'notices', 'create_exams', 'enter_marks'
    ]
];
?>

<!-- Mobile Navigation Bar -->
<nav class="sigma-mobile-top-bar d-lg-none">
    <div class="sigma-mobile-brand">
        <i class="fas fa-atom text-primary-gradient"></i> SIGMA 
    </div>
    <button class="sigma-hamburger-btn" onclick="toggleSigmaAdminSidebar()">
        <i class="fas fa-bars"></i>
    </button>
</nav>

<!-- Background Blur Overlay for Mobile View -->
<div class="sigma-sidebar-overlay" id="sigmaAdminOverlay" onclick="toggleSigmaAdminSidebar()"></div>

<!-- Sidebar Container -->
<div class="sidebar" id="sigmaAdminSidebar">
    
    <div class="sidebar-brand d-flex align-items-center justify-content-between w-100">
        <span><i class="fas fa-atom me-2"></i> SIGMA </span>
        <!-- Mobile close button -->
        <button type="button" class="btn-close-custom d-lg-none" onclick="toggleSigmaAdminSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="sidebar-menu-wrapper">
        <div class="nav flex-column">
            
            <?php if(isset($permissions[$role]) && in_array('dashboard', $permissions[$role])): ?>
            <a href="dashboard.php" class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-th-large me-2"></i> Dashboard
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('students', $permissions[$role])): ?>
            <a href="students.php" class="nav-link <?= ($current_page == 'students.php') ? 'active' : '' ?>">
                <i class="fas fa-users me-2"></i> Students
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('classes', $permissions[$role])): ?>
            <a href="classes.php" class="nav-link <?= ($current_page == 'classes.php') ? 'active' : '' ?>">
                <i class="fas fa-layer-group me-2"></i> Classes
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('teachers', $permissions[$role])): ?>
            <a href="teachers.php" class="nav-link <?= ($current_page == 'teachers.php') ? 'active' : '' ?>">
                <i class="fas fa-chalkboard-teacher me-2"></i> Teachers
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('timetable', $permissions[$role])): ?>
            <a href="timetable.php" class="nav-link <?= ($current_page == 'timetable.php') ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt me-2"></i> Time Table
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('attendance', $permissions[$role])): ?>
            <a href="attendance.php" class="nav-link <?= ($current_page == 'attendance.php') ? 'active' : '' ?>">
                <i class="fas fa-calendar-check me-2"></i> Attendance
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('payment', $permissions[$role])): ?>
            <a href="payment.php" class="nav-link <?= ($current_page == 'payment.php') ? 'active' : '' ?>">
                <i class="fas fa-credit-card me-2"></i> Payments
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('payment_transfer', $permissions[$role])): ?>
            <a href="payment_transfer.php" class="nav-link <?= ($current_page == 'payment_transfer.php') ? 'active' : '' ?>">
                <i class="fas fa-right-left me-2"></i> Fee Transfer
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('materials', $permissions[$role])): ?>
            <a href="materials.php" class="nav-link <?= ($current_page == 'materials.php') ? 'active' : '' ?>">
                <i class="fas fa-folder-open me-2"></i> Materials
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('quiz', $permissions[$role])): ?>
            <a href="add_quiz.php" class="nav-link <?= ($current_page == 'add_quiz.php') ? 'active' : '' ?>">
                <i class="fas fa-lightbulb me-2"></i> Add Quiz
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('create_exams', $permissions[$role])): ?>
            <a href="create_exams.php" class="nav-link <?= ($current_page == 'create_exams.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-file-medical me-2"></i> Create Exams
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('enter_marks', $permissions[$role])): ?>
            <a href="enter_marks.php" class="nav-link <?= ($current_page == 'enter_marks.php') ? 'active' : '' ?>">
                <i class="fa-solid fa-pen-to-square me-2"></i> Enter Marks
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('reports', $permissions[$role])): ?>
            <a href="reports.php" class="nav-link <?= ($current_page == 'reports.php') ? 'active' : '' ?>">
                <i class="fas fa-chart-bar me-2"></i> Reports
            </a>
            <?php endif; ?>

            <?php if(isset($permissions[$role]) && in_array('notices', $permissions[$role])): ?>
            <a href="add_notice.php" class="nav-link <?= ($current_page == 'add_notice.php') ? 'active' : '' ?>">
                <i class="fas fa-bullhorn me-2"></i> Notices
            </a>
            <?php endif; ?>
            
        </div>
    </div>

    <div class="sidebar-footer">
        <div style="padding: 20px 24px; color:#94a3b8; font-size:13px; background: rgba(255,255,255,0.01);">
            <span style="color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Logged as</span>
            <div style="color:white; font-weight: 600; font-size: 0.95rem; margin-top: 2px;">
                <?= htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
            </div>
            <div style="margin-top: 4px; display: inline-block; padding: 2px 8px; background: rgba(59, 130, 246, 0.1); border-radius: 6px; color:#3b82f6; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">
                <?= strtoupper(htmlspecialchars($role)); ?>
            </div>
        </div>

        <a href="ad_logout.php" class="nav-link logout-btn">
            <i class="fas fa-power-off me-2"></i> Logout System
        </a>
    </div>
</div>

<style>
    :root {
        --sidebar-w: 280px;
    }

    /* Base Sidebar Style */
    .sidebar {
        width: var(--sidebar-w);
        height: 100vh;
        background: #000000 !important;
        display: flex;
        flex-direction: column;
        border-right: 1px solid #1c1c1f;
        box-shadow: 10px 0 30px rgba(0, 0, 0, 0.6);
        overflow: hidden;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1000;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Mobile UI Top Navbar Styling */
    .sigma-mobile-top-bar {
        position: sticky;
        top: 0;
        left: 0;
        width: 100%;
        height: 60px;
        background: #000000;
        border-bottom: 1px solid #1c1c1f;
        padding: 0 20px;
        z-index: 998;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .sigma-mobile-brand {
        font-size: 1.15rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: 1.2px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sigma-hamburger-btn {
        background: rgba(255, 255, 255, 0.05);
        border: none;
        color: #ffffff;
        font-size: 20px;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 10px;
        transition: background 0.2s ease;
    }
    
    .sigma-hamburger-btn:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .btn-close-custom {
        background: transparent;
        border: none;
        color: #64748b;
        font-size: 20px;
        cursor: pointer;
        padding: 5px;
        transition: color 0.2s ease;
    }

    .btn-close-custom:hover {
        color: #ffffff;
    }

    /* Dark Overlay behind Mobile Sidebar */
    .sigma-sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(5px);
        z-index: 999;
    }

    .sidebar-brand {
        padding: 30px 24px;
        font-size: 1.35rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: 1.5px;
        border-bottom: 1px solid #111115;
        background: #000000;
        flex-shrink: 0;
    }
    
    .sidebar-brand i, .text-primary-gradient {
        background: linear-gradient(45deg, #3b82f6, #00d2ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .sidebar-menu-wrapper {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 15px 12px;
    }

    /* Custom Webkit Scrollbar */
    .sidebar-menu-wrapper::-webkit-scrollbar { width: 5px; }
    .sidebar-menu-wrapper::-webkit-scrollbar-track { background: transparent; }
    .sidebar-menu-wrapper::-webkit-scrollbar-thumb {
        background: transparent; 
        border-radius: 10px;
        transition: background 0.3s ease;
    }
    .sidebar:hover .sidebar-menu-wrapper::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.2); }
    .sidebar-menu-wrapper::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.6) !important; }

    .sidebar .nav-link {
        color: #94a3b8;
        padding: 12px 16px;
        font-weight: 500;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        border-radius: 12px;
        margin-bottom: 5px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
    }

    .sidebar .nav-link i {
        font-size: 1.1rem;
        width: 28px;
        transition: transform 0.25s ease;
    }

    .sidebar .nav-link:hover {
        color: #ffffff;
        background-color: rgba(255, 255, 255, 0.04);
        padding-left: 20px;
    }
    
    .sidebar .nav-link:hover i {
        transform: scale(1.1);
        color: #3b82f6;
    }

    .sidebar .nav-link.active {
        color: #ffffff;
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.02) 100%);
        border-left: 4px solid #3b82f6;
        font-weight: 600;
        padding-left: 18px;
    }
    
    .sidebar .nav-link.active i { color: #3b82f6; }
    .sidebar-footer { background: #000000; border-top: 1px solid #111115; flex-shrink: 0; }

    .sidebar .logout-btn {
        color: #ef4444 !important;
        padding: 18px 24px;
        font-weight: 600;
        border-radius: 0;
        margin-bottom: 0;
        border-top: 1px solid #111115;
    }

    .sidebar .logout-btn:hover { background-color: rgba(239, 68, 68, 0.06) !important; }
    .sidebar .logout-btn:hover i { transform: rotate(90deg); color: #ef4444; }

    /* Responsive Queries - Logic Handles Screen Breakpoints */
    @media (max-width: 991.98px) {
        .sidebar {
            transform: translateX(-100%);
            z-index: 1050;
        }
        .sidebar.open {
            transform: translateX(0);
        }
        .sigma-sidebar-overlay.show {
            display: block;
        }
    }

    @media (min-width: 992px) {
        .sigma-mobile-top-bar {
            display: none !important;
        }
        .sidebar {
            transform: none !important;
        }
    }
</style>

<!-- Toggle Functions Handle Mobile Transitions Dynamically -->
<script>
    function toggleSigmaAdminSidebar() {
        const sidebar = document.getElementById('sigmaAdminSidebar');
        const overlay = document.getElementById('sigmaAdminOverlay');
        
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    }
</script>