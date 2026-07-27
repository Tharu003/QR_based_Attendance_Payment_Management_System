<?php 
session_start();
include 'db.php';

// පිටුව ආරක්ෂා කිරීම
if (!isset($_SESSION['student_data'])) { 
    header("Location: st_login.php"); 
    exit(); 
}

$s = $_SESSION['student_data'];
$student_id = $s['student_id'];

$msg = "";
$msg_type = "";

// 1. Profile Picture Update Logic
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $filename = $_FILES['profile_pic']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $folder = "uploads/profile_pics/";
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }
        
        $new_filename = "student_" . $student_id . "_" . time() . "." . $ext;
        $destination = $folder . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
            $query = "UPDATE students SET photo = ? WHERE student_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $destination, $student_id);
            if ($stmt->execute()) {
                $_SESSION['student_data']['photo'] = $destination;
                $s['photo'] = $destination;
                $msg = "Profile picture updated successfully!";
                $msg_type = "success";
            }
        } else {
            $msg = "Failed to upload image.";
            $msg_type = "error";
        }
    } else {
        $msg = "Invalid file type. Only JPG, PNG and WEBP allowed.";
        $msg_type = "error";
    }
}

// 2. Profile Details Update Logic
if (isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    $query = "UPDATE students SET student_name = ?, phone = ?, address = ? WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $name, $phone, $address, $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['student_data']['student_name'] = $name;
        $_SESSION['student_data']['phone'] = $phone;
        $_SESSION['student_data']['address'] = $address;
        $s = $_SESSION['student_data'];
        $msg = "Profile updated successfully!";
        $msg_type = "success";
    } else {
        $msg = "Something went wrong. Please try again.";
        $msg_type = "error";
    }
}

// 3. Password Change Logic
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    $query = "SELECT password FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($current_pass, $user['password']) || $current_pass == $user['password']) {
        if ($new_pass === $confirm_pass) {
            $hashed_pass = password_hash($new_pass, PASSWORD_BCRYPT);
            
            $update_query = "UPDATE students SET password = ? WHERE student_id = ?";
            $up_stmt = $conn->prepare($update_query);
            $up_stmt->bind_param("si", $hashed_pass, $student_id);
            
            if ($up_stmt->execute()) {
                $msg = "Password changed successfully!";
                $msg_type = "success";
            }
        } else {
            $msg = "New passwords do not match!";
            $msg_type = "error";
        }
    } else {
        $msg = "Current password is incorrect!";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sigma Hub | My Profile</title>

    <!-- Fonts & Essentials -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Round">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
       :root {
            --bg: #030712;
            --card: rgba(17, 24, 39, 0.7);
            --card-glass: rgba(17, 24, 39, 0.45);
            --border-glass: rgba(255, 255, 255, 0.06);
            --sidebar: #0b1220;
            --border: rgba(255, 255, 255, 0.06);
            --accent-gold: #fbbf24;
            --accent-blue: #38bdf8;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --purple: #8b5cf6;
            --emerald: #10b981;
            --glow: rgba(251, 191, 36, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }
        
        /* --- MAIN CONTENT (RESPONSIVE FIX) --- */
        .main-content {
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* Large screens (Desktops) සඳහා පමණක් වමෙන් ඉඩ තැබීම */
        @media (min-width: 992px) {
            .main-content {
                margin-left: 280px; /* Sidebar පළල */
                padding: 60px;
            }
        }

        .profile-container {
            width: 100%;
            max-width: 850px;
        }

        /* --- ARTISTIC CARD WRAPPER --- */
        .art-card-wrapper {
            position: relative;
            padding: 2px;
            background: linear-gradient(135deg, rgba(251, 191, 36, 0.25), rgba(56, 189, 248, 0.1), rgba(251, 191, 36, 0.05));
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6);
        }

        @media (min-width: 768px) {
            .art-card-wrapper {
                padding: 4px;
                border-radius: 42px;
            }
        }

        /* දිදුලන background wave effect */
        .art-card-wrapper::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(251,191,36,0.08) 0%, transparent 60%);
            animation: floatingWave 12s linear infinite;
        }

        @keyframes floatingWave {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .glass-card {
            background: var(--card-glass);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-radius: 26px;
            overflow: hidden;
            position: relative;
            z-index: 2;
        }

        @media (min-width: 768px) {
            .glass-card {
                border-radius: 38px;
            }
        }

        /* Header Area */
        .profile-header-premium {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(3, 7, 18, 0.95) 100%);
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-glass);
            position: relative;
        }

        @media (min-width: 768px) {
            .profile-header-premium {
                padding: 65px 40px;
            }
        }

        /* Nisadas / Poetry Block */
        .poetic-quote {
            max-width: 500px;
            margin: 0 auto 25px auto;
            font-style: italic;
            color: #c4b5fd;
            font-weight: 500;
            font-size: 0.95rem;
            line-height: 1.6;
            text-shadow: 0 2px 10px rgba(196, 181, 253, 0.2);
            opacity: 0.9;
        }

        @media (min-width: 768px) {
            .poetic-quote {
                font-size: 1.05rem;
                margin-bottom: 30px;
            }
        }

        .poetic-quote span {
            color: var(--accent-gold);
            font-weight: 700;
        }

        /* Avatar Frame */
        .avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 15px auto;
            cursor: pointer;
        }

        @media (min-width: 768px) {
            .avatar-container {
                width: 145px;
                height: 145px;
                margin-bottom: 20px;
            }
        }

        .profile-img-main {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #1e293b;
            box-shadow: 0 0 0 4px var(--accent-gold), 0 15px 30px rgba(0,0,0,0.5);
            transition: all 0.4s ease;
        }

        .avatar-overlay-glow {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            border-radius: 50%;
            background: rgba(3, 7, 18, 0.65);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 3;
        }

        .avatar-container:hover .avatar-overlay-glow { opacity: 1; }
        .avatar-container:hover .profile-img-main { transform: scale(1.03); box-shadow: 0 0 30px rgba(251, 191, 36, 0.4); }

        /* --- INFO GRID BOXES (RESPONSIVE FIX) --- */
        .info-card-grid {
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr; /* Mobile වලදී තනි පේළියට */
            gap: 15px;
        }

        @media (min-width: 650px) {
            .info-card-grid {
                grid-template-columns: repeat(2, 1fr); /* Tablet/Desktop වලදී කොටස් දෙකකට */
                padding: 40px;
                gap: 20px;
            }
            .info-full-width { grid-column: span 2; } /* Address එක පේළිය පුරාම */
        }

        .info-art-box {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
            border: 1px solid rgba(255, 255, 255, 0.04);
            padding: 18px 20px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 768px) {
            .info-art-box {
                padding: 24px 28px;
                border-radius: 24px;
                gap: 20px;
            }
        }

        .info-art-box::after {
            content: '';
            position: absolute;
            bottom: -20px; right: -20px; width: 80px; height: 80px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.04) 0%, transparent 70%);
            border-radius: 50%;
        }

        .info-art-box:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.01));
            border-color: rgba(251, 191, 36, 0.2);
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        .icon-art-circle {
            width: 46px;
            height: 46px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.04);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 20px;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        @media (min-width: 768px) {
            .icon-art-circle {
                width: 52px;
                height: 52px;
                border-radius: 18px;
                font-size: 24px;
            }
        }

        .info-art-box:hover .icon-art-circle {
            background: rgba(251, 191, 36, 0.12);
            color: var(--accent-gold);
            transform: rotate(-8deg);
        }

        .label-text-style {
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        
        @media (min-width: 768px) {
            .label-text-style { font-size: 0.85rem; }
        }

        .value-text-style {
            color: var(--text-main);
            font-weight: 700;
            font-size: 0.95rem;
            word-break: break-word;
        }

        @media (min-width: 768px) {
            .value-text-style { font-size: 1.1rem; }
        }

        .token-badge {
            background: rgba(56, 189, 248, 0.08);
            color: var(--accent-blue);
            padding: 4px 10px;
            border-radius: 8px;
            font-family: monospace;
            border: 1px solid rgba(56, 189, 248, 0.2);
            font-size: 0.9rem;
        }

        /* --- BUTTONS & FOOTER ACTIONS --- */
        .action-area {
            padding: 20px;
            background: rgba(0, 0, 0, 0.25);
            border-top: 1px solid var(--border-glass);
        }

        @media (min-width: 768px) {
            .action-area {
                padding: 35px 40px;
            }
        }

        .btn-custom {
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.9rem;
            width: 100%; /* Mobile වලදී full width වෙනවා */
        }

        @media (min-width: 576px) {
            .btn-custom {
                padding: 13px 26px;
                border-radius: 16px;
                font-size: 1rem;
                width: auto; /* Buttons වල මුල් ප්‍රමාණයට පත්වේ */
            }
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
            border: 1px solid var(--border-glass);
        }
        .btn-back:hover { background: rgba(255, 255, 255, 0.1); transform: translateX(-4px); color:white;}

        .btn-edit { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #030712; box-shadow: 0 10px 20px rgba(251,191,36,0.2); }
        .btn-edit:hover { transform: translateY(-2px); box-shadow: 0 15px 25px rgba(251,191,36,0.3); color: #030712;}

        .btn-security { background: rgba(56, 189, 248, 0.1); color: var(--accent-blue); border: 1px solid rgba(56, 189, 248, 0.2); }
        .btn-security:hover { background: var(--accent-blue); color: #030712; transform: translateY(-2px); }

        /* --- GLASS MODALS & AUTOFILL FIX --- */
        .modal-content-glass {
            background: rgba(11, 15, 26, 0.92) !important;
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 20px !important;
            color: var(--text-main);
        }

        @media (min-width: 768px) {
            .modal-content-glass { border-radius: 28px !important; }
        }

        .form-control-glass {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #f8fafc !important;
            opacity: 1 !important;
            -webkit-text-fill-color: #f8fafc !important;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .form-control-glass::placeholder { color: rgba(248, 250, 252, 0.5); }
        .form-control-glass:focus {
            background: rgba(255, 255, 255, 0.12) !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
            box-shadow: none;
            border-color: var(--accent-gold);
        }

        input, textarea { caret-color: #fbbf24; }

        .form-control-glass:-webkit-autofill,
        .form-control-glass:-webkit-autofill:hover, 
        .form-control-glass:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--text-main) !important;
            -webkit-box-shadow: 0 0 0px 1000px rgba(20, 26, 44, 1) inset !important;
            transition: background-color 5000s ease-in-out 0s;
        }
    </style>
</head>
<body>

   <?php include 'st_sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="profile-container animate__animated animate__fadeInUp">
            
            <!-- ARTISTIC PREMIUM FRAME WRAPPER -->
            <div class="art-card-wrapper">
                <div class="glass-card">
                    
                    <!-- HEADER WITH NISADAS & IMAGE -->
                    <div class="profile-header-premium">
                        
                        <!-- Poetic Note / නිසඳැස -->
                        <div class="poetic-quote">
                            "නොනැවතී ඉදිරියටම යන ගමනක, <br>
                            නොසැලෙනා අධිෂ්ඨානය นුඹයි... <br>
                            සිග්මා පියසක දැල්වුණු ඒ ඥාන ආලෝකය, <br>
                            හෙට දින නුඹව <span>ජයග්‍රහණයේ මාවතට</span> රැගෙන යනු ඇත!"
                        </div>

                        <!-- Image Wrapper -->
                        <div class="avatar-container">
                            <form id="avatarForm" action="" method="POST" enctype="multipart/form-data">
                                <input type='file' id="imageUpload" name="profile_pic" accept=".png, .jpg, .jpeg, .webp" onchange="document.getElementById('avatarForm').submit();" style="display:none;" />
                                <label for="imageUpload" class="w-100 h-100 m-0">
                                    <div class="avatar-overlay-glow">
                                        <span class="material-icons-round text-white" style="font-size: 30px;">add_a_photo</span>
                                    </div>
                                    <img src="<?php echo !empty($s['photo']) ? $s['photo'] : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'; ?>" class="profile-img-main">
                                </label>
                            </form>
                        </div>
<br>
                        <h2 class="fw-800 m-0" style="font-size: calc(1.3rem + 0.6vw); letter-spacing: -0.5px;"><?php echo $s['student_name']; ?></h2>
                        <p class="text-warning fw-600 mt-2 mb-0" style="font-size: 0.85rem; letter-spacing: 1.5px;">⭐ GRADE <?php echo $s['registered_grade']; ?> STUDENT</p>
                    </div>

                    <!-- MODERN DESIGN DETAILS SECTION -->
                    <div class="info-card-grid">
                        
                        <!-- Student ID Box -->
                        <div class="info-art-box">
                            <div class="icon-art-circle"><span class="material-icons-round">fingerprint</span></div>
                            <div>
                                <div class="label-text-style">Student ID</div>
                                <div class="value-text-style">#<?php echo $student_id; ?></div>
                            </div>
                        </div>

                        <!-- QR Token Box -->
                        <div class="info-art-box">
                            <div class="icon-art-circle"><span class="material-icons-round">qr_code_2</span></div>
                            <div>
                                <div class="label-text-style">QR Token</div>
                                <div class="value-text-style" style="margin-top: 3px;"><span class="token-badge"><?php echo $s['qr_token']; ?></span></div>
                            </div>
                        </div>

                        <!-- Phone Box -->
                        <div class="info-art-box">
                            <div class="icon-art-circle"><span class="material-icons-round">phone_iphone</span></div>
                            <div>
                                <div class="label-text-style">Phone Number</div>
                                <div class="value-text-style"><?php echo $s['phone']; ?></div>
                            </div>
                        </div>

                        <!-- Date Box -->
                        <div class="info-art-box">
                            <div class="icon-art-circle"><span class="material-icons-round">event_available</span></div>
                            <div>
                                <div class="label-text-style">Registered On</div>
                                <div class="value-text-style"><?php echo date("d F Y", strtotime($s['registered_date'])); ?></div>
                            </div>
                        </div>

                        <!-- Address Box -->
                        <div class="info-art-box info-full-width">
                            <div class="icon-art-circle"><span class="material-icons-round">home</span></div>
                            <div>
                                <div class="label-text-style">Home Address</div>
                                <div class="value-text-style"><?php echo $s['address']; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER ACTIONS (RESPONSIVE FLEX) -->
                    <div class="action-area d-flex flex-column flex-sm-row gap-3 justify-content-sm-between align-items-center">
                        <a href="student_dashboard.php" class="btn-custom btn-back order-2 order-sm-1">
                            <span class="material-icons-round">arrow_back</span>
                            Dashboard
                        </a>
                        <div class="d-flex flex-column flex-sm-row gap-3 w-100 w-sm-auto order-1 order-sm-2">
                            <button class="btn-custom btn-security" data-bs-toggle="modal" data-bs-target="#passwordModal">
                                <span class="material-icons-round">vpn_key</span>
                                Security
                            </button>
                            <button class="btn-custom btn-edit" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <span class="material-icons-round">auto_fix_high</span>
                                Edit Profile
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <p class="text-center text-muted mt-4 small" style="opacity: 0.4;">
                © 2026 Sigma Learning Management System. Crafted for Excellence.
            </p>

        </div>
    </main>

    <!-- MODAL: EDIT PROFILE -->
    <div class="modal fade" id="editProfileModal" tabIndex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered px-3">
            <div class="modal-content modal-content-glass">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2 fw-700"><span class="material-icons-round text-warning">edit</span> Edit Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-3 p-md-4">
                        <div class="mb-3">
                           <label class="mb-2 text-white fw-semibold">Full Name</label>
                            <input type="text" name="student_name" class="form-control form-control-glass" value="<?php echo $s['student_name']; ?>" required>
                        </div>
                        <div class="mb-3">
                           <label class="mb-2 text-white fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control form-control-glass" value="<?php echo $s['phone']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="mb-2 text-white fw-semibold">Home Address</label>
                            <textarea name="address" class="form-control form-control-glass" rows="3" required><?php echo $s['address']; ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-column flex-sm-row gap-2">
                        <button type="button" class="btn-custom btn-back w-100 w-sm-auto m-0" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn-custom btn-edit w-100 w-sm-auto m-0">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL: CHANGE PASSWORD -->
    <div class="modal fade" id="passwordModal" tabIndex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered px-3">
            <div class="modal-content modal-content-glass">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2 fw-700"><span class="material-icons-round text-info">lock</span> Update Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST">
                    <div class="modal-body p-3 p-md-4">
                        <div class="mb-3">
                            <label class="mb-2 text-white fw-semibold">Current Password</label>
                            <input type="password" name="current_password" class="form-control form-control-glass" required>
                        </div>
                        <div class="mb-3">
                            <label class="mb-2 text-white fw-semibold">New Password</label>
                            <input type="password" name="new_password" class="form-control form-control-glass" required>
                        </div>
                        <div class="mb-3">
                            <label class="mb-2 text-white fw-semibold">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control form-control-glass" required>
                        </div>
                    </div>
                    <div class="modal-footer d-flex flex-column flex-sm-row gap-2">
                        <button type="button" class="btn-custom btn-back w-100 w-sm-auto m-0" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="change_password" class="btn-custom btn-security w-100 w-sm-auto m-0">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bundle.min.js"></script>

    <!-- SweetAlert Popups -->
    <?php if(!empty($msg)): ?>
    <script>
        Swal.fire({
            icon: '<?php echo $msg_type; ?>',
            title: '<?php echo ($msg_type == "success") ? "Success!" : "Ops..."; ?>',
            text: '<?php echo $msg; ?>',
            background: '#0b0f1a',
            color: '#f8fafc',
            confirmButtonColor: '#fbbf24'
        });
    </script>
    <?php endif; ?>
</body>
</html>