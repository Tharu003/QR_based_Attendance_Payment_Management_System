<?php
// Session එක පටන්ගෙන නැත්නම් විතරක් ස්ටාර්ට් කරන්න
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// දැනට Active වෙලා තියෙන පිටුව හඳුනා ගැනීමට
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Main Sidebar Layout */
    .sigma-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 280px;
        height: 100vh;
        background: #0b1220;
        border-right: 1px solid rgba(255, 255, 255, 0.06);
        padding: 30px 20px;
        z-index: 999;
        display: flex;
        flex-direction: column;
        box-shadow: 10px 0 30px rgba(0, 0, 0, 0.2);
        transition: transform 0.3s ease-in-out; /* Mobile sliding animation එක සඳහා */
    }

    /* Fixed Logo Section */
    .sigma-logo {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 28px;
        font-weight: 900;
        color: #fbbf24;
        letter-spacing: 2px;
        margin-bottom: 25px;
        padding-left: 10px;
        text-shadow: 0 0 15px rgba(251, 191, 36, 0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0; /* Logo එක කුඩා වීම වළක්වයි */
    }

    /* Mobile Close Button */
    .sigma-close-btn {
        display: none;
        background: transparent;
        border: none;
        color: #ffffff;
        font-size: 24px;
        cursor: pointer;
        margin-left: auto;
    }

    /* Scrollable Menu Area */
    .sigma-nav-container {
        flex-grow: 1;
        overflow-y: auto; /* Links ප්‍රමාණය වැඩි වුවහොත් Scroll වේ */
        padding-right: 5px; /* Scrollbar එකට ඉඩ තැබීමට */
        margin-bottom: 15px;
    }

    .sigma-nav-menu {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* Custom Scrollbar for Sidebar Menu */
    .sigma-nav-container::-webkit-scrollbar {
        width: 5px;
    }
    .sigma-nav-container::-webkit-scrollbar-track {
        background: transparent;
    }
    .sigma-nav-container::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }
    .sigma-nav-container::-webkit-scrollbar-thumb:hover {
        background: rgba(251, 191, 36, 0.3); /* Hover කළ විට Gold පැහැයට හුරු වේ */
    }

    /* Navigation Links */
    .sigma-nav-link {
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #94a3b8;
        padding: 14px 18px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.95rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent;
    }

    /* Hover Effect */
    .sigma-nav-link:hover {
        background: rgba(255, 255, 255, 0.03);
        color: #ffffff;
        transform: translateX(6px);
        border-color: rgba(255, 255, 255, 0.03);
    }

    /* Active Link Style */
    .sigma-nav-link.active {
        background: linear-gradient(135deg, rgba(251, 191, 36, 0.15), rgba(251, 191, 36, 0.03));
        color: #fbbf24;
        border-left: 4px solid #fbbf24;
        border-top-left-radius: 4px;
        border-bottom-left-radius: 4px;
        font-weight: 700;
        box-shadow: inset 5px 0 15px rgba(251, 191, 36, 0.02);
    }

    /* Fixed Footer Section */
    .sigma-sidebar-footer {
        width: 100%;
        flex-shrink: 0; /* Footer එක කුඩා වීම හෝ සැඟවීම වළක්වයි */
    }

    .sigma-divider {
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        margin: 15px 0;
    }

    /* Premium Logout Design */
    .sigma-btn-logout {
        color: #ef4444 !important;
        background: rgba(239, 68, 68, 0.03);
        border: 1px solid rgba(239, 68, 68, 0.08);
    }

    .sigma-btn-logout:hover {
        background: #ef4444 !important;
        color: #ffffff !important;
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.2);
        transform: translateX(0) translateY(-2px);
    }

    /* Mobile Top Navbar Styling */
    .sigma-mobile-top-bar {
        display: none;
        position: sticky;
        top: 0;
        left: 0;
        width: 100%;
        height: 60px;
        background: #0b1220;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        padding: 0 20px;
        z-index: 998;
        align-items: center;
        justify-content: space-between;
    }

    .sigma-mobile-brand {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 20px;
        font-weight: 900;
        color: #fbbf24;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sigma-hamburger-btn {
        background: transparent;
        border: none;
        color: #ffffff;
        font-size: 22px;
        cursor: pointer;
        padding: 5px;
    }

    /* Dark Overlay behind Mobile Sidebar */
    .sigma-sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 990;
    }

    /* Responsive Logic Configuration */
    @media (max-width: 992px) {
        .sigma-mobile-top-bar {
            display: flex; /* Mobile වලදී විතරක් Top Bar එක පෙන්වයි */
        }

        .sigma-sidebar {
            transform: translateX(-100%); /* සාමාන්‍යයෙන් Mobile වලදී Sidebar එක සඟවයි */
            z-index: 1000;
        }

        .sigma-sidebar.open {
            transform: translateX(0); /* Hamburger Click කලවිට Sidebar එක Slide-in වේ */
        }

        .sigma-close-btn {
            display: block; /* Mobile එකේදී Sidebar එක ඇතුලේ Close button එක පෙන්වයි */
        }

        .sigma-sidebar-overlay.show {
            display: block; /* Sidebar එක ඇරෙනකොට පිටිපස්සෙන් වැහෙන අඳුරු තිරය */
        }
    }
</style>

<!-- Mobile Top Navigation Bar -->
<div class="sigma-mobile-top-bar">
    <div class="sigma-mobile-brand">
        <i class="fas fa-atom"></i> SIGMA 
    </div>
    <button class="sigma-hamburger-btn" onclick="toggleSigmaSidebar()">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Background Blur Overlay for Mobile View -->
<div class="sigma-sidebar-overlay" id="sigmaOverlay" onclick="toggleSigmaSidebar()"></div>

<aside class="sigma-sidebar" id="sigmaSidebar">
    <div class="sigma-logo">
        <i class="fas fa-atom"></i> SIGMA 
        <!-- Mobile එකට අවශ්‍ය Close Button එක -->
        <button class="sigma-close-btn" onclick="toggleSigmaSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="sigma-nav-container">
        <div class="sigma-nav-menu">
            <a href="student_dashboard.php" class="sigma-nav-link <?= ($current_page == 'student_dashboard.php') ? 'active' : ''; ?>">
                <span class="material-icons-round">space_dashboard</span>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="profile.php" class="sigma-nav-link <?= ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <span class="material-icons-round">person</span>
                <span class="nav-text">Profile</span>
            </a>
            <a href="lms.php" class="sigma-nav-link <?= ($current_page == 'lms.php') ? 'active' : ''; ?>">
                <span class="material-icons-round">menu_book</span>
                <span class="nav-text">LMS</span>
            </a>
            <a href="st_attendance.php" class="sigma-nav-link <?= ($current_page == 'st_attendance.php') ? 'active' : ''; ?>">
                <span class="material-icons-round">rule</span>
                <span class="nav-text">Attendance</span>
            </a>
            <a href="st_payment.php" class="sigma-nav-link <?= ($current_page == 'st_payment.php') ? 'active' : ''; ?>">
                <span class="material-icons-round">payments</span>
                <span class="nav-text">Payments</span>
            </a>
            <a href="st_timetable.php" class="sigma-nav-link <?= ($current_page == 'st_timetable.php') ? 'active' : ''; ?>">
                <span class="material-icons-round">view_timeline</span>
                <span class="nav-text">Time Table</span>
            </a>
            <a href="student_results.php" class="sigma-nav-link <?= ($current_page == 'student_results.php') ? 'active' : ''; ?>">
                <span class="material-icons-round">emoji_events</span>
                <span class="nav-text">Exam Results</span>
            </a>
          <a href="student_missing_tutes.php" class="sigma-nav-link <?= ($current_page == 'student_missing_tutes.php') ? 'active' : ''; ?>">
    <span class="material-icons-round">description</span>
    <span class="nav-text">Missing Tutes</span>
</a>
            <!-- 🧠 GAMIFIED BRAIN ZONE (CHESS) LINK -->
            <a href="st_game.php" class="sigma-nav-link <?= ($current_page == 'st_game.php') ? 'active' : ''; ?>">
                <span class="material-icons-round">psychology</span>
                <span class="nav-text">Brain Zone</span>
            </a>
        </div>
    </div>
    
    <div class="sigma-sidebar-footer">
        <div class="sigma-divider"></div>
        <a href="logout.php" class="sigma-nav-link sigma-btn-logout">
            <span class="material-icons-round">logout</span>
            <span class="nav-text">Logout</span>
        </a>
    </div>
</aside>

<!-- Mobile Sidebar එක Open/Close කිරීමට අවශ්‍ය සරල JavaScript කේතය -->
<script>
    function toggleSigmaSidebar() {
        const sidebar = document.getElementById('sigmaSidebar');
        const overlay = document.getElementById('sigmaOverlay');
        
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    }
</script>